<?php

namespace App\Models;

use App\Enums\CustomerSegment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's RFM scores as of the last nightly run. The whole table is
 * rebuilt each time, so a row is never edited.
 */
class CustomerSegmentModel extends Model
{
    protected $table = 'customer_segments';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'segment' => CustomerSegment::class,
        'last_order_on' => 'date',
        'computed_at' => 'datetime',
    ];

    /** @return BelongsTo<UserModel, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'user_id');
    }
}
