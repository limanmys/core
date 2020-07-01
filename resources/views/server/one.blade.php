@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">{{__("Sunucular")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$server->name}}</li>
        </ol>
    </nav>

    <div class="row mb-2 serverName">
        <div class="col-auto align-self-center">
            @if($favorite)
                <button onclick="favorite('false')" class="btn btn-warning fas fa-star btn-sm" data-toggle="tooltip" title="Favorilerden Çıkar"></button>
            @else
                <button onclick="favorite('true')" class="btn btn-success far fa-star btn-sm" data-toggle="tooltip" title="Favorilere Ekle"></button>
            @endif
        </div>
        <div class="col-auto align-self-center">
            <h5>{{$server->name}}</h5>
        </div>
    </div>

    @include('errors')

    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('Sunucu Bilgileri') }}</h3>
            </div>
            <div class="card-body">
                <strong>{{ __('Hostname') }}</strong>
                <p class="text-muted">{{$outputs["hostname"]}}</p>
                <hr>
                <strong>{{ __('İşletim Sistemi') }}</strong>
                <p class="text-muted">{{$outputs["version"]}}</p>
                <hr>
                <strong>{{ __('IP Adresi') }}</strong>
                <p class="text-muted">
                    {{ $server->ip_address }}
                </p>
                <hr>
                <strong>{{ __('Şehir') }}</strong>
                <p class="text-muted">
                    {{ cities($server->city) }}
                </p>
                <hr>
                <strong>{{ __('Eklenti Durumları') }}</strong>
                <p class="text-muted">
                    @if($installed_extensions->count() > 0)
                        @foreach($installed_extensions as $extension)
                            <span 
                                class="badge btn-secondary status_{{$extension->id}}"
                                style="cursor:pointer;font-size: 18px; margin-bottom: 5px;"
                                onclick="window.location.href = '{{route('extension_server',["extension_id" => $extension->id, "city" => $server->city, "server_id" => $server->id])}}'">
                                {{$extension->display_name}}
                            </span>
                        @endforeach
                    @else
                        {{__("Yüklü eklenti yok.")}}
                    @endif
                </p>
                <hr>
            </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-tabs" role="tablist">
                        @php($firstRendered = false)
                        @if(server()->type == "linux_ssh" || server()->type == "linux_certificate")
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="pill" href="#usageTab" role="tab" aria-selected="true">{{__("Sistem Durumu")}}</a>
                            </li>
                            @php($firstRendered = true)
                        @endif
                        <li class="nav-item">
                            <a class="nav-link @if(!$firstRendered) active @endif" data-toggle="pill" href="#extensionsTab" role="tab">{{__("Eklentiler")}}</a>
                        </li>
                        @if(server()->type == "linux_ssh" || server()->type == "linux_certificate")
                            @if(\App\Permission::can(user()->id,'liman','id','server_services'))
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" onclick="getServices()" href="#servicesTab" role="tab">{{__("Servisler")}}</a>
                                </li>
                            @endif
                            @if(server()->type == "linux_ssh" || server()->type == "linux_certificate")
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" onclick="getPackages()" href="#packagesTab" role="tab">{{__("Paketler")}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" onclick="getUpdates()" href="#updatesTab" role="tab">
                                        {{__("Güncellemeler")}}
                                        <small class="badge bg-danger updateCount" style="display:none;margin-left: 5px;">0</small>
                                    </a>
                                </li>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                                      {{ __('Kullanıcı İşlemleri') }} <span class="caret"></span>
                                    </a>
                                    <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 40px, 0px);">
                                        <a class="dropdown-item" href="#usersTab" onclick="getLocalUsers()" data-toggle="tab">{{__("Yerel Kullanıcılar")}}</a>
                                        <a class="dropdown-item" href="#groupsTab" onclick="getLocalGroups()" data-toggle="tab">{{__("Yerel Gruplar")}}</a>
                                        <a class="dropdown-item" href="#sudoersTab" onclick="getSudoers()" data-toggle="tab">{{__("Yetkili Kullanıcılar")}}</a>
                                    </div>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" onclick="getOpenPorts()" href="#openPortsTab" role="tab">{{__("Açık Portlar")}}</a>
                                </li>
                            @endif
                        @endif
                        @if(\App\Permission::can(user()->id,'liman','id','view_logs'))
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="pill" href="#logsTab" onclick="getLogs()" role="tab">{{__("Erişim Kayıtları")}}</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#settingsTab" role="tab">{{__("Sunucu Ayarları")}}</a>
                        </li>
                        @if(server()->type == "linux" || server()->type == "windows")
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="pill" href="#extraTab" role="tab">{{__("Ek Özellikler")}}</a>
                            </li>
                        @endif
                        {!! serverModuleButtons() !!}
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @if(server()->type == "linux_ssh" || server()->type == "linux_certificate")
                            <div class="tab-pane fade show active" id="usageTab" role="tabpanel">
                                    <h4>{{__("Kaynak Kullanımı")}}</h4>
                                    <div class="row">
                                        <div class="col-md-4">
                                        <h5>{{__("Cpu Kullanımı")}}</h5>
                                        <span id="cpuText" style="text-align: center;font-weight: bold">
                                            {{__("Yükleniyor...")}}
                                        </span><br>
                                        <canvas id="cpu" width="400px" height="200px" style="float:left"></canvas>
                                        </div>
                                        <div class="col-md-4">
                                        <h5>{{__("Disk Kullanımı")}}</h5>
                                        <span id="diskText" style="text-align: center;font-weight: bold">
                                            {{__("Yükleniyor...")}}
                                        </span><br>
                                        <canvas id="disk" width="400px" height="200px" style="float:left"></canvas>
                                        </div>
                                        <div class="col-md-4">
                                        <h5>{{__("Ram Kullanımı")}}</h5>
                                        <span id="ramText" style="text-align: center;font-weight: bold">
                                            {{__("Yükleniyor...")}}
                                        </span><br>
                                        <canvas id="ram" width="400px" height="200px" style="float:left;"></canvas>
                                        </div>
                                    </div>
                                    <hr>
                            </div>
                        @endif
                        <div class="tab-pane fade show @if(!$firstRendered) active @endif" id="extensionsTab" role="tabpanel">
                            @if(auth()->user()->id == server()->user_id || auth()->user()->isAdmin())
                                <button class="btn btn-success" data-toggle="modal" data-target="#install_extension"><i
                                            data-toggle="tooltip" title="Ekle"
                                            class="fa fa-plus"></i></button>
                                <button onclick="removeExtension()" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i>
                                </button><br><br>
                            @endif
                            @include('table',[
                                "id" => "installed_extensions",
                                "value" => $installed_extensions,
                                "title" => [
                                    "Eklenti Adı" , "Versiyon", "Düzenlenme Tarihi", "*hidden*"
                                ],
                                "display" => [
                                    "name" , "version", "updated_at","id:extension_id"
                                ],
                                "noInitialize" => "true"
                            ])
                            <?php
                            $input_extensions = [];
                            foreach ($available_extensions as $extension) {
                                $arr = [];
                                if (isset($extension->install)) {
                                    foreach (
                                        $extension->install
                                        as $key => $parameter
                                    ) {
                                        $arr[$parameter["name"]] =
                                            $key . ":" . $parameter["type"];
                                    }
                                }
                                $arr[
                                    $extension->display_name .
                                        ":" .
                                        $extension->id
                                ] = "extension_id:hidden";
                                $input_extensions[] = [
                                    "name" => $extension->display_name,
                                    "id" => $extension->id,
                                ];
                            }
                            ?>
                        </div>
                            {!! serverModuleViews() !!}
                        <div class="tab-pane fade show" id="filesTab" role="tabpanel">
                            @include('modal-button',[
                                "class" => "btn-primary fa fa-upload",
                                "target_id" => "file_upload",
                                "text" => "Yükle"
                            ])
                            @include('modal-button',[
                                "class" => "btn-primary fa fa-download",
                                "target_id" => "file_download",
                                "text" => "İndir"
                            ])
                        </div>
                        @if($server->canRunCommand())
                            <div class="tab-pane fade show" id="servicesTab" role="tabpanel"></div>
                            <div class="tab-pane fade show right" id="updatesTab" role="tabpanel">
                                <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateAllPackages" onclick="updateAllPackages()">{{ __('Tümünü Güncelle') }}</button>
                                <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateSelectedPackages" onclick="updateSelectedPackages()">{{ __('Seçilenleri Güncelle') }}</button>
                                <div id="updatesTabTable"></div>
                            </div>

                            @if($server->isLinux())
                                    <div class="tab-pane fade show" id="packagesTab" role="tabpanel">
                                        <button type="button" data-toggle="modal" data-target="#installPackage" style="margin-bottom: 5px;" class="btn btn-success">
                                            <i class="fas fa-upload"></i> {{ __('Paket Kur') }}
                                        </button>
                                        <div id="packages">

                                        </div>
                                    </div>

                                    <div class="tab-pane fade show" id="usersTab" role="tabpanel">
                                        @include('modal-button',[
                                            "class"     =>  "btn btn-success mb-2",
                                            "target_id" =>  "addLocalUser",
                                            "text"      =>  "Kullanıcı Ekle",
                                            "icon" => "fas fa-plus"
                                        ])
                                        <div id="users"></div>
                                    </div>

                                    <div class="tab-pane fade show" id="groupsTab" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12">
                                                @include('modal-button',[
                                                    "class"     =>  "btn btn-success mb-2",
                                                    "target_id" =>  "addLocalGroup",
                                                    "text"      =>  "Grup Ekle",
                                                    "icon" => "fas fa-plus"
                                                ])
                                                <div id="groups"></div>
                                            </div>
                                            <div class="col-md-6 d-none">
                                                @include('modal-button',[
                                                    "class"     =>  "btn btn-success mb-2",
                                                    "target_id" =>  "addLocalGroupUserModal",
                                                    "text"      =>  "Kullanıcı Ekle",
                                                    "icon" => "fas fa-plus"
                                                ])
                                                <div id="groupUsers"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade show" id="sudoersTab" role="tabpanel">
                                        @include('modal-button',[
                                            "class"     =>  "btn btn-success mb-2",
                                            "target_id" =>  "addSudoers",
                                            "text"      =>  "Tam Yetkili Kullanıcı Ekle",
                                            "icon" => "fas fa-plus"
                                        ])
                                        <div id="sudoers"></div>
                                    </div>
                            @endif
                        @endif
                        <div class="tab-pane fade show" id="logsTab" role="tabpanel">
                            <div class="form-group">
                                    <label>{{__('Arama Terimi')}}</label>
                                    <div class="input-group">
                                        <input id="logQueryFilter" type="text" class="form-control" placeholder="{{__('Arama Terimi')}}">
                                        <span class="input-group-append">
                                            <button type="button" class="btn btn-primary btn-flat" onclick="getLogs()"><i class="fa fa-search" aria-hidden="true"></i></button>
                                        </span>
                                    </div>
                                </div>
                            <div id="logsWrapper">
                            </div>
                        </div>
                        <div class="tab-pane fade show" id="openPortsTab" role="tabpanel"> 
                        </div>
                        
                        <div class="tab-pane fade show" id="settingsTab" role="tabpanel">
                            <table class="notDataTable" style="width: 900px;">
                                <tr>
                                    <td>
                                        <form id="edit_form" onsubmit="return request('{{route('server_update')}}',this,reload)" target="#">
                                            <h5>{{__("Sunucu Adı")}}</h5>
                                            <input type="text" name="name" placeholder="Sunucu Adı" class="form-control " required=""
                                                    value="{{server()->name}}"><br>
                                            <h5>{{__("Kontrol Portu")}}</h5>
                                            <input type="number" name="control_port" placeholder="Kontrol Portu" class="form-control "
                                                    required="" value="{{server()->control_port}}"><br>
                                            <h5>{{__("Ip Adresi")}}</h5>
                                            <input type="text" name="ip_address" placeholder="Ip Adresi" class="form-control "
                                                    required="" value="{{server()->ip_address}}"><br>
                                            <h5>{{__("Şehir")}}</h5>
                                            <select name="city" class="form-control" required="">
                                                @foreach(cities() as $city=>$value)
                                                    <option value="{{$value}}" @if($value == server()->city) selected @endif>{{$city}}</option>
                                                @endforeach
                                            </select>
                                    </td>
                                    <td style="width:300px;text-align:center;padding-left:60px">
                                        <button type="submit" class="btn btn-success btn-block">{{__("Bilgileri Güncelle")}}</button><br><br>
                                        @include('modal-button',[
                                            "class" => "btn-danger btn-block",
                                            "target_id" => "delete",
                                                "text" => "Sunucuyu Sil"
                                        ])
                                        </form>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="tab-pane fade show" id="extraTab" role="tabpanel">
                            @if(server()->type == "linux")
                                <p>{{__("Linux Sunucunuza SSH anahtarı ekleyerek Liman üzerindeki ekstra özelliklere erişebilirsiniz.")}}</p>
                            @elseif(server()->type == "windows")
                                <p>{{__("Windows Sunucunuza WinRM anahtarı ekleyerek Liman üzerindeki ekstra özelliklere erişebilirsiniz.")}}</p>
                            @endif
                            <form id="upgrade_form" onsubmit="return request('{{route('server_upgrade')}}',this,reload,errorSwal)" target="#">
                                    <h5>{{__("Kullanıcı Adı")}}</h5>
                                    <input type="text" name="username" placeholder="{{__("Kullanıcı Adı")}}" class="form-control " required=""
                                            value=""><br>
                                    <h5>{{__("Parola")}}</h5>
                                    <input type="password" name="password" placeholder="{{__("Parola")}}" class="form-control "
                                            required="" value=""><br>
                                    <button type="submit" class="btn btn-success">{{__("Yükselt")}}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @component('modal-component',[
        "id"=>"installPackage",
        "title" => "Paket Kur",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "installPackageButton()",
            "text" => "Kur"
        ],
    ])
    <ul class="nav nav-pills" role="tablist" style="margin-bottom: 15px;">
        <li class="nav-item">
            <a class="nav-link active" href="#fromRepo" data-toggle="tab">{{__("Depodan Yükle")}}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#fromDeb" data-toggle="tab">{{__("Paket Yükle (.deb)")}}</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="fromRepo" role="tabpanel">
            @include('inputs', [
                'inputs' => [
                    "Paketin Adı" => "package:text:Paketin depodaki adını giriniz. Örneğin: chromium",
                ]
            ])
        </div>
        <div class="tab-pane fade show" id="fromDeb" role="tabpanel">
            @include('file-input', [
                'title' => 'Deb Paketi',
                'name' => 'debUpload',
                'callback' => 'onDebUploadSuccess'
            ])
        </div>
    </div>
    @endcomponent

    @include('modal',[
        "id"=>"delete",
        "title" => "Sunucuyu Sil",
        "url" => route('server_remove'),
        "text" => "$server->name isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "type" => "danger",
        "next" => "redirect",
        "submit_text" => "Sunucuyu Sil"
    ])

    @include('modal',[
        "id"=>"delete_extensions",
        "title" => "Eklentileri Sil",
        "text" => "Seçili eklentileri silmek istediğinize emin misiniz?",
        "type" => "danger",
        "onsubmit" => "removeExtensionFunc",
        "submit_text" => "Eklentileri Sil"
    ])

    @include('modal',[
        "id"=>"addLocalUser",
        "title" => "Kullanıcı Ekle",
        "url" => route('server_add_local_user'),
        "next" => "reload",
        "inputs" => [
            "İsim" => "user_name:text",
            "Şifre" => "user_password:password",
            "Şifre Onayı" => "user_password_confirmation:password"
        ],
        "submit_text" => "Kullanıcı Ekle"
    ])

    @include('modal',[
        "id"=>"addSudoers",
        "title" => "Tam Yetkili Kullanıcı Ekle",
        "url" => route('server_add_sudoers'),
        "next" => "getSudoers",
        "inputs" => [
            "İsim" => "name:text:Grup veya kullanıcı ekleyebilirsiniz. Örneğin: kullaniciadi veya %grupadi"
        ],
        "submit_text" => "Grup Ekle"
    ])

    @include('modal',[
        "id"=>"addLocalGroup",
        "title" => "Grup Ekle",
        "url" => route('server_add_local_group'),
        "next" => "reload",
        "inputs" => [
            "İsim" => "group_name:text"
        ],
        "submit_text" => "Grup Ekle"
    ])

    @component('modal-component',[
        "id"=>"addLocalGroupUserModal",
        "title" => "Gruba Kullanıcı Ekle",
        "submit_text" => "Ekle",
        "footer" => [
            "class" => "btn-success",
            "onclick" => "addLocalGroupUser()",
            "text" => "Ekle"
        ],
    ])
        @include('inputs', [
            "inputs" => [
                "İsim" => "user:text"
            ],
        ])
    @endcomponent


    @include('modal',[
        "id"=>"startService",
        "title" => "Servisi Başlat",
        "text" => "Seçili servisi başlatmak istediğinize emin misiniz?",
        "type" => "danger",
        "next" => "reload",
        "inputs" => [
            "name:-" => "name:hidden",
        ],
        "url" => route('server_start_service'),
        "submit_text" => "Servisi Başlat"
    ])

    @include('modal',[
        "id"=>"stopService",
        "title" => "Servisi Durdur",
        "text" => "Seçili servisi durdurmak istediğinize emin misiniz?",
        "type" => "danger",
        "next" => "reload",
        "inputs" => [
            "name:-" => "name:hidden",
        ],
        "url" => route('server_stop_service'),
        "submit_text" => "Servisi Durdur"
    ])

    @include('modal',[
        "id"=>"restartService",
        "title" => "Servisi Yeniden Başlat",
        "text" => "Seçili servisi yeniden başlatmak istediğinize emin misiniz?",
        "type" => "danger",
        "next" => "reload",
        "inputs" => [
            "name:-" => "name:hidden",
        ],
        "url" => route('server_restart_service'),
        "submit_text" => "Servisi Yeniden Başlat"
    ])

    @include('modal',[
        "id"=>"file_upload",
        "title" => "Dosya Yükle",
        "url" => route('server_upload'),
        "next" => "nothing",
        "inputs" => [
            "Yüklenecek Dosya" => "file:file",
            "Yol" => "path:text",
        ],
        "submit_text" => "Yükle"
    ])

    @include('modal',[
        "id"=>"file_download",
        "onsubmit" => "downloadFile",
        "title" => "Dosya İndir",
        "next" => "",
        "inputs" => [
            "Yol" => "path:text",
        ],
        "submit_text" => "İndir"
    ])
    @include('modal-table',[
        "id" => "log_table",
        "title" => "Sunucu Logları",
        "table" => [
            "value" => [],
            "title" => [
                "Komut" , "User ID", "Tarih", "*hidden*"
            ],
            "display" => [
                "command" , "username", "created_at", "_id:_id"
            ],
            "onclick" => "logDetails"
        ]
    ])
    @if(count($input_extensions))
        @component('modal-component',[
            "id"=>"install_extension",
            "title" => "Eklenti Ekle",
            "footer" => [
                "class" => "btn-success",
                "onclick" => "server_extension()",
                "text" => "Ekle"
            ],
        ])
            @include('table', [
                "value" => $input_extensions,
                "noInitialize" => true,
                "title" => [
                    "Eklenti", "*hidden*"
                ],
                "display" => [
                    "name", "id:id"
                ]
            ])
        @endcomponent
    @else
        <script>
        $("button[data-target='#install_extension']").click(function(){
            showSwal("{{__('Seçilebilir herhangi bir eklenti bulunmamaktadır!')}}",'error',2000);
        });
        </script>
    @endif

    @component('modal-component',[
        "id" => "updateLogs",
        "title" => "Paket Yükleme Günlüğü"
    ])
        <pre style='height: 500px; font-family: "Menlo", "DejaVu Sans Mono", "Liberation Mono", "Consolas", "Ubuntu Mono", "Courier New", "andale mono", "lucida console", monospace;'>
            <code class="updateLogsBody"></code>
        </pre>
        <p class="progress-info"><p>
        <div class="progress progress-sm active">
            <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                <span class="sr-only progress-info"></span>
            </div>
        </div>
    @endcomponent

    @component('modal-component',[
        "id" => "serviceStatusModal",
        "title" => "Servis Durumu"
    ])
        <pre id="serviceStatusWrapper"></pre>
    @endcomponent

    @component('modal-component',[
        "id"=>"logDetailModal",
        "title" => "Log Detayı"
    ])
    <div class="row">
        <div class="col-4">
            <div class="list-group" role="tablist" id="logTitleWrapper">
            </div>
        </div>
        <div class="col-8">
            <div class="tab-content" id="logContentWrapper">
            </div>
        </div>
    </div>
    @endcomponent
    <script>
        customRequestData["server_id"] = '{{server()->id}}';

        $('#install_extension table').DataTable(dataTablePresets('multiple'));


        function server_extension(){
            showSwal('{{__("Okunuyor...")}}','info');

            var items = [];
            var table = $("#install_extension table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                items.push(element[2]);
            });

            if(items.length === 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error');
                return false;
            }

            var data = new FormData();
            data.append("extensions", JSON.stringify(items));

            request('{{route('server_extension')}}', data, function (response) {
                Swal.close();
                reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }


        @if(server()->type == "linux_ssh" || server()->type == "linux_certificate")
            if(location.hash !== "#updatesTab"){
                getUpdates();
                Swal.close();
            }
        @endif

        function errorSwal(){
            showSwal('{{__("Ayarlarınız doğrulanamadı!")}}','error',2000);
        }

        function checkStatus(id) {
            var data = new FormData();
            if (!id) {
                return false;
            }
            data.append('extension_id', id);
            request('{{route('server_check')}}', data, function (response) {
                var json = JSON.parse(response);
                var element = $(".status_" + id);
                element.removeClass('btn-secondary').removeClass('btn-danger').removeClass('btn-success').addClass(json["message"]);
            });
        }

        @if($installed_extensions->count() > 0)
            @foreach($installed_extensions as $service)
            setInterval(function () {
                checkStatus('{{$service->id}}');
            }, 3000);
            @endforeach
        @endif

        @if(server()->type == "linux_ssh" || server()->type == "windows_powershell" || server()->type == "linux_certificate")
        setInterval(function () {
            stats();
        }, 15000);

        stats();
        function updateChart(element, time, data) {
            // First, Update Text
            $("#" + element + "Text").text("%" + data);
            window[element + "Chart"].data.labels.push(time);
            window[element + "Chart"].data.datasets.forEach((dataset) => {
                dataset.data.push(data);
            });
            window[element + "Chart"].update();
        }

        function createChart(element, time, data) {
            $("#" + element + "Text").text("%" + data[0]);
            window[element + "Chart"] = new Chart($("#" + element), {
                type: 'line',
                data: {
                    datasets: [{
                        data: data,
                    }],
                    labels: [
                        time,
                    ]
                },
                options: {
                    animation: false,
                    responsive: true,
                    legend: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                min: 0,
                                max: 100
                            }
                        }]
                    },
                }
            })
        }
        var firstStats = true;
        function stats() {
            var form = new FormData();
            form.append('server_id', '{{server()->id}}');
            var time = "{{\Carbon\Carbon::now()->format("H:i:s")}}";
            request('{{route('server_stats')}}', form, function (response) {
                data = JSON.parse(response);
                if(firstStats){
                    firstStats = false;
                    createChart("ram", time, [data['ram']]);
                    createChart("cpu", time, [data['cpu']]);
                    createChart("disk", time, [data['disk']]);
                }
                updateChart("disk", data['time'], data['disk']);
                updateChart("ram", data['time'], data['ram']);
                updateChart("cpu", data['time'], data['cpu']);
            })
        }

        function downloadFile(form) {
            window.location.assign('/sunucu/indir?path=' + form.getElementsByTagName('input')[0].value + '&server_id=' + form.getElementsByTagName('input')[1].value);
            return false;
        }

        @endif
        function logDetails(element) {
            var log_id = element.querySelector('#_id').innerHTML;
            partialPageRequest("/logs/" + log_id);
        }

        function favorite(action) {
            var form = new FormData();
            form.append('server_id', '{{server()->id}}');
            form.append('action', action);
            request('{{route('server_favorite')}}', form, function (response) {
                location.reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function getSudoers(){
            $('.modal').modal('hide');
            showSwal('{{__("Okunuyor...")}}','info');

            request('{{route('server_sudoers_list')}}', new FormData(), function (response) {
                Swal.close();
                $("#sudoersTab #sudoers").html(response);
                $("#sudoersTab #sudoers table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function deleteSudoers(row){
            Swal.fire({
                title: "{{ __('Onay') }}",
                text: "{{ __('Yetkili kullanıcıyı silmek istediğinizden emin misiniz?') }}",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: "{{ __('İptal') }}",
                confirmButtonText: "{{ __('Sil') }}"
            }).then((result) => {
                if (result.value) {
                    showSwal('{{__("Yükleniyor...")}}','info');
                    var data = new FormData();
                    data.append('name',$(row).find("#name").text());
                    
                    request('{{route('server_delete_sudoers')}}',data,function(response){
                        Swal.close();
                        getSudoers();
                    }, function(response){
                        var error = JSON.parse(response);
                        showSwal(error.message,'error',2000);
                    });
                }
            });
        }

        function getLocalUsers(){
            showSwal('{{__("Okunuyor...")}}','info');

            request('{{route('server_local_user_list')}}', new FormData(), function (response) {
                Swal.close();
                $("#usersTab #users").html(response);
                $("#usersTab #users table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function getLocalGroups(){
            showSwal('{{__("Okunuyor...")}}','info');

            request('{{route('server_local_group_list')}}', new FormData(), function (response) {
                Swal.close();
                $("#groupsTab #groups").html(response);
                $("#groupsTab #groups table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        var activeLocalGroup = "";
        var activeLocalGroupElement = "";

        function localGroupDetails(element){
            $('#groups').closest('.col-md-12').removeClass("col-md-12").addClass('col-md-6');
            $('#groupUsers').closest('.col-md-6').removeClass('d-none');
            $(element).parent().find('tr').css('fontWeight','normal');
            $(element).parent().find('tr').css('backgroundColor','');
            $(element).css('backgroundColor','#b0bed9');
            $(element).css('fontWeight','bolder');
            showSwal('{{__("Okunuyor...")}}','info');
            var group = element.querySelector('#group').innerHTML;
            activeLocalGroup = group;
            activeLocalGroupElement = element;
            var data = new FormData();
            data.append('group', group);

            request('{{route('server_local_group_users_list')}}', data, function (response) {
                Swal.close();
                $("#groupsTab #groupUsers").html(response);
                $("#groupsTab #groupUsers table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function addLocalGroupUser(){
            showSwal('{{__("Okunuyor...")}}','info');

            var form = new FormData();
            form.append('group',activeLocalGroup);
            form.append('user',$('#addLocalGroupUserModal').find("input[name=user]").val());

            request('{{route('server_add_local_group_user')}}',form,function(response){
                var json = JSON.parse(response);
                showSwal(json.message,'info',2000);
                localGroupDetails(activeLocalGroupElement);
                $('#addLocalGroupUserModal').modal('hide');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function getPackages() {
            showSwal('{{__("Okunuyor...")}}','info');
            request('{{route('server_package_list')}}', new FormData(), function (response) {
                Swal.close();
                $("#packagesTab #packages").html(response);
                $("#packagesTab #packages table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function getOpenPorts() {
            showSwal('{{__("Okunuyor...")}}','info');
            request('{{route('server_get_open_ports')}}', new FormData(), function (response) {
                var json = JSON.parse(response);
                $("#openPortsTab").html(json.message);
                $("#openPortsTab table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function statusService(element) {
            var name = element.querySelector('#name').innerHTML;
            showSwal('{{__("Okunuyor...")}}','info');
            var form = new FormData();
            form.append('name',name);
            request('{{route('server_service_status')}}', form, function (response) {
                var json = JSON.parse(response);
                $("#serviceStatusWrapper").html(json.message);
                $("#serviceStatusModal").modal('show');
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        $("#logQueryFilter").on('keyup', function (e) {
            if (e.keyCode === 13) {
                getLogs();
            }
        });
        function getLogs(page = 1) {
            showSwal('{{__("Okunuyor...")}}','info');
            var form = new FormData();
            form.append('page',page);
            var query = $("#logQueryFilter").val();
            if(query.length !== 0){
                form.append('query',query);
            }
            request('{{route('server_get_logs')}}', form, function (response) {
                var json = JSON.parse(response);
                $("#logsWrapper").html(json.message.table);
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function getLogDetails(element){
            var log_id = element.querySelector('#id').innerHTML;
            showSwal('{{__("Okunuyor...")}}','info');
            var form = new FormData();
            form.append('log_id',log_id);
            request('{{route('server_get_log_details')}}', form, function (response) {
                var json = JSON.parse(response);
                var modal = $("#logDetailModal");
                var logTitleWrapper = $("#logTitleWrapper");
                var logContentWrapper = $("#logContentWrapper");
                logTitleWrapper.html("");
                logContentWrapper.html("");
                $.each(json.message,function (index,current) {
                    current.id =  "a" + Math.random().toString(36).substr(2, 9);
                    logTitleWrapper.append("<a class='list-group-item list-group-item-action' id='"+ current.id + "_title' href='#" + current.id + "_content' data-toggle='list' role='tab' aria-controls='home'>" + current.title + "</a>");
                    logContentWrapper.append("<div class='tab-pane fade' id='" + current.id + "_content' role='tabpanel' aria-labelledby='" + current.id +"_title'><pre style='white-space:pre-wrap;'>" + current.message + "</pre></div>");
                });
                modal.modal("show");
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function getServices() {
            showSwal('{{__("Okunuyor...")}}','info');
            request('{{route('server_service_list')}}', new FormData(), function (response) {
                $("#servicesTab").html(response);
                $("#servicesTab table").DataTable(dataTablePresets('normal'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }
        var index = 0;
        var packages = [];
        var modes = {};

        function installPackageButton(){
            if($('#installPackage').find('[href="#fromRepo"]').hasClass('active')){
                console.log("repo tab");
                index = 0;
                packages = [];
                var package_name = $('#installPackage').find('input[name=package]').val();
                if(package_name){
                    packages.push(package_name);
                    modes[package_name] = "install";
                    installPackage();
                }
            }else if($('#installPackage').find('[href="#fromDeb"]').hasClass('active')){
                if(!packages.length){
                    showSwal("{{__('Lütfen önce bir deb paketi yükleyin.')}}",'error');
                    return;
                }
                index = 0;
                installPackage();
            }
        }

        function onDebUploadSuccess(upload){
            showSwal('{{__("Yükleniyor...")}}','info');
            var data = new FormData();
            data.append('filePath', upload.info.file_path);
            request('{{route('server_upload_deb')}}', data, function (response) {
                Swal.close();
                response = JSON.parse(response);
                if(response.message){
                    index = 0;
                    packages = [];
                    packages.push(response.message);
                    modes[response.message] = "install";
                }
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }


        function updateAllPackages(){
            index = 0;
            $('#updateLogs').find('.updateLogsBody').text("");
            getUpdates(function(package_list){
                var package_list_tmp = [];
                package_list.forEach(function(pkg){
                    var package_name = pkg.name.split('/')[0];
                    package_list_tmp.push(package_name);
                });
                packages = package_list_tmp;
                installPackage();
            });
        }

        function updateSelectedPackages(){
            index = 0;
            packages = [];
            var table = $("#updatesTabTable table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                packages.push(element[1].split('/')[0]);
            });
            if(packages.length === 0){
                showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error');
                return false;
            }
            installPackage();
        }

        function updateSinglePackage(row){
            index = 0;
            packages = [];
            packages.push($(row).find("#name").text().split('/')[0]);
            installPackage();
        }

        function installPackage(){
            updateProgress();
            $('#updateLogs').modal('show');
            var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
            scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
            var data = new FormData();
            data.append("package_name", packages[index]);
            if(modes[packages[index]]){
                data.append("mode", modes[packages[index]]);
            }
            $('#updateLogs').find('.updateLogsBody').append("\n"+packages[index]+" paketi kuruluyor. Lütfen bekleyin...<span id='"+packages[index]+"'></span>");
            request('{{route('server_install_package')}}', data, function (response) {
                checkPackage();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function updateProgress(){
            $('#updateLogs').find('.progress-info').text(index+"/"+packages.length+" "+packages[index]+" paketi kuruluyor...");
            var percent = (index/packages.length)*100;
            $('div[role=progressbar]').attr('aria-valuenow', percent);
            $('div[role=progressbar]').attr('style', 'width: '+percent+'%');
            if(packages.length !== index){
                $('div[role=progressbar]').closest('.progress').addClass('active');
            }else{
                $('div[role=progressbar]').closest('.progress').removeClass('active');
                $('#updateLogs').find('.progress-info').text("Tüm işlemler bitti.");
            }

        }

        function checkPackage(){
            var data = new FormData();
            data.append("package_name", packages[index]);
            if(modes[packages[index]]){
                data.append("mode", modes[packages[index]]);
            }
            request('{{route('server_check_package')}}', data, function (response) {
                response = JSON.parse(response);
                if(response.message.output){
                    $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.output);
                    var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                    scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
                }
                $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.status);
                var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
                index++;
                if(packages.length !== index){
                    installPackage();
                }else{
                    updateProgress();
                    getUpdates();
                    $('#updateLogs').find('.updateLogsBody').append("\n"+"Tüm işlemler bitti.");
                }
            }, function(response){
                response = JSON.parse(response);
                if(response.message.output){
                    $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.output);
                    var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                    scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
                }
                setTimeout(function(){
                    checkPackage();
                },5000);
            });
        }

        function getUpdates(getList) {
            showSwal('{{__("Okunuyor...")}}','info');
            request('{{route('server_update_list')}}', new FormData(), function (response) {
                var updates = JSON.parse(response);
                if(getList){
                    getList(updates.list);
                }
                $('.updateCount').text(updates.count);
                if(updates.count>0){
                    $('.updateCount').show();
                    $('.updateAllPackages').show();
                    $('.updateSelectedPackages').show();
                }else{
                    $('.updateCount').hide();
                    $('.updateAllPackages').hide();
                    $('.updateSelectedPackages').hide();
                }
                $("#updatesTabTable").html(updates.table);
                $("#updatesTabTable table").DataTable(dataTablePresets('multiple'));
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            })
        }

        function removeExtension(){
            var data = [];
            var table = $("#installed_extensions").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[4]);
            });
            if(data.length === 0){
                showSwal('{{__("Lütfen önce seçim yapınız.")}}','error',2000);
                return false;
            };
            $("#delete_extensions").modal('show');
        }

        function removeExtensionFunc() {
          var data = [];
          var table = $("#installed_extensions").DataTable();
          table.rows( { selected: true } ).data().each(function(element){
              data.push(element[4]);
          });
          if(data.length === 0){
              showSwal('{{__("Lütfen önce seçim yapınız.")}}','error',2000);
              return false;
          }
          showSwal('{{__("Siliniyor...")}}','info');
          var form = new FormData();
          form.append('extensions',JSON.stringify(data));
          request('{{route('server_extension_remove')}}', form, function (response) {
              var json = JSON.parse(response);
              showSwal(json["message"],'success',2000);
              setTimeout(function () {
                      location.reload();
              },2000);
          }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
          });
          return false;
        }

        $(function () {
            $("#installed_extensions").DataTable(dataTablePresets('multiple'));
        });

        
    </script>
@endsection
