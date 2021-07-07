@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Kasa")}}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <h3 class="profile-username text-center font-weight-bold">{{__("Kasa")}}</h3>
                    <p class="text-muted text-center mb-0">{{ __("Bu sayfadan mevcut verilerini görebilirsiniz. Buradaki veriler, eklentiler tarafından kullanılmaktadır.") }}</p>
                </div>
            </div>
        </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#add_settings"><i class="fa fa-key "></i> {{__("Anahtar Ekle")}}</button>
                <button type="button" class="btn btn-secondary" onclick="cleanSessions()">{{__("Önbelleği Temizle")}}</button>
                <div class="tab-pane active" id="settings" style="margin-top: 15px;">
                    <div class="alert alert-info alert-dismissible">
                        <h5><i class="icon fas fa-info"></i> {{ __('Bilgilendirme!') }}</h5>
                        {{__("Güvenliğiniz için varolan verileriniz gösterilmemektedir.")}}
                    </div>
                    @include('table',[
                    "value" => $settings,
                        "title" => [
                            "Veri Adı" , "Sunucu" , "*hidden*", "*hidden*"
                        ],
                        "display" => [
                            "name" , "server_name", "id:id", "type:type"
                        ],
                        "menu" => [
                            "Güncelle" => [
                                "target" => "updateSetting",
                                "icon" => " context-menu-icon-edit"
                            ],
                            "Sil" => [
                                "target" => "delete_settings",
                                "icon" => " context-menu-icon-delete"
                            ]
                        ]
                    ])
                </div>
            </div>
        </div>
        </div>
    </div>
@component('modal-component',[
    "id" => "add_settings",
    "title" => "Anahtar Ekle"
])
<div class="modal-body">
    <label>{{__("Sunucu")}}</label>
    <select name="targetServer" id="targetServer" class="select2 form-control" onchange="setIPAdress()">
    @foreach(servers() as $server)
        <option value="{{$server->ip_address . ':' . $server->id}}">{{$server->name}}</option>
    @endforeach
    </select>
    <input type="text" id="serverHostName" hidden>
</div>
@include('keys.add',["success" => "addServerKey()"])

@endcomponent

    @include('modal',[
        "id"=>"update_settings",
        "title" => "Veriyi Güncelle",
        "url" => route('user_setting_update'),
        "next" => "reload",
        "inputs" => [
            "Yeni Değer" => "new_value:password",
            "id:-" => "setting_id:hidden",
            "type:-" => "type:hidden"
        ],
        "submit_text" => "Veriyi Güncelle"
    ])

    @include('modal',[
       "id"=>"delete_settings",
       "title" =>"Veriyi Sil",
       "url" => route('user_setting_remove'),
       "text" => "Veri'yi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Setting Id:'null'" => "id:hidden",
           "-:-" => "type:hidden"
       ],
       "submit_text" => "Veriyi Sil"
   ])

   <script>
        $("#useKeyLabel").fadeOut();
        keySettingsChanged();
        function cleanSessions(){
            request('{{route('clean_sessions')}}', new FormData(), function(response){
                showSwal("{{__("Önbellek temizlendi!")}}",'success',2000);
                reload();
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function updateSetting(element){
            var type = element.querySelector('#type').innerHTML;
            var id = element.querySelector('#id').innerHTML;
            if(type == "key"){
                showSwal("{{__('Sunucu anahtarını güncellemek için yeniden anahtar eklemelisiniz.')}}","info",2000);
                $("#add_settings").modal('show');
            }else{
                $("#update_settings").find('input[name=setting_id]').val(id);
                $("#update_settings").find('input[name=type]').val(type);
                $("#update_settings").modal('show');
            }
        }

        function setIPAdress(){
            $("#serverHostName").val($("#targetServer").val().split(":")[0]);
        }

        function addServerKey(){
            if(isKeyOK == false){
                showSwal("Lütfen önce anahtarınızı doğrulayın!",'error',2000);
                return;
            }
            showSwal("Ekleniyor...","info");
            let form = new FormData();
            form.append('username',$("#keyUsername").val());
            form.append('password',$("#keyPassword").val());
            form.append('type',$("#keyType").val());
            form.append('key_port',$("#port").val());
            form.append('server_id',$("#targetServer").val().split(":")[1]);
            request("{{route('key_add')}}",form,function(success){
                let json = JSON.parse(success);
                showSwal(json.message,'success','2000');
                setTimeout(() => {
                    reload();
                }, 1500);
            },function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }
        $("#keySubmitButton").text("{{__('Anahtarı Ekle')}}");
        setIPAdress();
   </script>
@endsection