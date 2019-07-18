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
            <button onclick="favorite('false')" class="btn btn-warning fa fa-star-o" style="margin-top:-15px"></button>
        @else
            <button onclick="favorite('true')" class="btn btn-success fa fa-star" style="margin-top:-15px"></button>
        @endif
        <h2 style="display: inline-block;">{{server()->name}}</h2>
    </div>

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
                @endif
                <li class=""><a href="#filesTab" data-toggle="tab" aria-expanded="false">{{__("Dosya Transferi")}}</a>
                </li>
            @endif
            @if(server()->type == "linux_ssh")
                <li class=""><a href="#terminalTab" onclick="openTerminal()" data-toggle="tab"
                                aria-expanded="false">{{__("Terminal")}}</a></li>
            @endif
            <li class=""><a href="#settingsTab" data-toggle="tab" aria-expanded="false">{{__("Ayarlar")}}</a></li>
            @if(server()->type == "linux" || server()->type == "windows")
                <li class=""><a href="#extraTab" data-toggle="tab" aria-expanded="false">{{__("Ek Özellikler")}}</a>
                </li>
            @endif
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="usageTab">
                @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                    <h4>Hostname : {{$hostname}}</h4>
                @endif
                <h4>{{__("İp Adresi : ") . server()->ip_address }}</h4>
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
                        "Eklenti Adı" , "Versiyon", "*hidden*"
                    ],
                    "display" => [
                        "name" , "version", "id:extension_id"
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

                </div>
            @endif
            @if(server()->type == "linux_ssh")
                <div class="tab-pane" id="packagesTab">

                </div>
            @endif
            <div class="tab-pane" id="settingsTab">
                <form id="edit_form" onsubmit="return request('{{route('server_update')}}',this,reload)" target="#">
                    <div class="modal-body" style="width: 100%">
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
                        </select><br>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">{{__("Bilgileri Güncelle")}}</button>
                    </div>
                </form>
                @include('l.modal-button',[
                    "class" => "btn-danger",
                    "target_id" => "delete",
                        "text" => "Sunucuyu Sil"
                ])
            </div>
            <div class="tab-pane" id="extraTab">
                @if(server()->type == "linux")
                    <p>{{__("Linux Sunucunuza SSH anahtarı ekleyerek Liman üzerindeki ekstra özelliklere erişebilirsiniz.")}}</p>
                @elseif(server()->type == "windows")
                    <p>{{__("Windows Sunucunuza WinRM anahtarı ekleyerek Liman üzerindeki ekstra özelliklere erişebilirsiniz.")}}</p>
                @endif
                <form id="upgrade_form" onsubmit="return request('{{route('server_upgrade')}}',this,reload)" target="#">
                    <div class="modal-body" style="width: 100%">
                        <h5>{{__("Kullanıcı Adı")}}</h5>
                        <input type="text" name="username" placeholder="{{__("Kullanıcı Adı")}}" class="form-control " required=""
                               value=""><br>
                        <h5>{{__("Parola")}}</h5>
                        <input type="password" name="password" placeholder="{{__("Parola")}}" class="form-control "
                               required="" value=""><br>
                        <button type="submit" class="btn btn-success">{{__("Yükselt")}}</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @include('l.modal',[
        "id"=>"delete",
        "title" => "Sunucuyu Sil",
        "url" => route('server_remove'),
        "text" => "$server->name isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "redirect",
        "submit_text" => "Sunucuyu Sil"
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
            "url" => route('server_extension'),
            "next" => "reload",
            "selects" => $input_extensions,
            "inputs" => [
            ],
            "submit_text" => "Değiştir"
        ])
    @endif
    <script>
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

        let ramChart, cpuChart, diskChart;
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

        function stats() {
            let form = new FormData();
            form.append('server_id', '{{server()->id}}');
            request('{{route('server_stats')}}', form, function (response) {
                data = JSON.parse(response);
                updateChart("disk", data['time'], data['disk']);
                updateChart("ram", data['time'], data['ram']);
                updateChart("cpu", data['time'], data['cpu']);
            })
        }

        createChart("ram", "{{\Carbon\Carbon::now()->format("H:i:s")}}", ["0"]);
        createChart("cpu", "{{\Carbon\Carbon::now()->format("H:i:s")}}", ["0"]);
        createChart("disk", "{{\Carbon\Carbon::now()->format("H:i:s")}}", ["0"]);

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
            })
        }

        function openTerminal() {
            let terminalFrame = $("#terminalFrame");
            if (terminalFrame.attr("src") === "") {
                terminalFrame.attr("src", "{{route('server_terminal',["server_id" => $server->id])}}");
            }
        }

        function removeExtension() {
          let data = [];
          let table = $("#installed_extensions").DataTable();
          table.rows( { selected: true } ).data().each(function(element){
              data.push(element[3]);
          });
          if(data.length === 0){
              Swal.fire({
                  type: 'error',
                  title: 'Lütfen önce seçim yapınız.'
              });
              return false;
          }
          Swal.fire({
              position: 'center',
              type: 'info',
              title: '{{__("Siliniyor...")}}',
              showConfirmButton: false,
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
