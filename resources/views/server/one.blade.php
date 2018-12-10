@extends('layouts.app')

@section('content')
    <script>
        var server_id = "{{$server->_id}}";
        var params = [];
        var script_id = "";
    </script>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{$server->name}}</h1>
    </div>
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editModal">
        {{__("Düzenle")}}
    </button>

    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#commandModal">
        {{__("Komut Çalıştır")}}
    </button>
    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#addService">
        {{__("Servisleri Düzenle")}}
    </button>
    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#changeNetwork">
        {{__("Network")}}
    </button>
    <button type="button" class="btn btn-primary" data-toggle="modal" onclick="getHostname()">
        {{__("Hostname")}}
    </button>
    @isset($scripts)
        @foreach($scripts as $script)
            <button class="btn-primary btn" onclick="generateModal('{{$script->inputs}}','{{$script->name}}','{{$script->_id}}')">{{$script->name}}</button>
        @endforeach
    @endisset
    <br><br>
    <h4>{{__("Servis Durumları")}}</h4>
        @foreach($services as $service)
            <button type="button" class="btn btn-info btn-lg" style="cursor:default;" id="status_{{$service}}">
                {{strtoupper($service)}}
            </button>
        @endforeach
    <br><br>
    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>


    @include('modal',[
                       "id"=>"changeNetwork",
                       "title" => __("Network Değiştir"),
                       "url" => "/sunucu/network",
                        "inputs" => [
                        __("İp Adresi") => "ip:text",
                        __("Cidr Adresi") => "cidr:text",
                        __("Gateway") => "gateway:text",
                        __("Interface") => "interface:text",
                        __("SSH Parolası") => "ssh:password"
                    ],
                       "submit_text" => __("Değiştir")
                   ])
    @include('modal',[
                      "id"=>"editModal",
                      "title" => __("Sunucu Düzenle"),
                      "url" => "/sunucu/calistir",
                       "inputs" => [
                       __("İp Adresi") => "ip:text",
                       __("Cidr Adresi") => "cidr:text",
                       __("Gateway") => "gateway:text",
                       __("Interface") => "interface:text",
                       __("SSH Parolası") => "ssh:password"
                   ],
                      "submit_text" => __("Değiştir")
                  ])


    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
        {{__("Sunucuyu Sil")}}
    </button>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{__("Sunucuyu Sil")}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h2><b>{{$server->name }}</b></h2>{{__("isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.")}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__("İptal")}}</button>
                    <button type="button" class="btn btn-danger" onclick="deleteServer()">{{__("Sunucuyu Sil")}}</button>
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
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" onclick="document.getElementById('run_command').disabled = !this.checked;" id="commandResponsibility">
                        <label class="form-check-label" for="defaultCheck1">
                            {{__("Özel komut çalıştırma sorumluluğunu kabul ediyorum.")}}
                        </label>
                    </div><br>
                    <div class="form-group">
                        <textarea class="form-control" id="run_command" rows="3" disabled></textarea>
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
                    <h5 class="modal-title" id="exampleModalLabel">DNS Kurulumu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <h5>Domain Adı</h5>
                        <input id="dns_domain" type="text" class="form-control"
                               placeholder="Domain Adı">
                    </div>
                    <div class="form-group">
                        <h5>Interface</h5>
                        <input id="dns_interface" type="text" class="form-control"
                               placeholder="Interface">
                    </div>
                    <div class="collapse" id="installServiceOutput">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="installService()">Kur</button>
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
                    <div class="collapse" id="customScriptOutput">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cs_cancel" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" id="cs_submit" class="btn btn-warning" onclick="runCustomScript()">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="changeHostname" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="exampleModalLabel">Hostname Değiştir</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5>Aktif hostname : </h5>
                    <h4 id="currentHostname"></h4><br>
                    <input id="new_hostname" type="text" class="form-control" placeholder="Yeni Hostname"
                           data-validation="required" data-validation-error-msg="Girilmesi Zorunlu Alan">
                </div>
                <div class="modal-footer">
                    <button type="button" id="cs_cancel" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" id="cs_submit" class="btn btn-warning" onclick="changeHostname()">Değiştir</button>
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
                    server_id : server_id
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
            if($("#commandResponsibility").is(':checked') === false){
                return;
            }
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
        
        function getHostname() {
            var command = "cat /etc/hostname";
            $.ajax({
                url : "{{ route('server_run') }}",
                type : "POST",
                data :{
                    server_id : server_id,
                    command : command
                },
                success : function (data) {
                    if(data["result"] === 200){
                        $("#currentHostname").html(data["data"]);
                    }else{
                        alert("Hata");
                    }
                }
            });
            $('#changeHostname').modal('show');
        }

        function changeHostname() {
            $.ajax({
                url : "{{ route('server_hostname') }}",
                type : "POST",
                data :{
                    server_id : server_id,
                    hostname : $("#new_hostname").val()
                },
                success : function (data) {
                    if(data["result"] === 200){
                        getHostname();
                    }else{
                        alert("Hata");
                    }
                }
            });
        }

        function generateModal(raw_inputs,title,id) {
            script_id = id;
            $("#customScriptInputs").html("");
            $('#customScriptOutput').html("");
            var inputs = raw_inputs.split(',');
            $("#customScripts .modal-title").html(title);
            if (inputs[0] !== ""){
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
            }else{
                runCustomScript();
            }
            $('#customScripts').modal('show');
        }


        function checkStatus(extension) {
            var element = $("#status_" + extension);
            $.ajax({
                    url : "{{ route('server_check') }}",
                    type : "POST",
                    data : {
                        extension : extension,
                        server_id : '{{$server->_id}}'
                    },
                    success : function (data) {
                        if(data["result"] === 200){
                            element.removeClass('btn-info').removeClass('btn-danger').addClass('btn-success');
                        }else if(data["result"] === 201){
                            element.removeClass('btn-success').removeClass('btn-info').addClass('btn-danger');
                        }else{
                            element.removeClass('btn-success').removeClass('btn-info').addClass('btn-secondary');
                        }
                    }
            });
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
                        $('#customScriptOutput').html(data["data"]).collapse();
                        $("#cs_cancel").fadeOut();
                        $("#cs_submit").fadeOut();
                        // console.log(data["data"]);
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
        


        function installService(service) {
            var dns_domain = $("#dns_domain").val();
            var interface = $("#dns_interface").val();
            $.ajax({
                url : "{{ route('server_extension') }}",
                type : "POST",
                data: {
                    server_id : server_id,
                    extension : 'dns',
                    domain : dns_domain,
                    interface : interface
                },

            },function (data,status) {
                if(data["result"] === 200){
                    location.reload();
                }else{
                    $('#installServiceOutput').html(data["data"]).collapse();
                }
            });
        }
        @foreach($server->extensions as $extension)
            setInterval(function () {
                checkStatus('{{$extension}}');
            }, 3000);
        @endforeach
    </script>
@endsection