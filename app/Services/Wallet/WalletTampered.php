<?php

namespace App\Services\Wallet;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use RuntimeException;

/**
 * A wallet or a ledger row no longer matches its signature.
 *
 * Thrown rather than logged: a balance the application cannot vouch for must
 * not be spent, refunded or shown as if it were true.
 */
class WalletTampered extends RuntimeException
{
    public function __construct(string $message = 'Ví đang tạm khoá để đối soát. Vui lòng liên hệ cửa hàng.')
    {
        parent::__construct($message);
    }

    /**
     * Handled here rather than at each of the dozen call sites: whatever the
     * customer or the admin was doing, the answer is the same — the wallet is
     * held until somebody reconciles it, and nothing was written.
     */
    public function render(Request $request)
    {
        Session::flash('iconMessage', 'error');

        return back()->with('message', $this->getMessage());
    }
}
