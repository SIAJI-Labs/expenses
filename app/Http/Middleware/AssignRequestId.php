<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) generateRandomMixCharacter(32, false);

        // Assign Request ID to Request
        if(!$request->headers->has('Request-Id')){
            $request->headers->set('Request-Id', $id);
        }
        
        return $next($request);
    }
}
