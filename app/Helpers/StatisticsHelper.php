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
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

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