<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'order_coupon_value', 
        'order_total', 
        'order_payment', 
        'order_payment_status', 
        'order_date', 
        'order_delivery_status', 
        'order_status', 
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
                $gateway->whereIn('order_payment', ['payUrl', 'redirect'])
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

    /**
     * Oldest first, by the time the carrier stamped rather than the time the
     * callback arrived: GHN retries and reorders.
     */
    /**
     * The shop has checked this order and it is waiting to be sent. Nothing
     * else may be handed to a carrier: a cancelled order has already put its
     * stock back, and a returned one is travelling the other way.
     */
    public function isConfirmed(): bool
    {
        return (int) $this->order_status === 1;
    }

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
        return $this->shipmentEvents
            ->filter(fn ($event) => $event->shipping_code !== null
                && $event->shipping_code !== $this->order_shipping_code)
            ->groupBy('shipping_code')
            ->map(fn ($events) => $events->sortByDesc('happened_at')->values())
            ->sortByDesc(fn ($events) => $events->first()->happened_at);
    }
}