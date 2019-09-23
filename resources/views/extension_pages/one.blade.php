@extends('layouts.app')

@section('content')
    @php($extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true))

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a
                        href="{{route('extensions_settings')}}">{{__("Eklenti Yönetimi")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $extension["name"] }}</li>
        </ol>
    </nav>
    @include('l.errors')

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">{{__("Genel Ayarlar")}}</a></li>
            <li id="server_type"><a href="#tab_2" data-toggle="tab"
                                    aria-expanded="false">{{__("Eklenti Veritabanı")}}</a></li>
            <li id="server_type"><a href="#tab_2_2" data-toggle="tab" aria-expanded="false">{{__("Sayfa Ayarları")}}</a>
            </li>
            <li class=""><a href="#tab_3" data-toggle="tab" aria-expanded="false">{{__("Widgetlar")}}</a></li>
            <li class=""><a href="#tab_4" data-toggle="tab" aria-expanded="false">{{__("Fonksiyonlar")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1">
                <h3>{{__("Eklenti Adı")}}</h3>
                <input id="extensionName" type="text" class="form-control" value="{{$extension["name"]}}" disabled required>
                <h3>{{__("Yayınlayan")}}</h3>
                <input type="text" name="name" class="form-control" value="{{$extension["publisher"]}}" disabled required>
                <h3>{{__("Destek Email'i")}}</h3>
                <input id="support" type="text" name="email" class="form-control" value="{{$extension["support"]}}" required>
                <h3>{{__("Logo (Font Awesome Ikon)")}}</h3>
                <input id="icon" type="text" name="icon" class="form-control" value="{{$extension["icon"]}}" required>
                <h3>{{__("Versiyon")}}</h3>
                <input id="version" type="text" name="version" class="form-control" value="{{$extension["version"]}}" required>
                <h3>{{__("Ayar Doğrulama Fonksiyonu/Betiği")}}</h3>
                <input id="verification" type="text" name="verification" class="form-control" value="{{array_key_exists("verification",$extension) ? $extension["verification"] : ""}}" required>
                <h3>{{__("Servis Adı yada Kontrol Etmek için Port")}}</h3>
                <input id="service" type="text" name="service" class="form-control" value="{{$extension["service"]}}" required>
                <h3>{{__("SSL Sertifikası Eklenecek Portlar")}}</h3>
                <small>{{__("Birden fazla port yazmak için aralarında virgül bırakabilirsiniz.")}}</small>
                <input id="sslPorts" type="text" name="service" class="form-control" value="{{array_key_exists("sslPorts",$extension) ? $extension["sslPorts"] : ""}}" required><br>
                <button class="btn btn-success btn-sm" onclick="updateExtension('general')">{{__("Kaydet")}}</button>
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane" id="tab_2">
                @include('l.modal-button',[
                    "class" => "btn-success btn-sm",
                    "target_id" => "add_database",
                    "text" => "Veri Ekle"
                ])<br><br>
                @include('l.table',[
                    "value" => $extension["database"],
                    "title" => [
                        "Adı" , "Türü" , "Variable Adı", "" , "", ""
                    ],
                    "display" => [
                        "name" , "type", "variable", "variable:variable_old", "type:type_old", "name:name_old"
                    ],
                    "menu" => [
                        "Ayarları Düzenle" => [
                            "target" => "edit_database",
                            "icon" => "fa-edit"
                        ],
                        "Sil" => [
                            "target" => "remove_database",
                            "icon" => "fa-trash"
                        ]
                    ]
                ])

                @include('l.modal',[
                    "id"=>"add_database",
                    "title" => "Veri Ekle",
                    "url" => route('extension_settings_add'),
                    "next" => "reload",
                    "inputs" => [
                        "Adı" => "name:text:Veri adı oluşturulan formlarda gösterilmek için kullanılır.",
                        "Türü" => "type:text:Verinizin türü form elemanını belirler. Örneğin text, password vs.",
                        "Variable Adı" => "variable:text:Eklenti içinden veriye bu isim ile erişirsiniz.",
                        "table:database" => "table:hidden"
                    ],
                    "submit_text" => "Veri Ekle"
                ])

                @include('l.modal',[
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
                        "table:database" => "table:hidden"
                    ],
                    "submit_text" => "Veri Düzenle"
                ])
                @include('l.modal',[
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
            <div class="tab-pane" id="tab_2_2">
                @include('l.modal-button',[
                    "class" => "btn-success btn-sm",
                    "target_id" => "add_view",
                    "text" => "Sayfa Ekle"
                ])<br><br>
                @include('l.table',[
                    "value" => $extension["views"],
                    "title" => [
                        "Sayfa Adı" , "Çalışacak Betik/Fonksiyon" , "", ""
                    ],
                    "display" => [
                        "name" , "scripts", "name:name_old", "scripts:scripts_old"
                    ],
                    "menu" => [
                        "Ayarları Düzenle" => [
                            "target" => "edit_view",
                            "icon" => "fa-edit"
                        ],
                        "Kodu Düzenle" => [
                            "target" => "editPage",
                            "icon" => "fa-edit"
                        ],
                        "Sil" => [
                            "target" => "remove_view",
                            "icon" => "fa-trash"
                        ]
                    ]
                ])
                @include('l.modal',[
                    "id"=>"add_view",
                    "title" => "Sayfa Ekle",
                    "url" => route('extension_settings_add'),
                    "next" => "reload",
                    "inputs" => [
                        "Sayfa Adı" => "name:text:Sayfanızın adı.",
                        "table:views" => "table:hidden",
                        "Çalışacak Betik/Fonksiyon" => "scripts:text:Sayfanız çalışıtırılmadan önce çalışacak betik veya fonksiyon."
                    ],
                    "submit_text" => "Sayfa Ekle"
                ])
                @include('l.modal',[
                    "id"=>"edit_view",
                    "title" => "Sayfa Düzenle",
                    "url" => route('extension_settings_update'),
                    "next" => "updateTable",
                    "inputs" => [
                        "Sayfa Adı" => "name:text",
                        "Çalışacak Betik/Fonksiyon" => "d-scripts:text",
                        "Sayfa Adı:a" => "name_old:hidden",
                        "table:views" => "table:hidden",
                        "Çalışacak Betik/Fonksiyon:a" => "scripts_old:hidden"
                    ],
                    "submit_text" => "Sayfa Düzenle"
                ])
                @include('l.modal',[
                    "id"=>"remove_view",
                    "title" => "Sayfa'yı Sil",
                    "url" => route('extension_settings_remove'),
                    "next" => "reload",
                    "text" => "Sayfa'yı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
                    "inputs" => [
                        "Sayfa Adı:a" => "name:hidden",
                        "table:views" => "table:hidden",
                        "Çalışacak Betik/Fonksiyon:a" => "scripts:hidden"
                    ],
                    "submit_text" => "Sayfa'yı Sil"
                ])
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane" id="tab_3">
                @include('l.modal-button',[
                    "class" => "btn-success btn-sm",
                    "target_id" => "add_widget",
                    "text" => "Widget Ekle"
                ])<br><br>
                @include('l.table',[
                    "value" => $extension["widgets"],
                    "title" => [
                        "Widget Adı" , "Türü" , "Çalışacak Betik/Fonksiyon" , "", "", ""
                    ],
                    "display" => [
                        "name" , "type", "target", "name:name_old", "type:type_old", "target:target_old"
                    ],
                    "menu" => [
                        "Düzenle" => [
                            "target" => "edit_widget",
                            "icon" => "fa-edit"
                        ],
                        "Sil" => [
                            "target" => "remove_widget",
                            "icon" => "fa-trash"
                        ]
                    ]
                ])
                @include('l.modal',[
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
                        "Çalışacak Betik/Fonksiyon" => "target:text:Widget verilerinin hangi fonksiyon yada betikten getirileceğini belirler."
                    ],
                    "submit_text" => "Widget Ekle"
                ])
                @include('l.modal',[
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
                        "Çalışacak Betik/Fonksiyon" => "target:text",
                        "Widget Adı:a" => "name_old:hidden",
                        "table:widgets" => "table:hidden",
                        "Türü:a" => "type_old:hidden",
                        "Çalışacak Betik/Fonksiyon:a" => "target_old:hidden"
                    ],
                    "submit_text" => "Widget Düzenle"
                ])
                @include('l.modal',[
                    "id"=>"remove_widget",
                    "title" => "Widget'ı Sil",
                    "url" => route('extension_settings_remove'),
                    "next" => "reload",
                    "text" => "Widget'ı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
                    "inputs" => [
                        "Widget Adı:a" => "name:hidden",
                        "Türü:a" => "type:hidden",
                        "table:widgets" => "table:hidden",
                        "Çalışacak Betik/Fonksiyon:a" => "target:hidden"
                    ],
                    "submit_text" => "Widget'ı Sil"
                ])
            </div>
            <div class="tab-pane" id="tab_4">
                <button class="btn btn-success" data-toggle="modal" data-target="#addFunctionModal"><i class="fa fa-plus"></i></button><br><br>
                @include('l.table',[
                    "value" => array_key_exists("functions",$extension) ? $extension["functions"] : [],
                    "title" => [
                        "Fonksiyon Adı" , "Ceviri Key'i", "Yetki Sistemi" ,"*hidden*"
                    ],
                    "display" => [
                        "name" , "description", "isActive", "name:old"
                    ],
                    "menu" => [
                        "Ayarları Düzenle" => [
                            "target" => "updateFunctionModal",
                            "icon" => "fa-edit"
                        ],
                        "Sil" => [
                            "target" => "removeFunctionModal",
                            "icon" => "fa-trash"
                        ]
                    ]
                ])

                @include('l.modal',[
                    "id"=>"addFunctionModal",
                    "title" => "Fonksiyon Ekle",
                    "url" => route('extension_add_function'),
                    "next" => "reload",
                    "inputs" => [
                        "Fonksiyon Adı" => "name:text",
                        "Açıklama (Çeviri Key)" => "description:text",
                        "Yetki Sistemine Dahil Et" => "isActive:checkbox",
                    ],
                    "submit_text" => "Fonksiyon Ekle"
                ])

                @include('l.modal',[
                    "id"=>"updateFunctionModal",
                    "title" => "Fonksiyon Duzenle",
                    "url" => route('extension_update_function'),
                    "next" => "reload",
                    "inputs" => [
                        "Fonksiyon Adı" => "name:text",
                        "Açıklama (Çeviri Key)" => "description:text",
                        "Yetki Sistemine Dahil Et" => "isActive:checkbox",
                        "-:-" => "old:hidden"
                    ],
                    "submit_text" => "Fonksiyon Duzenle"
                ])

                @include('l.modal',[
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

        </div>
    </div>
    <script>
        function editPage(element){
            let page = $(element).find("#name").text();
            location.href = location.protocol+'//'+location.host+location.pathname + "/" + page;
        }

        function updateExtension(type,tableId = null){
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kaydediliyor...")}}',
                showConfirmButton: false,
            });
            let data = new FormData();
            data.append('type',type);
            data.append('name',$("#extensionName").val());
            data.append('icon',$("#icon").val());
            data.append('support',$("#support").val());
            data.append('version',$("#version").val());
            data.append('service',$("#service").val());
            data.append('sslPorts',$("#sslPorts").val());
            data.append('verification',$("#verification").val());
            request('{{route('extension_settings_update')}}',data,function(){
                Swal.fire({
                    position: 'center',
                    type: 'success',
                    title: "{{__("Başarıyla kaydedildi")}}",
                    showConfirmButton: false,
                    timer: 1500
                });
                setTimeout(function(){
                    location.reload();
                },1500);
            });
        }
    </script>
@endsection
