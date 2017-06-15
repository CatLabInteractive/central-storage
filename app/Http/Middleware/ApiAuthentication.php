<?php

namespace App\Http\Middleware;

use App\Models\Consumer;
use CentralStorage;
use Closure;
use CatLab\CentralStorage\Client\CentralStorageClient;
use Illuminate\Http\Request;

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
     * @param Request $request
     * @return bool
     */
    protected function isValidRequest(Request $request)
    {
        // Look for key
        $key = $request->header(CentralStorageClient::HEADER_KEY);
        $consumer = Consumer::findFromKey($key);
        if (!$consumer) {
            return false;
        }

        if (!CentralStorage::isValid($request, $consumer->key, $consumer->secret)) {
            return false;
        }

        $request->merge([ 'consumer' => $consumer ]);

        return true;
    }
}