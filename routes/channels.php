<?php

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
