<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserModel as User;
use App\Models\ProductModel as Product;

class CommentModel extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "comments";
    protected $primaryKey = "comment_id";
    public $timestamps = true;
    protected $fillable = [
        'comment_id',
        'comment_content',
        'comment_hidden',
        'comment_date',
        'pro_id',
        'user_id',
        'comment_name',
        'comment_email',
    ];
    protected $attributes = [
        'comment_hidden' => 0,
    ];

    public function getUsers() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function getProducts() {
        return $this->belongsTo(Product::class, 'pro_id', 'pro_id');
    }
}
