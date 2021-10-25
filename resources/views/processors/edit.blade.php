@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Edit Processor "{{ $processor->variation_name }}"
@endsection

@section('inner-content')

    {!! BootForm::open([ 'files' => true ]) !!}

    @foreach($processor->getConfigValidation() as $k => $v)
        @switch($processor->getConfigType($k))
            @case('file')
                @if($processor->getConfig($k))
                    <a href="{{ $processor->getConfig($k)->getUrl() }}" target="_blank">{{ $processor->getConfig($k)->name }}</a>
                @endif
                {!! BootForm::file($k, $k) !!}
                @break;

            @case('number')
                {!! BootForm::number($k, $k, $processor->getConfig($k)) !!}
            @break;

            @default
            @case('text')
                {!! BootForm::text($k, $k, $processor->getConfig($k)) !!}
                @break;
        @endswitch

    @endforeach

    {!! BootForm::submit('Save') !!}

    {!! BootForm::close() !!}

    <a href="{{ action('ProcessorController@index', [ $consumer ]) }}" class="btn btn-default">Back</a>

@endsection
