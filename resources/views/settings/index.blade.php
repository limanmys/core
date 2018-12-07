@extends('layouts.app')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Sistem Ayarları</h1>
    </div>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">{{__("Kullanıcılar")}}</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">Profile</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">Contact</a>
            </li>
          </ul>
          <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab"><br>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#new">
                            {{__("Kullanıcı Ekle")}}
                        </button><br><br>
                    <table id="mainTable" class="table">
                            <thead>
                            <tr>
                                <th scope="col">Kullanıcı Adı</th>
                                <th scope="col">Email</th>
                                <th scope="col"></th>
                            </tr>
                            </thead>
                            <tbody data-toggle="modal" data-target="#new">
                            @foreach ($users as $user)
                                <tr class="highlight">
                                    <td>{{$user->name}}</td>
                                    <td>{{$user->email}}</td>
                                </tr>
                    
                            @endforeach
                            </tbody>
                    </table>
            </div>
            <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#deneme">
                            {{__("Testing")}}
                    </button>
                @include('modal',[
                    "id"=>"deneme",
                    "title" => "Merhaba Ertan",
                    "url" => "/user/add",
                    "inputs" => [
                        "Kullanıcı Adı" => "username:text",
                        "İp Adresi" => "ip_address:number",
                    ],
                    "submit_text" => "Ekle"
                ])
            </div>
            <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">

            </div>
          </div>
          <div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title" id="exampleModalLabel">{{__("Kullanıcı Ekle")}}</h1>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form onsubmit="return addUser(this)">
                            <div class="modal-body">
                                <td>
                                    <div class="form-group">
                                        <h3>{{__("Kullanıcı Adı")}}</h3>
                                        <input name="username" type="text" class="form-control" placeholder="{{__("Kullanıcı Adı")}}" required>
                                    </div>
                                    <div class="form-group">
                                        <h3>{{__("E-Mail Adresi")}}</h3>
                                        <input name="email" type="text" class="form-control" placeholder="{{__("E-Mail Adresi")}}" required>
                                    </div>
                                    <div class="form-group">
                                    <h3>{{__("Parola")}}</h3>
                                        <input placeholder="{{__("Parola")}}" name="password" type="password" class="form-control" required>
                                    </div>
                                </td>
                            </div>
                            <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__("İptal")}}</button>
                            <button type="submit" class="btn btn-success">{{__("Ekle")}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                function addUser(form){
                    console.log(this);
                    var data = new FormData(form);
                    console.log(data.get('username'));
                    return false;
                }
            </script>
@endsection