<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],
    
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
    ],

    'momo' => [
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
    ],

    'captcha' => [
        'key' => env('CAPTCHA_KEY'),
        'secret' => env('CAPTCHA_SECRET'),

        // The login form shows the reCAPTCHA widget once this many sign-in
        // attempts have failed, and from that point the token is required.
        // Both the form and LoginRequest read this same number.
        'after_failed_attempts' => env('CAPTCHA_AFTER_FAILED_ATTEMPTS', 3),
    ],

    'ghn' => [
        'host' => env('GHN_HOST', 'https://online-gateway.ghn.vn'),

        // The shop's own parcel management site on GHN, opened from the admin
        // sidebar. The sandbox account lives on a different host from the live
        // one, so it follows GHN_HOST rather than being hardcoded.
        'dashboard' => env('GHN_DASHBOARD', 'https://khachhang.ghn.vn'),
        'token' => env('GHN_TOKEN'),
        'shop_id' => env('GHN_SHOP_ID'),

        // Where the parcels are picked up. GHN reads it from the shop, but the
        // fee and leadtime endpoints want it spelled out on every call.
        'from_district_id' => env('GHN_FROM_DISTRICT_ID'),
        'from_ward_code' => env('GHN_FROM_WARD_CODE'),

        // One box for every parcel. Shoe boxes barely vary, and GHN bills on
        // whichever is larger, the real weight or length*width*height/5000.
        'box' => [
            'length' => (int) env('GHN_BOX_LENGTH', 32),
            'width' => (int) env('GHN_BOX_WIDTH', 22),
            'height' => (int) env('GHN_BOX_HEIGHT', 13),
        ],

        // What to charge when GHN cannot be reached. Losing the carrier must
        // not lose the order.

        'timeout' => (int) env('GHN_TIMEOUT', 8),

        // The pickup address printed on the label. GHN keeps one on the shop
        // too, but the create call wants it spelled out.
        'from_name' => env('GHN_FROM_NAME'),
        'from_phone' => env('GHN_FROM_PHONE'),
        'from_address' => env('GHN_FROM_ADDRESS'),

        // Off unless someone turns it on. A create call books a courier, and
        // against the production host that courier is real.
        'create_orders' => filter_var(env('GHN_CREATE_ORDERS', false), FILTER_VALIDATE_BOOL),
        // GHN signs no callback, so the secret is the URL itself. Empty means
        // the webhook answers 404 to everyone.
        'webhook_token' => env('GHN_WEBHOOK_TOKEN'),
        // How long one admin page holds its event stream open before the browser
        // reconnects. Each open stream occupies a PHP worker for that long.
        'stream_seconds' => (int) env('GHN_STREAM_SECONDS', 60),

        // Opens an admin page that feeds made-up GHN callbacks into the
        // tracker. Never available in production, whatever this says.
        'simulator' => filter_var(env('GHN_SIMULATOR', false), FILTER_VALIDATE_BOOL),
    ],
    'wallet' => [
        // Signs the balance and the ledger. Kept out of the database on
        // purpose: that is the whole point — somebody who can write to MySQL
        // still cannot produce a valid signature. Falls back to the app key so
        // a fresh checkout works, but production should give it its own.
        'signing_key' => env('WALLET_SIGNING_KEY'),
    ],
    'vnpay' => [
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
    ],

];
