<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next , string $permission): Response
    {
          $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            return response()->json(['message' => "Forbidden: missing '{$permission}' permission."], 403);
        }

        return $next($request);
    }
}
