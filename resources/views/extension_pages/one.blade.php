@extends('layouts.app')

@section('content')

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a
                        href="{{route('settings')}}">{{__("Sistem Ayarları")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a
                        href="{{route('settings')}}#extensions">{{__("Eklentiler")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $extension["name"] }}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#tab_1" role="tab" aria-selected="true">{{__("Genel Ayarlar")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab_2" role="tab" >{{__("Eklenti Veritabanı")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab_3" role="tab">{{__("Bileşenler")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab_4" role="tab">{{__("Fonksiyonlar")}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab_5" role="tab">{{__("Mail Tagleri")}}</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab_1" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>{{__("Eklenti Adı")}}</label>
                                <input type="text" class="form-control" value="{{$extension['name']}}" disabled required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Yayınlayan")}}</label>
                                <input type="text" class="form-control" value="{{$extension['publisher']}}" disabled required>
                            </div>
                            <div class="col-md-4 mb-3">
                            <label>{{__("Yazılım Dili")}}</label>
                                <select class="form-control" value="{{$extension['language']}}" autocomplete="off" disabled><br>
                                    <option value="php" @if($extension["language"] == "php") selected='true' @endif >PHP 7.3</option>
                                    <option value="python" @if($extension["language"] == "python") selected='true' @endif>Python 3</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Anahtar'ı Zorunlu Kıl")}}</label>
                                <select id="require_key" class="form-control" autocomplete="off" enabled><br>
                                    <option value="true" @if(array_key_exists("require_key",$extension) && $extension["require_key"] == "true") selected='true' @endif >Evet</option>
                                    <option value="false" @if(!array_key_exists("require_key",$extension) || $extension["require_key"] != "true") selected='true' @endif>Hayır</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Destek Email'i")}}</label>
                                <input id="support" type="text" name="email" class="form-control" value="{{$extension["support"]}}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Logo (Font Awesome Ikon)")}}</label>
                                <input id="icon" type="text" name="icon" class="form-control" value="{{$extension["icon"]}}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Paket bağımlılıkları")}}</label>
                                <input id="dependencies" type="text" name="dependencies" class="form-control" value="{{array_key_exists("dependencies",$extension) ? $extension["dependencies"] : ""}}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Versiyon")}}</label>
                                <input id="version" type="text" name="version" class="form-control" value="{{$extension["version"]}}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Ayar Doğrulama Fonksiyonu/Betiği")}}</label>
                                <input id="verification" type="text" name="verification" class="form-control" value="{{array_key_exists("verification",$extension) ? $extension["verification"] : ""}}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("Gerekli Minimum Liman Sürüm Kodu")}}</label>
                                <input id="supportedLiman" type="text" name="icon" class="form-control" value="{{array_key_exists("supportedLiman",$extension) ? $extension["supportedLiman"] : getVersionCode()}}" required>
                                <small style="cursor:pointer; font-style: italic;" onclick="$('#supportedLiman').val('{{getVersionCode()}}')">{{__("Mevcut Liman Sürüm Kodunu Al")}}</small>
                            </div>    
                            <div class="col-md-4 mb-3">
                                <label>{{__("Servis Adı yada Kontrol Etmek için Port")}}</label>
                                <input id="service" type="text" name="service" class="form-control" value="{{$extension["service"]}}" required>
                                <small>{{__("Birden fazla paket yazmak için aralarında boşluk bırakabilirsiniz.")}}</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{__("SSL Sertifikası Eklenecek Portlar")}}</label>
                                <input id="sslPorts" type="text" name="service" class="form-control" value="{{array_key_exists("sslPorts",$extension) ? $extension["sslPorts"] : ""}}" required>
                                <small>{{__("Birden fazla port yazmak için aralarında virgül bırakabilirsiniz.")}}</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{__("Eklenti Görünen Adı")}}</label>
                                <input id="display_name" type="text" class="form-control" value="{{$extension['display_name']}}" required>
                            </div>
                        </div>    
                       
                        <button class="btn btn-success " onclick="updateExtension('general')"><i class="fas fa-save mr-1"></i>{{__("Kaydet")}}</button><br>
                    </div>
                    <div class="tab-pane fade show" id="tab_2" role="tabpanel">
                        @include('modal-button',[
                            "class" => "btn-success btn-sm",
                            "target_id" => "add_database",
                            "text" => "Veri Ekle"
                        ])<br><br>
                        @include('table',[
                            "value" => collect($extension["database"])->map(function($item){
                                $item['required'] = isset($item['required']) && $item['required'] ? 'on' : '';
                                $item['global'] = isset($item['global']) && $item['global'] ? 'on' : '';
                                $item['writable'] = isset($item['writable']) && $item['writable'] ? 'on' : '';
                                return $item;
                            }),
                            "title" => [
                                "Adı" , "Türü" , "Variable Adı", "*hidden*" , "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*"
                            ],
                            "display" => [
                                "name" , "type", "variable", "variable:variable_old", "type:type_old", "name:name_old", "required:required", "global:global", "writable:writable"
                            ],
                            "menu" => [
                                "Ayarları Düzenle" => [
                                    "target" => "edit_database",
                                    "icon" => "  context-menu-icon-edit"
                                ],
                                "Sil" => [
                                    "target" => "remove_database",
                                    "icon" => " context-menu-icon-delete"
                                ]
                            ]
                        ])

                        @include('modal',[
                            "id"=>"add_database",
                            "title" => "Veri Ekle",
                            "url" => route('extension_settings_add'),
                            "next" => "reload",
                            "inputs" => [
                                "Adı" => "name:text:Veri adı oluşturulan formlarda gösterilmek için kullanılır.",
                                "Türü" => "type:text:Verinizin türü form elemanını belirler. Örneğin text, password vs.",
                                "Variable Adı" => "variable:text:Eklenti içinden veriye bu isim ile erişirsiniz.",
                                "Zorunlu Alan" => "required:checkbox",
                                "Kullanıcılar Arası Paylaşımlı" => "global:checkbox",
                                "Yazılabilir" => "writable:checkbox",
                                "table:database" => "table:hidden"
                            ],
                            "submit_text" => "Veri Ekle"
                        ])

                        @include('modal',[
                            "id"=>"edit_database",
                            "title" => "Veri Düzenle",
                            "url" => route('extension_settings_update'),
                            "next" => "updateTable",
                            "inputs" => [
                                "Adı" => "name:text",
                                "Türü" => "type:text",
                                "Variable Adı" => "variable:text",
                                "Sayfa Adı:a" => "name_old:hidden",
                                "Türü:a" => "type_old:hidden",
                                "Variable Adı:a" => "variable_old:hidden",
                                "Zorunlu Alan" => "required:checkbox",
                                "Kullanıcılar Arası Paylaşımlı" => "global:checkbox",
                                "Yazılabilir" => "writable:checkbox",
                                "table:database" => "table:hidden"
                            ],
                            "submit_text" => "Veri Düzenle"
                        ])
                        @include('modal',[
                            "id"=>"remove_database",
                            "title" => "Veri'yi Sil",
                            "url" => route('extension_settings_remove'),
                            "next" => "reload",
                            "text" => "Veri'yi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
                            "inputs" => [
                                "Sayfa Adı:a" => "name:hidden",
                                "Türü:a" => "type:hidden",
                                "Variable Adı:a" => "variable:hidden",
                                "table:database" => "table:hidden"
                            ],
                            "submit_text" => "Veri'yi Sil"
                        ])
                    </div>
                    <div class="tab-pane fade show" id="tab_3" role="tabpanel">
                        @include('modal-button',[
                            "class" => "btn-success btn-sm",
                            "target_id" => "add_widget",
                            "text" => "Widget Ekle"
                        ])<br><br>
                        @include('table',[
                            "value" => $extension["widgets"],
                            "title" => [
                                "Widget Adı" , "Türü" , "Çalışacak Fonksiyon" , "*hidden*", "*hidden*", "*hidden*"
                            ],
                            "display" => [
                                "name" , "type", "target", "name:name_old", "type:type_old", "target:target_old"
                            ],
                            "menu" => [
                                "Düzenle" => [
                                    "target" => "edit_widget",
                                    "icon" => " context-menu-icon-edit"
                                ],
                                "Sil" => [
                                    "target" => "remove_widget",
                                    "icon" => " context-menu-icon-delete"
                                ]
                            ]
                        ])
                        @include('modal',[
                            "id"=>"add_widget",
                            "title" => "Widget Ekle",
                            "url" => route('extension_settings_add'),
                            "next" => "reload",
                            "inputs" => [
                                "Widget Adı" => "name:text:Widgetınızın adı.",
                                "İkon (FontAwesome)" => "icon:text:Widgetınızın sol menüde görüntülenecek ikonu.",
                                "Türü:type:Widget türü widgetın görünümünü belirler." => [
                                    "Sayı" => "count_box",
                                    "Grafik" => "chart"
                                ],
                                "table:widgets" => "table:hidden",
                                "Çalışacak Fonksiyon" => "target:text:Widget verilerinin hangi fonksiyon yada betikten getirileceğini belirler."
                            ],
                            "submit_text" => "Widget Ekle"
                        ])
                        @include('modal',[
                            "id"=>"edit_widget",
                            "title" => "Widget Düzenle",
                            "url" => route('extension_settings_update'),
                            "next" => "updateTable",
                            "inputs" => [
                                "Widget Adı" => "name:text",
                                "İkon (FontAwesome)" => "icon:text",
                                "Türü:type" => [
                                    "Sayı" => "count_box",
                                    "Grafik" => "chart"
                                ],
                                "Çalışacak Fonksiyon" => "target:text",
                                "Widget Adı:a" => "name_old:hidden",
                                "table:widgets" => "table:hidden",
                                "Türü:a" => "type_old:hidden",
                                "Çalışacak Fonksiyon:a" => "target_old:hidden"
                            ],
                            "submit_text" => "Widget Düzenle"
                        ])
                        @include('modal',[
                            "id"=>"remove_widget",
                            "title" => "Widget'ı Sil",
                            "url" => route('extension_settings_remove'),
                            "next" => "reload",
                            "text" => "Widget'ı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
                            "inputs" => [
                                "Widget Adı:a" => "name:hidden",
                                "Türü:a" => "type:hidden",
                                "table:widgets" => "table:hidden",
                                "Çalışacak Fonksiyon:a" => "target:hidden"
                            ],
                            "submit_text" => "Widget'ı Sil"
                        ])
                    </div>
                    <div class="tab-pane fade show" id="tab_4" role="tabpanel">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addFunctionModal"><i data-toggle="tooltip" title="{{ __('Ekle') }}" class="fa fa-plus"></i></button><br><br>
                        @include('table',[
                            "value" => array_key_exists("functions",$extension) ? $extension["functions"] : [],
                            "title" => [
                                "Fonksiyon Adı" , "Çeviri Key'i", "Yetki Sistemi", "Logu Görüntüle" ,"*hidden*"
                            ],
                            "display" => [
                                "name" , "description", "isActive", "displayLog", "name:old"
                            ],
                            "menu" => [
                                "İzin Parametreleri" => [
                                    "target" => "updateFunctionParameters",
                                    "icon" => "fa-cog"
                                ],
                                "Ayarları Düzenle" => [
                                    "target" => "updateFunctionModalHandler",
                                    "icon" => " context-menu-icon-edit"
                                ],
                                "Sil" => [
                                    "target" => "removeFunctionModal",
                                    "icon" => " context-menu-icon-delete"
                                ]
                            ]
                        ])

                        @include('modal',[
                            "id"=>"addFunctionModal",
                            "title" => "Fonksiyon Ekle",
                            "url" => route('extension_add_function'),
                            "next" => "reload",
                            "inputs" => [
                                "Fonksiyon Adı" => "name:text",
                                "Açıklama (Çeviri Key)" => "description:text",
                                "Yetki Sistemine Dahil Et" => "isActive:checkbox",
                                "Sunucu Loglarında Görüntüle" => "displayLog:checkbox",
                            ],
                            "submit_text" => "Fonksiyon Ekle"
                        ])

                        @include('modal',[
                            "id"=>"updateFunctionModal",
                            "title" => "Fonksiyon Duzenle",
                            "url" => route('extension_update_function'),
                            "next" => "reload",
                            "inputs" => [
                                "Fonksiyon Adı" => "name:text",
                                "Açıklama (Çeviri Key)" => "description:text",
                                "Yetki Sistemine Dahil Et" => "isActive:checkbox",
                                "Sunucu Loglarında Görüntüle" => "displayLog:checkbox",
                                "-:-" => "old:hidden"
                            ],
                            "submit_text" => "Fonksiyon Duzenle"
                        ])

                        @component('modal-component',[
                            "id" => "updateFunctionParametersModal",
                            "title" => "Fonksiyon İzin Parametreleri"
                        ])
                            <button class="btn btn-success" onclick="addFunctionParameters()">
                                <i data-toggle="tooltip" title="{{ __('Ekle') }}" class="fa fa-plus"></i>
                            </button>
                            <div id="functionParameters" class="mt-2"></div>
                        @endcomponent

                        @include('modal',[
                            "id"=>"addFunctionParametersModal",
                            "title" => "Fonksiyon İzin Parametresi Ekle",
                            "url" => route('extension_add_function_parameters'),
                            "next" => "getActiveFunctionParameters",
                            "inputs" => [
                                "Parametre Adı" => "name:text",
                                "Değişken Adı" => "variable:text",
                                "Tip:type" => [
                                    "Yazı" => "text",
                                    "Uzun Metin" => "textarea",
                                    "Sayı" => "number"
                                ],
                                "function_name:function_name" => "function_name:hidden",
                            ],
                            "submit_text" => "Kaydet"
                        ])

                        @include('modal',[
                            "id"=>"removeFunctionModal",
                            "title" => "Fonksiyonu Sil",
                            "url" => route('extension_remove_function'),
                            "next" => "reload",
                            "text" => "Fonksiyonu silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
                            "inputs" => [
                                "-:-" => "name:hidden"
                            ],
                            "submit_text" => "Fonksiyonu Sil"
                        ])
                    </div>
                    <div class="tab-pane fade show" id="tab_5" role="tabpanel">
                        @include('table',[
                            "value" => array_key_exists("mail_tags",$extension) ? $extension["mail_tags"] : [],
                            "title" => [
                                "Tag Açıklaması" , "Tag"
                            ],
                            "display" => [
                                "description" , "tag"
                            ],
                            "menu" => [
                                "Ayarları Düzenle" => [
                                    "target" => "updateFunctionModalHandler",
                                    "icon" => " context-menu-icon-edit"
                                ],
                                "Sil" => [
                                    "target" => "removeFunctionModal",
                                    "icon" => " context-menu-icon-delete"
                                ]
                            ]
                        ])
                    </div>
                </div>
        </div>
    </div>
    <script>
        customRequestData["extension_id"] = "{{extension()->id}}";
        function editPage(element){
            var page = $(element).find("#name").text();
            window.location.href = location.protocol+'//'+location.host+location.pathname + "/" + page;
        }

        function updateExtension(type,tableId = null){
            if ($("#icon").val() == ""){
                showSwal("{{ __('Lütfen bir ikon ekleyin!') }}","error",2000);
                return;
            }
            showSwal('{{__("Kaydediliyor...")}}','info');
            var data = new FormData();
            data.append('type', type);
            data.append('display_name', $("#display_name").val());
            data.append('icon', $("#icon").val());
            data.append('support', $("#support").val());
            data.append('version', $("#version").val());
            data.append('service', $("#service").val());
            data.append('require_key', $("#require_key").val());
            data.append('sslPorts', $("#sslPorts").val());
            data.append('supportedLiman', $("#supportedLiman").val());
            data.append('verification', $("#verification").val());
            data.append('dependencies', $("#dependencies").val());
            request('{{route('extension_settings_update')}}',data,function(){
                showSwal("{{__("Başarıyla kaydedildi")}}",'success');
                setTimeout(function(){
                    location.reload();
                },1500);
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        var activeFunction = null;

        function getFunctionParameters(function_name){
            showSwal('{{__("Yükleniyor...")}}','info');

            var data = new FormData();
            data.append('function_name',function_name);
            activeFunction = function_name;

            request('{{route('extension_function_parameters')}}',data,function(response){
                Swal.close();
                $('#updateFunctionParametersModal').find('#functionParameters').html(response);
                $('#updateFunctionParametersModal')
                    .find('#functionParameters table')
                    .DataTable(dataTablePresets('normal'));
                $('#updateFunctionParametersModal').modal('show');
            }, function(response){
                var error = JSON.parse(response);
                showSwal(error.message,'error',2000);
            });
        }

        function getActiveFunctionParameters(){
            $('#addFunctionParametersModal').modal('hide');
            getFunctionParameters(activeFunction);
        }

        function updateFunctionParameters(row){
            var function_name = $(row).find("#name").text();
            getFunctionParameters(function_name);
        }

        function addFunctionParameters(){
            $('#addFunctionParametersModal').find('input[name=function_name]').val(activeFunction);
            $('#addFunctionParametersModal').modal('show');
        }

        function deleteFunctionParameters(row){
            Swal.fire({
                title: "{{ __('Onay') }}",
                text: "{{ __('Parametreyi silmek istediğinizden emin misiniz?') }}",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: "{{ __('İptal') }}",
                confirmButtonText: "{{ __('Sil') }}"
            }).then((result) => {
                if (result.value) {
                    showSwal('{{__("Yükleniyor...")}}','info');
                    var parameter_variable = $(row).find("#variable").text();
                    var data = new FormData();
                    data.append('parameter_variable',parameter_variable);
                    data.append('function_name',activeFunction);

                    request('{{route('extension_remove_function_parameters')}}',data,function(response){
                        Swal.close();
                        getFunctionParameters(activeFunction);
                    }, function(response){
                        var error = JSON.parse(response);
                        showSwal(error.message,'error',2000);
                    });
                }
            });
        }
        function updateFunctionModalHandler(element){
            var modal = $("#updateFunctionModal");
            modal.find("input[name='name']").val(element.querySelector('#name').innerHTML);
            modal.find("input[name='old']").val(element.querySelector('#name').innerHTML);
            modal.find("input[name='description']").val(element.querySelector('#description').innerHTML);
            if(element.querySelector('#isActive') && element.querySelector('#isActive').innerHTML == "true"){
                modal.find("input[name='isActive']").prop("checked",true);
            }
            if(element.querySelector('#displayLog') && element.querySelector('#displayLog').innerHTML == "true"){
                modal.find("input[name='displayLog']").prop("checked",true);
            }

            modal.modal('show');
        }
    </script>
@endsection
