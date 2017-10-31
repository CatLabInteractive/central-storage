@extends('layouts.container')

@section('title')
    Consumer upload test
@endsection

@section('inner-content')

    <form action="{{ action('ConsumerController@test', [ $consumer->id ]) }}" method="post" enctype="multipart/form-data">
        {{ csrf_field() }}

        Select image to upload:
        <input type="file" name="file" id="file"><br>
        <button type="submit" name="submit">Upload file</button>
    </form>

@endsection
