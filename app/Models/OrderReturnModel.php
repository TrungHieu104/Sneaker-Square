<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer sending back an order they already received.
 *
 * One per order: a rejected request is final, the way the marketplaces treat
 * it, so there is never a second request to reconcile against the first.
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
    ];

    protected $fillable = [
        'order_id',
        'status',
        'reason',
        'description',
        'images',
        'refund_info',
    ];

    protected $casts = [
        'images' => 'array',
        'decided_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'order_id');
    }

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

        return $refund;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * What the shop sends GHN as client_order_code for the parcel coming
     * back. It must differ from the outbound parcel's, and it is how a
     * callback for a parcel booked on GHN's own dashboard finds this request.
     */
    public function reference(): string
    {
        return $this->order->order_code.self::REFERENCE_SUFFIX;
    }

    public const REFERENCE_SUFFIX = '-TH';

    public static function forReference(?string $reference): ?self
    {
        if (! $reference || ! str_ends_with($reference, self::REFERENCE_SUFFIX)) {
            return null;
        }

        $orderCode = substr($reference, 0, -strlen(self::REFERENCE_SUFFIX));

        return self::whereHas('order', fn ($q) => $q->where('order_code', $orderCode))->first();
    }
}
