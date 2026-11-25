<?php

namespace App\Http\Middleware;

use App\Models\UserModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The CKFinder file manager uploads, renames and deletes files under
 * public/uploads, so only active, signed-in staff may open it.
 */
class CustomCKFinderAuth
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        // The same test AuthAdminMiddleware applies to the admin area.
        $staff = $user instanceof UserModel && (int) $user->user_role === 1 && (int) $user->user_status === 1
            && ! ($user->locked_at > Carbon::now());

        abort_unless($staff, 403);

        config(['ckfinder.authentication' => fn () => $staff]);

        return $next($request);
    }
}
