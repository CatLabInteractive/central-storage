@extends('layouts.container')

@section('title')
    Consumer upload test
@endsection

@section('inner-content')

    <pre>{{ print_r($asset->getAttributes(), true) }}</pre>

@endsection
