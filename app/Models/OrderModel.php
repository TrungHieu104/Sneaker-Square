<?php

namespace App\Models;

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
        'order_delivery_fee',
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
}