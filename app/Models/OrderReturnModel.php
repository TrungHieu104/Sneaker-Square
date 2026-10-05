<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer sending back goods from an order they already received.
 *
 * An order may collect several of these over its return window — one per
 * conversation with the shop — but only ever one at a time. A request that
 * was refused, or that the customer called off, frees the units it named so
 * they can be asked for again; a request that reached the shop's shelves
 * spends them for good.
 */
class OrderReturnModel extends Model
{
    protected $table = 'order_returns';

    protected $primaryKey = 'return_id';

    public const REQUESTED = 'requested';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const RECEIVED = 'received';

    public const REFUNDED = 'refunded';

    public const CANCELLED = 'cancelled';

    /**
     * A request the shop still has work to do on. At most one of these exists
     * per order: two open requests would race each other over the same units.
     */
    public const OPEN = [self::REQUESTED, self::APPROVED, self::RECEIVED];

    /**
     * Requests whose units are gone or spoken for. What is left of a line,
     * and so what may still be sent back, is the quantity bought minus these.
     */
    public const HOLDS_GOODS = [self::REQUESTED, self::APPROVED, self::RECEIVED, self::REFUNDED];

    /**
     * A request the shop agreed to. It spends the order's one return: from
     * here a courier has been sent, or is about to be, and the shop pays for
     * that trip. A refusal or a cancellation costs nothing and spends nothing.
     */
    public const SETTLED = [self::APPROVED, self::RECEIVED, self::REFUNDED];

    /**
     * Reasons the shop brought on itself. The parcel coming back is then the
     * shop's own cost; the others are the buyer changing their mind, and the
     * carriage comes out of what they get back.
     *
     * @var array<int, string>
     */
    public const SHOP_AT_FAULT = ['loi_san_pham', 'giao_nham', 'khong_giong_mo_ta', 'khac'];

    /** @var array<string, string> */
    public const REASONS = [
        'sai_size' => 'Sai size, không vừa',
        'loi_san_pham' => 'Sản phẩm lỗi, hư hỏng',
        'giao_nham' => 'Giao nhầm mẫu hoặc màu',
        'khong_giong_mo_ta' => 'Không giống mô tả',
        'khac' => 'Lý do khác',
    ];

    /** @var array<string, string> */
    private const STATUS_LABELS = [
        self::REQUESTED => 'Chờ duyệt',
        self::APPROVED => 'Đã duyệt, chờ nhận hàng trả',
        self::REJECTED => 'Bị từ chối',
        self::RECEIVED => 'Đã nhận hàng trả, chờ hoàn tiền',
        self::REFUNDED => 'Đã hoàn tiền',
        self::CANCELLED => 'Khách đã huỷ',
    ];

    protected $fillable = [
        'order_id',
        'status',
        'reason',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'return_shipping_fee' => 'integer',
        'decided_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /** @return BelongsTo<OrderModel, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'order_id');
    }

    /** @return HasMany<OrderReturnItemModel, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItemModel::class, 'return_id', 'return_id');
    }

    /**
     * Whether the customer kept part of the order. Compared line by line
     * against what was bought, because a return of every line but one unit is
     * still a partial one.
     */
    public function isPartial(): bool
    {
        $ordered = OrderDetailModel::where('order_id', $this->order_id)
            ->pluck('quantity', 'order_details_id');
        $returning = $this->items->pluck('quantity', 'order_details_id');

        if ($returning->count() !== $ordered->count()) {
            return true;
        }

        foreach ($ordered as $lineId => $quantity) {
            if ((int) ($returning[$lineId] ?? 0) !== (int) $quantity) {
                return true;
            }
        }

        return false;
    }

    /**
     * What the shop owes back if it accepts this return.
     *
     * The goods at the price the customer paid, less their share of the order
     * coupon. The delivery fee is only given back when the whole order goes
     * home: on a partial return the parcel was still carried, so that money
     * was genuinely spent.
     */
    public function refundDue(): int
    {
        $order = $this->order;
        $goods = $this->items->sum(fn (OrderReturnItemModel $item) => $item->lineTotal());

        $orderGoods = (int) OrderDetailModel::where('order_id', $this->order_id)
            ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
            ->value('total');

        $coupon = (int) $order->order_coupon_value;
        $share = $orderGoods > 0 ? (int) round($coupon * $goods / $orderGoods) : 0;

        $refund = max(0, $goods - $share);

        if (! $this->isPartial()) {
            $refund += (int) $order->order_delivery_fee;
        }

        return max(0, $refund - $this->buyerBorneShipping());
    }

    /**
     * Whether the shop caused this return.
     */
    public function shopAtFault(): bool
    {
        return in_array($this->reason, self::SHOP_AT_FAULT, true);
    }

    /**
     * What the buyer pays towards bringing the parcel home.
     *
     * Zero while no parcel has been booked: the shop cannot charge for
     * carriage it has not arranged, and a return handed over at the counter
     * costs nobody anything.
     */
    public function buyerBorneShipping(): int
    {
        return $this->shopAtFault() ? 0 : (int) $this->return_shipping_fee;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * Whether the shop still owes this request an answer or an action.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public const REFERENCE_SUFFIX = '-TH';

    /**
     * What the shop sends GHN as client_order_code for the parcel coming back.
     *
     * It carries this request's own id, not just the order code: GHN keeps
     * client_order_code unique across the shop, so an order sending a second
     * parcel home under the same string would be refused.
     */
    public function reference(): string
    {
        return $this->order->order_code.self::REFERENCE_SUFFIX.$this->return_id;
    }

    /**
     * The request a callback belongs to.
     *
     * Parcels booked before the id was added carry the bare suffix; those
     * resolve to the order's open request, which is the only one a parcel can
     * belong to.
     */
    public static function forReference(?string $reference): ?self
    {
        if (! $reference || ! preg_match('/^(.+)'.preg_quote(self::REFERENCE_SUFFIX, '/').'(\d*)$/', $reference, $m)) {
            return null;
        }

        [, $orderCode, $id] = $m;

        if ($id !== '') {
            return self::where('return_id', (int) $id)
                ->whereHas('order', fn ($q) => $q->where('order_code', $orderCode))
                ->first();
        }

        return self::whereHas('order', fn ($q) => $q->where('order_code', $orderCode))
            ->whereIn('status', self::OPEN)
            ->orderByDesc('return_id')
            ->first();
    }
}
