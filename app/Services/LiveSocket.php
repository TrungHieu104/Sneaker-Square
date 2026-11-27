<?php

namespace App\Services;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\DB;

/**
 * The Reverb socket the open pages listen on: what a browser needs to reach
 * it, and how a change is announced on it. Pages are only told that something
 * changed; they fetch it through their usual routes, so every read is still
 * checked there.
 */
class LiveSocket
{
    // The browser builds of Pusher and Echo, kept in public/ since the project has no build step.
    public const SCRIPTS = ['js/realtime/pusher-8.6.0.min.js', 'js/realtime/echo-2.6.3.iife.js'];

    // How often an admin page asks again while the socket is off or unreachable.
    public const ADMIN_POLL_MS = 15000;

    /**
     * Where a browser reaches the socket server, or null while broadcasting
     * is off and pages poll instead. Only the public key leaves the server;
     * the secret signs channel access here.
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

    /**
     * Sends the event once the change is committed, so whoever fetches on it
     * finds the change.
     */
    public function send(object $event): void
    {
        DB::afterCommit(function () use ($event) {
            try {
                event($event);
            } catch (BroadcastException $e) {
                // Without the socket server the pages fall back to polling, so a
                // lost signal only delays a refresh; the change that caused it
                // must still go through.
                report($e);
            }
        });
    }
}
