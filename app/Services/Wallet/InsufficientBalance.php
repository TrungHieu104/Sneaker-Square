<?php

namespace App\Services\Wallet;

use RuntimeException;

class InsufficientBalance extends RuntimeException
{
    public function __construct(string $message = 'Số dư ví không đủ.')
    {
        parent::__construct($message);
    }
}
