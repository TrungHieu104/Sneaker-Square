<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;

trait CapturesLogs
{
    /**
     * Points a log channel at memory for the rest of the test.
     */
    private function captureLog(string $channel): TestHandler
    {
        config(["logging.channels.{$channel}" => ['driver' => 'monolog', 'handler' => TestHandler::class]]);
        Log::forgetChannel($channel);

        $logger = Log::channel($channel)->getLogger();
        $this->assertInstanceOf(Logger::class, $logger);
        $handler = $logger->getHandlers()[0];
        $this->assertInstanceOf(TestHandler::class, $handler);

        return $handler;
    }

    /**
     * @return list<array<mixed>> the context of every record with this message
     */
    private function logged(TestHandler $log, string $message): array
    {
        return array_values(array_map(
            fn (LogRecord $record) => $record->context,
            array_filter($log->getRecords(), fn (LogRecord $record) => $record->message === $message),
        ));
    }
}
