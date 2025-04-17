<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AllowAdminOrUser
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

        // Allow if role is either 'admin' or 'user'
        if ($user && in_array($user->role, ['admin', 'user'])) {
            return $next($request);
        }

        return response()->json([
            'code' => 403,
            'success' => false,
            'message' => 'Access denied. Only admin or user can access this route.',
        ], 403);
    }
}
