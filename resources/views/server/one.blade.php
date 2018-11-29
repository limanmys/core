@extends('layouts.app')

@section('content')
    <style>
        .service_change{
            display: none;
        }
    </style>
    <script>
        var server_id = "{{$server->_id}}";
        var params = [];
        var script_id = "";
    </script>

    <link href="js/form-validator/theme-default.min.css" rel="stylesheet" type="text/css"/>

    <script src="../js/form-validator/jquery.form-validator.min.js"></script>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{$server->name}}</h1>
    </div>
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">Geri Don</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editModal">
        Düzenle
    </button>

    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#commandModal">
        Komut Çalıştır
    </button>
    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#addService">
        Servisleri Düzenle
    </button>
    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#changeNetwork">
        Network
    </button>
    <button type="button" class="btn btn-primary" data-toggle="modal" onclick="getHostname()">
        Hostname
    </button>
    @isset($scripts)
        @foreach($scripts as $script)
            <button class="btn-primary btn" onclick="generateModal('{{$script->inputs}}','{{$script->name}}','{{$script->_id}}')">{{$script->name}}</button>
        @endforeach
    @endisset
    <br><br>
    <h4>Servis Durumları</h4>
        @foreach($services as $service)
            <button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#status"style="cursor:default;" id="status_{{$service}}">
                {{strtoupper($service)}}
            </button>
        @endforeach
    <br><br>
    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>

    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
        Sunucuyu Sil
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
    <div class="modal fade" id="status" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Servis Durumunu Seç</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                                <ul><button type="button"  class="btn btn-warning" data-dismiss="modal" onclick="serverDisabled(this.id)" id={{$service}}>Servis Devre Dışı Bırak</button></ul>
                                <ul><button type="button" class="btn btn-primary" data-dismiss="modal" onclick="serverRun(this.id)" id={{$service}}>Servisi Çalıştır</button></ul>
                        <ul><button type="button" class="btn btn-success" data-dismiss="modal" onclick="serverStop(this.id)" id={{$service}}>Servisi Durdur</button></ul>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
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
                            Özel komut çalıştırma sorumluluğunu kabul ediyorum.
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
                    <h5 class="modal-title" id="exampleModalLabel">Servisleri Düzenle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <select id="inputType">
                        @foreach($extensions as $extension)
                        <option value={{$loop->index + 1}}>{{$extension->name}}</option>
                        @endforeach
                    </select>
                    <div class="service_change">
                    <div class="pr-service d1">
                        <label for="hostname"><b>DNS Hostname</b></label>
                        <input type="text" placeholder="Input Hostname For DNS option" name="dns" required><br/>
                        <label for="deneme"><b>Deneme</b></label>
                        <input type="text" placeholder="Input Deneme For DNS option" name="dns" required>
                    </div>
                    <div class="pr-service d2">
                        <label for="hostname"><b>DHCP Hostname:</b></label>
                        <input type="text" placeholder="Input For DHCP option" name="dns" required><br/>
                        <label for="deneme"><b>Deneme</b></label>
                        <input type="text" placeholder="Input Deneme For DHCP option" name="dns" required>
                    </div>
                    <div class="pr-service d3">
                        <label for="hostname"><b>Hostname</b></label>
                        <input type="text" placeholder="Input For Kullanıcılar option" name="kullanıcılar" required><br/>
                        <label for="deneme"><b>Deneme</b></label>
                        <input type="text" placeholder="Input Deneme For Kullanıcılar option" name="dns" required>
                    </div>
                    <div class="pr-service d4">
                        <label for="hostname"><b>Hostname</b></label>
                        <input type="text" placeholder="Input For Gruplar option" name="gruplar" required><br/>
                        <label for="deneme"><b>Deneme</b></label>
                        <input type="text" placeholder="Input Deneme For Gruplar option" name="dns" required>
                    </div>
                    <div class="pr-service d5">
                        <label for="hostname"><b>Hostname</b></label>
                        <input type="text" placeholder="Input For Bilgisayarlar option" name="bilgisayarlar" required><br/>
                        <label for="deneme"><b>Deneme</b></label>
                        <input type="text" placeholder="Input Deneme For Bilgisayarlar option" name="dns" required>
                    </div>
                    </div>
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
    <div class="modal fade" id="changeNetwork" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="exampleModalLabel">Hostname Değiştir</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <h3>İp Adresi</h3>
                        <input id="new_ip" type="text" class="form-control"
                               placeholder="İp Adresi">
                    </div>
                    <div class="form-group">
                        <h3>Cidr Adresi</h3>
                        <input id="new_cidr" type="text" class="form-control"
                               placeholder="Cidr Adresi">
                    </div>
                    <div class="form-group">
                        <h3>Gateway</h3>
                        <input id="new_gateway" type="text" class="form-control"
                               placeholder="Gateway">
                    </div>
                    <div class="form-group">
                        <h3>Interface</h3>
                        <input id="new_interface" type="text" class="form-control"
                               placeholder="Interface">
                    </div>
                    <div class="form-group">
                        <h3>SSH Parolası</h3>
                        <input id="new_password" type="text" class="form-control"
                               placeholder="SSH Parolası">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cs_cancel" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" id="cs_submit" class="btn btn-warning" onclick="changeNetwork()">Değiştir</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Sunucu Düzenle</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <h3>Adı</h3>
                        <input id="add_name" type="text" class="form-control" placeholder="Sunucu kısa adı" data-validation="required" data-validation-error-msg="Girilmesi Zorunlu Alan">
                    </div>
                    <div class="form-group">
                        <h3>İp Adresi</h3>
                        <input id="add_ip" type="text" class="form-control" placeholder="Sunucu Ipv4 Adresi" data-validation-help="Örnek Ip:192.168.56.10" data-validation="custom"  data-validation-regexp="^(?=.*[^\.]$)((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.?){4}$" data-validation-error-msg="Geçerli Ip Adress Girin." >
                    </div>
                    <div class="form-group">
                        <h3>Bağlantı Portu</h3>
                        <input id="add_port" type="text" class="form-control" placeholder="Bağlantı Portu" value="22">
                    </div>
                    <div class="form-group">
                        <h3>Şehirler</h3>
                        @include("server.cities")
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-success" onclick="edit()">Düzenle</button>
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


        function checkStatus(feature) {
            var element = $("#status_" + feature);
            $.ajax({
                    url : "{{ route('server_check') }}",
                    type : "POST",
                    data : {
                        feature : feature,
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
        
        function changeNetwork() {
            //ip,cidr,gateway,interface,password
            $.ajax({
                url : "{{ route('server_network') }}",
                type : "POST",
                data :{
                    server_id : server_id,
                    ip : $("#new_ip").val(),
                    cidr : $("#new_cidr").val(),
                    gateway : $("#new_gateway").val(),
                    interface : $("#new_interface").val(),
                    password : $("#new_password").val(),
                },
                success : function (data) {
                    if(data["result"] === 200){
                        location.reload();
                    }else{
                        alert("Hata");
                    }
                }
            });
        }
        function edit(){
            var name = $("#add_name").val();
            var ip = $("#add_ip").val();
            var port = $("#add_port").val();
            var city_value = $("#city").val();
            $.ajax({
                url : "{{ route('server_run') }}",
                type : "POST",
                data: {
                    name:name,
                    ip:ip,
                    port:port,
                    city:city_value
                },

            },function (data,status) {
                if(data["result"] === 200){
                    // window.location.replace("{{route('servers')}}" + "/" + data["id"]);
                }else{
                    alert("Hata!");
                }
            });
        }
        function serverDisabled(eventId){

            console.log(eventId);
            $.ajax({
                url : "{{ route('server_service') }}",
                type : "POST",
                data: {
                    extensions:eventId,
                    action:"disable",
                    server_id:server_id

                },

            },function (data,status) {
                if(data["result"] === 200){
                    console.log("geldim");
                    // window.location.replace("{{route('servers')}}" + "/" + data["id"]);
                }else{
                    alert("Hata!");
                }
            });
        }
        function serverRun(eventId){
            console.log(eventId);
            $.ajax({
                url : "{{ route('server_service') }}",
                type : "POST",
                data: {
                    extensions:eventId,
                    action:"start",
                    server_id:server_id
                },

            },function (data,status) {
                if(data["result"] === 200){
                    console.log("dsa");
                    // window.location.replace("{{route('servers')}}" + "/" + data["id"]);
                }else{
                    alert("Hata!");
                }
            });
        }
        function serverStop(eventId){

            $.ajax({
                url : "{{ route('server_service') }}",
                type : "POST",
                data: {
                    extensions:eventId,
                    action:"stop",
                    server_id:server_id
                },

            },function (data,status) {
                if(data["result"] === 200){
                    // window.location.replace("{{route('servers')}}" + "/" + data["id"]);
                }else{
                    alert("Hata!");
                }
            });
        }
        $('#inputType').on('change', function() {
            $('.pr-service').hide();
            $('.service_change').show();
                $('.d'+$(this).val()).show();

        });
        @foreach($server->extensions as $feature)
            setInterval(function () {
                checkStatus('{{$feature}}');
            }, 5000);
        @endforeach
    </script>
@endsection