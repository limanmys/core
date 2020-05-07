@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Modüller")}}</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Modüller') }}</h3>
        </div>
        <div class="card-body">
            @include('errors')
            @include('table',[
                "value" => $modules,
                "title" => [
                    "Adı" , "Durumu", "*hidden*", "*hidden*"
                ],
                "display" => [
                    "name" , "enabled_text", "id:module_id", "enabled:enabled"
                ],
            ])
        </div>
    </div>
@endsection