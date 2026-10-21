@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Edit Processor "{{ $processor->variation_name }}"
@endsection

@section('inner-content')

    <form method="POST" action="{{ action('ProcessorController@edit', [ $consumer, $processor ]) }}" accept-charset="UTF-8" role="form" class="vertical" enctype="multipart/form-data">
        @csrf

        @foreach($processor->getConfigValidation() as $k => $v)
            @switch($processor->getConfigType($k))
                @case('file')
                    @if($processor->getConfig($k))
                        <a href="{{ $processor->getConfig($k)->getUrl() }}" target="_blank">{{ $processor->getConfig($k)->name }}</a>
                    @endif
                    @include('blocks.form.file', [ 'name' => $k, 'label' => $k ])
                    @break

                @case('number')
                    @include('blocks.form.input', [ 'type' => 'number', 'name' => $k, 'label' => $k, 'value' => $processor->getConfig($k) ])
                    @break

                @default
                @case('text')
                    @include('blocks.form.input', [ 'type' => 'text', 'name' => $k, 'label' => $k, 'value' => $processor->getConfig($k) ])
                    @break
            @endswitch

        @endforeach

        @include('blocks.form.submit', [ 'label' => 'Save' ])

    </form>

    <a href="{{ action('ProcessorController@index', [ $consumer ]) }}" class="btn btn-default">Back</a>

@endsection
