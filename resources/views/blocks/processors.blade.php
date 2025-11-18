@if($processors->isEmpty())
    <p>
        No processors created yet.
    </p>
@else
    <table class="table table-striped">

        <tr>
            <th>Variation name</th>
            <th>Triggers</th>
            <th>Default?</th>
            <th>Pending jobs</th>
            <td>&nbsp;</td>
        </tr>

        @foreach($processors as $processor)
            <tr>
                <td>
                    <a href="{{ action('ProcessorController@edit', [ $consumer->id, $processor->id ]) }}">{{ $processor->getName() }}</a>
                </td>

                <td>
                    <pre>{{ $processor->triggers->pluck('mimetype')->implode(', ') }}</pre>
                </td>

                <td>
                    @if($processor->default_variation)
                        default
                    @endif
                </td>

                <td>
                    {{$processor->countPendingJobs()}}
                </td>

                <td>
                    @if($processor->default_variation)
                        <a href="{{ action('ProcessorController@setDefault',  [ $consumer->id, $processor->id, 'default' => 0 ]) }}">Make not default</a>
                    @else
                        <a href="{{ action('ProcessorController@setDefault',  [ $consumer->id, $processor->id, 'default' => 1 ]) }}">Make default</a>
                    @endif

                    | <a href="{{ action('ProcessorController@delete',  [ $consumer->id, $processor->id ]) }}">Delete</a>
                </td>
            </tr>
        @endforeach

    </table>
@endif

<a href="{{ action('ProcessorController@create', [ $consumer ]) }}" class="btn btn-primary">Create processor</a>