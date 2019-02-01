@extends('layouts.app')

@section('content_header')
    <h1>{{request('server')->name}} Sunucusunda <b>{{$extension->name}}</b> Yönetimi</h1>
@stop

@section('content')

    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>

    <div class="card">
                    <div class="card-body mainArea">
                        @if(is_file(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php')))
                            <?php require(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php')); ?>
                        @endif
                        <br>
                            @include('extensions.' . strtolower($extension->name) . '.' . $view)
                    </div>
                </div>
@endsection