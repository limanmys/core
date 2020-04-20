@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Modüller")}}</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Modüller') }}</h3>
        </div>
        <div class="card-body">
            @include('errors')
            @include('table',[
                "value" => $modules,
                "title" => [
                    "Adı" , "Durumu", "Tetikleyici Sayısı", "*hidden*", "*hidden*"
                ],
                "display" => [
                    "name" , "enabled_text", "hook_count", "id:module_id", "enabled:enabled"
                ],
                "menu" => [
                    "Verileri Düzenle" => [
                        "target" => "getModuleSettings",
                        "icon" => " context-menu-icon-edit"
                    ],
                    "Yetkileri Düzenle" => [
                        "target" => "details",
                        "icon" => "fas fa-user-secret"
                    ]
                ]
            ])
        </div>
    </div>
    
    @component('modal-component',[
        "id" => "moduleDetails",
        "title" => "Modül Yetkileri"
    ])
    <div>
        <div class="float-right">
            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-success active" id="moduleEnableButton" onclick="toggleStatusOfModule(true)">
                  <input type="radio" value="enabled">{{__("Aktif")}}
                </label>
                <label class="btn btn-light" id="moduleDisableButton" onclick="toggleStatusOfModule(false)">
                  <input type="radio" value="disabled">{{__("Devre Dışı")}}
                </label>
            </div>
        </div>
    </div><br>
    <div class="row">
        <div class="col-md-12">
            <p>{{__("Tetikleyiciler Liman MYS içerisindeki belli başlı olayların modüllere iletilmesi ile görevlidir.Örneğin bir modül eğer bir anahtar yöntemi oluşturmak ile görevli ise, bunun için anahtar doğrulama tetiğine ihtiyaç duyar.")}}</p>
            <p>{{__("Tetikleyicileri aşağıdan kısıtlayabilirsiniz, fakat bu durumda modül çalışmayabilir.")}}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            {{__("Seçili Tetikleyicilere :  ")}}
            <button class="btn btn-sm btn-primary" onclick="modifyStatusOfHooks('allow')">{{__("İzin Ver")}}</button>
            <button class="btn btn-sm btn-secondary" onclick="modifyStatusOfHooks('deny')">{{__("İznini Sil")}}</button>        
        </div>
    </div><br>
    <div id="moduleHooksWrapper"></div>
    @endcomponent

    @component('modal-component',[
        "id" => "moduleVariables",
        "title" => "Modül Yetkileri"
    ])
    <div id="moduleVariablesWrapper"></div>
    @endcomponent

    <script>
        let lastElement = null;
        function details(element = null){
            if(element == null){
                element = lastElement;
            }else{
                lastElement = element;
            }
            let module_id = element.querySelector('#module_id').innerHTML;
            toggleModuleStatusButtons(element.querySelector("#enabled").innerHTML == 1 ? true : false);
            showSwal("Yükleniyor...",'info');
            let form = new FormData();
            form.append('module_id',module_id);
            request("{{route('module_hooks')}}", form, function(success){
                $("#moduleHooksWrapper").html(success);
                $("#moduleHooks").DataTable(dataTablePresets('multiple'));
                Swal.close();
            }, function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
            $("#moduleDetails").modal('show');
        }

        function modifyStatusOfHooks(target){
            let form = new FormData();
            let items = [];
            let table = $("#moduleHooks").DataTable();
            table.rows( { selected: true } ).data().each(function(element){
                items.push(element[3]);
            });
            if(items.length == 0){
                return showSwal("Lütfen önce bir seçim yapınız.",'error',2000);
            }
            showSwal("Güncelleniyor...",'info');
            form.append('target_ids',JSON.stringify(items));
            form.append('target_status',target);
            request("{{route('module_hooks_update')}}",form,function(success){
                let json = JSON.parse(success);
                showSwal(json.message,'success',2000);
                setTimeout(() => {
                    details();
                }, 1500);
            },function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }

        function toggleStatusOfModule(targetStatus){
            toggleModuleStatusButtons(targetStatus);
            showSwal("Kaydediliyor...",'info');
            let form = new FormData();
            form.append('module_id',lastElement.querySelector('#module_id').innerHTML);
            form.append('moduleStatus',targetStatus ? "true" : "false");
            request("{{route('module_update')}}",form,function(success){
                let json = JSON.parse(success);
                showSwal(json.message,'success',2000);
            },function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }

        function toggleModuleStatusButtons(status){
            if(status){
                $("#moduleEnableButton").attr("class","btn btn-success active");
                $("#moduleDisableButton").attr("class","btn btn-light");
                lastElement.querySelector("#enabled").innerHTML = 1;
                lastElement.querySelector("#enabled_text").innerHTML = "Aktif";
            }else{
                $("#moduleDisableButton").attr("class","btn btn-danger active");
                $("#moduleEnableButton").attr("class","btn btn-light");
                lastElement.querySelector("#enabled").innerHTML = 0;
                lastElement.querySelector("#enabled_text").innerHTML = "İzin Verilmemiş";
            }
            
        }

        function getModuleSettings(element){
            let form = new FormData();
            form.append('module_id',element.querySelector('#module_id').innerHTML);
            request("{{route('module_settings_get')}}",form,function(success){
                $("#moduleVariablesWrapper").html(success);
                $("#moduleVariables").modal('show');
            },function(error){
                let json = JSON.parse(error);
                showSwal(json.message,'error',2000);
            });
        }
    </script>
@endsection