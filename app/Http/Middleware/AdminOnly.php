<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request);
        $user = Auth::user();

        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        return response()->json([
            'code' => 403,
            'success' => false,
            'message' => 'Access denied. Only admin can access this route.',
        ], 403);
    }
}
