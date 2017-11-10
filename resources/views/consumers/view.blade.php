@extends('layouts.container')

@section('title')
    Consumer: {{ $consumer->name }}
@endsection

@section('inner-content')

    <h2>Details</h2>
    <table class="table">
        <tr>
            <td>Name</td>
            <td>{{ $consumer->name }}</td>
        </tr>

        <tr>
            <td>Key</td>
            <td>{{ $consumer->key }}</td>
        </tr>

        <tr>
            <td>Secret</td>
            <td>{{ $consumer->secret }}</td>
        </tr>
    </table>

    <h2>Processors</h2>
    <a href="{{ action('ConsumerController@index') }}">Back</a>

    <h2>Statistics</h2>
    <table class="table">
    @foreach($consumer->getStatistics() as $k => $v)

        <tr>
            <td>{{ trans('application.statistics.' . $k) }}</td>
            <td>{{ $v }}</td>
        </tr>

    @endforeach
    </table>

    <a href="{{ action('ConsumerController@index') }}">Back</a>

@endsection