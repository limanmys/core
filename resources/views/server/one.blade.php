@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('servers')}}">{{__("Sunucular")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{$server->name}}</li>
        </ol>
    </nav>
    <div class="serverName" style="margin-top:-20px;">
        @if(\Illuminate\Support\Facades\DB::table("user_favorites")->where(["user_id" => auth()->user()->id,"server_id" => server()->id])->exists())
            <button onclick="favorite('false')" class="btn btn-warning fa fa-star-o" style="margin-top:-15px" data-toggle="tooltip" title="Favorilerden Cikar"></button>
        @else
            <button onclick="favorite('true')" class="btn btn-success fa fa-star" style="margin-top:-15px" data-toggle="tooltip" title="Favorilere Ekle"></button>
        @endif
        <h2 style="display: inline-block;">{{server()->name}}</h2>
    </div>
    @include('l.errors')    

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#usageTab" data-toggle="tab" aria-expanded="false">{{__("Sistem Durumu")}}</a>
            </li>
            <li class=""><a href="#extensionsTab" data-toggle="tab" aria-expanded="false">{{__("Eklentiler")}}</a></li>
            @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                <li class=""><a href="#servicesTab" onclick="getServices()" data-toggle="tab"
                                aria-expanded="false">{{__("Servisler")}}</a></li>
                @if(server()->type == "linux_ssh")
                    <li class=""><a href="#packagesTab" onclick="getPackages()" data-toggle="tab"
                                    aria-expanded="false">{{__("Paketler")}}</a></li>
                    <li class="">
                        <a href="#updatesTab" onclick="getUpdates()" data-toggle="tab" aria-expanded="false">
                            {{__("Güncellemeler")}}
                            <small class="label pull-right bg-red updateCount" style="display:none;margin-left: 5px;margin-top: 2px;">3</small>
                        </a>
                    </li>
                @endif
            @endif
            <li class=""><a href="#logsTab" onclick="getLogs()" data-toggle="tab" aria-expanded="false">{{__("Günlük Kayıtları")}}</a></li>
            <li class=""><a href="#settingsTab" data-toggle="tab" aria-expanded="false">{{__("Sunucu Ayarları")}}</a></li>
            <li class=""><a href="#extensionSettings" data-toggle="tab" aria-expanded="false">{{__("Eklenti Ayarları")}}</a></li>
            @if(server()->type == "linux" || server()->type == "windows")
                <li class=""><a href="#extraTab" data-toggle="tab" aria-expanded="false">{{__("Ek Özellikler")}}</a>
                </li>
            @endif
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="usageTab">
                @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                    <h4>Hostname : {{$hostname}}</h4>
                    @if(server()->type == "linux_ssh")
                        <h4>{{server()->run("lsb_release -ds")}}</h4>
                    @else
                        <h4>{{__("Versiyon : " ) . explode("|",server()->run("(Get-WmiObject Win32_OperatingSystem).name"))[0]}}</h4>
                        
                    @endif
                @endif
                <h4>{{__("İp Adresi : ") . server()->ip_address }}</h4>
                <h4>{{__("Şehir : ") . cities(server()->city) }}</h4>
                @if($installed_extensions->count() > 0)
                    <h4>{{__("Eklenti Durumları")}}</h4>
                    @foreach($installed_extensions as $extension)
                        <button type="button" class="btn btn-primary btn-lg status_{{$extension->id}}"
                                style="cursor:default;"
                                onclick="location.href = '{{route('extension_server',["extension_id" => $extension->id, "city" => $server->city, "server_id" => $server->id])}}'">
                            {{$extension->name}}
                        </button>
                    @endforeach
                @else
                    <h4>{{__("Yüklü eklenti yok.")}}</h4>
                @endif
                @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                    <br><br>
                    <hr>
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
                @endif
            </div>
            <div class="tab-pane" id="extensionsTab">
                @if(auth()->user()->id == server()->user_id || auth()->user()->isAdmin())
                    <button class="btn btn-success" data-toggle="modal" data-target="#install_extension"><i
                                class="fa fa-plus"></i></button>
                    <button onclick="removeExtension()" class="btn btn-danger"><i class="fa fa-minus"></i>
                    </button><br><br>
                @endif
                @include('l.table',[
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
            <div class="tab-pane" id="terminalTab">
                <iframe id="terminalFrame" src=""
                        style="width: 100%;height: 600px;background: black"></iframe>
            </div>
            <div class="tab-pane" id="filesTab">
                @include('l.modal-button',[
                        "class" => "btn-primary fa fa-upload",
                        "target_id" => "file_upload",
                        "text" => "Yükle"
                    ])

                @include('l.modal-button',[
                    "class" => "btn-primary fa fa-download",
                    "target_id" => "file_download",
                    "text" => "İndir"
                ])
            </div>
            @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                <div class="tab-pane" id="servicesTab">
                @include('l.table',[
                    "id" => "servicesTable",
                    "value" => [],
                    "title" => [
                        "Servis Adı" , "Aciklamasi" , "Durumu"
                    ],
                    "display" => [
                        "name" , "description", "status"
                    ],
                    "menu" => [
                        "Baslat" => [
                            "target" => "startService",
                            "icon" => "fa-play"
                        ],
                        "Durdur" => [
                            "target" => "stopService",
                            "icon" => "fa-stop"
                        ],
                        "Yeniden Baslat" => [
                            "target" => "restartService",
                            "icon" => "fa-refresh"
                        ]

                    ],
                ])
                </div>
                <div class="tab-pane right" id="updatesTab">
                    <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateAllPackages" onclick="updateAllPackages()">Tümünü Güncelle</button>
                    <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateSelectedPackages" onclick="updateSelectedPackages()">Seçilenleri Güncelle</button>
                    <div id="updatesTabTable"></div>
                </div>
            @endif
            @if(server()->type == "linux_ssh")
                <div class="tab-pane" id="packagesTab">

                </div>
            @endif
            <div class="tab-pane" id="logsTab">

            </div>
            <div class="tab-pane" id="settingsTab" >
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
                        <h5>{{__("Şehir")}}</h5>
                        <select name="city" class="form-control" required="">
                            @foreach(cities() as $city=>$value)
                                <option value="{{$value}}" @if($value == server()->city) selected @endif>{{$city}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td style="width:300px;text-align:center;padding-left:60px">
                        <button type="submit" class="btn btn-success btn-block">{{__("Bilgileri Güncelle")}}</button><br><br>
                
                @include('l.modal-button',[
                    "class" => "btn-danger btn-block",
                    "target_id" => "delete",
                        "text" => "Sunucuyu Sil"
                ])
                </form>
                    </td>
            </tr>
            </table>
                
                    
            </div>
            <div class="tab-pane" id="extraTab">
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
            <div class="tab-pane" id="extensionSettings">
                @include('l.table',[
                    "value" => user()->settings->where('server_id',server()->id),
                    "title" => [
                        "Veri Adi" , "*hidden*"
                    ],
                    "display" => [
                        "name" , "id:id"
                    ],
                    "menu" => [
                        "Sil" => [
                            "target" => "startService",
                            "icon" => "fa-play"
                        ]
                    ],
                ])
            </div>
        </div>
    </div>

    @include('l.modal',[
        "id"=>"delete",
        "title" => "Sunucuyu Sil",
        "url" => route('server_remove'),
        "text" => "$server->name isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "type" => "danger",
        "next" => "redirect",
        "submit_text" => "Sunucuyu Sil"
    ])

    @include('l.modal',[
        "id"=>"delete_extensions",
        "title" => "Eklentileri Sil",
        "text" => "Secili eklentileri silmek istediginize emin misiniz?",
        "type" => "danger",
        "onsubmit" => "removeExtensionFunc",
        "submit_text" => "Eklentileri Sil"
    ])

    @include('l.modal',[
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

    @include('l.modal',[
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

    @include('l.modal',[
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

    @include('l.modal',[
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

    @include('l.modal',[
        "id"=>"file_download",
        "onsubmit" => "downloadFile",
        "title" => "Dosya İndir",
        "next" => "",
        "inputs" => [
            "Yol" => "path:text",
        ],
        "submit_text" => "İndir"
    ])
    @include('l.modal-table',[
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
        @include('l.modal',[
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

    @component('l.modal-component',[
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
        }, 30000);

        stats();
        function updateChart(element, time, data) {
            // First, Update Text
            $("#" + element + "Text").html("%" + data);
            window[element + "Chart"].data.labels.push(time);
            window[element + "Chart"].data.datasets.forEach((dataset) => {
                dataset.data.push(data);
            });
            window[element + "Chart"].update();
        }

        function createChart(element, time, data) {
            $("#" + element + "Text").html("%" + data[0]);
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
                $("#packagesTab").html(response);
                $("#packagesTab table").DataTable({
                    bFilter: true,
                    select: {
                        style: 'multi'
                    },
                    "language": {
                        url: "/turkce.json"
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
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
                    select: {
                        style: 'multi'
                    },
                    "language": {
                        url: "/turkce.json"
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
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
                let json = JSON.parse(response);
                let table = $("#servicesTab table").DataTable();
                table.rows().remove();
                let counter = 1;
                json["message"].forEach(element => {
                    let row = table.row.add([
                        counter++, element["name"], element["description"], element["status"]
                    ]).draw(false).node();
                    $(row).addClass('tableRow');
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
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
                    "language": {
                        url: "/turkce.json"
                    }
                });
                setTimeout(function () {
                    Swal.close();
                }, 1500);
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
          });
          return false;
        }

        $(function () {
            $("#installed_extensions").DataTable({
                bFilter: true,
                select: {
                    style: 'multi'
                },
                "language": {
                    url: "/turkce.json"
                }
            });
        });

        
    </script>
@endsection
