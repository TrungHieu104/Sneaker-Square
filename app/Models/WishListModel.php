<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishListModel extends Model
{
    use HasFactory;
    
    protected $table = "like";
    protected $primaryKey = "like_id";
    public $timestamps = true;
    protected $fillable = [
        'pro_id',
        'user_id',
    ];

    public function products()
    {
        return $this->belongsTo(ProductModel::class, 'pro_id');
    }
}
