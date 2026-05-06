<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LikeModel extends Model
{
    use HasFactory;
    protected $table = "like";
    protected $primaryKey = "like_id";
    public $timestamps = true;
    protected $fillable = [
        'pro_id',
        'like_status',
        'user_id',
    ];
    protected $attributes = [
        'like_status' => 1,
    ];
}
