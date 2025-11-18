<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProcessorRequest;
use App\Http\Requests\EditProcessorRequest;
use App\Models\Consumer;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorTrigger;
use Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProcessorController
 * @package App\Http\Controllers
 */
class ProcessorController extends Controller
{
    /**
     * @param Consumer $consumer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Consumer $consumer)
    {
        $this->authorize('index', [ $consumer, Processor::class ]);
        $processors = $consumer->processors;

        // Sort on processor name + variation name
        usort($processors, function($a, $b) {
            return strcmp($a->processor . $a->variation_name, $b->processor . $b->variation_name);
        });

        return view(
            'processors/index',
            [
                'consumer' => $consumer,
                'processors' => $processors,
            ]
        );
    }

    /**
     * @param Consumer $consumer
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Consumer $consumer)
    {
        $this->authorize('create', [ $consumer, Processor::class ]);

        $processors = [];
        foreach (Processor::getProcessors() as $v) {
            $processors[class_basename($v)] = class_basename($v);
        }

        return view(
            'processors/create',
            [
                'consumer' => $consumer,
                'processors' => $processors
            ]
        );
    }

    /**
     * @param Consumer $consumer
     * @param CreateProcessorRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function processCreate(Consumer $consumer, CreateProcessorRequest $request)
    {
        $this->authorize('create', [ $consumer, Processor::class ]);

        $processor = new Processor([
            'processor' => $request->input('processor'),
            'variation_name' => $request->input('variation_name'),
            'default_variation'  => $request->input('default_variation') ? true : false
        ]);

        $consumer->processors()->save($processor);

        // also add the trigger
        $processorTrigger = new ProcessorTrigger([
            'mimetype' => $request->input('trigger_mimetype')
        ]);

        $processor->triggers()->save($processorTrigger);

        return redirect(action('ProcessorController@edit', [ $consumer->id, $processor->id ]));
    }

    public function delete(Consumer $consumer, Processor $processor)
    {
        return view(
            'processors/delete',
            [
                'consumer' => $consumer,
                'processor' => $processor
            ]
        );
    }

    public function processDelete(Consumer $consumer, Processor $processor)
    {
        $this->authorize('delete', [ $processor ]);

        // delete associated triggers
        foreach ($processor->triggers as $trigger) {
            $trigger->delete();
        }

        // delete the processor
        $processor->delete();

        return redirect(action('ProcessorController@index', [ $consumer->id ]));
    }

    /**
     * @param Consumer $consumer
     * @param Processor $processor
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Consumer $consumer, Processor $processor)
    {
        $this->authorize('edit', [ $processor ]);

        return view(
            'processors/edit',
            [
                'consumer' => $processor->consumer,
                'processor' => $processor
            ]
        );
    }

    /**
     * @param Consumer $consumer
     * @param Processor $processor
     * @param EditProcessorRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function processEdit(Consumer $consumer, Processor $processor, EditProcessorRequest $request)
    {
        $this->authorize('edit', [ $processor ]);

        // save configuration
        $processor->saveConfig($request->getProcessorConfigFields());

        // redirect back to the index
        return redirect(action('ProcessorController@index', [ $processor->consumer->id ]));
    }

    /**
     * @param Consumer $consumer
     * @param Processor $processor
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function run(Consumer $consumer, Processor $processor)
    {
        $this->authorize('run', [ $processor ]);
    }

    /**
     * @param Consumer $consumer
     * @param Processor $processor
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function setDefault(Consumer $consumer, Processor $processor)
    {
        $this->authorize('edit', [ $processor ]);
        $default = !!\Request::input('default');

        $processor->default_variation = $default;
        $processor->save();

        return redirect(action('ProcessorController@index', [ $processor->consumer->id ]));
    }

    /**
     * Called by external processors.
     * @param \Illuminate\Http\Request $request
     * @param string $processorName
     * @return \Illuminate\Http\JsonResponse
     */
    public function notification($processorName, \Illuminate\Http\Request $request)
    {
        \Log::info('Incoming notification: ' . "\n" . print_r($request->input(), true));

        $processor = Processor::getFromClassName($processorName);

        $response = $processor->notify($request);
        if ($response instanceof Response) {
            return $response;
        }

        return \Response::json([ 'success' => true ]);
    }
}
