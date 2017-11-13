@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Edit Processor "{{ $processor->variation_name }}"
@endsection

@section('inner-content')

    {!! BootForm::open() !!}

    @foreach($processor->getConfigValidation() as $k => $v)
        {!! BootForm::text($k, $k, $processor->getConfig($k)) !!}
    @endforeach

    {!! BootForm::submit('Save') !!}

    {!! BootForm::close() !!}

    <a href="{{ action('ProcessorController@index', [ $consumer ]) }}" class="btn btn-default">Back</a>

@endsection
