@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Delete Processor
@endsection

@section('inner-content')

    <form method="POST" action="{{ action('ProcessorController@delete', [ $consumer, $processor ]) }}" accept-charset="UTF-8" role="form" class="vertical">
        @csrf

        <div class="alert alert-danger" role="alert">
            Are you sure you want to delete the processor variation <strong>{{ $processor->name }}</strong>?
            This will remove all associated data, but not the resulting variations.
        </div>

        @include('blocks.form.submit', [ 'label' => 'Delete Processor Variation' ])
        <a href="{{ action('ProcessorController@edit', [ $consumer, $processor ]) }}" class="btn btn-default">Cancel</a>

    </form>

@endsection
