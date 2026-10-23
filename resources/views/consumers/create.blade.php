@extends('layouts.container')

@section('title')
    Create consumer
@endsection

@section('inner-content')

    <form method="POST" action="{{ action('ConsumerController@create') }}" accept-charset="UTF-8" role="form" class="vertical">
        @csrf

        @include('blocks.form.input', [ 'name' => 'name', 'label' => 'Consumer name' ])
        @include('blocks.form.submit', [ 'label' => 'Create' ])

    </form>

    <a href="{{ action('ConsumerController@index') }}">Back</a>

@endsection
