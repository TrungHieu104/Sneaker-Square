<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One reminder email about one abandoned cart. A cart gets at most two
 * automatic ones and one coupon an admin offers by hand (sent_by), told apart
 * by step; cart_key ties them to the same cart.
 *
 * lines is the cart as it was when the email went out, so the restore link
 * brings back what the email showed even if the shopper has since emptied it.
 */
class CartReminderModel extends Model
{
    public const FIRST = 1;

    public const SECOND = 2;

    public const OFFER = 3;

    protected $table = 'cart_reminders';

    protected $fillable = [
        'user_id', 'cart_key', 'step', 'lines', 'cart_value', 'coupon_id', 'sent_by', 'token',
        'sent_at', 'opened_at', 'clicked_at',
    ];

    protected $casts = [
        'lines' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /** @return BelongsTo<UserModel, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'user_id');
    }

    /** @return BelongsTo<CouponModel, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(CouponModel::class, 'coupon_id', 'coupon_id');
    }
}
