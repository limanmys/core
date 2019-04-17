@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item"><a href="/l/{{extension()->_id}}">{{extension()->name}} {{ __('Sunucuları') }}</a>
        </li>
        <li class="breadcrumb-item"><a
                    href="/l/{{extension()->_id}}/{{request('city')}}">{{cities(request('city'))}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{server()->name}}</li>
    </ol>
    <button class="btn btn-primary" onclick="location.href = '{{route('server_one',server()->_id)}}'">{{__("Sunucu Detayları")}}</button>
    <div class="card">
        <div class="card-body mainArea">
            @if(is_file(base_path('resources/views/extensions/' . strtolower(extension()->name) . '/functions.php')))
                <?php require(base_path('resources/views/extensions/' . strtolower(extension()->name) . '/functions.php')); ?>
            @endif
            @include('extensions.' . strtolower(extension()->name) . '.' . $view)
        </div>
    </div>
@endsection