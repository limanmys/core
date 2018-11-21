@extends('layouts.app')

@section('content')
    <h2>{{$server->name}} Sunucusunda <b>{{$feature->name}}</b> Yönetimi</h2>
    <div class="card-group">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Card title</h5>
                <p class="card-text">{{$output}}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Önerilen Betikler</h5>
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
@endsection