<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A provider sign-in that cannot go through however often it is retried. The
 * message is shown to the customer as it is.
 */
class SocialSignInRefused extends RuntimeException {}
