<?php

namespace App\Services\Assistant;

/**
 * What Claude answered, and what the call cost in tokens, so each reply can
 * keep a trace of how it was made.
 */
final class ClaudeAnswer
{
    /**
     * @param  array<string, mixed>  $data  the JSON answer, in the shape of the schema asked for
     */
    public function __construct(
        public readonly array $data,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly int $cacheReadTokens,
        public readonly int $cacheWriteTokens,
    ) {}
}
