<?php

namespace App\Http\Controllers;

use Auth;

use Illuminate\Http\Request;
use App\Http\Requests\CreateConsumerRequest;
use App\Models\Consumer;
use CatLab\CentralStorage\Client\CentralStorageClient;
use CatLab\CentralStorage\Client\Exceptions\StorageServerException;

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function test(Consumer $consumer)
    {
        $this->authorize('view', $consumer);
        return view('consumers/test', [ 'consumer' => $consumer ]);
    }

    /**
     * @param Consumer $consumer
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function uploadTest(Consumer $consumer, Request $request)
    {
        $this->authorize('view', $consumer);

        $client = new CentralStorageClient(
            url('/'),
            $consumer->key,
            $consumer->secret
        );

        $file = $request->file('file');

        try {
            $asset = $client->store($file);
            return view('consumers/uploadTest', [ 'asset' => $asset ]);

        } catch (StorageServerException $e) {
            echo '<h1>ERROR</h1>';
            echo '<h2>' . $e->getMessage() . '</h2>';
            echo $e->getResponse();
        }
    }

    /**
     * @param Consumer $consumer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function view(Consumer $consumer)
    {
        $this->authorize('view', $consumer);
        return view('consumers/view', [ 'consumer' => $consumer ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Consumer::class);
        return view('consumers/create');
    }

    /**
     * @param CreateConsumerRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
