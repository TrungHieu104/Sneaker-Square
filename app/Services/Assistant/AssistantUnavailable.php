<?php

namespace App\Services\Assistant;

use RuntimeException;

/**
 * The Claude API is not configured, down, slow past the timeout, or
 * answered something unusable.
 */
class AssistantUnavailable extends RuntimeException {}
