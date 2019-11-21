@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item"><a href="{{route('settings')}}">{{__("Ayarlar")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Sunucu Ayarları")}}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">{{__("Genel Ayarlar")}}</a></li>
            <li id="server_type"><a href="#tab_2" data-toggle="tab"
                                    aria-expanded="false">{{__("Veritabanı Ayarları")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1">
                <form id="generalSettings" onsubmit="return saveGeneral()">
                    <h3><b>{{__("Genel Ayarlar")}}</b></h3><br>
                    <h4>{{__("Sistem Adı")}}</h4>
                    <input type="text" class="form-control" value="{{env('APP_NAME')}}">
                    <h4>{{__("Debug Modu")}}</h4>
                    <input type="text" class="form-control" value="{{env('APP_DEBUG')}}">
                    <h4>{{__("Sistem Adresi")}}</h4>
                    <input type="text" class="form-control" value="{{env('APP_URL')}}">
                    <h4>{{__("Syslog Dosya Konumu")}}</h4>
                    <input type="text" class="form-control" value="{{env('LOG_PATH')}}">
                    <h4>{{__("Sunucu Bağlantı Zaman Aşımı")}}</h4>
                    <input type="text" class="form-control" value="{{env('SERVER_CONNECTION_TIMEOUT')}}"><br>
                    <button disabled type="button" class="btn btn-danger">{{__("Değişiklikleri Kaydet")}}</button>
                </form>
            </div>
            <div class="tab-pane" id="tab_2">
                <form action="">
                    <h3><b>{{__("Veritabanı Ayarları")}}</b></h3><br>
                    <h5>{{__("Dikkat: Yaptığınız değişikler ile sunucuya erişiminizi kaybedebilirsiniz.")}}</h5>
                    <h4>{{__("Veritabanı Konumu")}}</h4>
                    <input type="text" class="form-control" value="{{env('DB_PATH')}}"><br>
                    <button disabled type="button" class="btn btn-danger">{{__("Değişiklikleri Kaydet")}}</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function saveGeneral(form) {
            swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this imaginary file!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then((willDelete) => {
                    if (willDelete) {
                        swal("Poof! Your imaginary file has been deleted!", {
                            icon: "success",
                        });
                    } else {
                        swal("Your imaginary file is safe!");
                    }
                });

        }
    </script>
@endsection
