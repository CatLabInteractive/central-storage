@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Create Processor
@endsection

@section('inner-content')

    {!! BootForm::open() !!}

    {!! BootForm::select('processor', 'Processor', $processors) !!}

    {!! BootForm::text('variation_name', 'Target variation name') !!}
    <div class="alert alert-info" role="alert">
        Variation name must be unique.
    </div>

    {!! BootForm::checkbox('default_variation', 'Make this the default variation') !!}
    <div class="alert alert-info" role="alert">
        Any call to assets matching the mimetype filter will load the default variation when called without
        variation parameter.
    </div>

    {!! BootForm::text('trigger_mimetype', 'Mimetype trigger') !!}

    {!! BootForm::submit('Create') !!}

    {!! BootForm::close() !!}

    <a href="{{ action('ProcessorController@index', [ $consumer ]) }}" class="btn btn-default">Back</a>

@endsection
