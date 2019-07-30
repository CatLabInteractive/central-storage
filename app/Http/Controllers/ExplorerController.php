<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use App\Models\Consumer;
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
     * @param Consumer $consumer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function explore(Consumer $consumer)
    {
        $this->authorize('explore', $consumer);
        $assets = $consumer->consumerAssets()->orderBy('id', 'desc')->paginate(50);

        return view('explorer', [ 'assets' => $assets ]);
    }
}