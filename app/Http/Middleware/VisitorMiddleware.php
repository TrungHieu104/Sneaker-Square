<?php

namespace App\Http\Middleware;

use App\Models\VisitorModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VisitorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $visitorIP = $request->ip();
        $visitorLastActive = now();

        // Check whether this visitor has already been seen recently.
        $existingVisitor = VisitorModel::where('visitor_ip', $visitorIP)
            ->where('visitor_date', '>=', now()->subMinutes(10))
            ->first();

        if (! $existingVisitor) {
            // Record a new row when this is a first-time visitor.
            $existingVisitor = VisitorModel::updateOrCreate([
                'visitor_ip' => $visitorIP,
            ], [
                'visitor_date' => $visitorLastActive,
            ]);

        }

        return $next($request);
    }
}
