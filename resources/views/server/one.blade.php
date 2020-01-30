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
            @if(\Illuminate\Support\Facades\DB::table("user_favorites")->where(["user_id" => auth()->user()->id,"server_id" => server()->id])->exists())
                <button onclick="favorite('false')" class="btn btn-warning fas fa-star btn-sm" data-toggle="tooltip" title="Favorilerden Çıkar"></button>
            @else
                <button onclick="favorite('true')" class="btn btn-success far fa-star btn-sm" data-toggle="tooltip" title="Favorilere Ekle"></button>
            @endif
        </div>
        <div class="col-auto align-self-center">
            <h5>{{server()->name}}</h5>
        </div>
    </div>
    @include('errors')

    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ _('Sunucu Bilgileri') }}</h3>
            </div>
            <div class="card-body">
                @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                    <strong>{{ __('Hostname') }}</strong>
                    <p class="text-muted">
                        {{$hostname}}
                    </p>
                    <hr>
                    <strong>{{ __('İşletim Sistemi') }}</strong>
                    <p class="text-muted">
                        @if(server()->type == "linux_ssh")
                            {{server()->run("lsb_release -ds")}}
                        @else
                            {{ explode("|",server()->run("(Get-WmiObject Win32_OperatingSystem).name"))[0]}}
                        @endif
                    </p>
                    <hr>
                @endif
                <strong>{{ __('IP Adresi') }}</strong>
                <p class="text-muted">
                    {{ server()->ip_address }}
                </p>
                <hr>
                <strong>{{ __('Şehir') }}</strong>
                <p class="text-muted">
                    {{ cities(server()->city) }}
                </p>
                <hr>
                <strong>{{ __('Eklenti Durumları') }}</strong>
                <p class="text-muted">
                    @if($installed_extensions->count() > 0)
                        @foreach($installed_extensions as $extension)
                            <span 
                                class="badge btn-secondary status_{{$extension->id}}"
                                style="cursor:pointer;font-size: 18px; margin-bottom: 5px;"
                                onclick="location.href = '{{route('extension_server',["extension_id" => $extension->id, "city" => $server->city, "server_id" => $server->id])}}'">
                                {{$extension->name}}
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
                        @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="pill" href="#usageTab" role="tab" aria-selected="true">{{__("Sistem Durumu")}}</a>
                            </li>
                            @php($firstRendered = true)
                        @endif
                        <li class="nav-item">
                            <a class="nav-link @if(!$firstRendered) active @endif" data-toggle="pill" href="#extensionsTab" role="tab">{{__("Eklentiler")}}</a>
                        </li>
                        @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="pill" onclick="getServices()" href="#servicesTab" role="tab">{{__("Servisler")}}</a>
                            </li>
                            @if(server()->type == "linux_ssh")
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" onclick="getPackages()" href="#packagesTab" role="tab">{{__("Paketler")}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" onclick="getUpdates()" href="#updatesTab" role="tab">
                                        {{__("Güncellemeler")}}
                                        <small class="badge bg-danger updateCount" style="display:none;margin-left: 5px;">0</small>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#usersTab" role="tab">{{__("Yerel Kullanıcılar")}}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="pill" href="#groupsTab" role="tab">{{__("Yerel Gruplar")}}</a>
                                </li>
                            @endif
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#logsTab" onclick="getLogs()" role="tab">{{__("Günlük Kayıtları")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#settingsTab" role="tab">{{__("Sunucu Ayarları")}}</a>
                        </li>
                        @if(server()->type == "linux" || server()->type == "windows")
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="pill" href="#extraTab" role="tab">{{__("Ek Özellikler")}}</a>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
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
                                    foreach ($extension->install as $key => $parameter) {
                                        $arr[$parameter["name"]] = $key . ":" . $parameter["type"];
                                    }
                                }
                                $arr[$extension->name . ":" . $extension->id] = "extension_id:hidden";
                                $input_extensions[$extension->name . ":" . $extension->id] = $arr;
                            }
                            ?>
                        </div>
                        <div class="tab-pane fade show" id="terminalTab" role="tabpanel">
                            <iframe id="terminalFrame" src="" style="width: 100%;height: 600px;background: black"></iframe>
                        </div>
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
                        @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                            <div class="tab-pane fade show" id="servicesTab" role="tabpanel">
                                
                            </div>
                            <div class="tab-pane fade show right" id="updatesTab" role="tabpanel">
                                <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateAllPackages" onclick="updateAllPackages()">Tümünü Güncelle</button>
                                <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateSelectedPackages" onclick="updateSelectedPackages()">Seçilenleri Güncelle</button>
                                <div id="updatesTabTable"></div>
                            </div>
                        @endif
                        @if(server()->type == "linux_ssh")
                            <div class="tab-pane fade show" id="packagesTab" role="tabpanel">
                                
                            </div>

                            <div class="tab-pane fade show" id="usersTab" role="tabpanel">
                                <pre>{{server()->run("cut -d: -f1,3 /etc/passwd | egrep ':[0-9]{4}$' | cut -d: -f1")}}</pre>
                            </div>

                            <div class="tab-pane fade show" id="groupsTab" role="tabpanel">
                                <pre>{{server()->run("getent group | cut -d ':' -f1")}}</pre>
                            </div>
                        @endif
                        <div class="tab-pane fade show" id="logsTab" role="tabpanel">
                                
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
        "text" => "Secili eklentileri silmek istediginize emin misiniz?",
        "type" => "danger",
        "onsubmit" => "removeExtensionFunc",
        "submit_text" => "Eklentileri Sil"
    ])

    @include('modal',[
        "id"=>"startService",
        "title" => "Servisi Baslat",
        "text" => "Secili servisi baslatmak istediginize emin misiniz?",
        "type" => "danger",
        "next" => "reload",
        "inputs" => [
            "name:-" => "name:hidden",
        ],
        "url" => route('server_start_service'),
        "submit_text" => "Servisi Baslat"
    ])

    @include('modal',[
        "id"=>"stopService",
        "title" => "Servisi Durdur",
        "text" => "Secili servisi durdurmak istediginize emin misiniz?",
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
        "title" => "Servisi Yeniden Baslat",
        "text" => "Secili servisi yeniden baslatmak istediginize emin misiniz?",
        "type" => "danger",
        "next" => "reload",
        "inputs" => [
            "name:-" => "name:hidden",
        ],
        "url" => route('server_restart_service'),
        "submit_text" => "Servisi Yeniden Baslat"
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
        @include('modal',[
            "id"=>"install_extension",
            "title" => "Eklenti Ekle",
            "type" => "info",
            "url" => route('server_extension'),
            "next" => "reload",
            "selects" => $input_extensions,
            "inputs" => [
            ],
            "submit_text" => "Ekle"
        ])
    @else
        <script>
        $("button[data-target='#install_extension']").click(function(){
            Swal.fire({
                type: 'error',
                title: '{{__('Seçilebilir herhangi bir eklenti bulunmamaktadır!')}}'
            });
        });
        </script>
    @endif

    @component('modal-component',[
        "id" => "updateLogs",
        "title" => "Güncelleme Günlüğü"
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
    <script>
        @if(server()->type == "linux_ssh") //|| server()->type == "windows_powershell")
            if(location.hash !== "#updatesTab"){
                getUpdates();
                Swal.close();
            }
        @endif
        function errorSwal(){
            Swal.fire({
                position: 'center',
                type: 'error',
                title: '{{__("Ayarlarınız doğrulanamadı!")}}',
            });
        }

        function checkStatus(id) {
            let data = new FormData();
            if (!id) {
                return false;
            }
            data.append('extension_id', id);
            request('{{route('server_check')}}', data, function (response) {
                let json = JSON.parse(response);
                let element = $(".status_" + id);
                element.removeClass('btn-secondary').addClass(json["message"]);
            });
        }

        @if($installed_extensions->count() > 0)
        @foreach($installed_extensions as $service)
        setInterval(function () {
            checkStatus('{{$service->id}}');
        }, 3000);

        @endforeach
        @endif
        @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
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
        let firstStats = true;
        function stats() {
            let form = new FormData();
            form.append('server_id', '{{server()->id}}');
            let time = "{{\Carbon\Carbon::now()->format("H:i:s")}}";
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
            let log_id = element.querySelector('#_id').innerHTML;
            window.location.href = "/logs/" + log_id
        }

        function favorite(action) {
            let form = new FormData();
            form.append('server_id', '{{server()->id}}');
            form.append('action', action);
            request('{{route('server_favorite')}}', form, function (response) {
                location.reload();
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            })
        }

        function getPackages() {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Okunuyor...")}}',
                showConfirmButton: false,
            });
            request('{{route('server_package_list')}}', new FormData(), function (response) {
                Swal.close();
                $("#packagesTab").html(response);
                $("#packagesTab table").DataTable({
                    bFilter: true,
                    "language": {
                        url: "/turkce.json"
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            })
        }

        function getLogs() {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Okunuyor...")}}',
                showConfirmButton: false,
            });
            request('{{route('server_get_logs')}}', new FormData(), function (response) {
                $("#logsTab").html(response);
                $("#logsTab table").DataTable({
                    bFilter: true,
                    "language": {
                        url: "/turkce.json"
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            })
        }

        function getServices() {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Okunuyor...")}}',
                showConfirmButton: false,
            });
            request('{{route('server_service_list')}}', new FormData(), function (response) {
                $("#servicesTab").html(response);
                $("#servicesTab table").DataTable({
                    bFilter: true,
                    "language": {
                        url: "/turkce.json"
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            })
        }
        let index = 0;
        let packages = [];
        function updateAllPackages(){
            index = 0;
            $('#updateLogs').find('.updateLogsBody').text("");
            getUpdates(function(package_list){
                let package_list_tmp = [];
                package_list.forEach(function(package){
                    let package_name = package.name.split('/')[0];
                    package_list_tmp.push(package_name);
                });
                packages = package_list_tmp;
                updatePackage();
            });
        }

        function updateSelectedPackages(){
            index = 0;
            packages = [];
            let table = $("#updatesTabTable table").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                packages.push(element[1].split('/')[0]);
            });
            if(packages.length === 0){
                Swal.fire({
                    type: 'error',
                    title: 'Lütfen önce seçim yapınız.'
                });
                return false;
            }
            updatePackage();
        }

        function updateSinglePackage(row){
            index = 0;
            packages = [];
            packages.push($(row).find("#name").text().split('/')[0]);
            updatePackage();
        }

        function updatePackage(){
            updateProgress();
            $('#updateLogs').modal('show');
            let scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
            scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
            let data = new FormData();
            data.append("package_name", packages[index]);
            $('#updateLogs').find('.updateLogsBody').append("\n"+packages[index]+" paketi kuruluyor. Lütfen bekleyin...<span id='"+packages[index]+"'></span>");
            request('{{route('server_update_package')}}', data, function (response) {
                checkUpdate();
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            })
        }

        function updateProgress(){
            $('#updateLogs').find('.progress-info').text(index+"/"+packages.length+" "+packages[index]+" paketi kuruluyor...");
            let percent = (index/packages.length)*100;
            $('div[role=progressbar]').attr('aria-valuenow', percent);
            $('div[role=progressbar]').attr('style', 'width: '+percent+'%');
            if(packages.length !== index){
                $('div[role=progressbar]').closest('.progress').addClass('active');
            }else{
                $('div[role=progressbar]').closest('.progress').removeClass('active');
                $('#updateLogs').find('.progress-info').text("Tüm işlemler bitti.");
            }

        }

        function checkUpdate(){
            let data = new FormData();
            data.append("package_name", packages[index]);
            request('{{route('server_check_update')}}', data, function (response) {
                response = JSON.parse(response);
                if(response.message.output){
                    $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.output);
                    let scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                    scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
                }
                $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.status);
                let scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
                index++;
                if(packages.length !== index){
                    updatePackage();
                }else{
                    updateProgress();
                    getUpdates();
                    $('#updateLogs').find('.updateLogsBody').append("\n"+"Tüm işlemler bitti.");
                }
            }, function(response){
                response = JSON.parse(response);
                if(response.message.output){
                    $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.output);
                    let scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                    scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
                }
                setTimeout(function(){
                    checkUpdate();
                },5000);
            });
        }

        function getUpdates(getList) {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Okunuyor...")}}',
                showConfirmButton: false,
            });
            request('{{route('server_update_list')}}', new FormData(), function (response) {
                let updates = JSON.parse(response);
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
                $("#updatesTabTable table").DataTable({
                    bFilter: true,
                    select: {
                        style: 'multi'
                    },
                    dom: 'Blfrtip',
                    buttons: {
                        buttons: [
                            { extend: 'selectAll', className: 'btn btn-xs btn-primary mr-1' },
                            { extend: 'selectNone', className: 'btn btn-xs btn-primary mr-1' }
                        ],
                        dom: {
                            button: { className: 'btn' }
                        }
                    },
                    language: {
                        url : "/turkce.json",
                        buttons: {
                            selectAll: "{{ __('Tümünü Seç') }}",
                            selectNone: "{{ __('Tümünü Kaldır') }}",
                        }
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
            }, function(response){
                let error = JSON.parse(response);
                Swal.fire({
                    type: 'error',
                    title: error.message,
                    timer : 2000
                });
            })
        }

        function openTerminal() {
            let terminalFrame = $("#terminalFrame");
            if (terminalFrame.attr("src") === "") {
                terminalFrame.attr("src", "{{route('server_terminal',["server_id" => $server->id])}}");
            }
        }
        function removeExtension(){
            let data = [];
            let table = $("#installed_extensions").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                data.push(element[4]);
            });
            if(data.length === 0){
                Swal.fire({
                  type: 'error',
                  title: '{{__("Lütfen önce seçim yapınız.")}}',
                  position: 'center',
                  timer: 2000,
                  showConfirmButton: false
              });
                return false;
            };
            $("#delete_extensions").modal('show');
        }
        function removeExtensionFunc() {
          let data = [];
          let table = $("#installed_extensions").DataTable();
          table.rows( { selected: true } ).data().each(function(element){
              data.push(element[4]);
          });
          if(data.length === 0){
              Swal.fire({
                  type: 'error',
                  title: '{{__("Lütfen önce seçim yapınız.")}}',
                  position: 'center',
                  timer: 2000,
                  showConfirmButton: false
              });
              return false;
          }
          Swal.fire({
              position: 'center',
              type: 'info',
              title: '{{__("Siliniyor...")}}',
              showConfirmButton: false
          });
          let form = new FormData();
          form.append('extensions',JSON.stringify(data));
          request('{{route('server_extension_remove')}}', form, function (response) {
              let json = JSON.parse(response);
              Swal.fire({
                  position: 'center',
                  type: 'success',
                  title: json["message"],
                  showConfirmButton: false,
                  timer: 2000
              });
              setTimeout(function () {
                      location.reload();
              },2000);
          }, function(response){
            let error = JSON.parse(response);
            Swal.fire({
                type: 'error',
                title: error.message,
                timer : 2000
            });
          });
          return false;
        }

        $(function () {
            $("#installed_extensions").DataTable({
                bFilter: true,
                select: {
                    style: 'multi'
                },
                dom: 'Blfrtip',
                buttons: {
                    buttons: [
                        { extend: 'selectAll', className: 'btn btn-xs btn-primary mr-1' },
                        { extend: 'selectNone', className: 'btn btn-xs btn-primary mr-1' }
                    ],
                    dom: {
                        button: { className: 'btn' }
                    }
                },
                language: {
                    url : "/turkce.json",
                    buttons: {
                        selectAll: "{{ __('Tümünü Seç') }}",
                        selectNone: "{{ __('Tümünü Kaldır') }}"
                    }
                }
            });
        });

        
    </script>
@endsection
