<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One thing a shopper did, written once and never edited.
 *
 * session_id is the visitor cookie, not Laravel's session id: that one is
 * regenerated at login, which would cut a guest's history off from the account
 * it turns into.
 */
class UserEventModel extends Model
{
    public const PAGE_VIEW = 'page_view';

    public const VIEW_PRODUCT = 'view_product';

    public const SEARCH = 'search';

    public const FILTER = 'filter';

    public const SELECT_VARIANT = 'select_variant';

    public const STOCK_OUT_HIT = 'stock_out_hit';

    public const ADD_CART = 'add_cart';

    public const REMOVE_CART = 'remove_cart';

    public const WISHLIST = 'wishlist';

    public const CHECKOUT_START = 'checkout_start';

    public const PLACE_ORDER = 'place_order';

    public const PURCHASE = 'purchase';

    public const REVIEW = 'review';

    protected $table = 'user_events';

    protected $primaryKey = 'event_id';

    public $timestamps = false;

    protected $fillable = [
        'session_id', 'user_id', 'event_type', 'pro_id', 'size_id', 'color_id', 'order_id',
        'meta', 'url', 'referrer', 'device', 'ip', 'is_simulated', 'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_simulated' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Leaves out the rows the seeder made up, for any figure that claims to be
     * about real visitors.
     *
     * @param  Builder<UserEventModel>  $query
     * @return Builder<UserEventModel>
     */
    public function scopeReal(Builder $query): Builder
    {
        return $query->where('is_simulated', false);
    }
}
