@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <pre>
        {{$stats}}
    </pre>

    <canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>

@endsection