@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Delete Processor
@endsection

@section('inner-content')

    {!! BootForm::open() !!}

    <div class="alert alert-danger" role="alert">
        Are you sure you want to delete the processor variation <strong>{{ $processor->name }}</strong>?
        This will remove all associated data, but not the resulting variations.
    </div>

    {!! BootForm::submit('Delete Processor Variation') !!}
    <a href="{{ action('ProcessorController@edit', [ $consumer, $processor ]) }}" class="btn btn-default">Cancel</a>

    {!! BootForm::close() !!}

@endsection
