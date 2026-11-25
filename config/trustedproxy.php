<?php

return [

    /*
    | Proxies whose X-Forwarded-* headers are believed: "*" for whichever one
    | is calling (a local tunnel), or a comma-separated list of IPs. Left
    | empty, those headers are ignored and anyone could forge them.
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
