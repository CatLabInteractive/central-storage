@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Create Processor
@endsection

@section('inner-content')

    <form method="POST" action="{{ action('ProcessorController@create', [ $consumer ]) }}" accept-charset="UTF-8" role="form" class="vertical">
        @csrf

        @include('blocks.form.select', [ 'name' => 'processor', 'label' => 'Processor', 'options' => $processors ])

        @include('blocks.form.input', [ 'name' => 'variation_name', 'label' => 'Target variation name' ])
        <div class="alert alert-info" role="alert">
            Variation name must be unique.
        </div>

        @include('blocks.form.checkbox', [ 'name' => 'default_variation', 'label' => 'Make this the default variation' ])
        <div class="alert alert-info" role="alert">
            Any call to assets matching the mimetype filter will load the default variation when called without
            variation parameter.
        </div>

        @include('blocks.form.input', [ 'name' => 'trigger_mimetype', 'label' => 'Mimetype trigger' ])

        @include('blocks.form.submit', [ 'label' => 'Create' ])

    </form>

    <a href="{{ action('ProcessorController@index', [ $consumer ]) }}" class="btn btn-default">Back</a>

@endsection
