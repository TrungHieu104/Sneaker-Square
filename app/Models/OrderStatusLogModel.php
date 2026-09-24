<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of an order's history: what it was, what it became, and who moved it.
 *
 * Written only by OrderModel::moveTo(), so a row here is proof the change went
 * through the one door rather than a stray assignment somewhere.
 */
class OrderStatusLogModel extends Model
{
    public const ACTOR_CUSTOMER = 'khach';

    public const ACTOR_ADMIN = 'admin';

    public const ACTOR_SYSTEM = 'he_thong';

    public const ACTOR_CARRIER = 'ghn';

    protected $table = 'order_status_logs';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = ['order_id', 'from_status', 'to_status', 'actor', 'user_id', 'note', 'created_at'];

    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
        'created_at' => 'datetime',
    ];

    public function actorLabel(): string
    {
        return match ($this->actor) {
            self::ACTOR_CUSTOMER => 'Khách hàng',
            self::ACTOR_ADMIN => 'Quản trị viên',
            self::ACTOR_CARRIER => 'GHN',
            default => 'Hệ thống',
        };
    }

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'user_id');
    }
}
