<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;


class AuthAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check()) {
            $user = Auth::user();

            if ($user->user_role == 1 && $user->user_status == 1) {
                if ($user->locked_at > Carbon::now()) {
                    Session::flash('iconMessage', 'error');
                    $remainingTime = now()->diffInMinutes($user->locked_at) . ' phút ' . now()->diffInSeconds($user->locked_at) % 60 . ' giây';
                    return redirect(route('admin.login'))->with('message', 'Vui lòng thử lại sau '. $remainingTime . '!');
                }
                return $next($request);
            }
            Session::flash('iconMessage', 'error');
            return redirect(route('admin.login'))->with('message', 'Không được phép truy cập');
        } 
        Session::flash('iconMessage', 'error');
        return redirect(route('admin.login'))->with('message', 'Không được phép truy cập');
    }
}