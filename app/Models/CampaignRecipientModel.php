<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One customer's copy of a campaign. The coupon is theirs alone, so an order
 * using it is traced back to the campaign without guessing.
 */
class CampaignRecipientModel extends Model
{
    protected $table = 'campaign_recipients';

    protected $fillable = ['campaign_id', 'user_id', 'coupon_id', 'token', 'sent_at', 'opened_at', 'clicked_at'];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /** @return BelongsTo<CampaignModel, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CampaignModel::class, 'campaign_id');
    }

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
