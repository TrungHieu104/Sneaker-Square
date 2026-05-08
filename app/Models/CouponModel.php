<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CouponModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table ="coupon"; 
    public $primaryKey = "coupon_id";
    protected $dates = ['deleted_at'];
    public $timestamps = true;  
    protected $fillable = ['coupon_name', 'coupon_code', 'coupon_value','coupon_quantity','coupon_used','coupon_condition','coupon_date','coupon_start','coupon_end'];
    public function Coupon()
    {
        return $this->hasMany(OrderModel::class, 'coupon_id');
    }
}
