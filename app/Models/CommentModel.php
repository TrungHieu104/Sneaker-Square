<?php

namespace App\Models;

use App\Models\ProductModel as Product;
use App\Models\UserModel as User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommentModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'comments';

    protected $primaryKey = 'comment_id';

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
        'rating',
    ];

    protected $attributes = [
        'comment_hidden' => 0,
    ];

    /** @return BelongsTo<User, $this> */
    public function getUsers(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function getProducts(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pro_id', 'pro_id');
    }
}
