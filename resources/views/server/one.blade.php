@extends('layouts.app')

@section('content')
    <script>
        var server_id = "{{$server->_id}}";
        var params = [];
        var script_id = "";
    </script>
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">Geri Don</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editModal">
        Düzenle
    </button>

    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#commandModal">
        Komut Çalıştır
    </button>
    @isset($scripts)
        @foreach($scripts as $script)
            <button class="btn-primary btn" onclick="generateModal('{{$script->inputs}}','{{$script->name}}','{{$script->_id}}')">{{$script->name}}</button>
        @endforeach
    @endisset
    <br><br>
    <button type="button" class="btn btn-secondary btn-lg" data-toggle="modal" data-target="#addService">
        Servisleri Düzenle
    </button>
    <br><br>
    <h3>{{$server->name}}</h3><br>

    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>

    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
        Sunucu Sil
    </button>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Sunucu Sil</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h2><b>{{$server->name }}</b></h2> isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" onclick="deleteServer()">Sunucu Sil</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Komut Çalıştır</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <textarea class="form-control" id="run_command" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" id="commandOutput" rows="3" readonly></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="runCommand()">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addService" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Servisleri Düzenle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">Adı</th>
                            <th scope="col">Durumu</th>
                        </tr>
                        </thead>
                        <tbody>
                    @foreach($features as $feature)
                            <tr>
                                <th scope="row">{{$loop->index + 1}}</th>
                                <td>{{$feature->name}}</td>
                                <td>
                                    @if($server_features->where('_id',$feature->_id)->count() > 0)
                                        <button type="button" class="btn btn-danger">Devre Dışı Bırak</button>
                                    @else
                                        <button type="button" class="btn btn-success">Servisi Ekle</button>
                                    @endif
                                </td>
                            </tr>
                    @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="runCommand()">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customScripts" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="exampleModalLabel">Servisleri Düzenle</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group" id="customScriptInputs">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="runCustomScript()">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $("#commandOutput").fadeOut();
        function deleteServer() {
            $.ajax({
                url : "{{ route('server_remove') }}",
                type : "POST",
                data :{
                    id : server_id
                },
                success : function (data) {
                    if(data["result"] === 200){
                        window.location.replace("{{route('servers')}}");
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
        function runCommand() {
            var command = $("#run_command").val();
            $.ajax({
                url : "{{ route('server_run') }}",
                type : "POST",
                data :{
                    server_id : server_id,
                    command : command
                },
                success : function (data) {
                    if(data["result"] === 200){
                        $("#commandOutput").fadeIn().html(data["data"]);
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
        
        function generateModal(raw_inputs,title,id) {
            script_id = id;
            $("#customScriptInputs").html("");
            var inputs = raw_inputs.split(',');
            $("#customScripts .modal-title").html(title);
            inputs.forEach(function (input) {
                var current = input.split(':');
                var newInput = document.createElement("input");
                switch (current[1]) {
                    case "number":
                    case "ip_address":
                        newInput.type="number";
                        break;
                    case "string":
                    default:
                        newInput.type="text";
                        break;
                }
                newInput.name = "custom_" + current[0];
                params.push(current[0]);
                $(newInput).addClass("form-control");
                $("#customScriptInputs").append("<h3>" + current[0] +"</h3>");
                document.getElementById('customScriptInputs').appendChild(newInput);
            });
            $('#customScripts').modal('show');
        }
        
        function runCustomScript() {
            //setup params
            var data = {};
            params.forEach(function (param) {
                data[param] = $("input[name='custom_" + param + "']").val();
            });
            data["server_id"] = server_id;
            data["script_id"] = script_id;
            $.ajax({
                url : "{{ route('script_run') }}",
                type : "POST",
                data :data,
                success : function (data) {
                    if(data["result"] === 200){
                        console.log(data["data"]);
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
    </script>
@endsection