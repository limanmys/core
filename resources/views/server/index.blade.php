@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sunucular")}}</li>
        </ol>
    </nav>
    <button href="#tab_1" type="button" class="btn btn-success" data-toggle="modal" data-target="#add_server">{{__("Sunucu Ekle")}}</button><br><br>
    @include('l.errors')
    <?php
    use Illuminate\Support\Facades\DB;
    $servers = servers();
    foreach ($servers as $server){
        $server->extension_count = DB::table('server_extensions')->where('server_id',$server->id)->count();
    }
    ?>
    @include('l.table',[
        "value" => $servers,
        "title" => [
            "Sunucu Adı" , "İp Adresi" , "*hidden*" , "Kontrol Portu", "Eklenti Sayısı", "*hidden*" ,"*hidden*"
        ],
        "display" => [
            "name" , "ip_address", "type:type" , "control_port", "extension_count", "city:city", "id:server_id"
        ],
        "menu" => [
            "Düzenle" => [
                "target" => "edit",
                "icon" => "fa-edit"
            ],
            "Sil" => [
                "target" => "delete",
                "icon" => "fa-trash"
            ]
        ],
        "onclick" => "details"
    ])
    <div id="add_server" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h2 class="modal-title">{{__("Sunucu Ekle")}}</h2>

                </div>
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a id="networkTab" href="#network" data-toggle="tab" aria-expanded="true">{{__("Bağlantı Bilgileri")}}</a></li>
                        <li ><a id="generalTab" href="#general" data-toggle="tab" aria-expanded="false">{{__("Genel Ayarlar")}}</a></li>
                        <li ><a id="keyTab" href="#key" data-toggle="tab" aria-expanded="false">{{__("Anahtar")}}</a></li>
                        <li ><a id="summaryTab" onclick="setSummary()" href="#summary" data-toggle="tab" aria-expanded="false">{{__("Özet")}}</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="network">
                            <form onsubmit="return checkAccess(this)">
                                <div class="modal-body" style="margin-top: -20px">
                                    <h4>{{__("Sunucunuzun Adresi")}}</h4>
                                    <input type="text" id="serverHostName" name="hostname" class="form-control" placeholder="{{__("Sunucunuzun Hostname yada IP Adresini girin.")}}" required><br>
                                    <h4>{{__("Sunucunuzun Portu")}}</h4>
                                    <h6>{{__("Sunucunuzun açık olup olmadığını algılamak için kontrol edilebilecek bir port girin.")}}</h6>
                                    <pre>{{__("SSH : 22\nWinRM : 5986\nActive Directory, Samba : 636")}}</pre>
                                    <input id="serverControlPort" type="number" name="port" class="form-control" placeholder="{{__("Kontrol Portu Girin (Yalnızca Sayı).")}}" required min="1">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">{{__("Bağlantıyı Kontrol Et")}}</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane" id="general">
                            <form onsubmit="return checkGeneral(this)">
                                <div class="modal-body" style="margin-top: -20px">
                                    <h4>{{__("Sunucunuzun Adı")}}</h4>
                                    <input id="server_name" type="text" name="server_name" class="form-control" placeholder="{{__("Sunucunuzun Adı")}}" required><br>
                                    <h4>{{__("Şehir")}}</h4>
                                    <small>{{__("Sunucunuza bir şehir atayarak, eklentileri kullanırken Türkiye haritası üzerinde erişiminizi kolaylaştırabilirsiniz.")}}</small><br>
                                    <select name="server_city" id="serverCity" class="form-control" required>
                                        <option value="">{{__('Şehir Seçiniz')}}</option>
                                        @foreach(cities() as $name=>$code)
                                            <option value="{{$code}}">{{$name}}</option>
                                        @endforeach
                                    </select><br>
                                    <h4>{{__("Sunucunuzun İşletim Sistemi")}}</h4>
                                    <div class="form-group">
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="operating_system" value="windows" data-content="{{__("Microsoft Windows")}}">
                                                {{__("Microsoft Windows")}}
                                            </label>
                                            <label>
                                                <input type="radio" name="operating_system" value="linux" checked data-content="{{__("GNU/Linux")}}">
                                                {{__("GNU/Linux")}}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">{{__("Ayarları Onayla")}}</button>
                                </div>
                            </form>

                        </div>
                        <div class="tab-pane" id="key">
                            <form onsubmit="return checkKey(this)">
                                <div class="modal-body">
                                    <p>{{__("Liman üzerindeki sunucuların eklentileri servisler üzerinden kullanabileceğiniz gibi, bazı eklentileri sunucuya bağlantı kurmadan kullanamazsınız.")}}</p>
                                    <p>{{__("Bu sebeple, bir anahtar eklemek istiyorsanız öncelikle konuşma protokolünü seçin.")}}</p>
                                    <label>
                                        <input id="useKey" type="checkbox" onchange="keySettingsChanged()">
                                        {{__("Bir Anahtar Kullanmak İstiyorum")}}
                                    </label>
                                    <div id="keyDiv" style="display: none;">
                                        <br>
                                        <div class="form-group">
                                            <label><h4>{{__("Anahtar Türü")}}</h4></label>
                                            <select name="key_type" class="form-control" disabled onchange="setPort(this)" id="keyType">
                                                <option value="ssh" selected>{{__("SSH")}}</option>
                                                <option value="winrm">{{__("WinRM")}}</option>
                                            </select>
                                        </div><hr>
                                        <h4>{{__("Kullanıcı Adı")}}</h4>
                                        <input id="keyUsername" type="text" name="username" class="form-control" placeholder="{{__("Kullanıcı Adı")}}" required disabled><br>
                                        <h4>{{__("Şifre")}}</h4>
                                        <input id="keyPassword" type="password" name="password" class="form-control" placeholder="{{__("Şifre")}}" required disabled><br>
                                        <h4>{{__("Port")}}</h4>
                                        <small>{{__("Eğer bilmiyorsanız varsayılan olarak bırakabilirsiniz.")}}</small>
                                        <input id="port" type="number" name="port" class="form-control" placeholder="{{__("Port")}}" required disabled min="0" value="22"><br>

                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">{{__("Ayarları Onayla")}}</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane" id="summary">
                            <div class="modal-body">
                                <style>td{padding:15px;}</style>
                                <table class="notDataTable">
                                    <tr>
                                        <td>{{__("Sunucu Adı")}}</td>
                                        <td id="tableServerName"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__("Şehir")}}</td>
                                        <td id="tableServerCity"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__("İşletim Sistemi")}}</td>
                                        <td id="tableOperatingSystem"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__("Sunucu Adresi")}}</td>
                                        <td id="tableServerHostname"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__("Sunucu Portu")}}</td>
                                        <td id="tableServerPort"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__("Anahtar")}}</td>
                                        <td id="tableKey"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="addServer()" class="btn btn-success">{{__("Sunucuyu Ekle")}}</button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        let isNetworkOK = false;
        let isGeneralOK = false;
        let isKeyOK = false;
        function checkAccess(form) {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kontrol Ediliyor...")}}',
                showConfirmButton: false,
                allowOutsideClick : false,
            });
            return request('{{route('server_check_access')}}',form,function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: "success",
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer: 2000
                });
                isNetworkOK = true;
                $("#networkTab").css('color','green');
                $("#generalTab").click();
            },function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: "error",
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer: 2000
                });
                isNetworkOK = false;
                $("#networkTab").css('color','red');
            });
        }

        function checkGeneral(form){
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kontrol Ediliyor...")}}',
                showConfirmButton: false,
                allowOutsideClick : false,
            });
            return request('{{route('server_verify_name')}}',form,function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: "success",
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer: 2000
                });
                isGeneralOK = true;
                $("#generalTab").css('color','green');
                $("#keyTab").click();
            },function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: "error",
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer: 2000
                });
                isGeneralOK = false;
                $("#generalTab").css('color','red');
            });
        }

        function checkKey(form) {
            let option = $("#useKey");
            if(option.is(':checked') === false){
                isKeyOK = true;
                $("#keyTab").css('color','green');
                $("#summaryTab").click();
                return false;
            }
            let data = new FormData(form);
            data.append('ip_address',$("#serverHostName").val());
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kontrol Ediliyor...")}}',
                showConfirmButton: false,
                allowOutsideClick : false,
            });
            return request('{{route('server_verify_key')}}',data,function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: "success",
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer: 2000
                });
                isKeyOK = true;
                $("#keyTab").css('color','green');
                $("#summaryTab").click();
            },function (response) {
                let json = JSON.parse(response);
                Swal.fire({
                    position: 'center',
                    type: "error",
                    title: json["message"],
                    showConfirmButton: false,
                    allowOutsideClick : false,
                    timer: 2000
                });
                isKeyOK = false;
                $("#keyTab").css('color','red');
            });
        }

        function keySettingsChanged(){
            let option = $("#useKey");
            if(option.is(':checked')){
                isKeyOK = false;
                $('#keyDiv').find('input, select').prop('disabled', false);
                $("#keyDiv").fadeIn(0);
            }else{
                isKeyOK = true;
                $("#keyTab").css('color','green');
                $("#summaryTab").click();
                $("#keyDiv").fadeOut(0);
                $('#keyDiv').find('input, select').prop('disabled', true);
            }
        }

        function details(element) {
            let server_id = element.querySelector('#server_id').innerHTML;
            window.location.href = "/sunucular/" + server_id
        }

        function setPort(select) {
            if(select.value === "winrm"){
                $("#port").val("5986");
            }else if(select.value === "ssh"){
                $("#port").val("22");
            }
        }

        function setSummary(){
            $("#tableServerHostname").html($("#serverHostName").val());
            $("#tableServerPort").html($("#serverControlPort").val());
            $("#tableOperatingSystem").html($("input[name=operating_system]:checked").attr('data-content'));
            $("#tableServerName").html($("#server_name").val());
            $("#tableServerCity").html($("#serverCity").val());
            $("#tableKey").html(($("#useKey").is(':checked') === true) ? $("#keyType").val() : "{{__("Anahtarsız")}}");
        }

        function addServer() {
            if(!isNetworkOK || !isGeneralOK || !isKeyOK){
                Swal.fire({
                    position: 'center',
                    type: 'info',
                    title: '{{__("Lütfen Tüm Ayarları Tamamlayın")}}',
                    showConfirmButton: false,
                    allowOutsideClick : false,
                });
                return false;
            }
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Sunucu Ekleniyor...")}}',
                showConfirmButton: false,
                allowOutsideClick : false,
            });
            let form = new FormData();
            form.append("name",$("#server_name").val());
            form.append("ip_address",$("#serverHostName").val());
            form.append("control_port",$("#serverControlPort").val());
            form.append("city",$("#serverCity").val());
            form.append('type',$("input[name=operating_system]:checked").val());
            if($("#useKey").is(':checked') === true){
                console.log("checked");
                form.append('username',$("#keyUsername").val());
                form.append('password',$("#keyPassword").val());
            }
            request('{{route('server_add')}}',form,"",function (errors) {
                let json = JSON.parse(errors);
                if(json["status"] == "202"){
                    Swal.fire({
                        position: 'center',
                        type: 'info',
                        title: json["message"],
                        showConfirmButton: true,
                        allowOutsideClick : false,
                    });
                }else{
                    Swal.fire({
                        position: 'center',
                        type: 'error',
                        title: json["message"],
                        showConfirmButton: false,
                        allowOutsideClick : false,
                        timer : 2000
                    });
                }
            });
        }
    </script>


    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Sunucuyu Sil",
       "url" => route('server_remove'),
       "text" => "Sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Sunucu Id:'null'" => "server_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
   ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "updateTable",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Sunucu Id:''" => "server_id:hidden",
            "Şehir:city" => cities(),
        ],
        "submit_text" => "Düzenle"
    ])

@endsection
