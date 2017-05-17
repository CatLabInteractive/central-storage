<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use App\Models\ConsumerAsset;
use Epyc\CentralStorage\Client\Models\Asset;

/**
 * Class StatisticsController
 * @package App\Http\Controllers
 */
class StatisticsController extends Controller
{
    /**
     * Consumer index page
     */
    public function index()
    {
        return view('statistics', [ 'statistics' => $this->getStatistics() ]);
    }

    /**
     * @return array
     */
    public function getStatistics()
    {
        $uploaded = ConsumerAsset::count();
        $uploadedUnique = ConsumerAsset::distinct('asset_id')->count('asset_id');

        $size = ConsumerAsset::join('assets', 'consumer_assets.asset_id', '=', 'assets.id')
            ->sum('assets.size');

        $uniqueSize = Asset::whereIn('id', function($builder) {
            return $builder->from('consumer_assets')
                ->distinct('asset_id')
                ->select('asset_id');
        })->sum('size');

        return [
            'uploaded' => $uploaded,
            'uploadedUnique' => $uploadedUnique,
            'uniquePercentage' => StatisticsHelper::formatPercentage($uploadedUnique / max(1, $uploaded)),
            'uploadedSize' => StatisticsHelper::formatBytes($size),
            'uploadedUniqueSize' => StatisticsHelper::formatBytes($uniqueSize),
            'uniqueSizePercentage' => StatisticsHelper::formatPercentage($uniqueSize / max(1, $size)),
            'savings' => StatisticsHelper::formatPercentage(1 - ($uniqueSize / max(1, $size))),
            'spaceSaved' => StatisticsHelper::formatBytes($size - $uniqueSize)
        ];
    }
}