@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{request('server')->name}} Sunucusunda <b>{{$extension->name}}</b> Yönetimi</h1>
    </div>
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
        var server_id = "{{request('server')->_id}}";
        var extension = "{{$extension->name}}";
        var history = [];
        function request(url,next,...inputs) {
            var data = {
                server_id : server_id,
                extension : extension,
                url : url
            };
            inputs.forEach(function (input) {
                var key = input.split(':')[0];
                data[key] = input.split(':')[1];
            });
            $.ajax({
                url : '{{route('extension_api',$extension->name)}}',
                type : "POST",
                data :data,
                success : function (data) {
                    next(data);
                }
            });
        }
        
        function redirect(path,...inputs) {
            var params = "";
            inputs.forEach(function (input) {
                params = params + "&" + input.split(':')[0] + "=" + input.split(':')[1];
            });
            location.href = location.href + '/' + path + params;
        }
    </script>
@endsection