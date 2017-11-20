@extends('layouts.container')

@section('title')
    {{ $consumer->name }}: Processors
@endsection

@section('inner-content')

    @include('blocks.processors')

@endsection
