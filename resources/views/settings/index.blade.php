@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sistem Ayarları")}}</li>
        </ol>
    </nav>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-tabs" role="tabpanel">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#users" aria-selected="true">{{__("Kullanıcı Ayarları")}}</a>
                        </li>
                        <li class="nav-item">
                            <a id="extensionNavLink" class="nav-link" data-toggle="tab" href="#extensions" aria-selected="true">{{__("Eklentiler")}} @if(is_file(storage_path("extension_updates"))) <span style="color:green" class="blinking">*</span> @endif</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#roles" onclick="getRoleList()" aria-selected="true">{{__("Rol Grupları")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#roleList" onclick="allRoles()" aria-selected="true">{{__("İzin Listesi")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#certificates" >{{__("Sertifikalar")}}</a>
                        </li>
                        @if(! env('CONTAINER_MODE', false))
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#health" onclick="checkHealth()">{{__("Sağlık Durumu")}}</a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#externalNotifications" onclick="">{{__("Dış Bildirimler")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#limanMarket" onclick="checkMarketAccess()">{{__("Liman Market")}}</a>
                        </li>
                        @if(! env('CONTAINER_MODE', false))
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#dnsSettings" onclick="getDNS()">{{__("DNS Ayarları")}}</a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#mailSettings" onclick="getCronMails()">{{__("Mail Ayarları")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#rsyslog">{{__("Log Yönlendirme")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#limanTweaks" onclick="getLimanTweaks()">{{__("İnce Ayarlar")}}</a>
                        </li>
                        {!! settingsModuleButtons() !!}
                    </ul>
                </div>
                <div class="card-body">
                    @include('errors')
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="users" role="tabpanel">
                            @include('modal-button',[
                                "class" => "btn-success",
                                "target_id" => "add_user",
                                "text" => "Kullanıcı Ekle"
                            ])<br><br>
                            <div id="usersTable">
                                @include('table',[
                                    "value" => \App\User::all()->map(function($user) {
                                        $user->status = (bool) $user->status ? __("Yönetici") : __("Kullanıcı");
                                        $user->username = empty($user->username) ? "-" : $user->username;
                                        $user->auth_type = ! empty($user->auth_type) ? (
                                            $user->auth_type == "local" ? "Liman" : (
                                                $user->auth_type == "ldap" ? "LDAP" : "Keycloak"
                                            )
                                        ) : "-";
                                        return $user;
                                    }),
                                    "title" => [
                                        "İsim Soyisim", "Kullanıcı Adı", "Email", "Yetki", "Giriş Türü", "*hidden*" ,
                                    ],
                                    "display" => [
                                        "name", "username", "email", "id:user_id", "status", "auth_type"
                                    ],
                                    "menu" => [
                                        "Parolayı Sıfırla" => [
                                            "target" => "passwordReset",
                                            "icon" => "fa-lock"
                                        ],
                                        "Sil" => [
                                            "target" => "deleteUser",
                                            "icon" => " context-menu-icon-delete"
                                        ]
                                    ],
                                    "onclick" => "userDetails"
                                ])
                            </div>
                        </div>
                        <div class="tab-pane fade show" id="roles" role="tabpanel">
                            @include('modal-button',[
                                "class" => "btn-success",
                                "target_id" => "add_role",
                                "text" => "Rol Grubu Ekle"
                            ])<br><br>
                            <div id="rolesTable">

                            </div>
                        </div>
                        <div class="tab-pane fade show" id="certificates" role="tabpanel">
                            <button class="btn btn-success" onclick="window.location.href = '{{route('certificate_add_page')}}'"><i
                                class="fa fa-plus"></i> {{__("Sertifika Ekle")}}</button>
                            <br><br>
                            @include('table',[
                                "value" => \App\Models\Certificate::all(),
                                "title" => [
                                    "Sunucu Adresi" , "Servis" , "*hidden*" ,
                                ],
                                "display" => [
                                    "server_hostname" , "origin", "id:certificate_id" ,
                                ],
                                "menu" => [
                                    "Detaylar" => [
                                        "target" => "showCertificateModal",
                                        "icon" => "fa-info"
                                    ],
                                    "Güncelle" => [
                                        "target" => "updateCertificate",
                                        "icon" => "fa-sync-alt"
                                    ],
                                    "Sil" => [
                                        "target" => "deleteCertificate",
                                        "icon" => " context-menu-icon-delete"
                                    ]
                                ],
                            ])
                        </div>
                        <div class="tab-pane fade show" id="health" role="tabpanel">
                            <pre id="output"></pre>
                        </div>
                        <div class="tab-pane fade show" id="extensions" role="tabpanel">
                            @include('extension_pages.manager')
                        </div>
                        <div class="tab-pane fade show" id="limanMarket" role="tabpanel">
                            <div id="marketStatus" class="alert alert-secondary" role="alert">

                            </div>
                            <div id="marketLoading">
                                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                            </div>
                            <div id="marketEnabled" style="display:none;">
                            <div id="marketTableWrapper">
                                @include('table',[
                                    "id" => "marketTable",
                                    "value" => [],
                                    "title" => [
                                        "Sistem Adı" , "Mevcut Versiyon", "Durumu"
                                    ],
                                    "display" => [
                                        "packageName" , "currentVersion", "status"
                                    ],
                                ])
                            </div>

                            </div>
                            <div id="marketDisabled" style="display:none">
                                <p>{{__("Liman kurulumunuzu Liman Market'e bağlayarak sistemdeki tüm güncellemeleri takip edebilir, güncellemeleri indirebilirsiniz.")}}</p>
                                <button type="button" class="btn btn-primary btn-lg" onclick="location.href = '{{route('redirect_market')}}'">{{__("Liman Market'i Bağla")}}</button>
                            </div>
                            <script>
                                function checkMarketAccess(){
                                    var status = $("#marketStatus");
                                    $("#marketTableWrapper").fadeOut(0);
                                    status.html("{{__('Market bağlantısı kontrol ediliyor...')}}");
                                    status.attr("class","alert alert-secondary");
                                    request("{{route('verify_market')}}",new FormData(),function(success){
                                        var json = JSON.parse(success);
                                        $("#marketLoading").fadeOut(0);
                                        $("#marketDisabled").fadeOut(0);
                                        $("#marketEnabled").fadeIn();
                                        status.html(json.message);
                                        status.attr("class","alert alert-success");
                                        setTimeout(() => {
                                            checkMarketUpdates();
                                        }, 1000);
                                    },function(error){
                                        var json = JSON.parse(error);
                                        $("#marketLoading").fadeOut(0);
                                        $("#marketEnabled").fadeOut(0);
                                        $("#marketDisabled").fadeIn();
                                        status.html(json.message);
                                        status.attr("class","alert alert-danger");
                                    });
                                }

                                function checkMarketUpdates(){
                                    var status = $("#marketStatus");
                                    status.html("{{__('Güncellemeler kontrol ediliyor...')}}");
                                    status.attr("class","alert alert-secondary");
                                    $("#marketLoading").fadeIn(0);
                                    request("{{route('check_updates_market')}}",new FormData(),function(success){
                                        var json = JSON.parse(success);
                                        var table = $("#marketTable").DataTable();
                                        var counter = 1;
                                        table.clear();
                                        $.each(json.message,function (index,current) {
                                            var row = table.row.add([
                                                counter++, current["packageName"], current["currentVersion"], current["status"]
                                            ]).draw().node();
                                        });
                                        table.draw();
                                        status.html("{{__('Güncellemeler başarıyla kontrol edildi')}}");
                                        status.attr("class","alert alert-success");
                                        $("#marketLoading").fadeOut(0);
                                        $("#marketTableWrapper").fadeIn(0);
                                    },function(error){
                                        var json = JSON.parse(error);
                                        status.html(json.message);
                                        status.attr("class","alert alert-danger");
                                    });
                                }
                            </script>

                        </div>
                        <div class="tab-pane fade show" id="dnsSettings" role="tabpanel">
                            <p>{{__("Liman'ın sunucu adreslerini çözebilmesi için gerekli DNS sunucularını aşağıdan düzenleyebilirsiniz.")}}</p>
                            <form onsubmit="return saveDNS(this);">
                                <label>{{__("Öncelikli DNS Sunucusu")}}</label>
                                <input type="text" name="dns1" id="dns1" class="form-control mb-3">
                                <label>{{__("Alternatif DNS Sunucusu")}}</label>
                                <input type="text" name="dns2" id="dns2" class="form-control mb-3">
                                <label>{{__("Alternatif DNS Sunucusu")}}</label>
                                <input type="text" name="dns3" id="dns3" class="form-control"><br>
                                <button type="submit" class="btn btn-primary">{{__("Kaydet")}}</button>
                            </form>
                        </div>
                        <div class="tab-pane fade show" id="servers" role="tabpanel">
                            <?php
                            $servers = servers();
                            foreach ($servers as $server) {
                                $server->enabled = $server->enabled
                                    ? __("Aktif")
                                    : __("Pasif");
                            }
                            ?>
                            <button class="btn btn-success" onclick="serverStatus(true)" disabled>{{__("Aktifleştir")}}</button>
                            <button class="btn btn-danger" onchange="serverStatus(false)" disabled>{{__("Pasifleştir")}}</button><br><br>
                            @include('table',[
                                "value" => $servers,
                                "title" => [
                                    "Sunucu Adı" , "İp Adresi" , "Durumu" , "*hidden*"
                                ],
                                "display" => [
                                    "name" , "ip_address", "enabled", "id:server_id"
                                ],
                                "noInitialize" => true
                            ])
                            <script>
                                $("#servers table").DataTable(dataTablePresets('multiple'));
                            </script>
                        </div>
                        {!! settingsModuleViews() !!}
                        <div class="tab-pane fade show" id="update" role="tabpanel">
                            @php($updateOutput = shell_exec("apt list --upgradable | grep 'liman'"))
                            @if($updateOutput)
                                <pre>{{$updateOutput}}</pre>
                            @else
                                <pre>{{__("Liman Sürümünüz : " . getVersion() . " güncel.")}}</pre>
                            @endif
                        </div>

                        <div class="tab-pane fade show" id="externalNotifications" role="tabpanel">
                        @include('modal-button',[
                                "class" => "btn-primary",
                                "target_id" => "addNewNotificationSource",
                                "text" => "Yeni İstemci Ekle"
                            ])<br><br>
                            @include('table',[
                                    "value" => \App\Models\ExternalNotification::all(),
                                    "title" => [
                                        "İsim" , "İp Adresi / Hostname", "Son Erişim Tarihi" , "*hidden*" ,
                                    ],
                                    "display" => [
                                        "name" , "ip", "last_used", "id:id" ,
                                    ],
                                    "menu" => [
                                        "Düzenle" => [
                                            "target" => "editExternalNotificationToken",
                                            "icon" => " context-menu-icon-edit"
                                        ],
                                        "Yeni Token Al" => [
                                            "target" => "renewExternalNotificationToken",
                                            "icon" => "fa-lock"
                                        ],
                                        "Sil" => [
                                            "target" => "deleteExternalNotificationToken",
                                            "icon" => " context-menu-icon-delete"
                                        ]
                                    ],
                                ])
                        </div>

                        <div class="tab-pane fade show" id="rsyslog" role="tabpanel">
                        <p>{{__("Liman Üzerindeki İşlem Loglarını hedef bir log sunucusuna rsyslog servisi ile göndermek için hedef log sunucusunun adresi ve portunu yazınız.")}}</p>
                            <form id="logForm" onsubmit="return saveLogSystem()">
                                <div class="form-row">
                                    <div class="form-group col-md-10">
                                        <label for="targetHostname">{{__("Sunucu Adresi")}}</label>
                                        <input type="text" class="form-control" name="targetHostname" id="logIpAddress">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="targetPort">{{__("Sunucu Portu")}}</label>
                                        <input type="number" class="form-control" name="targetPort" value="514" id="logPort">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="type">{{__("Bağlantı Türü")}}</label><br>
                                    <select id="type" class="select2" name="type">
                                        <option value="tcp">TCP</option>
                                        <option value="udp">UDP</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">{{__("Ayarları Kaydet")}}</button>
                            </form>
                        </div>
                        <div class="tab-pane fade show" id="roleList" role="tabpanel">
                            <div id="roleListInner"></div>  
                        </div>
                        <div class="tab-pane fade show" id="mailSettings" role="tabpanel">
                            <div id="mailWrapper"></div>
                            <script>
                                function getCronMails(){
                                    showSwal('{{ __("Okunuyor...") }}',"info");
                                    request("{{route('cron_mail_get')}}",new FormData(),function (success){
                                        $("#mailWrapper").html(success);
                                        $("#mailWrapper table").DataTable(dataTablePresets("normal"));
                                        Swal.close();
                                    },function(error){
                                        let json = JSON.parse(error);
                                        showSwal(json.message,'error',2000);
                                    });

                                }
                            </script>
                        </div>
                        <div class="tab-pane fade show" id="logForward" role="tabpanel">
                            @include("settings.log_forward")
                        </div>
                        <div class="tab-pane fade show" id="limanTweaks" role="tabpanel">
                            @include("settings.tweaks")
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    
    <style>
        .blinking {
            animation: blinker 1s linear infinite;
        }

        @keyframes blinker {
            50% { opacity: 0; }
        }
    </style>

    @include('modal',[
        "id"=>"add_user",
        "title" => "Kullanıcı Ekle",
        "url" => route('user_add'),
        "next" => "afterUserAdd",
        "selects" => [
            "Yönetici:administrator" => [
                "-:administrator" => "type:hidden"
            ],
            "Kullanıcı:user" => [
                "-:user" => "type:hidden"
            ]
        ],
        "inputs" => [
            "İsim Soyisim" => "name:text",
            "Kullanıcı Adı (opsiyonel)" => "username:text",
            "E-mail Adresi" => "email:email",
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
        "id"=>"add_role",
        "title" => "Rol Grubu Ekle",
        "url" => route('role_add'),
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text"
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
       "id"=>"deleteUser",
       "title" =>"Kullanıcıyı Sil",
       "url" => route('user_remove'),
       "text" => "Kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Kullanıcıyı Sil"
   ])

    @include('modal',[
        "id"=>"editRole",
        "title" => "Rol Grubu Yeniden Adlandır",
        "url" => route('role_rename'),
        "next" => "getRoleList",
        "inputs" => [
            "Rol Adı" => "name:text",
            "Rol Id:'null'" => "role_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])

    @include('modal',[
        "id"=>"deleteRole",
        "title" =>"Rol Grubunu Sil",
        "url" => route('role_remove'),
        "text" => "Rol grubunu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "getRoleList",
        "inputs" => [
            "Rol Id:'null'" => "role_id:hidden"
        ],
        "submit_text" => "Rol Grubunu Sil"
    ])

    @component('modal-component',[
        "id" => "showCertificate",
        "title" => "Sertifika Detayları",
    ])
        <div class="row">
            <div class="col-md-4">
                <div class="box box-solid">
                <div class="box-header with-border">
                    <h5 class="box-title" style="font-weight: 600">{{__("İmzalayan")}}</h5>
                </div>
                <hr class="my-2">
                <div class="box-body clearfix">
                    <div class="form-group">
                        <label>{{__("İstemci")}}</label>
                        <input type="text" id="issuerCN" readonly class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{__("Otorite")}}</label>
                        <input type="text" id="issuerDN" readonly class="form-control">
                    </div>
                </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h5 class="box-title" style="font-weight: 600">{{__("Parmak İzleri")}}</h5>
                    </div>
                    <hr class="my-2">
                <div class="box-body clearfix">
                    <div class="form-group">
                        <label>{{__("İstemci")}}</label>
                        <input type="text" id="subjectKeyIdentifier" readonly class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{__("Otorite")}}</label>
                        <input type="text" id="authorityKeyIdentifier" readonly class="form-control">
                    </div>
                </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="box box-solid">
                <div class="box-header with-border">
                    <h5 class="box-title" style="font-weight: 600">{{__("Geçerlilik Tarihi")}}</h5>
                </div>
                <hr class="my-2">
                <div class="box-body clearfix">
                    <div class="form-group">
                        <label>{{__("Başlangıç Tarihi")}}</label>
                        <input type="text" id="validFrom" readonly class="form-control">
                    </div>
                    <div class="form-group">
                        <label>{{__("Bitiş Tarihi")}}</label>
                        <input type="text" id="validTo" readonly class="form-control">
                    </div>
                </div>
                </div>
            </div>
        </div>
    @endcomponent

    @include('modal',[
           "id"=>"updateCertificate",
           "title" =>"Sertifikayı Güncelle",
           "url" => route('update_certificate'),
           "text" => "Sertifikayı güncellemek istediğinize emin misiniz?",
           "next" => "reload",
           "inputs" => [
               "Kullanici Id:'null'" => "certificate_id:hidden"
           ],
           "submit_text" => "Sertifikayı Güncelle"
       ])

    @include('modal',[
        "id"=>"deleteCertificate",
        "title" =>"Sertifikayı Sil",
        "url" => route('remove_certificate'),
        "text" => "Sertifikayı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "reload",
        "inputs" => [
            "Kullanici Id:'null'" => "certificate_id:hidden"
        ],
        "submit_text" => "Sertifikayı Sil"
    ])

    @include('modal',[
       "id"=>"passwordReset",
       "title" =>"Parolayı Sıfırla",
       "url" => route('user_password_reset'),
       "text" => "Parolayı sıfırlamak istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "nothing",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Parolayı Sıfırla"
   ])

    @include('modal',[
        "id"=>"addNewNotificationSource",
        "title" => "Yeni Bildirim İstemcisi Ekle",
        "url" => route('add_notification_channel'),
        "text" => "İp Adresi bölümüne izin vermek istediğiniz bir subnet adresini ya da ip adresini yazarak erişimi kısıtlayabilirsiniz. Örneğin : 192.168.1.0/24",
        "next" => "debug",
        "inputs" => [
            "Adı" => "name:text",
            "İp Adresi / Hostname" => "ip:text",
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
        "id"=>"editExternalNotificationToken",
        "title" =>"İstemciyi Düzenle",
        "url" => route('edit_notification_channel'),
        "text" => "İp Adresi bölümüne izin vermek istediğiniz bir subnet adresini ya da ip adresini yazarak erişimi kısıtlayabilirsiniz. Örneğin : 192.168.1.0/24",
        "next" => "reload",
        "inputs" => [
            "Adı" => "name:text",
            "İp Adresi / Hostname" => "ip:text",
            "-:-" => "id:hidden"
        ],
        "submit_text" => "Yenile"
    ])

    @include('modal',[
        "id"=>"renewExternalNotificationToken",
        "title" =>"İstemci Token'ı Yenile",
        "url" => route('renew_notification_channel'),
        "text" => "İstemciye ait token'i yenilemek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "debug",
        "inputs" => [
            "-:-" => "id:hidden"
        ],
        "submit_text" => "Yenile"
    ])

    @include('modal',[
        "id"=>"deleteExternalNotificationToken",
        "title" =>"İstemciyi Sil",
        "url" => route('revoke_notification_channel'),
        "text" => "İstemciyi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "reload",
        "inputs" => [
            "-:-" => "id:hidden"
        ],
        "submit_text" => "Sil"
    ])


    <script>

        function restrictionType(element){
            if($(element).val() == "user"){
                $('#domainUserSelect').show();
                $('#domainGroupSelect').hide();
            }else if($(element).val() == "group"){
                $('#domainGroupSelect').show();
                $('#domainUserSelect').hide();
            }
        }

        function showCertificateModal(node) {
            let form = new FormData();
            form.append("hostname", $(node).find("#server_hostname").html());
            form.append("port", $(node).find("#origin").html());
            request("{{ route('certificate_info') }}", form, function(data) {
                let response = JSON.parse(data)["message"];
                if(response["issuer"]["DC"]){
                    $("#issuerCN").val(response["issuer"]["CN"]);
                }
                if(response["issuer"]["DC"]){
                    $("#issuerDN").val(response["issuer"]["DC"].reverse().join('.'));
                }
                $("#validFrom").val(response["validFrom_time_t"]);
                $("#validTo").val(response["validTo_time_t"]);
                $("#authorityKeyIdentifier").val(response["authorityKeyIdentifier"]);
                $("#subjectKeyIdentifier").val(response["subjectKeyIdentifier"]);
                $("#showCertificate").modal("show");
            }, function(err) {
                let error = JSON.parse(err);
                showSwal(error.message, 'error', 2000);
            });
        }

        function saveLogSystem(){
            showSwal('{{__("Kaydediliyor...")}}','info');
            var data = new FormData(document.querySelector('#logForm'));
            return request("{{route('set_log_forwarding')}}", data, function(res) {
                var response = JSON.parse(res);
                showSwal(response.message,'success');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function afterUserAdd(response) {
            var json = JSON.parse(response);
            $("#add_user button[type='submit']").attr("disabled","true")
            getUserList();
        }

        $(function () {
            $("#add_user").find("input[name='username']").attr('required', false);
        });
        function getUserList(){
            request('{{route('get_user_list_admin')}}', new FormData(), function (response) {
                $("#usersTable").html(response);
                $('#usersTable table').DataTable();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function getRoleList(){
            $('.modal').modal('hide');
            request('{{route('role_list')}}', new FormData(), function (response) {
                $("#rolesTable").html(response);
                $('#rolesTable table').DataTable(dataTablePresets('normal'));
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function allRoles(){
            $('.modal').modal('hide');
            request('{{route('all_roles')}}', new FormData(), function (response) {
                $("#roleListInner").html(response);
                $('#roleListInner table').DataTable(dataTablePresets('normal'));
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function goToRoleItem(row) {
            let id = row.querySelector("#id").innerHTML;
            let morph_type = row.querySelector("#morph_type").innerHTML;
            let type = row.querySelector("#perm_type").innerHTML.charAt(0);

            let tag = "#";
            switch (type) {
                case "E":
                    tag = tag + "extension";
                    break;
                case "S":
                    tag = tag + "server";
                    break;
                case "F":
                    tag = tag + "function";
                    break;
                case "L":
                    tag = tag + "liman";
                    break;
                default:
                    break;
            }

            if (morph_type == "roles") {
                window.location.href = '/rol/' + id + tag;
            } 

            if (morph_type == "users") {
                window.location.href = '/ayarlar/' + id + tag;
            }
        }

        function roleDetails(row){
            var role_id = row.querySelector('#role_id').innerHTML;
            window.location.href = '/rol/' + role_id;
        }

        function userDetails(row) {
            var user_id = row.querySelector('#user_id').innerHTML;
            window.location.href = '/ayarlar/' + user_id;
        }

        function checkHealth() {
            showSwal('{{__("Okunuyor...")}}','info');
            request("{{route('health_check')}}", new FormData(), function (success) {
                Swal.close();
                var json = JSON.parse(success);
                var box = $("#output");
                box.html("");
                for (var i = 0; i < json["message"].length; i++) {
                    var current = json["message"][i];
                    box.append("<div class='alert alert-" + current["type"] + "' role='alert'>" +
                        current["message"] +
                        "</div>");
                }

            }, function (error) {
                console.log(error);
                var box = $("#output");
                box.html("");
                box.append("<div class='alert alert-" + "success" + "' role='alert'>" +
                        "Hata bulunamadı" +
                        "</div>");
                Swal.close();
            });
        }


        function saveDNS(form){
            return request('{{route('set_liman_dns_servers')}}',form, function (success){
                var json = JSON.parse(success);
                showSwal(json["message"],'success',2000);
                setTimeout(() => {
                    getDNS();
                }, 1500);
            }, function(error){
                var json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }

        function getDNS(){
            showSwal('{{__("Okunuyor")}}','info');
            request('{{route('get_liman_dns_servers')}}',new FormData(),function(success){
                var json = JSON.parse(success);
                $("#dns1").val(json["message"][0]);
                $("#dns2").val(json["message"][1]);
                $("#dns3").val(json["message"][2]);
                Swal.close();
            },function(error){
                var json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }


        $('#add_user').on('shown.bs.modal', function (e) {
            $("#add_user button[type='submit']").removeAttr("disabled");
          })
    </script>
@endsection
