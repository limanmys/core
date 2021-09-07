@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sunucu Takibi")}}</li>
        </ol>
    </nav>
    
    <div class="row">
            @foreach($monitor_servers as $server)
                @if($server->online)
                <div class="col-md-3 monitorServer" id="{{$server->id}}">
                    <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fa fa-thumbs-up"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">{{ $server->ip_address . " : " . $server->port}}</span>
                        <span class="info-box-number">{{$server->name}}</span>
                        <span class="progress-description">{{__("Son Kontrol : ")}}{{ $server->last_checked }}</span>
                    </div>
                    </div>
                </div>
                @else
                <div class="col-md-3 monitorServer" id="{{$server->id}}">
                    <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fa fa-thumbs-down"></i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">{{ $server->ip_address . " : " . $server->port}}</span>
                        <span class="info-box-number">{{$server->name}}</span>
                        <span class="progress-description">{{__("Son Kontrol : ")}}{{ $server->last_checked }}</span>
                    </div>
                    </div>
                </div>
                @endif
            @endforeach
            </div>

            @component('modal-component',[
                "id" => "addNewMonitor",
                "title" => "Yeni Sunucu Takibi Ekle"
            ])
            <div class="row">
                <div class="col-md-4">
                    <select name="server_list" id="server_list" class="select2">
                        @foreach($servers as $server)
                            <option value="{{$server->ip_address . ':' . $server->control_port}}">{{$server->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" onclick="loadServer()">{{__("Seçili sunucudan bilgileri oku")}}</button>
                </div>
            </div><br>
            <div class="row" id="monitorInputs">
                <div class="col-md-4">
                    @include('inputs', [
                        'inputs' => [
                            "İsim" => "name:text:Kolay hatırlanması için bir isim.",
                        ]
                    ])
                </div>
                <div class="col-md-3">
                    @include('inputs', [
                        'inputs' => [
                            "Sunucu İp Adresi" => "ip_address:text",
                        ]
                    ])
                </div>
                <div class="col-md-1">
                    @include('inputs', [
                        'inputs' => [
                            "Port" => "port:number",
                        ]
                    ])
                </div>
                
            </div><br>
            <button class="btn btn-primary" onclick="addNewServerMonitor()">{{__("Ekle")}}</button>
            @endcomponent
        <script>

            function loadServer(){
                let arr = $("#server_list").val().split(":");
                $("input[name='ip_address']").val(arr[0]);
                $("input[name='port']").val(arr[1]);
            }

            function addNewServerMonitor(){
                showSwal('{{ __("Ekleniyor...") }}',"info");
                let form = new FormData();
                form.append("ip_address", $("input[name='ip_address']").val());
                form.append("port", $("input[name='port']").val());
                form.append("name", $("input[name='name']").val());
                request("{{route('monitor_add')}}",form,function(success){
                    let json = JSON.parse(success);
                    showSwal(json.message,'success',2000);
                    setTimeout(() => {
                        location.reload();    
                    }, 1500);
                },function(error){
                    let json = JSON.parse(error);
                    showSwal(json.message,'error',2000);
                });
            }
            $.contextMenu({
                selector: '.monitorServer',
                callback: function (key, options) {
                    let form = new FormData();
                    form.append('server_monitor_id',options.$trigger[0].getAttribute("id"));
                    switch(key){
                        case "refresh":
                            request("{{route('monitor_refresh')}}",form,function(success){
                                let json = JSON.parse(success);
                                showSwal(json.message,'success',2000);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            },function(error){
                                let json = JSON.parse(error);
                                showSwal(json.message,'error',2000);
                            });
                            break;
                        case "remove":
                            request("{{route('monitor_remove')}}",form,function(success){
                                let json = JSON.parse(success);
                                showSwal(json.message,'success',2000);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            },function(error){
                                let json = JSON.parse(error);
                                showSwal(json.message,'error',2000);
                            });
                            break;
                    }
                },
                items: {
                    "refresh" : {
                        "name" : "{{ __("Şimdi Güncelle") }}",
                        "icon" : "fas fa-redo"
                    },
                    "remove" : {
                        "name" : "{{ __("Sil") }}",
                        "icon" : "fas fa-trash"
                    },
                }
            });
            function addNewMonitor(){
                $("#addNewMonitor").modal('show');
            }
        </script>

        <div class="float" style="margin-bottom: 50px;" onclick="addNewMonitor()" id="requestRecordButton">
            <i class="fas fa-plus"></i>
        </div>
        <style>
            .float {
                position: fixed;
                font-size: 25px;
                line-height: 50px;
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
                background-color: grey;
                color: #FFF;
                border-radius: 50px;
                text-align: center;
                box-shadow: 2px 2px 3px #999;
            }
        </style>
@endsection