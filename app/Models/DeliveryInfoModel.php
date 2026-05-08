<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryInfoModel extends Model
{
    use HasFactory;
    protected $table ="delivery_info"; 
    public $primaryKey = "info_id";
    public $timestamps = true;  
    protected $fillable = [
        'info_name',
        'info_phone',
        'info_email',
        'info_province',
        'info_district',
        'info_ward',
        'info_delivery_fee',
        'info_address',
        'info_default',
        'user_id',
    ];
}
