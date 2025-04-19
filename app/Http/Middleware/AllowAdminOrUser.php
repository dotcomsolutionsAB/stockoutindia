<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
// use Illuminate\Support\Facades\Auth;

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
        $user = $request->user();

        if (!$user || $user->role !== $role) {
            return response()->json(['error' => 'Unauthorized', 'status' => 403], 403);
        }

        return $next($request);
    }
}
