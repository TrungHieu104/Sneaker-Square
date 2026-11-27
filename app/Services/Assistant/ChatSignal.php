<?php

namespace App\Services\Assistant;

use App\Events\AssistantChatChanged;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\DB;

/**
 * Tells the open widget and the staff inbox that a chat changed, once the
 * change is committed, so whoever fetches it on the signal finds it.
 */
class ChatSignal
{
    // The browser builds the widget and the inbox load, kept in public/ since the project has no build step.
    public const SCRIPTS = ['js/realtime/pusher-8.6.0.min.js', 'js/realtime/echo-2.6.3.iife.js'];

    // Each poll fetches the whole inbox page, so it runs far less often than the widget's.
    public const INBOX_POLL_MS = 15000;

    /**
     * Where a browser reaches the socket server, or null while broadcasting
     * is off and the widget and the inbox poll instead. Only the public key
     * leaves the server; the secret signs channel access here.
     *
     * @return array{key: string, host: string, port: int, tls: bool}|null
     */
    public function forBrowser(): ?array
    {
        $reverb = (array) config('broadcasting.connections.reverb');

        if (config('broadcasting.default') !== 'reverb' || (string) ($reverb['key'] ?? '') === '') {
            return null;
        }

        return [
            'key' => (string) $reverb['key'],
            'host' => (string) ($reverb['options']['host'] ?? ''),
            'port' => (int) ($reverb['options']['port'] ?? 443),
            'tls' => (bool) ($reverb['options']['useTLS'] ?? true),
        ];
    }

    public function changed(int $conversationId): void
    {
        DB::afterCommit(function () use ($conversationId) {
            try {
                event(new AssistantChatChanged($conversationId));
            } catch (BroadcastException $e) {
                // Without the socket server the widget and the inbox fall back to
                // polling, so a lost signal only delays a line; the reply,
                // hand-off or answer that caused it must still go through.
                report($e);
            }
        });
    }
}
