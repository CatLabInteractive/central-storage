<?php

namespace App\Helpers;

/**
 * Class StatisticsHelper
 * @package App\Helpers
 */
class StatisticsHelper
{
    /**
     * @param $size
     * @param int $precision
     * @return string
     */
    public static function formatBytes($size, $precision = 2)
    {
        $suffixes = array('', 'K', 'M', 'G', 'T');

        // log(0) is -INF, which PHP 8.1+ refuses to use as an array index.
        if ($size <= 0) {
            return '0 ' . $suffixes[0];
        }

        $base = log($size, 1024);

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    /**
     * @param $float
     * @return string
     */
    public static function formatPercentage($float)
    {
        return round(($float) * 100) . '%';
    }
}