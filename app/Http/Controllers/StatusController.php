<?php

namespace App\Http\Controllers;

use App\Models\Asset;

class StatusController extends Controller
{
    public function status()
    {
        // Check db connection
        $file = Asset::first();

        return response()->json(['status' => 'ok']);
    }
}