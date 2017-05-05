<?php

namespace App\Http\Middleware;

use CentralStorage;
use Closure;

/**
 * Class ApiAuthentication
 * @package App\Http\Middleware
 */
class ApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (!CentralStorage::isValid($request)) {
            return response()->json([ 'error' => [ 'message' => 'Authentication failed' ]], 403);
        }

        return $next($request);
    }
}