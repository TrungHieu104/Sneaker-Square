<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeModel extends Model
{
    use HasFactory;
    protected $table = "size";
    protected $primaryKey = "size_id";
    public $timestamps = true;
    protected $fillable = [
        'size_id',
        'size',
        'size_hidden',
    ];
    protected $attributes = [
        'size_hidden' => 1,
    ];
}
