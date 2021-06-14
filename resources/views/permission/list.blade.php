@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{__("Yetki Talepleri")}}</li>
    </ol>
</nav>
<div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <h3 class="profile-username text-center font-weight-bold">{{__("Yetki Talepleri")}}</h3>
                <p class="text-muted text-center mb-0">Bu sayfadan mevcut yetki taleplerini görebilirsiniz. İşlem yapmak istediğiniz talebe sağ tıklayarak işlem yapabilirsiniz.</p>
              </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                @include('table',[
                "value" => $requests,
                "title" => [
                    "Tipi" , "Kullanıcı Adı" , "Not" , "Önem Derecesi", "Durumu", "*hidden*", "*hidden*"
                ],
                "display" => [
                    "type" , "user_name", "note" , "speed", "status", "id:request_id" , "user_id:user_id"
                ],
                "menu" => [
                    "İşleniyor" => [
                        "target" => "working",
                        "icon" => "fa-cog"
                    ],
                    "Tamamlandı" => [
                        "target" => "completed",
                        "icon" => "fa-thumbs-up"
                    ],
                    "Reddet" => [
                        "target" => "deny",
                        "icon" => "fa-thumbs-down"
                    ],
                    "Sil" => [
                        "target" => "deleteRequest",
                        "icon" => " context-menu-icon-delete"
                    ]
                ],
                "onclick" => "userSettings"
        ])
                </div>
            </div>
        </div>
</div>

<script>

    function userSettings(element){
        var user_id = element.querySelector('#user_id').innerHTML;
        window.location.href = "/ayarlar/" + user_id;
    }
    function update(current,status) {
        showSwal('{{__("Kaydediliyor.")}}','info');
        var form = new FormData();
        form.append('status',status);
        form.append('request_id',current.querySelector('#request_id').innerHTML);
        request('{{route('request_update')}}',form,function () {
            Swal.close();
            location.reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }
    function working(current) {
        update(current,'1');
    }
    function completed(current) {
        update(current,'2');
    }
    function deny(current) {
        update(current,'3');
    }
    function deleteRequest(current) {
        update(current,'4');
    }
</script>

@endsection