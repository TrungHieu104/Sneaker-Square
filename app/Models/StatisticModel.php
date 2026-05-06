<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatisticModel extends Model
{
    use HasFactory;
    protected $table = "statistical";
    protected $primaryKey = "id_statistical";
    public $timestamps = false;
    protected $fillable = [
        'order_date',
        'sales',
        'profit',
        'order_total',
    ];
}
