@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Ayarlar")}}</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-tabs" role="tabpanel">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#users" aria-selected="true">{{__("Kullanıcı Ayarları")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#roles" onclick="getRoleList()" aria-selected="true">{{__("Rol Grupları")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#serverGroups" aria-selected="true">{{__("Sunucu Grupları")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#certificates" >{{__("Sertifikalar")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#health" onclick="checkHealth()">{{__("Sağlık Durumu")}}</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#update">{{__("Güncelleme")}}</a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#changeLog">{{__("Son Değişiklikler")}}</a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#rsyslog" onclick="readLogs()">{{__("Log Yönetimi")}}</a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#externalNotifications" onclick="">{{__("Dış Bildirimler")}}</a>
                </li>  -->
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#restrictedMode" onclick="">{{__("Kısıtlı Mod")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#limanMarket" onclick="checkMarketAccess()">{{__("Liman Market")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#dnsSettings" onclick="getDNS()">{{__("DNS Ayarları")}}</a>
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
                            "value" => \App\User::all(),
                            "title" => [
                                "Kullanıcı Adı" , "Email" , "*hidden*" ,
                            ],
                            "display" => [
                                "name" , "email", "id:user_id" ,
                            ],
                            "menu" => [
                                "Parolayı Sıfırla" => [
                                    "target" => "passwordReset",
                                    "icon" => "fa-lock"
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
                    <button class="btn btn-success" onclick="location.href = '{{route('certificate_add_page')}}'"><i
                        class="fa fa-plus"></i> {{__("Sertifika Ekle")}}</button>
                    <br><br>
                    @include('table',[
                        "value" => \App\Certificate::all(),
                        "title" => [
                            "Sunucu Adresi" , "Servis" , "*hidden*" ,
                        ],
                        "display" => [
                            "server_hostname" , "origin", "id:certificate_id" ,
                        ],
                        "menu" => [
                            "Güncelle" => [
                                "target" => "updateCertificate",
                                "icon" => " context-menu-icon-update"
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
                            let status = $("#marketStatus");
                            $("#marketTableWrapper").fadeOut(0);
                            status.html("Market bağlantısı kontrol ediliyor...");
                            status.attr("class","alert alert-secondary");
                            request("{{route('verify_market')}}",new FormData(),function(success){
                                let json = JSON.parse(success);
                                $("#marketLoading").fadeOut(0);
                                $("#marketDisabled").fadeOut(0);
                                $("#marketEnabled").fadeIn();
                                status.html(json.message);
                                status.attr("class","alert alert-success");
                                setTimeout(() => {
                                    checkMarketUpdates();
                                }, 1000);
                            },function(error){
                                let json = JSON.parse(error);
                                $("#marketLoading").fadeOut(0);
                                $("#marketEnabled").fadeOut(0);
                                $("#marketDisabled").fadeIn();
                                status.html(json.message);
                                status.attr("class","alert alert-danger");
                            });
                        }

                        function checkMarketUpdates(){
                            let status = $("#marketStatus");
                            status.html("Güncellemeler kontrol ediliyor...");
                            status.attr("class","alert alert-secondary");
                            $("#marketLoading").fadeIn(0);
                            request("{{route('check_updates_market')}}",new FormData(),function(success){
                                let json = JSON.parse(success);
                                let table = $("#marketTable").DataTable();
                                let counter = 1;
                                table.clear();
                                $.each(json.message,function (index,current) {
                                    let row = table.row.add([
                                        counter++, current["packageName"], current["currentVersion"], current["status"]
                                    ]).draw().node();
                                });
                                table.draw();
                                status.html("Güncellemeler başarıyla kontrol edildi...");
                                status.attr("class","alert alert-success");
                                $("#marketLoading").fadeOut(0);
                                $("#marketTableWrapper").fadeIn(0);
                            },function(error){
                                let json = JSON.parse(error);
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
                        <input type="text" name="dns1" id="dns1" class="form-control">
                        <label>{{__("Alternatif DNS Sunucusu")}}</label>
                        <input type="text" name="dns2" id="dns2" class="form-control">
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

                <div class="tab-pane fade show" id="changeLog" role="tabpanel">
                    <ul>
                        @foreach (explode("\n",$changelog) as $line)
                        <li>{{$line}}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="tab-pane fade show" id="restrictedMode" role="tabpanel">
                    <p>{{__("Liman'ı kısıtlamak ve kullanıcıların yalnızca bir eklentiyi kullanması için bu modu kullanabilirsiniz. Bu modu kullandığınız taktirde, kullanıcılar varsayılan olarak eklenti ve sunucu yetkisine sahip olacak, ancak fonksiyon yetkilerine sahip olmayacaklardır. Yöneticiler mevcut liman arayüzünü görmeye devam edecek, kullanıcılar ise yalnızca eklenti çerçevesini görüntüleyebilecektir.")}}</p>
                    <form onsubmit="return saveRestricted(this);">
                        <div class="form-check">
                            <input name="LIMAN_RESTRICTED" type="checkbox" class="form-check-input" id="rectricedModeToggle" @if(env("LIMAN_RESTRICTED")) checked @endif>
                            <label class="form-check-label" for="rectricedModeToggle">{{__("Kısıtlı Modu Aktifleştir.")}}</label>
                        </div><br>

                        <div class="form-group">
                            <label for="restrictedServer">{{__("Gösterilecek Sunucu")}}</label>
                            <select name="LIMAN_RESTRICTED_SERVER" id="restrictedServer" class="form-control select2" required>
                                <option value="" disabled selected>{{__('Lütfen bir sunucu seçin.')}}</option>
                                        @foreach(servers() as $server)
                                            <option value="{{$server->id}}" @if(env("LIMAN_RESTRICTED_SERVER") == $server->id) selected @endif>{{$server->name}}</option>
                                        @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                        <label for="restrictedExtension">{{__("Gösterilecek Eklenti")}}</label>
                            <select name="LIMAN_RESTRICTED_EXTENSION" id="restrictedExtension" class="form-control select2" required>
                                <option value="" disabled selected>{{__('Lütfen bir eklenti seçin.')}}</option>
                                        @foreach(extensions() as $extension)
                                            <option value="{{$extension->id}}" @if(env("LIMAN_RESTRICTED_EXTENSION") == $extension->id) selected @endif>{{$extension->display_name}}</option>
                                        @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">{{__("Ayarları Kaydet")}}</button>
                    </form>
                    <script>
                        function saveRestricted(form){
                            return request('{{route("restricted_mode_update")}}',form,function(success){
                                let json = JSON.parse(success);
                                showSwal(json.message,'success');
                                setTimeout(() => {
                                    reload();
                                }, 2000);
                            },function(error){
                                let json = JSON.parse(error);
                                showSwal(json.message,'danger',2000);
                            });
                        }
                    </script>
                </div>

                <div class="tab-pane fade show" id="externalNotifications" role="tabpanel">
                @include('modal-button',[
                        "class" => "btn-primary",
                        "target_id" => "addNewNotificationSource",
                        "text" => "Yeni İstemci Ekle"
                    ])<br><br>
                    @include('table',[
                            "value" => \App\ExternalNotification::all(),
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
                        <div class="form-row">
                        <div class="form-group col-md-2">
                                <label for="logInterval">{{__("Log Gönderme Aralığı (Dakika)")}}</label>
                                <input type="number" class="form-control" name="logInterval" value="10" id="logInterval">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">{{__("Ayarları Kaydet")}}</button>
                    </form>
                </div>
                <div class="tab-pane fade show" id="serverGroups" role="tabpanel">
                @include('modal-button',[
                        "class" => "btn-success",
                        "target_id" => "addServerGroup",
                        "text" => "Sunucu Grubu Ekle"
                ])<br><br>

                <p>{{__("Sunucuları bir gruba ekleyerek eklentiler arası geçişi daha akıcı yapabilirsiniz.")}}</p>
                @include('table',[
                            "value" => \App\ServerGroup::all(),
                            "title" => [
                                "Adı", "*hidden*" , "*hidden*"
                            ],
                            "display" => [
                                "name" , "id:server_group_id" , "servers:servers"
                            ],
                            "menu" => [
                                "Düzenle" => [
                                    "target" => "modifyServerGroupHandler",
                                    "icon" => " context-menu-icon-edit"
                                ],
                                "Sil" => [
                                    "target" => "deleteServerGroup",
                                    "icon" => " context-menu-icon-delete"
                                ]
                            ],
                        ])
                </div>
            </div>
        </div>
    </div>
    
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
            "Adı" => "name:text",
            "E-mail Adresi" => "email:email",
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
        "id"=>"add_role",
        "title" => "Rol Grubu Ekle",
        "url" => route('role_add'),
        "next" => "getRoleList",
        "inputs" => [
            "Adı" => "name:text"
        ],
        "submit_text" => "Ekle"
    ])

    @include('modal',[
       "id"=>"delete",
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
        "id"=>"deleteServerGroup",
        "title" =>"Sunucu Grubunu Sil",
        "url" => route('delete_server_group'),
        "text" => "Sunucu grubunu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "reload",
        "inputs" => [
            "-:-" => "server_group_id:hidden"
        ],
        "submit_text" => "Sunucu Grubunu Sil"
    ])




    @component('modal-component',[
        "id" => "addServerGroup",
        "title" => "Sunucuları Gruplama",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "addServerGroup()",
            "text" => "Ekle"
        ],
    ])
    <div class="form-group">
        <label>{{__("Sunucu Grubu Adı")}}</label><br>
        <small>{{__("Görsel olarak hiçbir yerde gösterilmeyecektir, yalnızca düzenleme kısmındak kolay erişim için eklenmiştir.")}}</small>
        <input type="text" class="form-control" id="serverGroupName">
    </div>
    <label>{{__("Sunucular")}}</label><br>
    <small>{{__("Bu gruba eklemek istediğiniz sunucuları seçin.")}}</small>
    @include('table',[
        "id" => "serverGroupServers",
        "value" => servers(),
        "title" => [
            "Sunucu Adı" , "İp Adresi" , "*hidden*"
        ],
        "display" => [
            "name" , "ip_address", "id:server_id"
        ],
        "noInitialize" => "true"
    ])

    @endcomponent

    @component('modal-component',[
        "id" => "modifyServerGroupModal",
        "title" => "Sunucu Grubu Düzenleme",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "modifyServerGroup()",
            "text" => "Ekle"
        ],
    ])
    <div class="form-group">
        <label>{{__("Sunucu Grubu Adı")}}</label><br>
        <small>{{__("Görsel olarak hiçbir yerde gösterilmeyecektir, yalnızca düzenleme kısmındak kolay erişim için eklenmiştir.")}}</small>
        <input type="text" class="form-control" id="serverGroupNameModify">
    </div>
    <label>{{__("Sunucular")}}</label><br>
    <small>{{__("Bu gruba eklemek istediğiniz sunucuları seçin.")}}</small>
    @include('table',[
        "id" => "modifyServerGroupTable",
        "value" => servers(),
        "title" => [
            "Sunucu Adı" , "İp Adresi" , "*hidden*"
        ],
        "display" => [
            "name" , "ip_address", "id:server_id"
        ],
        "noInitialize" => "true"
    ])

    @endcomponent

    @include('modal',[
        "id"=>"deleteRoleMapping",
        "title" =>"Eşleştirmeyi Sil",
        "url" => route('delete_role_mapping'),
        "text" => "Eşleştirmeyi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "reload",
        "inputs" => [
            "Role Mapping Id:'null'" => "role_mapping_id:hidden"
        ],
        "submit_text" => "Eşleştirmeyi Sil"
    ])

    @include('modal',[
        "id"=>"addNewNotificationSource",
        "title" => "Yeni Bildirim İstemcisi Ekle",
        "url" => route('add_notification_channel'),
        "text" => "İp Adresi bölümüne izin vermek istediğiniz bir subnet adresini ya da ip adresini yazarak erişimi kısıtlayabilirsiniz. Örneğin : 192.168.1.0/24 ",
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
        "text" => "İp Adresi bölümüne izin vermek istediğiniz bir subnet adresini ya da ip adresini yazarak erişimi kısıtlayabilirsiniz. Örneğin : 192.168.1.0/24 ",
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


        
        function saveLogSystem(){
            showSwal('{{__("Kaydediliyor...")}}','info');
            let data = new FormData(document.querySelector('#logForm'));
            return request("{{route("save_log_system")}}", data, function(res) {
                let response = JSON.parse(res);
                showSwal(response.message,'success');
                reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function addRoleMapping(){
            showSwal('{{__("Kaydediliyor...")}}','info');
            let data = new FormData();
            data.append('dn', $('#addRoleMapping').find('select[name=dn]').val());
            data.append('role_id', $('#addRoleMapping').find('select[name=role_id]').val());
            request("{{route("add_role_mapping")}}", data, function(res) {
                let response = JSON.parse(res);
                showSwal(response.message,'success');
                reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function addServerGroup(){
            showSwal('{{__("Ekleniyor...")}}','info');
            let data = new FormData();
            let tableData = [];
            let table = $("#serverGroupServers").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                tableData.push(element[3]);
            });
            data.append('name', $('#serverGroupName').val());
            data.append('servers', tableData.join());
            request("{{route("add_server_group")}}", data, function(response) {
                let res = JSON.parse(response);
                showSwal(res.message,'success',2000);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }
        function modifyServerGroupHandler(row){
            let server_group_id = row.querySelector('#server_group_id').innerHTML;
            let server_ids = row.querySelector('#servers').innerHTML.split(",");
            current_server_group = server_group_id;
            $('#serverGroupNameModify').val(row.querySelector('#name').innerHTML);
            let table = $("#modifyServerGroupTable").DataTable();
            table.rows().deselect();
            table.rows().every(function(){
                let data = this.data();
                let current = this;
                if(server_ids.includes(data[3])){
                    current.select();
                }
                this.draw();
            });
            $("#modifyServerGroupModal").modal('show');
        }

        let current_server_group = null;
        function modifyServerGroup(){
            showSwal('{{__("Düzenleniyor...")}}','center');
            let data = new FormData();
            let tableData = [];
            let table = $("#modifyServerGroupTable").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                tableData.push(element[3]);
            });
            data.append('name', $('#serverGroupNameModify').val());
            data.append('servers', tableData.join());
            data.append('server_group_id',current_server_group);
            request("{{route("modify_server_group")}}", data, function(response) {
                let res = JSON.parse(response);
                showSwal(res.message,'success',2000);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function readLogs(){
            showSwal('{{__("Okunuyor...")}}','info');
            request("{{route("get_log_system")}}", new FormData(), function(res) {
                Swal.close();
                let response = JSON.parse(res);
                $("#logIpAddress").val(response["message"]["ip_address"]);
                $("#logPort").val(response["message"]["port"]);
                $("#logInterval").val(response["message"]["interval"]);
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function afterUserAdd(response) {
            let json = JSON.parse(response);
            $("#add_user button[type='submit']").attr("disabled","true")
            getUserList();
        }

        $(function () {
            $("#serverGroupServers").DataTable(dataTablePresets('multiple'));

            $("#modifyServerGroupTable").DataTable(dataTablePresets('multiple'));
        });
        function getUserList(){
            request('{{route('get_user_list_admin')}}', new FormData(), function (response) {
                $("#usersTable").html(response);
                $('#usersTable table').DataTable(dataTablePresets('multiple'));
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function getRoleList(){
            $('.modal').modal('hide');
            request('{{route('role_list')}}', new FormData(), function (response) {
                $("#rolesTable").html(response);
                $('#rolesTable table').DataTable(dataTablePresets('normal'));
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function roleDetails(row){
            let role_id = row.querySelector('#role_id').innerHTML;
            location.href = '/rol/' + role_id;
        }

        function details(row) {
            let user_id = row.querySelector('#user_id').innerHTML;
            location.href = '/ayarlar/' + user_id;
        }

        function checkHealth() {
            showSwal('{{__("Okunuyor...")}}','info');
            request("{{route('health_check')}}", new FormData(), function (success) {
                Swal.close();
                let json = JSON.parse(success);
                let box = $("#output");
                box.html("");
                console.log(json["message"]);
                for (let i = 0; i < json["message"].length; i++) {
                    let current = json["message"][i];
                    box.append("<div class='alert alert-" + current["type"] + "' role='alert'>" +
                        current["message"] +
                        "</div>");
                }

            }, function (error) {
                Swal.close();
                alert("hata");
            });
        }
        

        function saveDNS(form){
            return request('{{route('set_liman_dns_servers')}}',form, function (success){
                let json = JSON.parse(success);
                showSwal(json["message"],'success',2000);
                setTimeout(() => {
                    getDNS();
                }, 1500);
            }, function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }

        function getDNS(){
            showSwal('{{__("Okunuyor")}}','info');
            request('{{route('get_liman_dns_servers')}}',new FormData(),function(success){
                let json = JSON.parse(success);
                $("#dns1").val(json["message"][0]);
                $("#dns2").val(json["message"][1]);
                $("#dns3").val(json["message"][2]);
                Swal.close();
            },function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }


        $('#add_user').on('shown.bs.modal', function (e) {
            $("#add_user button[type='submit']").removeAttr("disabled");
          })
    </script>
@endsection
