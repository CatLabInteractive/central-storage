@extends('layouts.container')

@section('title')
    Consumers
@endsection

@section('inner-content')

    @if($consumers->isEmpty())
        <p>
            No consumers created yet.
        </p>
    @else
        <table class="table table-striped">

            <tr>
                <th>Name</th>
                <th>Owner</th>
                <th>Key</th>
                <th>Actions</th>
            </tr>

            @foreach($consumers as $consumer)
                <tr>
                    <td>
                        <a href="{{ action('ConsumerController@view', [ $consumer->id ]) }}">{{ $consumer->name }}</a>
                    </td>

                    <td>{{ $consumer->user->name }}</td>
                    <td>{{ $consumer->key }}</td>
                    <td>
                        <a href="{{ action('ExplorerController@explore', [ $consumer->id ]) }}">Explore</a> |
                        <a href="{{ action('ConsumerController@test', [ $consumer->id ]) }}">Test</a>
                    </td>
                </tr>
            @endforeach

        </table>
    @endif

    <a href="{{ action('ConsumerController@create') }}" class="btn btn-primary">Create consumer</a>

@endsection
