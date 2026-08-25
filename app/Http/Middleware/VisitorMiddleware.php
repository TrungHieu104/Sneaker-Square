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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $visitorIP = $request->ip();
        // session(['visitorIP' => $visitorIP]);
        // $visitorCurrent =  VisitorModel::where('visitor_ip', $visitorIP)->get();
        // $visitorCurrentCount = $visitorCurrent->count();
        // if($visitorCurrentCount < 1) {
        //     $vistior = new VisitorModel();
        //     $vistior->visitor_ip = $visitorIP;
        //     $vistior->visitor_date = Now();
        //     $vistior->save();
        // }
        // return $next($request);
        $visitorIP = $request->ip();
        $visitorLastActive = now();

        // Check whether this visitor has already been seen recently.
        $existingVisitor = VisitorModel::where('visitor_ip', $visitorIP)
        ->where('visitor_date', '>=', now()->subMinutes(10))
        ->first();

        if (!$existingVisitor) {
            // Record a new row when this is a first-time visitor.
            $existingVisitor = VisitorModel::updateOrCreate([
                'visitor_ip' => $visitorIP,
            ], [
                'visitor_date' => $visitorLastActive,
            ]);
            
        }
        
        // $visitorCurrent =  VisitorModel::where('visitor_ip', $visitorIP)->get();
        // $visitorCurrentCount = $visitorCurrent->count();
        // if($visitorCurrentCount < 1) {
        //     $vistior = new VisitorModel();
        //     $vistior->visitor_ip = $visitorIP;
        //     $vistior->visitor_date = $visitorLastActive;
        //     $vistior->save();
        // }

        return $next($request);
    }
}
