@include('modal-button',[
    "class" => "btn-primary",
    "target_id" => "extensionUpload",
    "text" => "Yükle"
])
@if(env('EXTENSION_DEVELOPER_MODE') == true)
    @include('modal-button',[
        "class" => "btn-secondary",
        "target_id" => "extensionExport",
        "text" => "İndir"
    ])
    @include('modal-button',[
        "class" => "btn-info",
        "target_id" => "newExtension",
        "text" => "Yeni"
    ])
@endif
@if($updateAvailable)
    <button class="btn btn-warning" onclick=showExtensionUpdates()>{{__("Güncellemeleri Yükle")}}</button>
@endif
<div class="float-sm-right">
    <button class="btn btn-success" onclick="window.location.href='{{ route('market') }}'"><i class="fas fa-shopping-cart mr-1"></i>{{ __("Eklenti Mağazası") }}</button>
</div><br><br>
@include('errors')

@include('table',[
    "value" => $extensions,
    "sortable" => true,
    "sortUpdateUrl" => route('update_ext_orders'),
    "afterSortFunction" => 'location.reload',
    "title" => [
        "Eklenti Adı" , "Versiyon", "İmzalayan", "Son Güncelleme Tarihi", "*hidden*"
    ],
    "display" => [
        "name" , "version", "issuer", "updated_at", "id:extension_id"
    ],
    "menu" => [
        "Lisans Ekle" => [
            "target" => "addLicenseToExtension",
            "icon" => " context-menu-icon-add"
        ],
        "Bağımlılıkları Yükle" => [
            "target" => "forceInstallDepencencies",
            "icon" => "fa-box-open"
        ],
        "Zorla Aktifleştir" => [
            "target" => "forceActivateExtension",
            "icon" => "fa-check-double"
        ],
        "Sil" => [
            "target" => "delete",
            "icon" => " context-menu-icon-delete"
    ]
    ],
    "onclick" => env('EXTENSION_DEVELOPER_MODE') ? "extensionDetails" : ""
])
@include('modal',[
    "id"=>"addLicenseToExtension",
    "title" => "Lisans Ekle",
    "text" => "Varsa mevcut lisansınızın üzerine yazılacaktır.",
    "url" => route('add_extension_license'),
    "next" => "reload",
    "inputs" => [
        "Lisans" => "license:text",
        "extension_id:extension_id" => "extension_id:hidden"
    ],
    "submit_text" => "Ekle"
])

@include('modal',[
    "id"=>"forceActivateExtension",
    "title" => "Zorla Aktifleştir",
    "text" => "Bu eklentiyi zorla aktifleştirmek istediğinize emin misiniz?",
    "url" => route('extension_force_enable'),
    "next" => "reload",
    "inputs" => [
        "extension_id:extension_id" => "extension_id:hidden"
    ],
    "submit_text" => "Aktifleştir"
])

@include('modal',[
    "id"=>"forceInstallDepencencies",
    "title" => "Bağımlılıkları Yükle",
    "text" => "Bu eklentinin bağımlılıklarını tekrar yüklemek istediğinize emin misiniz?",
    "url" => route('extension_force_dep_install'),
    "next" => "reload",
    "inputs" => [
        "extension_id:extension_id" => "extension_id:hidden"
    ],
    "submit_text" => "Yükle"
])

@component('modal-component',[
    "id" => "extensionUpdatesModal",
    "title" => "Eklenti Güncellemeleri"
])
<div id="extensionUpdatesWrapper">
    <div class="row">
        <div class="col-md-3">
            <ul class="list-group" id="extensionUpdatesList">
            </ul>
        </div>
        <div class="col-md-9">
            <div class="row">
                <div class="col-md-9">
                    <h3 id="extensionUpdateName"></h3>
                </div>
                <div class="col-md-3">
                    <h2 id="extensionNewVersion"></h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12" id="extensionChangeLogWrapper">
                </div>
            </div><br>
            <button class="btn btn-success" onclick="requestExtensionUpdate()">{{__("Şimdi Güncelle")}}</button>
        </div>
    </div>

</div>
@endcomponent

@include('modal',[
    "id"=>"extensionUpload",
    "title" => "Eklenti Yükle",
    "url" => route('extension_upload'),
    "next" => "reload",
    "error" => "extensionUploadError",
    "inputs" => [
        "Lütfen Eklenti Dosyasını(.lmne) Seçiniz" => "extension:file",
    ],
    "submit_text" => "Yükle"
])
@if(env('EXTENSION_DEVELOPER_MODE') == true)
<?php
$input_extensions = [];
foreach ($extensions as $extension) {
    $input_extensions[$extension->display_name] = $extension->id;
}
?>

@include('modal',[
    "id"=>"extensionExport",
    "onsubmit" => "downloadFile",
    "title" => "Eklenti İndir",
    "next" => "",
    "inputs" => [
        "Eklenti Seçin:extension_id" => $input_extensions
    ],
    "submit_text" => "İndir"
])

@php
    $templates = fetchExtensionTemplates();
@endphp

@include('modal',[
    "id"=>"newExtension",
    "url" => route('extension_new'),
    "next" => "debug",
    "title" => "Yeni Eklenti Oluştur",
    "inputs" => [
        "Eklenti Adı" => "name:text",
        "Tipi:template" => collect($templates->templates)->mapWithKeys(function($value, $key){
            return [$value => $key];
        })->toArray()
    ],
    "submit_text" => "Oluştur"
])
<script>
    $('#newExtension').find('select[name=template]').val('{{ $templates->default }}');
</script>

@endif

@include('modal',[
    "id"=>"delete",
    "title" =>"Eklentiyi Sil",
    "url" => route('extension_remove'),
    "text" => "Eklentiyi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
    "next" => "reload",
    "inputs" => [
        "Extension Id:'null'" => "extension_id:hidden"
    ],
    "submit_text" => "Eklentiyi Sil"
])

<script>
       var extensionUpdates = [];
       function showExtensionUpdates(){
           showSwal('{{__("Okunuyor...")}}','info');
           request('{{route('get_extension_updates')}}',new FormData(),function(success){
               let json = JSON.parse(success);
               let element = $("#extensionUpdatesList");
               element.html("");
               $.each(json.message,function (index,current){
                    element.append($("<li />").attr("id", `extension_${current["name"]}_button`).addClass("list-group-item").click(current["name"], setExtensionUpdateData).text(current["name"]));
                    extensionUpdates[current["name"]] = current;
               });
               if(json.message.length == 1){
                   setExtensionUpdateData(json.message[0]["name"]);
               }
               $("#extensionUpdatesModal").modal('show');
               Swal.close();
           }, function(error){
               let json = JSON.parse(error);
               showSwal(json.message,'error',2000);
           });
       }
    
    function setExtensionUpdateData(target){
        $("#extensionNewVersion").text(extensionUpdates[target]["newVersion"]);
        $("#extensionUpdateName").text(target);
        $("#extensionUpdatesList li").removeClass("active");
        $("#extensionChangeLogWrapper").html(extensionUpdates[target]["changeLog"]);
        $("#extension_" + target + "_button").addClass('active');
    }

    function requestExtensionUpdate(){
        let extension_id = extensionUpdates[$("#extensionUpdateName").text()]["extension_id"];
        let form = new FormData();
        form.append('extension_id', extension_id);
        request("{{route('update_extension_auto')}}",form, function (success){
            let json = JSON.parse(success);
            showSwal(json.message,'success',2000);
        },function(error){
            let json = JSON.parse(error);
            showSwal(json.message,'error',2000);
        });
    }

    function downloadFile(form){
        window.location.assign('/indir/eklenti/' + form.getElementsByTagName('select')[0].value);
        setTimeout(function(){
            Swal.close();
        }, 1000);
        return false;
    }
    $("#extensionUpload input").on('change',function(){
        if(this.files[0].size / 1024 / 1024 > 100){
            $(this).val('');
            showSwal('{{__("Maksimum eklenti boyutunu (100MB) aştınız!")}}','error');
        }
    });

    function extensionUploadError(response){
        var error = JSON.parse(response);
        if(error.status == 203){
            $('#extensionUpload_alert').hide();
            Swal.fire({
                title: "{{ __('Onay') }}",
                text: error.message,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: "{{ __('İptal') }}",
                confirmButtonText: "{{ __('Tamam') }}"
            }).then((result) => {
                if (result.value) {
                    showSwal('{{__("Yükleniyor...")}}','info');
                    var data = new FormData(document.querySelector('#extensionUpload_form'))
                    data.append("force", "1");
                    request('{{route('extension_upload')}}',data,function(response){
                        Swal.close();
                        reload();
                    }, function(response){
                        var error = JSON.parse(response);
                        Swal.close();
                        $('#extensionUpload_alert').removeClass('alert-danger').removeAttr('hidden').removeClass('alert-success').addClass('alert-danger').html(error.message).fadeIn();
                    });
                }
            });
        }
    }

    @if(env('EXTENSION_DEVELOPER_MODE') == true)
        function extensionDetails(element){
            var extension_id = element.querySelector('#extension_id').innerHTML;
            window.location.href = "/eklentiler/" + extension_id;
        }
    @endif
</script>