<?php

namespace App\Services\Returns;

use App\Enums\OrderStatus;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\OrderReturnItemModel;
use App\Models\OrderReturnModel;
use App\Models\OrderStatusLogModel;
use App\Services\OrderRevenue;
use App\Services\OrderStock;
use App\Services\Wallet\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Every step of a return after the customer has the goods:
 *
 *   requested → approved → received → refunded
 *            ↘ rejected (the order stays completed)
 *
 * A request names the lines and quantities going back, not the order, because
 * a customer who bought two pairs to try on keeps one. What comes off the
 * shelf, off the revenue report and out of the till is computed from those
 * rows every time. Each step locks the order and checks the step before it, so
 * a double click or two admins at once change nothing twice.
 */
class OrderReturns
{
    public function __construct(
        private OrderStock $stock,
        private OrderRevenue $revenue,
        private WalletService $wallets,
    ) {}

    /**
     * @param  array{reason: string, description: ?string, refund_info: string}  $data
     * @param  array<int, int>  $items  quantity to send back, keyed by order_details_id
     * @param  array<int, UploadedFile>  $images
     */
    public function request(OrderModel $order, array $data, array $items, array $images = []): OrderReturnModel
    {
        return DB::transaction(function () use ($order, $data, $items, $images) {
            $fresh = $this->lock($order);

            if (! $fresh->canRequestReturn()) {
                throw new ReturnNotAllowed('Đơn hàng này không thể yêu cầu trả hàng.');
            }

            $chosen = $this->checkItems($fresh, $items);

            $paths = array_map(
                fn (UploadedFile $file) => $file->store('returns/'.$fresh->order_code, 'public'),
                $images,
            );

            $return = OrderReturnModel::create([
                'order_id' => $fresh->order_id,
                'status' => OrderReturnModel::REQUESTED,
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'images' => $paths,
                'refund_info' => $data['refund_info'],
            ]);

            foreach ($chosen as $lineId => $quantity) {
                OrderReturnItemModel::create([
                    'return_id' => $return->return_id,
                    'order_details_id' => $lineId,
                    'quantity' => $quantity,
                ]);
            }

            return $return->load('items');
        });
    }

    /**
     * The shop agrees to take the goods back. Only now is anything on its way
     * home: until this point the customer has asked, and nothing has moved.
     */
    public function approve(OrderModel $order): void
    {
        $this->step($order, OrderReturnModel::REQUESTED, function (OrderReturnModel $return, OrderModel $fresh) {
            $return->status = OrderReturnModel::APPROVED;
            $return->decided_at = Carbon::now();

            $fresh->moveTo(OrderStatus::Returning, OrderStatusLogModel::ACTOR_ADMIN, 'Duyệt yêu cầu trả hàng');
        });
    }

    public function reject(OrderModel $order, string $reason): void
    {
        $this->step($order, OrderReturnModel::REQUESTED, function (OrderReturnModel $return, OrderModel $fresh) use ($reason) {
            $return->status = OrderReturnModel::REJECTED;
            $return->reject_reason = $reason;
            $return->decided_at = Carbon::now();

            // Nothing is coming back, so the sale stands as it was and the
            // order never left `completed`.
        });
    }

    /**
     * The goods are back on the shelf. The coupon stays spent: it paid for a
     * sale that did happen, and on a partial return part of it still stands.
     */
    public function receive(OrderModel $order): void
    {
        $this->step($order, OrderReturnModel::APPROVED, function (OrderReturnModel $return, OrderModel $fresh) {
            $this->stock->restockReturn($return);

            $return->status = OrderReturnModel::RECEIVED;
            $return->received_at = Carbon::now();

            if ((int) $fresh->order_payment_status === 1) {
                $fresh->order_refund_required = true;
            }

            $landing = $return->isPartial() ? OrderStatus::PartiallyReturned : OrderStatus::Returned;

            $fresh->moveTo($landing, OrderStatusLogModel::ACTOR_ADMIN, 'Đã nhận và kiểm tra hàng trả');
        });
    }

    /**
     * Pays the customer back into their wallet and takes the returned units
     * out of the revenue report.
     */
    public function refund(OrderModel $order, int $amount): void
    {
        $this->step($order, OrderReturnModel::RECEIVED, function (OrderReturnModel $return, OrderModel $fresh) use ($amount) {
            $return->status = OrderReturnModel::REFUNDED;
            $return->refund_amount = $amount;
            $return->refunded_at = Carbon::now();

            $this->wallets->refundOrder($fresh, $amount, 'Hoàn tiền trả hàng đơn '.$fresh->order_code);

            $fresh->order_refund_required = false;
            $fresh->save();

            $this->revenue->reverseReturn($fresh, $return);
        });
    }

    /**
     * The lines the customer may actually send back, or a refusal.
     *
     * Checked here rather than trusted from the form: the ids are posted by
     * the browser, and one belonging to somebody else's order would otherwise
     * restock their goods and refund against their prices.
     *
     * @param  array<int, int>  $items
     * @return array<int, int>
     */
    private function checkItems(OrderModel $order, array $items): array
    {
        $lines = OrderDetailModel::where('order_id', $order->order_id)->get()->keyBy('order_details_id');
        $chosen = [];

        foreach ($items as $lineId => $quantity) {
            $lineId = (int) $lineId;
            $quantity = (int) $quantity;

            if ($quantity <= 0) {
                continue;
            }

            $line = $lines[$lineId] ?? null;

            if (! $line) {
                throw new ReturnNotAllowed('Sản phẩm được chọn không thuộc đơn hàng này.');
            }

            if ($quantity > (int) $line->quantity) {
                throw new ReturnNotAllowed('Số lượng trả của "'.$line->pro_name.'" vượt quá số đã mua.');
            }

            $chosen[$lineId] = $quantity;
        }

        if ($chosen === []) {
            throw new ReturnNotAllowed('Vui lòng chọn ít nhất một sản phẩm muốn trả.');
        }

        return $chosen;
    }

    /**
     * @param  callable(OrderReturnModel, OrderModel): void  $change
     */
    private function step(OrderModel $order, string $from, callable $change): void
    {
        DB::transaction(function () use ($order, $from, $change) {
            $fresh = $this->lock($order);
            $return = OrderReturnModel::where('order_id', $fresh->order_id)->lockForUpdate()->first();

            if (! $return || $return->status !== $from) {
                throw new ReturnNotAllowed('Yêu cầu trả hàng đã được xử lý hoặc không còn ở bước này.');
            }

            $change($return, $fresh);
            $return->save();
        });
    }

    private function lock(OrderModel $order): OrderModel
    {
        return OrderModel::where('order_id', $order->order_id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Public URLs for the photos a customer attached.
     *
     * @return array<int, string>
     */
    public static function imageUrls(OrderReturnModel $return): array
    {
        return array_map(fn (string $path) => Storage::disk('public')->url($path), $return->images ?? []);
    }
}
