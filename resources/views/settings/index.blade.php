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
                        $("#servers table").DataTable({
                            bFilter: true,
                            select: {
                                style: 'multi'
                            },
                            "language" : {
                                url : "/turkce.json"
                            }
                        });
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
                    <button type="button" onclick="saveLDAPConf()" class="btn btn-primary">{{ __('Kaydet') }}</button>
                    @if(config('ldap.ldap_host', false))
                        <h5 class="mt-4 mb-2">{{ __('Domain Grup ve Rol Grup Eşleştirmeleri') }}</h5>
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
                    @endif
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

    @include('modal',[
        "id"=>"deleteRoleMapping",
        "title" =>"Eşleştirmeyi Sil",
        "url" => route('delete_role_mapping'),
        "text" => "Eşleştirmeyi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "reload",
        "inputs" => [
            "Role Mapping Id:'null'" => "role_mapping_id:hidden"
        ],
        "submit_text" => "Sertifikayı Sil"
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

    <div class="form-group">
        <label for="ldapUsername">{{ __('Kullanıcı Adı') }}</label>
        <input type="text" name="ldapUsername" class="form-control" id="ldapUsername" placeholder="{{ __('Kullanıcı Adı') }}">
    </div>

    <div class="form-group">
        <label for="ldapPassword">{{ __('Şifre') }}</label>
        <input type="password" name="ldapPassword" class="form-control" id="ldapPassword" placeholder="{{ __('Şifre') }}">
    </div>

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
        }

        function fetchDomainGroups(){
            ldapAuth(function(ldapUsername, ldapPassword){
                let data =  new FormData();
                data.append('ldapUsername', ldapUsername);
                data.append('ldapPassword', ldapPassword);
                request('{{route('fetch_domain_groups')}}', data, function (response) {
                    let json = JSON.parse(response);
                    var str = "";
                    json.message.forEach(function(item){
                        str += "<option value='" + item.id + "'>" + item.dn + "</option>";
                    });
                    $('#addRoleMapping').find('select[name=dn]').html(str);
                    $('#addRoleMapping').find('select[name=dn]').change();
                }, function(response){
                    let error = JSON.parse(response);
                    Swal.fire({
                        type: 'error',
                        title: error.message,
                        timer : 2000
                    });
                });
            });
        }

        function addRoleMapping(){
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kaydediliyor...")}}',
                showConfirmButton: false,
            });
            let data = new FormData();
            data.append('dn', $('#addRoleMapping').find('select[name=dn]').val());
            data.append('role_id', $('#addRoleMapping').find('select[name=role_id]').val());
            request("{{route("add_role_mapping")}}", data, function(res) {
                let response = JSON.parse(res);
                Swal.close();
                Swal.fire({
                    position: 'center',
                    type: 'success',
                    title: response.message,
                });
                reload();
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            });
        }

        function afterUserAdd(response) {
            let json = JSON.parse(response);
            $("#add_user button[type='submit']").attr("disabled","true")
            getUserList();
        }

        function getUserList(){
            $('.modal').modal('hide');
            request('{{route('get_user_list_admin')}}', new FormData(), function (response) {
                $("#usersTable").html(response);
                $('#usersTable table').DataTable({
                    bFilter: true,
                    select: {
                        style: 'multi'
                    },
                    "language" : {
                        url : "/turkce.json"
                    }
                });
            });
        }

        function getRoleList(){
            $('.modal').modal('hide');
            request('{{route('role_list')}}', new FormData(), function (response) {
                $("#rolesTable").html(response);
                $('#rolesTable table').DataTable({
                    bFilter: true,
                    "language" : {
                        url : "/turkce.json"
                    }
                });
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
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Okunuyor...")}}',
                showConfirmButton: false,
            });
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
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kaydediliyor...")}}',
                showConfirmButton: false,
            });
            let data = new FormData();
            data.append('ldapAddress', $('input[name=ldapAddress]').val());
            request("{{route("save_ldap_conf")}}", data, function(res) {
                let response = JSON.parse(res);
                Swal.close();
                Swal.fire({
                    position: 'center',
                    type: 'success',
                    title: response.message,
                });
                reload();
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            });
        }

        $('#add_user').on('shown.bs.modal', function (e) {
            $("#add_user button[type='submit']").removeAttr("disabled");
          })
    </script>
@endsection
