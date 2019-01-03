@extends('layouts.app')

@section('content')

    @include('title',[
        "title" => $server->name       
    ])
    <h5>Hostname : {{$hostname}}</h5>
    <button class="btn btn-outline-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>

    @include('modal-button',[
        "class" => "btn-outline-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ]) 
    @include('modal-button',[
        "class" => "btn-outline-warning",
        "target_id" => "command",
        "text" => "Komut Çalıştır"
    ])    
    @include('modal-button',[
        "class" => "btn-outline-secondary",
        "target_id" => "install_extension",
        "text" => "Servis Ekle"
    ])
    @include('modal-button',[
        "class" => "btn-outline-info",
        "target_id" => "change_network",
        "text" => "Network"
    ])
    @include('modal-button',[
        "class" => "btn-outline-primary",
        "target_id" => "change_hostname",
        "text" => "Hostname"
    ])
    @include('modal-button',[
        "class" => "btn-outline-warning",
        "target_id" => "file_upload",
        "text" => "Dosya Yükle"
    ])
    @include('modal-button',[
        "class" => "btn-outline-success",
        "target_id" => "terminal",
        "text" => "Terminal"
    ])<br><br>
    @if(count($services) > 0)
    <h4>{{__("Servis Durumları")}}</h4>
        @foreach($services as $service)
            <button type="button" class="btn btn-secondary btn-lg" style="cursor:default;" id="status_{{$service->service}}">
                {{strtoupper($service->name)}}
            </button>
        @endforeach
    @else
        <h4>{{__("Yüklü servis yok.")}}</h4>
    @endif
    <br><br>
    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>

    @include('modal-button',[
        "class" => "btn-outline-danger",
        "target_id" => "delete",
            "text" => "Sunucuyu Sil"
    ])

    @include('modal',[
        "id"=>"delete",
        "title" => $server->name,
        "url" => route('server_remove'),
        "text" => "isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "redirect",
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Sunucuyu Sil"
    ])

    @include('modal-iframe',[
        "id" => "terminal",
        "title" => "Terminal"
    ])

    @include('modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Şehir:city" => [
                 "Adana" => "01",
                 "Adıyaman" => "02",
                 "Afyonkarahisar" => "03",
                 "Ağrı" => "04",
                 "Amasya" => "05",
                 "Ankara" => "06",
                 "Antalya" => "07",
                 "Artvin" => "08",
                 "Aydın" => "09",
                 "Balıkesir" => "10",
                 "Bilecik" => "11",
                 "Bingöl" => "12",
                 "Bitlis" => "13",
                 "Bolu" => "14",
                 "Burdur" => "15",
                 "Bursa" => "16",
                 "Çanakkale" => "17",
                 "Çankırı" => "18",
                 "Çorum" => "19",
                 "Denizli" => "20",
                 "Diyarbakır" => "21",
                 "Edirne" => "22",
                 "Elazığ" => "23",
                 "Erzincan" => "24",
                 "Erzurum" => "25",
                 "Eskişehir" => "26",
                 "Gaziantep" => "27",
                 "Giresun" => "28",
                 "Gümüşhane" => "29",
                 "Hakkâri" => "30",
                 "Hatay" => "31",
                 "Isparta" => "32",
                 "Mersin" => "33",
                 "İstanbul" => "34",
                 "İzmir" => "35",
                 "Kars" => "36",
                 "Kastamonu" => "37",
                 "Kayseri" => "38",
                 "Kırklareli" => "39",
                 "Kırşehir" => "40",
                 "Kocaeli" => "41",
                 "Konya" => "42",
                 "Kütahya" => "43",
                 "Malatya" => "44",
                 "Manisa" => "45",
                 "Kahramanmaraş" => "46",
                 "Mardin" => "47",
                 "Muğla" => "48",
                 "Muş" => "49",
                 "Nevşehir" => "50",
                 "Niğde" => "51",
                 "Ordu" => "52",
                 "Rize" => "53",
                 "Sakarya" => "54",
                 "Samsun" => "55",
                 "Siirt" => "56",
                 "Sinop" => "57",
                 "Sivas" => "58",
                 "Tekirdağ" => "59",
                 "Tokat" => "60",
                 "Trabzon" => "61",
                 "Tunceli" => "62",
                 "Şanlıurfa" => "63",
                 "Uşak" => "64",
                 "Van" => "65",
                 "Yozgat" => "66",
                 "Zonguldak" => "67",
                 "Aksaray" => "68",
                 "Bayburt" => "69",
                 "Karaman" => "70",
                 "Kırıkkale" => "71",
                 "Batman" => "72",
                 "Şırnak" => "73",
                 "Bartın" => "74",
                 "Ardahan" => "75",
                 "Iğdır" => "76",
                 "Yalova" => "77",
                 "Karabük" => "78",
                 "Kilis" => "79",
                 "Osmaniye" => "80",
                 "Düzce" => "81"
            ],
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])

    @include('modal',[
        "id"=>"change_network",
        "title" => "Network Değiştir",
        "url" => route('server_network'),
        "next" => "reload",
        "inputs" => [
            "İp Adresi" => "ip:text",
            "Cidr Adresi" => "cidr:text",
            "Gateway" => "gateway:text",
            "Arayüz" => "interface:text",
            "SSH Parolası" => "password:password",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Değiştir"
    ])

    @include('modal',[
        "id"=>"change_hostname",
        "title" => "Hostname Değiştir",
        "url" => route('server_hostname'),
        "next" => "reload",
        "inputs" => [
            "Hostname" => "hostname:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Değiştir"
    ])

    @include('modal',[
        "id"=>"file_upload",
        "title" => "Hostname Değiştir",
        "url" => route('server_upload'),
        "next" => "debug",
        "inputs" => [
            "Yüklenecek Dosya(lar)" => "file:file",
            "Yol" => "path:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Yükle"
    ])

    @include('modal',[
        "id"=>"install_extension",
        "title" => "Servis Yükle",
        "url" => route('server_extension'),
        "next" => "message",
        "selects" => [
            "DNS:5c0a170f7b57f19953126e37" => [
                "Domain Adı" => "domain:text",
                "Arayüz" => "interface:text",
                "DNS:5c0a170f7b57f19953126e37" => "extension_id:hidden"
            ],
            "DHCP:5c0a1c5f7b57f19953126e38" => [
                "Domain Adı" => "domain:text",
                "Arayüz" => "interface:text",
                "Subnet" => "subnet:text",
                "DHCP:5c0a1c5f7b57f19953126e38" => "extension_id:hidden"
            ]
        ],
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Değiştir"
    ])

    @include('modal',[
        "id"=>"command",
        "title" => "Özel Komut Çalıştır",
        "url" => route('server_run'),
        "next" => "commandDisplay",
        "inputs" => [
            "Sorumluluk Reddi" => "responsibility:checkbox",
            "Kod Alanı" => "command:textarea",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "output" => "command_output",
        "submit_text" => "Çalıştır"
    ])

    <script>
        function commandDisplay(output){
            let element = document.getElementById("command_output");
            let modal = document.getElementsByClassName("modal show")[0];
            let modal_id = modal.getAttribute("id");
            document.getElementById(modal_id + "_alert").innerHTML = "Komut Çalıştırıldı.";
            document.getElementById(modal_id + "_alert").removeAttribute('hidden');
            let json = JSON.parse(output);
            element.value = json["message"];
            element.removeAttribute('hidden');
        }

        function checkStatus(service){
            let data = new FormData();
            data.append('server_id','{{$server->_id}}');
            data.append('service',service);
            request('{{route('server_check')}}', data, function(response){
                let json = JSON.parse(response);
                let element = document.getElementById('status_' + service);
                element.classList.remove('btn-secondary');
                element.classList.add(json["message"]);
            });
        }

        @if(count($services) > 0)
            @foreach($services as $service)
                setInterval(function () {
                    checkStatus('{{$service->service}}');
                }, 3000);
            @endforeach
        @endif
        
    </script>
@endsection