@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sunucular")}}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <h3 class="profile-username text-center">{{__("Sunucular")}}</h3>
                <p class="text-muted text-center">Bu sayfadan mevcut sunucularını görebilirsiniz. Ayrıca yeni sunucu eklemek için Sunucu Ekle butonunu kullanabilirsiniz.</p>
              </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                        @if(\App\Permission::can(user()->id,'liman','id','add_server'))
                        <button href="#tab_1" type="button" class="btn btn-success" data-toggle="modal" data-target="#add_server">{{__("Sunucu Ekle")}}</button><br><br>
                    @endif
                    
                    @include('errors')
                    <?php
                    use Illuminate\Support\Facades\DB;
                    $servers = servers();
                    foreach ($servers as $server) {
                        $server->extension_count = DB::table(
                            'server_extensions'
                        )
                            ->where('server_id', $server->id)
                            ->count();
                    }
                    ?>
                    @include('table',[
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
                                "icon" => " context-menu-icon-edit"
                            ],
                            "Sil" => [
                                "target" => "delete",
                                "icon" => " context-menu-icon-delete"
                            ]
                        ],
                        "onclick" => "details"
                    ])
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add_server">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{__("Sunucu Ekle")}}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                </div>
                <ul class="nav nav-tabs" role="tablist" style="padding:20px;">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" id="networkTab" href="#network" role="tab" aria-controls="network" aria-selected="true">{{__("Bağlantı Bilgileri")}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" id="generalTab" href="#general" role="tab" aria-controls="general">{{__("Genel Ayarlar")}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" id="keyTab" href="#key" role="tab" aria-controls="key">{{__("Anahtar")}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" id="summaryTab" onclick="setSummary()" href="#summary" role="tab" aria-controls="summary">{{__("Özet")}}</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="network" role="tabpanel" aria-labelledby="networkTab">
                        <form onsubmit="return checkAccess(this)">
                            <div class="modal-body">
                                <h4>{{__("Sunucunuzun Adresi")}}</h4>
                                <input type="text" id="serverHostName" name="hostname" class="form-control" placeholder="{{__("Sunucunuzun Hostname yada IP Adresini girin.")}}" required><br>
                                <h4>{{__("Sunucunuzun Portu")}}</h4>
                                <h6>{{__("Sunucunuzun açık olup olmadığını algılamak için kontrol edilebilecek bir port girin.")}}</h6>
                                <pre>{{__("SSH : 22\nWinRM : 5986\nActive Directory, Samba : 636")}}</pre>
                                <input id="serverControlPort" type="number" name="port" class="form-control" placeholder="{{__("Kontrol Portu Girin (Yalnızca Sayı).")}}" required min="-1">
                                <small><i>{{__("Eğer hedefiniz UDP protokolü üzerinden dinliyorsa bu kontrolü atlamak için -1 girebilirsiniz.")}}</i></small>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">{{__("Bağlantıyı Kontrol Et")}}</button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="generalTab">
                        <form onsubmit="return checkGeneral(this)">
                            <div class="modal-body">
                                <h4>{{__("Sunucunuzun Adı")}}</h4>
                                <input id="server_name" type="text" name="server_name" class="form-control" placeholder="{{__("Sunucunuzun Adı")}}" required><br>
                                <h4>{{__("Şehir")}}</h4>
                                <small>{{__("Sunucunuza bir şehir atayarak, eklentileri kullanırken Türkiye haritası üzerinde erişiminizi kolaylaştırabilirsiniz.")}}</small><br>
                                <select name="server_city" id="serverCity" class="form-control select2" required>
                                    <option value="" disabled selected>{{__('Şehir Seçiniz')}}</option>
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
                    <div class="tab-pane fade" id="key" role="tabpanel" aria-labelledby="keyTab">
                        <form onsubmit="return checkKey(this)">
                            <div class="modal-body">
                                <p>{{__("Liman üzerindeki sunucuların eklentileri servisler üzerinden kullanabileceğiniz gibi, bazı eklentileri sunucuya bağlantı kurmadan kullanamazsınız.")}}</p>
                                <p>{{__("Bu sebeple, bir anahtar eklemek istiyorsanız öncelikle konuşma protokolünü seçin.")}}</p>
                                <label>
                                    <input id="useKey" type="checkbox" onchange="keySettingsChanged()" checked>
                                    {{__("Bir Anahtar Kullanmak İstiyorum")}}
                                </label>
                                <div id="keyDiv" style="display: none;">
                                    <br>
                                    <div class="form-group">
                                        <label><h4>{{__("Anahtar Türü")}}</h4></label>
                                        <select name="key_type" class="form-control" disabled onchange="setPort(this)" id="keyType">
                                            <option value="linux_ssh" selected>{{__("SSH")}}</option>
                                            <option value="linux_certificate">{{__("SSH Anahtarı")}}</option>
                                            <option value="windows_powershell">{{__("WinRM")}}</option>
                                            <option value="snmp">{{__("SNMP")}}</option>
                                        </select>
                                    </div><hr>
                                    <h4>{{__("Kullanıcı Adı")}}</h4>
                                    <input id="keyUsername" type="text" name="username" class="form-control" placeholder="{{__("Kullanıcı Adı")}}" required disabled><br>
                                    <h4 id="passwordPrompt">{{__("Şifre")}}</h4>
                                    <h4 id="certificatePrompt">{{__("SSH Private Key")}}</h4>
                                    <label id="certificateInformLabel">{{__("Anahtarınızın çalışabilmesi için şifreli olmaması ve sudo komutlarını çalıştırması için sudoers dosyasında NOPASSWD olarak eklenmiş olması gerekmektedir.")}}</label>
                                    <textarea class="form-control" name="password" id="keyPasswordCert" cols="30" rows="10" required disabled></textarea>
                                    <input id="keyPassword" type="password" name="password" class="form-control" placeholder="{{__("Şifre")}}" required disabled><br>
                                    <div id="snmpWrapper" style="display: none">
                                        <h4>{{__("Bağlantı Türü")}}</h4>
                                        <select id="SNMPsecurityLevel" name="SNMPsecurityLevel" class="select2 snmp-input" disabled>
                                            <option value="noAuthNoPriv">noAuthNoPriv</option>
                                            <option value="authNoPriv">authNoPriv</option>
                                            <option value="authPriv" selected>authPriv</option>
                                        </select><br>
                                        <h4>{{__("Giriş Protokolü")}}</h4>
                                        <select id="SNMPauthProtocol" name="SNMPauthProtocol" class="select2 snmp-input" disabled>
                                            <option value="MD5">MD5</option>
                                            <option value="SHA">SHA</option>
                                        </select><br>
                                        <h4>{{__("Giriş Parolası")}}</h4>
                                        <input id="SNMPauthPassword" name="SNMPauthPassword" type="password" class="form-control snmp-input" placeholder="{{__("Giriş Parolası")}}" disabled><br>
                                        <h4>{{__("Gizlilik Protokolü")}}</h4>
                                        <select id="SNMPprivacyProtocol" name="SNMPprivacyProtocol" class="select2 snmp-input" disabled>
                                            <option value="DES">DES</option>
                                            <option value="AES">AES</option>
                                        </select><br>
                                        <h4>{{__("Gizlilik Parolası")}}</h4>
                                        <input id="SNMPprivacyPassword" name="SNMPprivacyPassword" type="password" class="form-control snmp-input" placeholder="{{__("Gizlilik Parolası")}}" disabled><br>
                                    </div>
                                    <h4>{{__("Port")}}</h4>
                                    <small>{{__("Eğer bilmiyorsanız varsayılan olarak bırakabilirsiniz.")}}</small>
                                    <input id="port" type="number" name="port" class="form-control snmp-input" placeholder="{{__("Port")}}" required disabled min="0" value="22"><br>

                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">{{__("Ayarları Onayla")}}</button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summaryTab">
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

    <script>
        var isNetworkOK = false;
        var isGeneralOK = false;
        var isKeyOK = false;
        function checkAccess(form) {
            showSwal('{{__("Kontrol Ediliyor...")}}','info');
            return request('{{route('server_check_access')}}',form,function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                isNetworkOK = true;
                $("#networkTab").css('color','green');
                $("#generalTab").click();
            },function (response) {
                var json = JSON.parse(response);
                showSwal(json.message,'error',2000);
                isNetworkOK = false;
                $("#networkTab").css('color','red');
            });
        }

        function checkGeneral(form){
            showSwal('{{__("Kontrol Ediliyor...")}}','info');
            return request('{{route('server_verify_name')}}',form,function (response) {
                var json = JSON.parse(response);
            showSwal(json["message"],'success',500);
                isGeneralOK = true;
                $("#generalTab").css('color','green');
                $("#keyTab").click();
            },function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'error',2000);
                isGeneralOK = false;
                $("#generalTab").css('color','red');
            });
        }
        var helper;
        function checkKey(form) {           
            var option = $("#useKey");
            if(option.is(':checked') === false){
                isKeyOK = true;
                $("#keyTab").css('color','green');
                $("#summaryTab").click();
                return false;
            }
            var data = new FormData(form);
            helper = data;
            data.append('ip_address',$("#serverHostName").val());
            showSwal('{{__("Kontrol Ediliyor...")}}','info');
            return request('{{route('server_verify_key')}}',data,function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'success',2000);
                isKeyOK = true;
                $("#keyTab").css('color','green');
                $("#summaryTab").click();
            },function (response) {
                var json = JSON.parse(response);
                showSwal(json["message"],'error',2000);
                isKeyOK = false;
                $("#keyTab").css('color','red');
            });
        }

        function keySettingsChanged(){
            var option = $("#useKey");
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
        keySettingsChanged();
        function details(element) {
            var server_id = element.querySelector('#server_id').innerHTML;
            partialPageRequest("/sunucular/" + server_id);
        }
        $("#keyPasswordCert").fadeOut(0);
        $("#certificateInformLabel").fadeOut(0);
        $("#keyPassword").fadeIn(0);
        $("#certificatePrompt").fadeOut(0);
        $("#passwordPrompt").fadeIn(0);
        function setPort(select) {
            if(select.value === "windows_powershell"){
                $("#keyPasswordCert").fadeOut(0).attr("disabled","true");
                $("#certificateInformLabel").fadeOut(0);
                $("#keyPassword").fadeIn(0).removeAttr("disabled");
                $("#certificatePrompt").fadeOut(0);
                $("#passwordPrompt").fadeIn(0);
                $("#snmpWrapper").fadeOut();
                $(".snmp-input").attr("disabled","true");
                $("#port").val("5986").removeAttr("disabled");
            }else if(select.value === "linux_ssh"){
                $("#keyPasswordCert").fadeOut(0).attr("disabled","true");
                $("#keyPassword").fadeIn(0).removeAttr("disabled");
                $("#certificateInformLabel").fadeOut(0);
                $("#passwordPrompt").fadeIn(0);
                $("#certificatePrompt").fadeOut(0);
                $("#snmpWrapper").fadeOut();
                $(".snmp-input").attr("disabled","true");
                $("#port").val("22").removeAttr("disabled");
            }else if(select.value === "linux_certificate"){
                $("#keyPasswordCert").fadeIn(0).removeAttr("disabled");
                $("#certificateInformLabel").fadeIn(0);
                $("#passwordPrompt").fadeOut(0);
                $("#keyPassword").fadeOut(0).attr("disabled","true");
                $("#certificatePrompt").fadeIn(0);
                $("#snmpWrapper").fadeOut();
                $(".snmp-input").attr("disabled","true");
                $("#port").val("22").removeAttr("disabled");
            }else if(select.value === "snmp"){
                $("#keyPasswordCert").fadeOut(0).attr("disabled","true");
                $("#certificateInformLabel").fadeOut(0).attr("disabled","true");
                $("#passwordPrompt").fadeOut(0).attr("disabled","true");
                $("#keyPassword").fadeOut(0).attr("disabled","true");
                $("#certificatePrompt").fadeOut(0).attr("disabled","true");
                $(".snmp-input").removeAttr("disabled");
                $("#snmpWrapper").fadeIn();
                $("#port").val("161").attr("disabled","true");
            }
        }

        function setSummary(){
            $("#tableServerHostname").text($("#serverHostName").val());
            $("#tableServerPort").text($("#serverControlPort").val());
            $("#tableOperatingSystem").text($("input[name=operating_system]:checked").attr('data-content'));
            $("#tableServerName").text($("#server_name").val());
            $("#tableServerCity").text($("#serverCity").val());
            $("#tableKey").text(($("#useKey").is(':checked') === true) ? $("#keyType").val() : "{{__("Anahtarsız")}}");
        }

        function addServer() {
            if(!isNetworkOK || !isGeneralOK || !isKeyOK){
                showSwal('{{__("Lütfen Tüm Ayarları Tamamlayın")}}','info',2000);
                return false;
            }
            showSwal('{{__("Sunucu Ekleniyor...")}}','info');
            var form = new FormData();
            form.append("name",$("#server_name").val());
            form.append("ip_address",$("#serverHostName").val());
            form.append("control_port",$("#serverControlPort").val());
            form.append("city",$("#serverCity").val());

            if($("#useKey").is(':checked') === true){
                form.append('username',$("#keyUsername").val());
                if($("#keyType").val() == "linux_certificate"){
                    form.append('password',$("#keyPasswordCert").val());
                }else if($("#keyType").val() == "snmp"){
                    form.append("SNMPsecurityLevel",$("#SNMPsecurityLevel").val());
                    form.append("SNMPauthProtocol",$("#SNMPauthProtocol").val());
                    form.append("SNMPauthPassword",$("#SNMPauthPassword").val());
                    form.append("SNMPprivacyProtocol",$("#SNMPprivacyProtocol").val());
                    form.append("SNMPprivacyPassword",$("#SNMPprivacyPassword").val());
                }else{
                    form.append('password',$("#keyPassword").val());
                }
                
                form.append('type',$("#keyType").val());
                form.append('key_port',$("#port").val());
            }else{
                form.append('type',$("input[name=operating_system]:checked").val());
            }
            request('{{route('server_add')}}',form,"",function (errors) {
                var json = JSON.parse(errors);
                if(json["status"] == "202"){
                    showSwal(json["message"],'info');
                    $(".modal").modal('hide');
                }else{
                    showSwal(json["message"],'error',2000);
                }
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }
    </script>


    @include('modal',[
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

    @include('modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "updateTable",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Sunucu Id:''" => "server_id:hidden",
            "IP Adresi" => "ip_address:text",
            "Şehir:city" => cities(),
        ],
        "submit_text" => "Düzenle"
    ])

@endsection
