<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateConsumerRequest;
use App\Models\Consumer;
use Auth;

/**
 * Class ConsumerController
 * @package App\Http\Controllers
 */
class ConsumerController extends Controller
{
    /**
     * Consumer index page
     */
    public function index()
    {
        $this->authorize('index', Consumer::class);

        $user = Auth::getUser();

        if ($user->isAdmin()) {
            $consumers = Consumer::all();
        } else {
            $consumers = $user->consumers;
        }

        // Block if consumer cannot be viewed.
        foreach ($consumers as $consumer) {
            $this->authorize('view', $consumer);
        }

        return view('consumers/index', [ 'consumers' => $consumers ]);
    }

    /**
     * @param Consumer $consumer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view(Consumer $consumer)
    {
        $this->authorize('view', $consumer);
        return view('consumers/view', [ 'consumer' => $consumer ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('create', Consumer::class);
        return view('consumers/create');
    }

    /**
     * @param CreateConsumerRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processCreate(CreateConsumerRequest $request)
    {
        $this->authorize('create', Consumer::class);

        $user = Auth::getUser();

        $name = $request->input('name');

        $consumer = Consumer::create($name);
        $consumer->user()->associate($user);
        $consumer->save();

        return redirect()->action('ConsumerController@index');
    }
}