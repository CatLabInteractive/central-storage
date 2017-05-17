@extends('layouts.container')

@section('title')
    Statistics
@endsection

@section('inner-content')

    <h1>Central Storage</h1>
    <h2>Statistics</h2>
    <table class="table">
        @foreach($statistics as $k => $v)

            <tr>
                <td>{{ trans('application.statistics.' . $k) }}</td>
                <td>{{ $v }}</td>
            </tr>

        @endforeach
    </table>

@endsection