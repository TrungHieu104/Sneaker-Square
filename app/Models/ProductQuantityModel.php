<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel as Product;
use App\Models\SizeModel as Size;
use App\Models\ColorModel as Color;

class ProductQuantityModel extends Model
{
    use HasFactory;
    protected $table = "products_quantity";
    protected $primaryKey = 'quantity_id';
    public $timestamps = true;
    protected $fillable = [
        'quantity',
        'quantity_date',
        'pro_id',
        'size_id',
        'color_id',
    ];

    public function getProducts() {
        return $this->belongsTo(Product::class, 'pro_id', 'pro_id');
    }

    public function getSize() {
        return $this->belongsTo(Size::class, 'size_id', 'size_id');
    }

    public function getColor() {
        return $this->belongsTo(Color::class, 'color_id', 'color_id');
    }
}
