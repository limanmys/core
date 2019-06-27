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
                <li class=""><a href="#servicesTab" data-toggle="tab" aria-expanded="false">{{__("Servisler")}}</a></li>
                @if(server()->type == "linux_ssh")
                    <li class=""><a href="#packagesTab" data-toggle="tab" aria-expanded="false">{{__("Paketler")}}</a></li>
                @endif
                <li class=""><a href="#filesTab" data-toggle="tab" aria-expanded="false">{{__("Dosya Transferi")}}</a></li>
            @endif
            @if(server()->type == "linux_ssh")
                <li class=""><a href="#terminalTab" data-toggle="tab" aria-expanded="false">{{__("Terminal")}}</a></li>
            @endif
            <li class=""><a href="#settingsTab" data-toggle="tab" aria-expanded="false">{{__("Ayarlar")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="usageTab">
                @if(server()->type == "linux_ssh" || server()->type == "windows_powershell")
                    <h4>Hostname : {{server()->run("hostname")}}</h4>
                @endif
                <h4>{{__("İp Adresi : ") . server()->ip_address }}</h4>
                @if($installed_extensions->count() > 0)
                    <h4>{{__("Eklenti Durumları")}}</h4>
                    @foreach($installed_extensions as $extension)
                        <button type="button" class="btn btn-outline-primary btn-lg"
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
                    <table class="notDataTable">
                        <thead>
                        <tr>
                            <td>{{__("Cpu Kullanımı")}}</td>
                            <td>{{__("Disk Kullanımı")}}</td>
                            <td>{{__("Ram Kullanımı")}}</td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="padding-right: 50px">
                                <span id="cpuText" style="text-align: center;font-weight: bold">
                                    {{__("Yükleniyor...")}}
                                </span><br>
                                <canvas id="cpu" width="400px" height="200px" style="float:left"></canvas>
                            </td>
                            <td style="padding-right: 50px">
                                <span id="diskText" style="text-align: center;font-weight: bold">
                                    {{__("Yükleniyor...")}}
                                </span><br>
                                <canvas id="disk" width="400px" height="200px" style="float:left"></canvas>
                            </td>
                            <td style="padding-right: 50px">
                                <span id="ramText" style="text-align: center;font-weight: bold">
                                    {{__("Yükleniyor...")}}
                                </span><br>
                                <canvas id="ram" width="400px" height="200px" style="float:left;"></canvas>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <hr>
                @endif
            </div>
            <div class="tab-pane" id="extensionsTab">
                @if(auth()->user()->id == server()->user_id || auth()->user()->isAdmin())
                    <button class="btn btn-success" data-toggle="modal" data-target="#install_extension"><i
                                class="fa fa-plus"></i></button>
                    <button onclick="removePermission('script')" class="btn btn-danger"><i class="fa fa-minus"></i>
                    </button><br><br>
                @endif
                @include('l.table',[
                    "value" => $installed_extensions,
                    "title" => [
                        "Eklenti Adı" , "Versiyon", "*hidden*"
                    ],
                    "display" => [
                        "name" , "version", "id:extension_id"
                    ]
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
                <iframe src="{{route('server_terminal',["server_id" => $server->id])}}"
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
                        <div id="edit_alert" class="alert" role="alert" hidden="">
                        </div>
                        <h5>{{__("Sunucu Adı")}}</h5>
                        <input type="text" name="name" placeholder="Sunucu Adı" class="form-control " required=""
                               value="{{server()->name}}"><br>
                        <h5>{{__("Kontrol Portu")}}</h5>
                        <input type="number" name="control_port" placeholder="Kontrol Portu" class="form-control "
                               required="" value="{{server()->control_port}}"><br>
                        <h5>{{__("Şehir")}}</h5>
                        <select name="city" class="form-control" required="">
                            <option value="01">Adana</option>
                            <option value="02">Adıyaman</option>
                            <option value="03">Afyonkarahisar</option>
                            <option value="04">Ağrı</option>
                            <option value="05">Amasya</option>
                            <option value="06">Ankara</option>
                            <option value="07">Antalya</option>
                            <option value="08">Artvin</option>
                            <option value="09">Aydın</option>
                            <option value="10">Balıkesir</option>
                            <option value="11">Bilecik</option>
                            <option value="12">Bingöl</option>
                            <option value="13">Bitlis</option>
                            <option value="14">Bolu</option>
                            <option value="15">Burdur</option>
                            <option value="16">Bursa</option>
                            <option value="17">Çanakkale</option>
                            <option value="18">Çankırı</option>
                            <option value="19">Çorum</option>
                            <option value="20">Denizli</option>
                            <option value="21">Diyarbakır</option>
                            <option value="22">Edirne</option>
                            <option value="23">Elazığ</option>
                            <option value="24">Erzincan</option>
                            <option value="25">Erzurum</option>
                            <option value="26">Eskişehir</option>
                            <option value="27">Gaziantep</option>
                            <option value="28">Giresun</option>
                            <option value="29">Gümüşhane</option>
                            <option value="30">Hakkâri</option>
                            <option value="31">Hatay</option>
                            <option value="32">Isparta</option>
                            <option value="33">Mersin</option>
                            <option value="34">İstanbul</option>
                            <option value="35">İzmir</option>
                            <option value="36">Kars</option>
                            <option value="37">Kastamonu</option>
                            <option value="38">Kayseri</option>
                            <option value="39">Kırklareli</option>
                            <option value="40">Kırşehir</option>
                            <option value="41">Kocaeli</option>
                            <option value="42">Konya</option>
                            <option value="43">Kütahya</option>
                            <option value="44">Malatya</option>
                            <option value="45">Manisa</option>
                            <option value="46">Kahramanmaraş</option>
                            <option value="47">Mardin</option>
                            <option value="48">Muğla</option>
                            <option value="49">Muş</option>
                            <option value="50">Nevşehir</option>
                            <option value="51">Niğde</option>
                            <option value="52">Ordu</option>
                            <option value="53">Rize</option>
                            <option value="54">Sakarya</option>
                            <option value="55">Samsun</option>
                            <option value="56">Siirt</option>
                            <option value="57">Sinop</option>
                            <option value="58">Sivas</option>
                            <option value="59">Tekirdağ</option>
                            <option value="60">Tokat</option>
                            <option value="61">Trabzon</option>
                            <option value="62">Tunceli</option>
                            <option value="63">Şanlıurfa</option>
                            <option value="64">Uşak</option>
                            <option value="65">Van</option>
                            <option value="66">Yozgat</option>
                            <option value="67">Zonguldak</option>
                            <option value="68">Aksaray</option>
                            <option value="69">Bayburt</option>
                            <option value="70">Karaman</option>
                            <option value="71">Kırıkkale</option>
                            <option value="72">Batman</option>
                            <option value="73">Şırnak</option>
                            <option value="74">Bartın</option>
                            <option value="75">Ardahan</option>
                            <option value="76">Iğdır</option>
                            <option value="77">Yalova</option>
                            <option value="78">Karabük</option>
                            <option value="79">Kilis</option>
                            <option value="80">Osmaniye</option>
                            <option value="81">Düzce</option>
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
        "id"=>"edit",
        "title" => "Bilgileri Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Şehir:city" => cities(),
        ],
        "submit_text" => "Düzenle"
    ])

    @include('l.modal',[
        "id"=>"change_hostname",
        "title" => "Hostname Değiştir",
        "url" => route('server_hostname'),
        "next" => "reload",
        "inputs" => [
            "Hostname" => "hostname:text",
        ],
        "submit_text" => "Değiştir"
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
        function checkStatus(service) {
            let data = new FormData();
            if (!service) {
                return false;
            }
            data.append('service', service);
            request('{{route('server_check')}}', data, function (response) {
                let json = JSON.parse(response);
                let element = $(".status_" + service);
                element.removeClass('btn-secondary').addClass(json["message"]);
            });
        }

        @if($installed_extensions->count() > 0)
        @foreach($installed_extensions as $service)
        setInterval(function () {
            checkStatus('{{$service->service}}');
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
                    responsive: false,
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
    </script>
@endsection