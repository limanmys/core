@extends('layouts.app')

@section('content_header')
    <h1>{{$server->name}}</h1>
@stop

@section('content')

    <?php
        $input_extensions = [];
        foreach($available_extensions as $extension){
            $arr = [];
            if(isset($extension->install)){
                foreach($extension->install as $key => $parameter){
                    $arr[$parameter["name"]] = $key . ":" . $parameter["type"];
                }    
            }
            $arr[$extension->name . ":" . $extension->_id] = "extension_id:hidden";
            $input_extensions[$extension->name . ":" . $extension->_id] = $arr;
        }
    ?>
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>

    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "edit",
        "text" => "Düzenle"
    ])
    @if(count($input_extensions))
        @include('l.modal-button',[
            "class" => "btn-secondary",
            "target_id" => "install_extension",
            "text" => "Servis Ekle"
        ])
    @endif
    @include('l.modal-button',[
        "class" => "btn-info",
        "target_id" => "change_network",
        "text" => "Network"
    ])
    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "change_hostname",
        "text" => "Hostname"
    ])
    @include('l.modal-button',[
        "class" => "btn-warning",
        "target_id" => "file_upload",
        "text" => "Dosya Yükle"
    ])
    @include('l.modal-button',[
        "class" => "btn-primary",
        "target_id" => "file_download",
        "text" => "Dosya İndir"
    ])
    @include('l.modal-button',[
        "class" => "btn-success",
        "target_id" => "terminal",
        "text" => "Terminal"
    ])
    @include('l.modal-button',[
        "class" => "btn-info",
        "target_id" => "give_permission",
        "text" => "Yetki Ver"
    ])<br><br>
        <h5>Hostname : {{$hostname}}</h5>
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

    @include('l.modal-button',[
        "class" => "btn-danger",
        "target_id" => "delete",
            "text" => "Sunucuyu Sil"
    ])

    @include('l.modal',[
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

    @include('l.modal-iframe',[
        "id" => "terminal",
        "url" => route('server_terminal',["server_id" => $server->_id]),
        "title" => "$server->name sunucusu terminali"
    ])

    @include('l.modal',[
        "id"=>"edit",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı:$server->name" => "name:text",
            "Kontrol Portu:$server->control_port" => "control_port:number",
            "Şehir:city" => cities(),
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])

    @include('l.modal',[
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

    @include('l.modal',[
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

    @include('l.modal',[
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

    @include('l.modal',[
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

    @include('l.modal',[
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
    
    @if(count($input_extensions))
        @include('l.modal',[
            "id"=>"install_extension",
            "title" => "Servis Yükle",
            "url" => route('server_extension'),
            "next" => "message",
            "selects" => $input_extensions,
            "inputs" => [
                "Sunucu Id:$server->_id" => "server_id:hidden"
            ],
            "submit_text" => "Değiştir"
        ])
    @endif
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
            //loading();
            window.location.assign('/sunucu/indir?path=' + form.getElementsByTagName('input')[0].value + '&server_id=' + form.getElementsByTagName('input')[1].value);
            return false;
        }
    </script>
@endsection