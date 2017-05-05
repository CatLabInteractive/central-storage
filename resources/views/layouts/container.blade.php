@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-md-offset-0">
                <div class="panel panel-default">
                    <div class="panel-heading">@yield('title')</div>

                    <div class="panel-body">

                        @yield('inner-content')

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection