<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Class AddDebugHeaders
 * @package App\Http\Middleware
 */
class AddDebugHeaders
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Perform action
        $response->headers->set('X-PHP-Memory-Usage', $this->formatBytes(memory_get_usage()));

        return $response;
    }

    /**
     * @param $size
     * @return string
     */
    private function formatBytes($size) {
        $base = log($size) / log(1024);
        $suffix = array("", "KB", "MB", "GB", "TB");
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 3) . $suffix[$f_base];
    }
}