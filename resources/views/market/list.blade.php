@extends("layouts.app")

@section("content")
<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
                @if (!request()->category_id && !request()->search_query)
                <li class="breadcrumb-item active" aria-current="page">{{__("Eklenti Mağazası")}}</li>
                @else
                    <li class="breadcrumb-item"><a href="{{route('market')}}">{{__("Eklenti Mağazası")}}</a></li>
                    @php
                    if (request()->category_id) {
                        $category_name = "";
                        foreach ($categories as $category) {
                            if ($category->id == request()->category_id){
                                $category_name = $category->name;
                                break;
                            }
                        }
                    } else {
                        $category_name = request()->search_query;
                    }
                    @endphp
                    <li class="breadcrumb-item active" aria-current="page">{{ $category_name }} eklentileri</li>
                @endif
            </ol>
        </nav>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body py-0 pl-2 row">
                <div class="col-6">
                <a href="{{ route('market') }}" class="extensions_category">Tüm Eklentiler</a>
                @foreach ($categories as $category)
                    <a href="{{ route('market_kategori', $category->id) }}" class="extensions_category">{{ $category->name }}</a>
                @endforeach
                <button class="btn btn-dark mt-2 ml-1" onclick="openExtensionUploadModal()"><i class="fas fa-download mr-1"></i>Eklenti yükle</button>
                </div>
                <div class="col-6">
                    <form action="{{ route('market_search') }}" method="GET">
                        <div class="input-group mt-2 w-50 float-right">
                            <input name="search_query" class="form-control py-2" type="search" placeholder="Eklentilerde ara..." id="extension_search">
                            <span class="input-group-append">
                                <button class="btn btn-dark" type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @foreach ($apps as $app)
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-3">
                        <i class="fas fa-puzzle-piece" style="font-size: 100px;"></i>
                    </div>
                    <div class="col-6">
                        <h4 style="font-weight: 600;">{{ $app->name }}</h4>
                        <p>{{ $app->description }}</p>
                    </div>
                    <div class="col-3 text-right">
                    @if ($app->publicVersion)
                        <button class="btn btn-success mb-2">
                            <i class="fas fa-download mr-1"></i> Yükle
                        </button>
                    @else
                        <button class="btn btn-primary mb-2">
                            <i class="fas fa-shopping-cart mr-1"></i> Satın Al
                        </button>
                    @endif
                        <small style="cursor:pointer;" onclick="showIframeModal('{{ env('MARKET_URL') . '/Application/' . mb_strtolower($app->packageName) }}')">Daha fazla detay</small>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-6">
                        @if ($app->publicVersion)
                        <small class="font-italic">
                            <b>Versiyon:</b> {{ $app->publicVersion->versionCode }}
                        @else
                        <small>
                            Ücretli eklenti
                        @endif
                        </small>
                    </div>
                    <div class="col-6 text-right">
                        <small>
                            <b>Geliştirici:</b> <a href="mailto:{{ $app->publisher->email }}">{{ $app->publisher->userName }}</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @if (count($apps) == 0)
    <div class="container-fluid">
        <div class="error-page mt-5">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle text-warning"></i></h2>
            <div class="error-content">
                <h3>Uyarı</h3>
                <p>
                    {{ request()->search_query }} aramasına uygun bir eklenti bulamadık.
                    <br><button class="btn btn-success mt-3" onclick="history.back()">{{__("Geri Dön")}}</button>
                </p>
            </div>
        </div>
    </div>
    @endif
</div>

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

@include('modal-iframe', [
    "id" => "iframeModal",
    "title" => "Eklenti Detayları",
    "url" => ""
])

<script>
    function openExtensionUploadModal() {
        $("#extensionUpload").modal('show');
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
                        showSwal('Eklenti başarıyla kuruldu!', 'success');
                        setTimeout(() => {
                            reload();
                        }, 1500);
                    }, function(response){
                        var error = JSON.parse(response);
                        Swal.close();
                        $('#extensionUpload_alert').removeClass('alert-danger').removeAttr('hidden').removeClass('alert-success').addClass('alert-danger').html(error.message).fadeIn();
                    });
                }
            });
        }
    }

    function showIframeModal(url) {
        $('#iframeModal').modal("show");
        $('#iframeModal').on('shown.bs.modal', function() {
            $(this).find('iframe').attr('src', url)
        })  
    }

    $(".extensions_category").each(function() {  
        if (this.href == window.location.href) {
            $(this).addClass("active_tab");
        }
    });
</script>
@endsection