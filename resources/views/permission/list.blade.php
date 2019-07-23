@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Yetki Talepleri")}}</li>
        </ol>
    </nav>
<button class="btn btn-success" onclick="history.back()">{{__("Geri Dön")}}</button><br><br>
@include('l.errors')    
@include('l.table',[
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
                "icon" => "fa-trash"
            ]
        ],
        "onclick" => "userSettings"
])
    <script>

        function userSettings(element){
            let user_id = element.querySelector('#user_id').innerHTML;
            window.location.href = "/ayarlar/" + user_id
        }
        function update(current,status) {
            Swal.fire({
                position: 'center',
                type: 'info',
                title: '{{__("Kaydediliyor.")}}',
                showConfirmButton: false,
            });
            let form = new FormData();
            form.append('status',status);
            form.append('request_id',current.querySelector('#request_id').innerHTML);
            request('{{route('request_update')}}',form,function () {
                Swal.close();
                location.reload();
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