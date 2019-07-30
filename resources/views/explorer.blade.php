@extends('layouts.container')

@section('title')
    Explore
@endsection

@section('inner-content')

    <h1>Central Storage</h1>
    <h2>Explorer</h2>
    <table class="table">

        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Size</th>
            <th>Date</th>
        </tr>

        @foreach($assets as $v)

            <tr>
                <td>{{ $v->id }}</td>
                <td><a href="{{ action('AssetController@viewConsumerAsset', [ $v->ca_key ] ) }}">{{ $v->name }}</a></td>
                <td>{{ App\Helpers\StatisticsHelper::formatBytes($v->asset->size) }}</td>
                <td>{{ $v->created_at->format('d/m/Y H:i') }}</td>
            </tr>

        @endforeach
    </table>

    {{ $assets->links() }}

@endsection