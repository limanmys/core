@extends('layouts.app')

@section('content_header')
    <h1>{{request('server')->name}} Sunucusunda <b>{{$extension->name}}</b> Yönetimi</h1>
@stop

@section('content')

    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
        <div class="row">
            <div class="col-9">
                <div class="card">
                    <div class="card-body mainArea">
                        @if(is_file(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php')))
                            <?php require(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php')); ?>
                        @endif
                            @include('extensions.' . strtolower($extension->name) . '.' . $view)
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{__("Önerilen Betikler")}}</h5>
                        <table class="table">
                            <tbody>
                            @foreach ($scripts as $script)
                                <tr class="highlight" onclick="window.location.href = '{{route('script_one',$script->_id)}}'">
                                    <td>{{$script->name}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
@endsection