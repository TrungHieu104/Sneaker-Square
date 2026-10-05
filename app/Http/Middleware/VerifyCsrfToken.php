<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    // Each entry proves the request some other way, as noted beside it.
    protected $except = [ // NOSONAR
        // CKFinder checks its own double-submit token (csrfProtection in config/ckfinder.php).
        'ckfinder/*',
        // The payment gateways post here from their own servers, so they have
        // no session and no token. Authenticity is proved by the HMAC on the
        // payload instead, which PaymentCallbackController checks.
        'ipn-thanh-toan',
        // GHN posts here from its own servers. The secret in the path is what
        // stands in for a token.
        'webhook/ghn/*',
    ];
}
