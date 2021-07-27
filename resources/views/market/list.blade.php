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
                    <li class="breadcrumb-item active" aria-current="page">{{ $category_name }} {{ __('eklentileri') }}</li>
                @endif
            </ol>
        </nav>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body py-0 pl-2 row">
                <div class="col-lg-7" style="max-height: 52px; overflow: hidden;">
                    <a href="javascript:void()" id="btn-nav-previous" class="extensions_category">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <a href="javascript:void()" id="btn-nav-next" style="right: 0;" class="extensions_category">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <div class="ext_menu">
                        <a href="{{ route('market') }}" class="extensions_category">{{ __("Tüm Eklentiler") }}</a>
                        @foreach ($categories as $category)
                            <a href="{{ route('market_kategori', $category->id) }}" class="extensions_category">{{ $category->name }}</a>
                        @endforeach
                       
                    </div>
                </div>
                <div class="col-lg-5 d-flex search-btns" style="justify-content: flex-end;">
                <button onclick="window.location.href='/ayarlar#extensions'" class="btn btn-dark mt-2 mr-2 float-right" data-toggle="tooltip" title="{{ __('Eklenti Ayarları') }}" style="height: 38px;" ><i class="fas fa-cogs"></i></button>
                    <button class="btn btn-dark mt-2 mr-2 float-right" style="height: 38px;" onclick="openExtensionUploadModal()"><i class="fas fa-download mr-1"></i>{{ __("Eklenti yükle") }}</button>
                    <form action="{{ route('market_search') }}" method="GET">
                        
                        <div class="input-group mt-2 float-right">
                            <input name="search_query" class="form-control py-2" @isset(request()->search_query) value="{{request()->search_query}}" @endisset type="search" placeholder="{{ __('Eklentilerde ara...') }}" id="extension_search">
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
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-lg-5 col-md-6 col-sm-6 col-5">
                    @if ($app->iconPath)
                        <img src="{{ env('MARKET_URL') . '/' . $app->iconPath }}" alt="{{ $app->name }}" class="img-fluid mb-3">
                    @else
                        <img src="{{ asset('images/no-icon.jpg') }}" alt="{{ $app->name }}" class="img-fluid mb-3">
                    @endif
                    </div>
                    <div class="col-xl-6 col-lg-7 col-md-6 col-sm-6 col-7">
                        <h4 style="font-weight: 600;">{{ $app->name }}</h4>
                        <p class="mb-0">{{ $app->shortDescription }}</p>
                    </div>
                    <div class="col-xl-3 col-lg-12 text-center">
                    @if ($app->publicVersion)
                        @if (!$app->isInstalled)
                        <button id="installBtn" class="btn btn-success mb-2 w-100" onclick="installExtension('{{ $app->packageName }}')">
                            <i class="fas fa-download mr-1"></i> {{ __("Yükle") }}
                        </button>
                        @endif

                        @if ($app->publicVersion->needsToBeUpdated)
                        <button id="installBtn" class="pl-1 pr-1 btn btn-warning mb-2 w-100" onclick="installExtension('{{ $app->packageName }}')">
                            <i class="fas fa-download mr-1"></i> {{ __("Güncelle") }}
                        </button>
                        @elseif ($app->isInstalled)
                        <button id="installBtn" class="btn btn-secondary mb-2 w-100 disabled" disabled>
                            <i class="fas fa-check mr-1"></i> {{ __("Kurulu") }}
                        </button>
                        @endif
                    @else
                        <button class="btn btn-primary mb-2" onclick="window.open('https://liman.havelsan.com.tr/iletisim/')">
                            <i class="fas fa-shopping-cart mr-1"></i> {{ __("Satın Al") }}
                        </button>
                    @endif
                        <a href="{{ env('MARKET_URL') . '/Application/' . mb_strtolower($app->packageName) }}" target="_blank"><small>{{ __("Daha fazla detay") }}</small></a>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-6">
                        @if ($app->publicVersion)
                        <small class="font-italic">
                            <b>{{ __("Versiyon") }}:</b> {{ $app->publicVersion->versionName }}
                        @else
                        <small>
                            {{ __("Kurumsal eklenti") }}
                        @endif
                        </small>
                    </div>
                    <div class="col-6 text-right">
                        <small>
                            <b>{{ __("Geliştirici") }}:</b> {{ $app->publisher->userName }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    
    @if (!$paginate->totalPages == 1 && !$paginate->totalPages == 0)
    {{ var_dump($paginate) }}
    <div class="col-12">
        <div class="row">
            <div class="col-6">
                <a href="?pageNumber={{ isset($paginate->previousPage) ? $paginate->previousPage : '' }}" class="btn btn-primary w-100 text-white @if(!$paginate->hasPreviousPage) disabled @endif"><i class="fas fa-chevron-left mr-1"></i> Önceki</a>
            </div>

            <div class="col-6">
                <a href="?pageNumber={{ isset($paginate->nextPage) ? $paginate->nextPage : '' }}" class="btn btn-primary w-100 text-white @if(!$paginate->hasNextPage) disabled @endif">Sonraki <i class="fas fa-chevron-right ml-1"></i></a>
            </div>
        </div>
    </div>
    @endif

    @if (count($apps) == 0)
    <div class="container-fluid">
        <div class="error-page mt-5">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle text-warning"></i></h2>
            <div class="error-content">
                <h3>Uyarı</h3>
                <p>
                    @if (request()->search_query)
                    {{ request()->search_query }} aramasına uygun bir eklenti bulamadık.
                    @else
                    Eklenti bulunamadı.
                    @endif
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

    function installExtension(package_name) 
    {
        showSwal('Kuruluyor...', 'info');
        let extdata = new FormData();
        request(`/market/kur/${package_name}`, extdata, function(response) {
            Swal.close();
            showSwal("Eklenti başarıyla kuruldu!", "success", 1500);
            setTimeout(_ => {
                window.location = "/ayarlar#extensions"
            }, 1500);
        }, function(err) {
            installUnsignedExtension(err, package_name);
        });
    }

    function installUnsignedExtension(response, package_name)
    {
        var error = JSON.parse(response);
        if(error.status == 203){
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
                    showSwal('Kuruluyor...', 'info');
                    let extdata = new FormData();
                    extdata.append("force", "1");
                    request(`/market/kur/${package_name}`, extdata, function(response) {
                        console.log(response);
                        Swal.close();
                        showSwal("Eklenti başarıyla kuruldu!", "success", 1500);
                        setTimeout(_ => {
                            window.location = "/ayarlar#extensions"
                        }, 1500);
                    }, function(err) {
                        console.log(err);
                        var error = JSON.parse(err);
                        Swal.close();
                        showSwal(error.message, "error", 3000);
                    });
                }
            });
        } else {
            showSwal("Eklenti kurulumunda hata oluştu!", "error", 3000);
        }
    }

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

    $(".extensions_category").each(function() {  
        if (this.href == window.location.href) {
            $(this).addClass("active_tab");
        }
    });

    function loopNext(){
        $('.ext_menu').stop().animate({scrollLeft:'+=40'}, 'fast', 'linear', loopNext);
    }

    function loopPrev(){
        $('.ext_menu').stop().animate({scrollLeft:'-=40'}, 'fast', 'linear', loopPrev);
    }

    function stop(){
        $('.ext_menu').stop();
    }


    $('#btn-nav-next').hover(function () {
        loopNext();
    },function () {
        stop();
    });

    $('#btn-nav-previous').hover(function () {
        loopPrev();
    },function () {
        stop();
    });
</script>
@endsection