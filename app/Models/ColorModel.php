<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorModel extends Model
{
    use HasFactory;
    protected $table = "color";
    protected $primaryKey = "color_id";
    public $timestamps = true;
    protected $fillable = [
        'color_id',
        'color',
        'color_vn',
        'color_hidden',
    ];
    protected $attributes = [
        'color_hidden' => 1,
    ];
}
