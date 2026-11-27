<?php

use App\Events\AdminNoticesChanged;
use App\Events\ShipmentChanged;
use App\Models\AssistantConversationModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// The staff inbox hears about every chat, so only those who may read it may
// listen. A customer's own chat channel is authorised by the widget's route
// instead, since a guest has no login for this one to check.
Broadcast::channel(AssistantConversationModel::INBOX_CHANNEL, fn (UserModel $user) => $user->can('Khách hàng liên hệ'));

// The same readers as the admin routes behind each signal: the order page for
// a shipment, any active admin for the bell.
Broadcast::channel(ShipmentChanged::CHANNEL.'.{orderId}', fn (UserModel $user) => $user->can('Quản trị Đơn hàng'));
Broadcast::channel(AdminNoticesChanged::CHANNEL, fn (UserModel $user) => $user->user_role == 1 && $user->user_status == 1);
