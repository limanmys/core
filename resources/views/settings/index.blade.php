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
                </div>
            </div>
        </div>
    </div>
    
    @include('modal',[
        "id"=>"add_user",
        "title" => "Kullanıcı Ekle",
        "url" => route('user_add'),
        "next" => "after_user_add",
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
    <script>

        function after_user_add(response) {
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
