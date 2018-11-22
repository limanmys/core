@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>{{$name}} Yönetimi</h2>
        <h5>Başlamak için lütfen bir şehir seçin.</h5>
    </div>

    @include('general.harita')
@endsection