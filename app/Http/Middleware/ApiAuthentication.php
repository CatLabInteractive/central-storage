<?php

namespace App\Http\Middleware;

use App\Models\Consumer;
use CentralStorage;
use Closure;
use Epyc\CentralStorage\Client\CentralStorageClient;

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
        if (!$this->isValidRequest($request)) {
            return response()->json([ 'error' => [ 'message' => 'Authentication failed' ]], 403);
        }

        return $next($request);
    }

    /**
     * @param $request
     * @return bool
     */
    protected function isValidRequest($request)
    {
        // Look for key
        $key = $request->header(CentralStorageClient::HEADER_KEY);
        $consumer = Consumer::findFromKey($key);
        if (!$consumer) {
            return false;
        }

        return CentralStorage::isValid($request, $consumer->key, $consumer->secret);
    }
}