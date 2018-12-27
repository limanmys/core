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
        "class" => "btn-warning",
        "target_id" => "command",
        "text" => "Komut Çalıştır"
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
    ])<br><br>
    @if(count($services) > 0)
    <h4>{{__("Servis Durumları")}}</h4>
        @foreach($services as $service)
            <button type="button" class="btn btn-info btn-lg" style="cursor:default;" id="status_{{$service}}">
                {{strtoupper($service)}}
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

    @include('modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
            "Kontrol Portu" => "control_port:number",
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
            element.value = output;
            element.removeAttribute('hidden');
        }

        @foreach($server->extensions as $extension)
            setInterval(function () {
                // checkStatus('{{$extension}}');
            }, 3000);
        @endforeach
        
    </script>
@endsection