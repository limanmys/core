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
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#update">{{__("Güncelleme")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#ldapIntegration">{{__("LDAP Entegrasyonu")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#changeLog">{{__("Son Değişiklikler")}}</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#rsyslog" onclick="readLogs()">{{__("Log Yönetimi")}}</a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#externalNotifications" onclick="">{{__("Dış Bildirimler")}}</a>
                </li>
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
                <div class="tab-pane fade show" id="servers" role="tabpanel">
                    <?php
                        $servers = servers();
                        foreach ($servers as $server){
                            $server->enabled = ($server->enabled) ? __("Aktif") : __("Pasif");
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
                <div class="tab-pane fade show" id="update" role="tabpanel">
                    @php($updateOutput = shell_exec("apt list --upgradable | grep 'liman'"))
                    @if($updateOutput)
                        <pre>{{$updateOutput}}</pre>
                    @else
                        <pre>{{__("Liman Sürümünüz : " . env("APP_VERSION") . " güncel.")}}</pre>
                    @endif
                </div>
                <div class="tab-pane fade show" id="ldapIntegration" role="tabpanel">
                    <div class="form-group">
                        <label>{{ __('Ldap Sunucu Adresi') }}</label>
                        <input type="text" value="{{ env('LDAP_HOST', "") }}" name="ldapAddress" class="form-control" placeholder="{{ __('IP Adresi Girin') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Ldap Object GUID Alanı') }}</label>
                        <input type="text" value="{{ config('ldap.ldap_guid_column', 'objectguid') }}" name="ldapObjectGUID" class="form-control" placeholder="{{ __('LDAP şemanızdaki objectguid alanının adını yazın.') }}">
                        <small>{{ __('LDAP şemanızdaki objectguid alanının adını yazın.') }}</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="ldapStatus" id="ldapStatus" @if(config('ldap.ldap_status', true)) checked @endif>
                        <label class="form-check-label" for="ldapStatus">
                          {{ __('Entegrasyonu Aktifleştir') }}
                        </label>
                      </div>
                    <button type="button" onclick="saveLDAPConf()" class="btn btn-primary">{{ __('Kaydet') }}</button>
                    @if(config('ldap.ldap_host', false))
                        <ul class="nav nav-pills" role="tablist" style="margin-top: 15px;margin-bottom: 15px;">
                            <li class="nav-item">
                                <a class="nav-link active" href="#restrictions" data-toggle="tab">{{__("Giriş Kısıtlamaları")}}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#mappings" data-toggle="tab">{{__("Domain Grup ve Rol Grup Eşleştirmeleri")}}</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="restrictions" role="tabpanel">
                                @include('alert', [
                                    "title" => "Bilgilendirme",
                                    "message" => "Bu alana bir kullanıcı veya grup eklediğinizde eklediğiniz kullanıcılar dışındakiler ve eklediğiniz gruplarda olmayan kullanıcılar giriş yapamaz. Herhangi bir kısıt eklemezseniz herkes giriş yapabilir."
                                ])
                                @include('modal-button',[
                                    "class" => "btn-success mb-2",
                                    "target_id" => "addLdapRestriction",
                                    "text" => "Ekle"
                                ])
                                @include('table',[
                                    "value" => \App\LdapRestriction::all(),
                                    "title" => [
                                        "Tip" , "İsim" , "*hidden*" ,
                                    ],
                                    "display" => [
                                        "type" , "name", "id:ldap_restriction_id" ,
                                    ],
                                    "menu" => [
                                        "Sil" => [
                                            "target" => "deleteLdapRestriction",
                                            "icon" => " context-menu-icon-delete"
                                        ]
                                    ],
                                ])
                            </div>
                            <div class="tab-pane fade show" id="mappings" role="tabpanel">
                                @include('modal-button',[
                                    "class" => "btn-success mb-2",
                                    "target_id" => "addRoleMapping",
                                    "text" => "Ekle"
                                ])
                                @include('table',[
                                    "value" => \App\RoleMapping::all()->map(function($item){
                                        $item->role_name = $item->role->name;
                                        return $item;
                                    }),
                                    "title" => [
                                        "Domain Grubu" , "Rol Grubu" , "*hidden*" ,
                                    ],
                                    "display" => [
                                        "dn" , "role_name", "id:role_mapping_id" ,
                                    ],
                                    "menu" => [
                                        "Sil" => [
                                            "target" => "deleteRoleMapping",
                                            "icon" => " context-menu-icon-delete"
                                        ]
                                    ],
                                ])
                            </div>
                        </div>
                    @endif
                </div>
                <div class="tab-pane fade show" id="changeLog" role="tabpanel">
                    <ul>
                        @foreach (explode("\n",$changelog) as $line)
                        <li>{{$line}}</li>
                        @endforeach
                    </ul>
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
        "id" => "addLdapRestriction",
        "title" => "Giriş Kısıtlaması Ekle",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "addLdapRestriction()",
            "text" => "Ekle"
        ],
    ])

    <div class="form-group">
        <label>{{ __('Kısıtlama Tipi') }}</label>
        <select name="type" class="form-control" required onchange="restrictionType(this)">
            <option value="user">{{ __('Kullanıcıya İzin Ver') }}</option>
            <option value="group">{{ __('Gruba İzin Ver') }}</option>
        </select>
    </div>
    <div class="form-group" id="domainUserSelect">
        <label>{{ __('Kullanıcı Adı') }}</label>
        <div class="input-group">
            <select class="form-control select2" name="username" data-placeholder="{{ __('Kullanıcı Adı Yazınız') }}" data-tags="true">
            </select>
            <span class="input-group-append">
                <button type="button" onclick="fetchDomainUsers()" class="btn btn-primary">{{ __('LDAP\'tan Getir') }}</button>
            </span>
        </div>
    </div>
    <div class="form-group" id="domainGroupSelect" style="display:none;">
        <label>{{ __('Domain Grubu (DN)') }}</label>
        <div class="input-group">
            <select class="form-control select2" name="dn" data-placeholder="{{ __('DN Yazınız') }}" data-tags="true">
            </select>
            <span class="input-group-append">
                <button type="button" onclick="fetchDomainGroups()" class="btn btn-primary">{{ __('LDAP\'tan Getir') }}</button>
            </span>
        </div>
    </div>

    @endcomponent

    @component('modal-component',[
        "id" => "addRoleMapping",
        "title" => "Domain Grup ve Rol Grup Eşleştirmesi",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "addRoleMapping()",
            "text" => "Ekle"
        ],
    ])
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ __('Domain Grubu (DN)') }}</label>
                <div class="input-group">
                    <select class="form-control select2" name="dn" data-placeholder="{{ __('DN Yazınız') }}" data-tags="true">
                    </select>
                    <span class="input-group-append">
                        <button type="button" onclick="fetchDomainGroups()" class="btn btn-primary">{{ __('LDAP\'tan Getir') }}</button>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ __('Rol Grubu') }}</label>
                <select name="role_id" class="form-control select2" required>
                    @foreach (\App\Role::all() as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    @endcomponent


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
        "id"=>"deleteLdapRestriction",
        "title" =>"Kısıtlamayı Sil",
        "url" => route('delete_ldap_restriction'),
        "text" => "Kısıtlamayı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "reload",
        "inputs" => [
            "Ldap Restriction Id:'null'" => "ldap_restriction_id:hidden"
        ],
        "submit_text" => "Kısıtlamayı Sil"
    ])

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

    @component('modal-component',[
        "id" => "ldapAuth",
        "title" => "Ldap İle Giriş Yap",
        "notSized" => true,
        "modalDialogClasses" => "modal-dialog-centered modal-sm",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "ldapLogin()",
            "text" => "Giriş Yap"
        ],
    ])

    <form onsubmit="return ldapLogin()">
        <div class="form-group">
            <label for="ldapUsername">{{ __('Kullanıcı Adı') }}</label>
            <input type="text" name="ldapUsername" class="form-control" id="ldapUsername" placeholder="{{ __('Kullanıcı Adı') }}">
        </div>
    
        <div class="form-group">
            <label for="ldapPassword">{{ __('Şifre') }}</label>
            <input type="password" name="ldapPassword" class="form-control" id="ldapPassword" placeholder="{{ __('Şifre') }}">
        </div>
        <input type="submit" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" />
    </form>

    @endcomponent
    <script>
        
        var ldapAuthNext = null;

        function ldapAuth(next){
            ldapAuthNext = next;
            $('#ldapAuth').modal('show');
        }

        function ldapLogin(){
            let ldapUsername = $('#ldapAuth').find('input[name=ldapUsername]').val();
            let ldapPassword = $('#ldapAuth').find('input[name=ldapPassword]').val();
            if(ldapAuthNext)
                ldapAuthNext(ldapUsername, ldapPassword);
            $('#ldapAuth').find('input[name=ldapUsername]').val("");
            $('#ldapAuth').find('input[name=ldapPassword]').val("");
            $('#ldapAuth').modal('hide');
            return false;
        }

        function fetchDomainUsers(){
            ldapAuth(function(ldapUsername, ldapPassword){
                $('select[name=username]').select2({
                    theme: 'bootstrap4',
                    ajax: {
                        type: 'POST',
                        url: "{{ route('fetch_domain_users') }}",
                        dataType: 'json',
                        delay: 250,
                        headers: {
                            "X-CSRF-TOKEN" : $('meta[name=csrf-token]').attr("content"),
                        },
                        data: function (params) {
                            return {
                                query: params.term, // search term
                                ldapUsername: ldapUsername,
                                ldapPassword: ldapPassword,
                            };
                        },
                        processResults: function (data, params) {
                            return {
                                results: data.message
                            };
                        },
                        cache: true
                    },
                });
                $('select[name=username]').select2('open');
            });
        }

        function fetchDomainGroups(){
            ldapAuth(function(ldapUsername, ldapPassword){
                $('select[name=dn]').select2({
                    theme: 'bootstrap4',
                    ajax: {
                        type: 'POST',
                        url: "{{ route('fetch_domain_groups') }}",
                        dataType: 'json',
                        delay: 250,
                        headers: {
                            "X-CSRF-TOKEN" : $('meta[name=csrf-token]').attr("content"),
                        },
                        data: function (params) {
                            return {
                                query: params.term, // search term
                                ldapUsername: ldapUsername,
                                ldapPassword: ldapPassword,
                            };
                        },
                        processResults: function (data, params) {
                            return {
                                results: data.message
                            };
                        },
                        cache: true
                    },
                });
                $('select[name=dn]').select2('open');
            });
        }

        function restrictionType(element){
            if($(element).val() == "user"){
                $('#domainUserSelect').show();
                $('#domainGroupSelect').hide();
            }else if($(element).val() == "group"){
                $('#domainGroupSelect').show();
                $('#domainUserSelect').hide();
            }
        }

        function addLdapRestriction(){
            showSwal('{{__("Kaydediliyor...")}}','info');
            let data = new FormData();
            data.append('dn', $('#addLdapRestriction').find('select[name=dn]').val());
            data.append('username', $('#addLdapRestriction').find('select[name=username]').val());
            data.append('type', $('#addLdapRestriction').find('select[name=type]').val());
            request("{{route("add_ldap_restriction")}}", data, function(res) {
                let response = JSON.parse(res);
                showSwal(response.message,'success');
                reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
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

        function saveLDAPConf(){
            showSwal('{{__("Kaydediliyor...")}}','info');
            let data = new FormData();
            data.append('ldapAddress', $('input[name=ldapAddress]').val());
            data.append('ldapObjectGUID', $('input[name=ldapObjectGUID]').val());
            data.append('ldapStatus', $('input[name=ldapStatus]').prop('checked'));
            request("{{route("save_ldap_conf")}}", data, function(res) {
                let response = JSON.parse(res);
                showSwal(response.message,'success');
                reload();
            }, function(response){
                let error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        $('#add_user').on('shown.bs.modal', function (e) {
            $("#add_user button[type='submit']").removeAttr("disabled");
          })
    </script>
@endsection
