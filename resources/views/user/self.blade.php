@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Profilim")}}</li>
        </ol>
    </nav>
    <div class="box box-solid box-primary" style="width:45%;min-height: 400px;min-width:300px;float:left;margin: 20px">
        <div class="box-header">
            <h3 class="box-title">{{__("Hesap Ayarları")}}</h3>
        </div>
        <div class="box-body">
            <form onsubmit="return saveUser(this)">
                <h3>{{__("Kullanıcı Adı")}}</h3>
                <input type="text" class="form-control" name="name" value="{{auth()->user()->name}}">
                <h3>{{__("Email Adresi")}}</h3>
                <input type="text" class="form-control" value="{{auth()->user()->email}}" disabled>
                <h3>{{__("Parola")}}</h3>
                <input type="password" class="form-control" name="password"><br>
                <button class="btn btn-success btn-lg" type="submit">{{__("Kaydet")}}</button>
            </form>
        </div>
        <script>
            function saveUser(data) {
                Swal.fire({
                    position: 'center',
                    type: 'info',
                    title: '{{__("Kaydediliyor...")}}',
                    showConfirmButton: false,
                });
                let form = new FormData(data);
                request('{{route('profile_update')}}',form,function (response) {
                    Swal.close();
                    let json = JSON.parse(response);
                    if(json["status"] === 200){
                        Swal.fire({
                            position: 'center',
                            type: 'success',
                            title: json["message"],
                            showConfirmButton: false,
                            timer: 1500
                        });
                        setTimeout(function () {
                            location.reload();
                        },1600);
                    }else{
                        Swal.fire({
                            position: 'center',
                            type: 'error',
                            title: json["message"],
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                });
                return false;

            }
        </script>
    </div>
@endsection