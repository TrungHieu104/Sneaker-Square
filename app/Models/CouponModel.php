<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coupon';

    public $primaryKey = 'coupon_id';

    public $timestamps = true;

    protected $fillable = ['coupon_name', 'coupon_code', 'coupon_value', 'coupon_quantity', 'coupon_used', 'coupon_condition', 'coupon_date', 'coupon_start', 'coupon_end'];

    /** @return HasMany<OrderModel, $this> */
    public function Coupon(): HasMany
    {
        return $this->hasMany(OrderModel::class, 'coupon_id');
    }
}
