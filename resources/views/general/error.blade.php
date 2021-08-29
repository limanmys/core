@extends('layouts.app')

@section('content_header')
    <div class="container-fluid">
        <div class="error-page mt-5">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle text-warning"></i></h2>
            <div class="error-content">
                <h3>Uyarı</h3>
                <p>
                    {{__($message)}}
                    <br><button class="btn btn-success mt-3" onclick="history.back()">{{__("Geri Dön")}}</button>
                </p>
            </div>
        </div>
    </div>
@stop

@section('content')

    <!-- -->

@endsection