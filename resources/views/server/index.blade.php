@extends('layouts.app')

@section('content')

    @include('title',[
        "title" => "Tüm Sunucular"
    ])
    @include('modal-button',[
        "class" => "btn-success",
        "target_id" => "add_server",
        "text" => "Server Ekle"
    ])<br><br>
    @if(isset($servers))
        <table class="table">
            <thead>
            <tr>
                <th scope="col">{{__("Sunucu Adı")}}</th>
                <th scope="col">{{__("İp Adresi")}}</th>
                <th scope="col">{{__("Sunucu Tipi")}}</th>
                <th scope="col">{{__("Kontrol Portu")}}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($servers as $server)
                <tr onclick="dummy('{{$server->_id}}')" class="highlight" oncontextmenu="return rightClick(this,event)">
                    <td id="name">{{$server->name}}</td>
                    <td id="ip_address">{{$server->ip_address}}</td>
                    <td id="type">{{$server->type}}</td>
                    <td id="control_port">{{$server->control_port}}</td>
                    <td id="server_id" hidden>{{$server->_id}}</td>
                </tr>
            @endforeach
            <div class="dropdown-menu" id="context-menu" style="color:white">
                <a class="dropdown-item" data-toggle="modal" data-target="#edit" href="#">Düzenle</a>
                <a class="dropdown-item" data-toggle="modal" data-target="#change_hostname" href="#">Hostname Değiştir</a>
                <a class="dropdown-item" data-toggle="modal" data-target="#delete" href="#">Sil</a>
            </div>
            </tbody>
        </table>
        <div id='context-menu-bye'></div>
    @else
        <h3>{{__("Sunucunuz Bulunmuyor.")}}</h3>
    @endif
    @include('modal',[
        "id"=>"add_server",
        "title" => "Sunucu Ekle",
        "url" => route('server_add'),
        "selects" => [
            "Linux Sunucusu:linux" => [
                "Linux:linux" => "type:hidden"
            ],
            "Linux Sunucusu (SSH):linux_ssh" => [
                "SSH Kullanıcı Adı" => "username:text",
                "SSH Parola" => "password:password",
                "SSH Portu" => "port:number",
                "Linux SSH:linux_ssh" => "type:hidden"
            ],
            "Windows Sunucusu:windows" => [
                "Windows:windows" => "type:hidden"
            ],
            "Windows Sunucusu (PowerShell):windows_powershell" => [
                "Uzak Masaüstu Hesabı" => "username:text",
                "Uzak Masaüstü Parolası" => "password:password",
                "Windows Powershell:windows_powershell" => "type:hidden"
            ]
        ],
        "inputs" => [
            "Adı" => "name:text",
            "İp Adresi" => "ip_address:text",
            "Sunucu Durumu Kontrol Portu" => "control_port:number",
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

        ],
        "submit_text" => "Ekle"
    ])

    <script>
        function dummy(id) {
            let main = document.getElementsByTagName('main')[0];
            main.innerHTML = document.getElementsByClassName('loading')[0].innerHTML;
            location.href = '/sunucular/' + id;
        }
        let menu = document.getElementById('context-menu');
        let menu_by = document.getElementById('context-menu-bye');
        function rightClick(element,event){
            menu.classList.toggle('show');
            menu.style.top = event.clientY+"px";
            menu.style.left = event.clientX+"px";
            menu.style.backgroundColor = 'white';
            menu.style.display = 'block';


            var elms = document.getElementById("delete").getElementsByTagName("*");
            var elms2 = document.getElementById("edit").getElementsByTagName("*");
            var elms3 = document.getElementById("change_hostname").getElementsByTagName("*");
            for (var i = 0; i < elms.length; i++) {
                if (elms[i].className === "modal-title")
                    elms[i].innerHTML=element.getElementsByTagName("td")[0].innerHTML;
                if(elms[i].name === "server_id")
                    elms[i].value=element.getElementsByTagName("td")[4].innerHTML;
            }
            for (var i = 0; i < elms2.length; i++) {
                if(elms2[i].name === "server_id")
                    elms2[i].value=element.getElementsByTagName("td")[4].innerHTML;
                else if(elms2[i].name === "name")
                    elms2[i].value=element.getElementsByTagName("td")[0].innerHTML;
                else if(elms2[i].name === "control_port")
                    elms2[i].value=element.getElementsByTagName("td")[3].innerHTML;
            }
            for (var i = 0; i < elms3.length; i++) {
                if(elms3[i].name === "server_id")
                    elms3[i].value=element.getElementsByTagName("td")[4].innerHTML;
            }
            return false;
        }
        menu_by.addEventListener("click",function(e){
            menu.style.display = 'none';
        },false);
        menu.addEventListener("click",function(e){
            menu.style.display = 'none';
        },false);
    </script>
    @include('modal',[
       "id"=>"delete",
       "title" =>"",
       "url" => route('server_remove'),
       "text" => "isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Sunucu Id:$server->_id" => "server_id:hidden"
       ],
       "submit_text" => "Sunucuyu Sil"
   ])
    @include('modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
            "Sunucu Id:$server->_id" => "server_id:hidden",
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
        ],
        "submit_text" => "Düzenle"
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

@endsection