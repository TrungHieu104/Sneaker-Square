<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Broadcast;

/**
 * Broadcasting switched to Reverb inside a test, and the channel signature a
 * browser gets back from `/broadcasting/auth`.
 */
trait ReverbFixtures
{
    private const KEY = 'khoa-cong-khai';

    private const SECRET = 'khoa-bi-mat';

    private const SOCKET = '1234.5678';

    /**
     * Broadcasting on Reverb, as a shop running `reverb:start` has it. The
     * channels are registered again, since the ones from boot went to the
     * null broadcaster the tests start with.
     */
    private function reverbOn(int $port = 8080): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => self::KEY,
            'broadcasting.connections.reverb.secret' => self::SECRET,
            'broadcasting.connections.reverb.app_id' => '1001',
            'broadcasting.connections.reverb.options' => ['host' => '127.0.0.1', 'port' => $port, 'scheme' => 'http', 'useTLS' => false],
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    private function signature(string $channel): string
    {
        return self::KEY.':'.hash_hmac('sha256', self::SOCKET.':'.$channel, self::SECRET);
    }
}
