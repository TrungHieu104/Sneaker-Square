<?php

namespace App\Models;

use App\Enums\CustomerSegment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignModel extends Model
{
    protected $table = 'campaigns';

    protected $fillable = ['name', 'segment', 'percent', 'min_order', 'max_discount', 'days', 'subject', 'message', 'sent_by'];

    protected $casts = [
        'segment' => CustomerSegment::class,
    ];

    /** @return HasMany<CampaignRecipientModel, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipientModel::class, 'campaign_id');
    }
}
