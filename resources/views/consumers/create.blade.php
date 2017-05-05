@extends('layouts.container')

@section('title')
    Create consumer
@endsection

@section('inner-content')

    {!! BootForm::open() !!}

    {!! BootForm::text('name', 'Consumer name') !!}
    {!! BootForm::submit('Create') !!}

    {!! BootForm::close() !!}

    <a href="{{ action('ConsumerController@index') }}">Back</a>

@endsection
