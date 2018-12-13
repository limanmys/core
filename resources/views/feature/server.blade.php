@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{request('server')->name}} Sunucusunda <b>{{$extension->name}}</b> Yönetimi</h1>
    </div>
    <button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button>
        <div class="row">
            <div class="col-9">
                <div class="card">
                    <div class="card-body mainArea">
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