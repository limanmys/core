@extends('layouts.app')

@section('content')

    @include('title',[
        "title" => $server->name       
    ])
    <h5>Hostname : {{$hostname}}</h5>
    <button class="btn btn-success" onclick="location.href = '/sunucular/';">{{__("Geri Dön")}}</button>

    @include('modal-button',[
        "class" => "btn-primary",
        "target_id" => "editModal",
        "text" => "Düzenle"
    ]) 
    @include('modal-button',[
        "class" => "btn-warning",
        "target_id" => "commandModal",
        "text" => "Komut Çalıştır"
    ])    
    @include('modal-button',[
        "class" => "btn-secondary",
        "target_id" => "addService",
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
    <h4>{{__("Servis Durumları")}}</h4>
        @foreach($services as $service)
            <button type="button" class="btn btn-info btn-lg" style="cursor:default;" id="status_{{$service}}">
                {{strtoupper($service)}}
            </button>
        @endforeach
    <br><br>
    <pre>
        @isset($stats)
            {{$stats}}
        @endisset
    </pre>
    
    @include('modal-button',[
        "class" => "btn-danger",
        "target_id" => "deleteModal",
            "text" => "Sunucuyu Sil"
    ])

    @include('modal',[
        "id"=>"deleteModal",
        "title" => $server->name,
        "url" => route('server_remove'),
        "text" => "isimli sunucuyu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        "next" => "debug",
        "inputs" => [
            "Sunucu Id:$server->_id" => "server_id:hidden"
        ],
        "submit_text" => "Sunucuyu Sil"
    ])

    @include('modal',[
        "id"=>"editModal",
        "title" => "Sunucuyu Düzenle",
        "url" => route('server_update'),
        "next" => "reload",
        "inputs" => [
            "Sunucu Adı" => "name:text",
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
            "İp Adresi" => "ip_address:text",
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

    <div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title">
                        {{__("Komut Çalıştır")}}
                    </h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" onclick="document.getElementById('run_command').disabled = !this.checked;" id="commandResponsibility">
                        <label class="form-check-label">
                            {{__("Özel komut çalıştırma sorumluluğunu kabul ediyorum.")}}
                        </label>
                    </div><br>
                    <div class="form-group">
                        <textarea class="form-control" id="run_command" rows="3" disabled></textarea>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" id="commandOutput" rows="3" readonly hidden></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="runCommand()">Çalıştır</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addService" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">DNS Kurulumu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <h5>Domain Adı</h5>
                        <input id="dns_domain" type="text" class="form-control"
                               placeholder="Domain Adı">
                    </div>
                    <div class="form-group">
                        <h5>Interface</h5>
                        <input id="dns_interface" type="text" class="form-control"
                               placeholder="Interface">
                    </div>
                    <div class="collapse" id="installServiceOutput">

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" onclick="installService()">Kur</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkStatus(extension) {
            var element = $("#status_" + extension);
            $.ajax({
                    url : "{{ route('server_check') }}",
                    type : "POST",
                    data : {
                        extension : extension,
                        server_id : '{{$server->_id}}'
                    },
                    success : function (data) {
                        if(data["result"] === 200){
                            element.removeClass('btn-info').removeClass('btn-danger').addClass('btn-success');
                        }else if(data["result"] === 201){
                            element.removeClass('btn-success').removeClass('btn-info').addClass('btn-danger');
                        }else{
                            element.removeClass('btn-success').removeClass('btn-info').addClass('btn-secondary');
                        }
                    }
            });
        }
        
        @foreach($server->extensions as $extension)
            setInterval(function () {
                checkStatus('{{$extension}}');
            }, 3000);
        @endforeach
    </script>
@endsection