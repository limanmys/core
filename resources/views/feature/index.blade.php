@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2 class="sehir_adi">{{__("Başlamak için lütfen bir şehir seçin.")}}</h2>
    </div>

    @include('general.harita')
@endsection