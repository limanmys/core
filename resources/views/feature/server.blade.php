@extends('layouts.app')

@section('content')
    <h2>{{request('server')->name}} Sunucusunda <b>{{$extension->name}}</b> Yönetimi</h2>
        <div class="row">
            <div class="col-9">
                <div class="card">
                    <div class="card-body mainArea">
                            @include('extensions.' . strtolower($extension->name) . '.index')
                    </div>
                </div>
            </div>
            <div class="col-3">
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
        </div>
    <script>
        var server_id = "{{request('server')->name}}";
        var extension = "{{$extension->name}}";
        function request(url,next,...inputs) {
            var args = Array.prototype.slice.call(arguments, 0);
            var data = {
                server_id : server_id,
                extension_name : extension,
                url : url
            };
            inputs.forEach(function (input) {
                var key = input.split(':')[0];
                var data = input.split(':')[1];
                data = $.merge(data,{key : data});
            });
            console.log(data);
            return;
            $.ajax({
                url : '{{route('extension_api',$extension->name)}}',
                type : "POST",
                data :data,
                success : function (data) {
                    next(data);
                }
            });
        }
    </script>
@endsection