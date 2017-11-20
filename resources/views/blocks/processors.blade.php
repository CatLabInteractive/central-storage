@if($processors->isEmpty())
    <p>
        No processors created yet.
    </p>
@else
    <table class="table table-striped">

        <tr>
            <th>Variation name</th>
            <th>Triggers</th>
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
                    <a href="{{ action('ProcessorController@run',  [ $consumer->id, $processor->id ]) }}">Run now</a>
                </td>
            </tr>
        @endforeach

    </table>
@endif

<a href="{{ action('ProcessorController@create', [ $consumer ]) }}" class="btn btn-primary">Create processor</a>