@extends('layouts.app')

@section('content')

    @include('title',[
        "title" => $server->name       
    ])
    <h5>Hostname : {{$hostname}}</h5>
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>

    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ])
    @include('modal-button',[
        "class" => "btn-secondary",
        "target_id" => "install_extension",
        "text" => "Servis Ekle"
    ])
    @include('modal-button',[
        "class" => "btn-info",
        "target_id" => "change_network",
        "text" => "Network"
    ])
    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "change_hostname",
        "text" => "Hostname"
    ])
    @include('modal-button',[
        "class" => "btn-warning",
        "target_id" => "file_upload",
        "text" => "Dosya Yükle"
    ])
    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "file_download",
        "text" => "Dosya İndir"
    ])
    @include('modal-button',[
        "class" => "btn-success",
        "target_id" => "terminal",
        "text" => "Terminal"
    ])
    @include('modal-button',[
        "class" => "btn-info",
        "target_id" => "give_permission",
        "text" => "Yetki Ver"
    ])<br><br>
    @if(count($installed_extensions) > 0)
    <h4>{{__("Servis Durumları")}}</h4>
        @foreach($installed_extensions as $service)
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
        "class" => "btn-danger",
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
        "url" => route('server_terminal',["server_id" => $server->_id]),
        "title" => "$server->name sunucusu terminali"
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
        "id"=>"give_permission",
        "title" => "Kullanıcıya Yetki Ver",
        "url" => route('server_grant_permission'),
        "next" => "function(){return false;}",
        "inputs" => [
            "Kullanıcı Emaili" => "email:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "text" => "Güvenlik sebebiyle kullanıcı listesi sunulmamaktadır.",
        "submit_text" => "Yetkilendir"
    ])

    @include('modal',[
        "id"=>"file_upload",
        "title" => "Dosya Yükle",
        "url" => route('server_upload'),
        "next" => "reload",
        "inputs" => [
            "Yüklenecek Dosya(lar)" => "file:file",
            "Yol" => "path:text",
            "Sunucu Id:$server->_id" => "server_id:hidden"
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
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "İndir"
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

    <script>
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

        @if(count($installed_extensions) > 0)
            @foreach($installed_extensions as $service)
                setInterval(function () {
                    checkStatus('{{$service->service}}');
                }, 3000);
            @endforeach
        @endif

        function downloadFile(form){
            window.location.assign('/sunucu/indir?path=' + form.getElementsByTagName('input')[0].value + '&server_id=' + form.getElementsByTagName('input')[1].value);
            return false;
        }
    </script>
@endsection