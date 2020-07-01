@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Profilim")}}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <h3 class="profile-username text-center">{{auth()->user()->name}}</h3>
                <p class="text-muted text-center">{{auth()->user()->email}}</p>
              </div>
            </div>
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ _('Bilgiler') }}</h3>
              </div>
              <div class="card-body">
                <strong>{{ _('Son Giriş Yapılan IP') }}</strong>
                <p class="text-muted">{{auth()->user()->last_login_ip}}</p>
                <hr>
                <strong>{{ _('Son Giriş Tarihi') }}</strong>
                <p class="text-muted">{{auth()->user()->last_login_at}}</p>
              </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <form class="form-horizontal" onsubmit="return saveUser(this)">
                        <div class="form-group row">
                            <label for="inputName" class="col-sm-2 col-form-label">{{__("Kullanıcı Adı")}}</label>
                            <div class="col-sm-10">
                            <input type="text" class="form-control" id="inputName" name="name" value="{{auth()->user()->name}}" required minlength="6" maxlength="255" @if(user()->auth_type == "ldap") disabled @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputEmail" class="col-sm-2 col-form-label">{{__("Email Adresi")}}</label>
                            <div class="col-sm-10">
                            <input type="email" class="form-control" id="inputEmail" value="{{auth()->user()->email}}" disabled required @if(user()->auth_type == "ldap") disabled @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputOldPassword" class="col-sm-2 col-form-label">{{__("Eski Parola")}}</label>
                            <div class="col-sm-10">
                            <input type="password" class="form-control" id="inputOldPassword" name="old_password" required minlength="10" maxlength="32" @if(user()->auth_type == "ldap") disabled @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputPassword" class="col-sm-2 col-form-label">{{__("Parola")}}</label>
                            <div class="col-sm-10">
                            <input type="password" class="form-control" id="inputPassword" name="password" minlength="10" maxlength="32" @if(user()->auth_type == "ldap") disabled @endif>
                            <small>{{__("Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı,özel karakter ve büyük harf içermelidir.")}}</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputPasswordConfirmation" class="col-sm-2 col-form-label">{{__("Parola Onayı")}}</label>
                            <div class="col-sm-10">
                            <input type="password" class="form-control" id="inputPasswordConfirmation" name="password_confirmation" minlength="10" maxlength="32" @if(user()->auth_type == "ldap") disabled @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="offset-sm-2 col-sm-10">
                            <button type="submit" class="btn btn-danger" @if(user()->auth_type == "ldap") disabled @endif>{{__("Kaydet")}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('input[type=password]').keyup(function(){
            var password = $('input[name=password]').val();
            var password_confirmation = $('input[name=password_confirmation]').val();
            $('.no-match').remove();
            if(password_confirmation!=="" && password !== password_confirmation){
                $('input[name=password_confirmation]').after('<span style="color: #dd4b39;" class="help-block no-match">Şifreler uyuşmuyor</span>');
            }
        });
        function saveUser(data) {
            showSwal('{{__("Kaydediliyor...")}}','info');
            var form = new FormData(data);
            request('{{route('profile_update')}}',form,function (response) {
                Swal.close();
                var json = JSON.parse(response);
                if(json["status"] === 200){
                    showSwal(json.message,'success',2000);
                    setTimeout(function () {
                        location.reload();
                    },1600);
                }
            },function (response) {
                var json = JSON.parse(response);
                showSwal(json.message,'error',2000);
            });
            return false;

        }
    </script>
@endsection