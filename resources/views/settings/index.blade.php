@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{__("Ayarlar")}}</li>
        </ol>
    </nav>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#users" data-toggle="tab" aria-expanded="true">{{__("Kullanıcı Ayarları")}}</a></li>
            <li><a href="#server" data-toggle="tab" aria-expanded="false">{{__("Sunucu Ayarları")}}</a></li>
            <li><a href="#health" onclick="checkHealth()" data-toggle="tab" aria-expanded="false">{{__("Sağlık Durumu")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="users">
                @include('l.modal-button',[
                    "class" => "btn-success",
                    "target_id" => "add_user",
                    "text" => "Kullanıcı Ekle"
                ])<br><br>
                @include('l.table',[
                    "value" => \App\User::all(),
                    "title" => [
                        "Sunucu Adı" , "Email" , "*hidden*" ,
                    ],
                    "display" => [
                        "name" , "email", "id:user_id" ,
                    ],
                    "menu" => [
                        "Parolayı Sıfırla" => [
                            "target" => "passwordReset",
                            "icon" => "fa-lock"
                        ],
                        "Sil" => [
                            "target" => "delete",
                            "icon" => "fa-trash"
                        ]
                    ],
                    "onclick" => "details"
                ])
            </div>
            <div class="tab-pane" id="server">
                <button class="btn btn-danger" onclick="location.href = '{{route('settings_server')}}'">{{__("Sunucu Ayarları")}}</button>
            </div>
            <div class="tab-pane" id="health">
                <pre id="output"></pre>
            </div>
        </div>
    </div>

    @include('l.modal',[
        "id"=>"add_user",
        "title" => "Kullanıcı Ekle",
        "url" => route('user_add'),
        "next" => "after_user_add",
        "selects" => [
            "Yönetici:administrator" => [
                "-:administrator" => "type:hidden"
            ],
            "Kullanıcı:user" => [
                "-:user" => "type:hidden"
            ]
        ],
        "inputs" => [
            "Adı" => "name:text",
            "E-mail Adresi" => "email:text",
        ],
        "submit_text" => "Ekle"
    ])

    @include('l.modal',[
       "id"=>"delete",
       "title" =>"Kullanıcıyı Sil",
       "url" => route('user_remove'),
       "text" => "Kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "reload",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Kullanıcıyı Sil"
   ])

    @include('l.modal',[
       "id"=>"passwordReset",
       "title" =>"Parolayı Sıfırla",
       "url" => route('user_password_reset'),
       "text" => "Parolayı sıfırlamak istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "next" => "nothing",
       "inputs" => [
           "Kullanici Id:'null'" => "user_id:hidden"
       ],
       "submit_text" => "Parolayı Sıfırla"
   ])
    <script>
      function after_user_add(response) {
        let json = JSON.parse(response);
        Swal.fire({
            position: 'center',
            type: 'info',
            title: json.message,
        });
      }
      function details(row) {
          let user_id = row.querySelector('#user_id').innerHTML;
          location.href = '/ayarlar/' + user_id;
      }
      
      function checkHealth() {
          Swal.fire({
              position: 'center',
              type: 'info',
              title: '{{__("Okunuyor...")}}',
              showConfirmButton: false,
          });
          request("{{route('health_check')}}",new FormData(),function (success) {
              Swal.close();
              let json = JSON.parse(success);
              let box = $("#output");
              box.html("");
              console.log(json["message"]);
              for (let i=0 ; i < json["message"].length; i++){
                  let current = json["message"][i];
                  box.append("<div class='alert alert-" + current["type"] +"' role='alert'>" +
                      current["message"] +
                      "</div>");
              }

          },function (error) {
              Swal.close();
            alert("hata");
          });
      }
    </script>
@endsection
