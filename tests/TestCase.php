<?php

namespace Tests;

use App\Logging\LogChannel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Monolog\Handler\NullHandler;

abstract class TestCase extends BaseTestCase
{
    // CreatesApplication refuses to boot unless the suite is pointed at the
    // in-memory SQLite database, so no test can reach real data.
    use CreatesApplication;

    /**
     * The fake callbacks and parcels a test run makes must not land in the
     * log files a developer reads; a test that checks a log captures it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['daily', LogChannel::PAYMENT, LogChannel::SHIPPING, LogChannel::MAIL] as $channel) {
            config(["logging.channels.{$channel}" => ['driver' => 'monolog', 'handler' => NullHandler::class]]);
        }
    }
}
