<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Services\ShopSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OrderModel extends Model
{
    use HasFactory;
    protected $table = "order";
    public $primaryKey = "order_id";
    public $timestamps = true;
    protected $fillable = [
        'order_code', 
        'order_name', 
        'order_phone', 
        'order_email', 
        'order_address', 
        'order_local',
        'order_district_id',
        'order_ward_code',
        'order_delivery_fee',
        'order_expected_delivery',
        'order_shipping_code',
        'order_shipping_status',
        'order_delivered_at',
        'order_completed_at',
        'order_coupon_value', 
        'order_total', 
        'order_payment', 
        'order_payment_status', 
        'order_date', 
        'order_delivery_status', 
        'order_status', 
        'order_cancel_reason', 
        'order_refund_required', 
        'note_customer', 
        'note_admin', 
        'coupon_id', 
        'user_id'
    ];

    /**
     * Orders that count as a real sale.
     *
     * Cash on delivery counts as soon as it is placed; a gateway order counts
     * only once the payment settled. The dashboard spelled this pair of
     * conditions out three times over, in full, each time.
     */
    public function scopeConfirmedSale(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where(function (Builder $cod) {
                $cod->where('order_payment', 'cod')->where('order_payment_status', 0);
            })->orWhere(function (Builder $gateway) {
                $gateway->whereIn('order_payment', ['payUrl', 'redirect', 'wallet'])
                    ->where('order_payment_status', 1);
            });
        });
    }

    public function User()
    {
        return $this->belongsTo(UserModel::class, 'user_id','user_id');
    }
    public function orderDetail()
    {
        return $this->hasMany(OrderDetailModel::class, 'order_id');
    }
    public function Coupon()
    {
        return $this->belongsTo(CouponModel::class, 'coupon_id');
    }
    public function Product()
    {
        return $this->hasMany(ProductModel::class, 'pro_id');
    }

    protected $casts = [
        'order_delivered_at' => 'datetime',
        'order_completed_at' => 'datetime',
        'order_payment_time' => 'datetime',
        'order_status' => OrderStatus::class,
        'order_refund_required' => 'boolean',
    ];

    /**
     * How the customer paid, as the bill and the confirmation email say it.
     */
    public function paymentLabel(): string
    {
        return match ($this->order_payment) {
            'cod' => 'Thanh toán khi nhận hàng',
            'payUrl' => 'Thanh toán qua MoMo',
            'redirect' => 'Thanh toán qua VNPay',
            'wallet' => 'Thanh toán bằng SPay',
            default => (string) $this->order_payment,
        };
    }

    /**
     * Whether the money has arrived, and when.
     *
     * Cash on delivery is not unpaid the way an abandoned gateway order is —
     * nobody walked away from it, the shop simply has not collected yet.
     */
    public function paymentStatusLabel(): string
    {
        if ((int) $this->order_payment_status === 1) {
            return $this->order_payment_time
                ? 'Đã thanh toán · '.$this->order_payment_time->format('H:i d/m/Y')
                : 'Đã thanh toán';
        }

        return $this->order_payment === 'cod' ? 'Thu khi giao hàng' : 'Chưa thanh toán';
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLogModel::class, 'order_id', 'order_id')->orderByDesc('created_at');
    }

    /**
     * The one door every status change goes through.
     *
     * Nothing else may assign `order_status`: the column now mirrors what the
     * carrier reports, so two writers with their own idea of the order would
     * leave it stuck between them. Going through here also means no change can
     * happen without a line of history explaining it.
     *
     * Other fields set on this instance beforehand are saved in the same call.
     *
     * @return bool whether this call is the one that moved the order
     */
    public function moveTo(OrderStatus $to, string $actor, ?string $note = null): bool
    {
        $from = $this->order_status;

        if ($from === $to) {
            $this->save();

            return false;
        }

        $this->order_status = $to;
        $this->save();

        OrderStatusLogModel::create([
            'order_id' => $this->order_id,
            'from_status' => $from,
            'to_status' => $to,
            'actor' => $actor,
            'user_id' => in_array($actor, [OrderStatusLogModel::ACTOR_ADMIN, OrderStatusLogModel::ACTOR_CUSTOMER], true)
                ? Auth::id()
                : null,
            'note' => $note,
            'created_at' => Carbon::now(),
        ]);

        return true;
    }

    public function hasStatus(OrderStatus ...$statuses): bool
    {
        return in_array($this->order_status, $statuses, true);
    }

    /**
     * GHN statuses between a failed delivery and the parcel landing back at
     * the shop — the stretch where somebody should be calling the customer.
     */
    public const RETURN_IN_PROGRESS = [
        'delivery_fail',
        'waiting_to_return',
        'return',
        'return_transporting',
        'return_sorting',
        'returning',
        'return_fail',
    ];

    /**
     * The shop has checked this order and the goods are still on its shelves.
     * Nothing else may be handed to a carrier: a cancelled order has already
     * put its stock back, and a returned one is travelling the other way.
     */
    public function isConfirmed(): bool
    {
        return $this->hasStatus(OrderStatus::Confirmed);
    }

    /**
     * The parcel is with a carrier, or already at the customer's door.
     */
    public function hasLeftWarehouse(): bool
    {
        return $this->hasStatus(OrderStatus::Delivering, OrderStatus::Delivered);
    }

    /**
     * Whether the customer may still ask the shop to call the order off. Once
     * the goods are on a van it is too late — from there it is a return.
     */
    public function canRequestCancel(): bool
    {
        return $this->hasStatus(OrderStatus::Confirmed, OrderStatus::ReadyToShip);
    }

    /**
     * Where a refused cancellation puts the order back. The parcel itself is
     * the record of how far it had got, so nothing needs remembering.
     */
    public function statusBeforeCancelRequest(): OrderStatus
    {
        return $this->order_shipping_code !== null ? OrderStatus::ReadyToShip : OrderStatus::Confirmed;
    }

    /**
     * A gateway order the customer walked away from without paying.
     *
     * Cash on delivery and the wallet both leave the till settled the moment
     * the order is placed, so neither is ever waiting on a gateway.
     */
    public function isAwaitingPayment(): bool
    {
        return in_array($this->order_payment, ['payUrl', 'redirect'], true)
            && (int) $this->order_payment_status === 0;
    }

    /**
     * Whether the customer can now say they have the goods.
     *
     * A parcel sent through GHN counts only once GHN says it was delivered:
     * handing it to the carrier is not the customer receiving it. An order the
     * shop delivered some other way has only the old handover flag to go on.
     */
    public function isAwaitingReceipt(): bool
    {
        if ($this->hasStatus(OrderStatus::Delivered)) {
            return true;
        }

        // A parcel the shop carried itself has no tracking to wait for, so it
        // never reaches `delivered`: the customer saying so is the only word.
        return $this->hasStatus(OrderStatus::Delivering) && $this->isHandedOverManually();
    }

    /**
     * Whether the parcel is GHN's to carry. A cancelled parcel leaves the
     * order free to be sent some other way, so it does not count.
     */
    public function usesGhn(): bool
    {
        return $this->order_shipping_code !== null
            || ($this->order_shipping_status !== null && $this->order_shipping_status !== 'cancel');
    }

    /**
     * The shop took this parcel to a carrier of its own. The two ways out of
     * the warehouse exclude each other: there is no tracking to follow here,
     * so the customer saying they have the goods is the only word on delivery.
     */
    public function isHandedOverManually(): bool
    {
        return (int) $this->order_delivery_status === 1 && ! $this->usesGhn();
    }

    /**
     * Every return the customer has opened on this order, newest first.
     */
    public function orderReturns()
    {
        return $this->hasMany(OrderReturnModel::class, 'order_id', 'order_id')->latest('return_id');
    }

    /**
     * The newest request. Kept as a singular relation because every screen
     * that shows "the" return means the one the customer is looking at now.
     */
    public function orderReturn()
    {
        return $this->hasOne(OrderReturnModel::class, 'order_id', 'order_id')->latestOfMany('return_id');
    }

    /**
     * The one the shop still has work to do on, if any. Only one may be open
     * at a time, which is what lets the admin screens act on an order rather
     * than on a request id.
     */
    public function activeReturn()
    {
        return $this->hasOne(OrderReturnModel::class, 'order_id', 'order_id')
            ->whereIn('status', OrderReturnModel::OPEN)
            ->latestOfMany('return_id');
    }

    /**
     * How many units of each line the customer could still send back.
     *
     * A refused or cancelled request gives its units back to the pool: the
     * goods never left the customer's house. Everything else holds them.
     *
     * @return array<int, int>  quantity left, keyed by order_details_id
     */
    public function returnableQuantities(): array
    {
        $daTra = OrderReturnItemModel::query()
            ->join('order_returns', 'order_returns.return_id', '=', 'order_return_items.return_id')
            ->where('order_returns.order_id', $this->order_id)
            ->whereIn('order_returns.status', OrderReturnModel::HOLDS_GOODS)
            ->groupBy('order_return_items.order_details_id')
            ->selectRaw('order_return_items.order_details_id, SUM(order_return_items.quantity) as da_tra')
            ->pluck('da_tra', 'order_details_id');

        $conLai = [];

        foreach (OrderDetailModel::where('order_id', $this->order_id)->get() as $dong) {
            $con = (int) $dong->quantity - (int) ($daTra[$dong->order_details_id] ?? 0);

            if ($con > 0) {
                $conLai[(int) $dong->order_details_id] = $con;
            }
        }

        return $conLai;
    }

    /**
     * Whether every unit the customer bought has gone back to the shop.
     */
    public function allUnitsReturned(): bool
    {
        return $this->returnableQuantities() === [];
    }

    /**
     * Whether the customer may open a return now: the order has arrived and
     * settled, the window is open, nothing else is being handled, and there
     * is something left to send back.
     */
    public function canRequestReturn(): bool
    {
        if (! $this->hasStatus(OrderStatus::Completed, OrderStatus::PartiallyReturned)) {
            return false;
        }

        if ($this->activeReturn()->exists() || $this->returnableQuantities() === []) {
            return false;
        }

        // Orders completed before the completion time was recorded fall back
        // to their last update, which is when the old button set them.
        $completedAt = $this->order_completed_at ?? $this->updated_at;
        $days = app(ShopSettings::class)->returnDays();

        return $completedAt !== null && $completedAt->copy()->addDays($days)->isFuture();
    }

    public function returnDeadline(): ?Carbon
    {
        $completedAt = $this->order_completed_at ?? $this->updated_at;

        return $completedAt?->copy()->addDays(app(ShopSettings::class)->returnDays());
    }

    public function isBeingReturned(): bool
    {
        return $this->hasStatus(OrderStatus::Returning)
            || in_array($this->order_shipping_status, self::RETURN_IN_PROGRESS, true);
    }

    /**
     * Oldest first, by the time the carrier stamped rather than the time the
     * callback arrived: GHN retries and reorders.
     */
    public function shipmentEvents()
    {
        return $this->hasMany(ShipmentEventModel::class, 'order_id', 'order_id')->orderBy('happened_at');
    }

    /**
     * The history of the parcel currently on this order. An order handed over
     * twice keeps the cancelled attempt's rows, and showing them under the new
     * code reads as if the new parcel were the one that failed.
     */
    public function currentShipmentEvents()
    {
        return $this->shipmentEvents->filter(
            fn ($event) => $event->shipping_code === null
                || $event->shipping_code === $this->order_shipping_code
        )->values();
    }

    /**
     * The attempts before this one, newest parcel first.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    public function previousShipments()
    {
        // The parcel coming back from the customer has its own panel.
        $returnCode = $this->orderReturn?->return_shipping_code;

        return $this->shipmentEvents
            ->filter(fn ($event) => $event->shipping_code !== null
                && $event->shipping_code !== $this->order_shipping_code
                && $event->shipping_code !== $returnCode)
            ->groupBy('shipping_code')
            ->map(fn ($events) => $events->sortByDesc('happened_at')->values())
            ->sortByDesc(fn ($events) => $events->first()->happened_at);
    }
}