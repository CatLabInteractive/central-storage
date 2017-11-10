<?php

namespace App\Http\Controllers;

use App\Models\Consumer;
use App\Models\Processor;

/**
 * Class ProcessorController
 * @package App\Http\Controllers
 */
class ProcessorController extends Controller
{
    /**
     * @param Consumer $consumer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Consumer $consumer)
    {
        $this->authorize('create', [ $consumer, Processor::class ]);
        return view('processors/create');
    }
}