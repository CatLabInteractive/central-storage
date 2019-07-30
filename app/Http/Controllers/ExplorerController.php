<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use App\Models\ConsumerAsset;
use CatLab\CentralStorage\Client\Models\Asset;

/**
 * Class ExplorerController
 * @package App\Http\Controllers
 */
class ExplorerController extends Controller
{
    /**
     * Consumer index page
     */
    public function explore()
    {
        $assets = ConsumerAsset::orderBy('id', 'desc')->paginate(50);

        return view('explorer', [ 'assets' => $assets ]);
    }
}