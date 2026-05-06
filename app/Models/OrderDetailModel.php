<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class OrderDetailModel extends Model
{
    use HasFactory;
    protected $table ="order_details"; 
    public $primaryKey = "order_details_id";
    public $timestamps = false;  
    protected $fillable = [
        'pro_name',
        'size',
        'color',
        'price',
        'quantity',
        'order_id',
        'pro_id'
    ];
    public function order(){
        return $this->belongsTo('App\Models\Order','order_id');
    }
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'pro_id');
    }
}