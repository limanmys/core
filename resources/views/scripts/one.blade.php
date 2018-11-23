@extends('layouts.app')

@section('content')
    <style type="text/css" media="screen">
        #editor {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }
    </style>
    <script>
        var data=[];

    </script>
    <!-- Scripts -->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.1/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
    <!-- Styles -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link href="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/theme-default.min.css"
          rel="stylesheet" type="text/css" />


    <button class="btn btn-success" onclick="history.back();">Geri Don</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#settingsModal">
        Ayarlar
    </button><br><br>
    <div class="cards">
        <div class="card w-auto">
            <div class="card-body">
                <h4 class="card-title">Gerekli Parametreler</h4>
                <div class="form-inline">
                    <table>
                        <tr>
                            <td style="margin:10px;">
                                <div class="form-group">
                                    <input id="inputName" type="text" class="form-control" placeholder="Parametre Adı" data-validation="length" data-validation-length="min0">
                                </div>
                            </td>
                            <td style="margin:10px;">
                                <select class="form-control" name="inputs" style="color:white;background-color:#5cb85c;"id="inputType">
                                    <option value="string" style="background-color: #33CC33;">Metin</option>
                                    <option value="number">Sayı</option>
                                    <option value="ip">Ip Adresi</option>
                                </select>
                            </td>
                            <td style="margin:10px;">
                                <button class="btn btn-primary" onclick="addInput()">Ekle</button>
                            </td>
                        </tr>
                    </table>

                </div>
                <br>
                <div class="inputs">

                </div>
            </div>
        </div>
        <div class="card w-auto" style="width: 18rem; height: 20rem;">
            <div class="card-body">
                <div class="form-group">
                    <label for="exampleFormControlTextarea1">Kodu buraya yazınız</label>
                    <div id="editor">function foo(items) {
                        var x = "All this is syntax highlighted";
                        return x;
                        }
                    </div>
                </div>
            </div>
        </div>
        <div class="card w-auto">
            <div class="card-body">
                <h5 class="card-title">Sonuç Parametreleri</h5>
                <div class="form-inline">
                    <table>
                        <tr>
                            <td style="margin:10px;">
                                <div class="form-group">
                                    <input id="ResultParameterName" type="text" class="form-control" placeholder="Parametre Adı" data-validation="length" data-validation-length="min0">
                                </div>
                            </td>
                            <td style="margin:10px;">
                                <select class="form-control" name="inputs" style="color:white;background-color:#5cb85c;"id="inputTypeResult">
                                    <option value="string" class="greenColor" >Metin</option>
                                    <option value="number" class="greenColor">Sayı</option>
                                    <option value="ip">Ip Adresi</option>
                                </select>
                            </td>

                            <td style="margin:10px;">
                                <button class="btn btn-primary" onclick="addResultParameters()">Ekle</button>
                            </td>
                        </tr>
                    </table>
                </div>
                <br>
                <div class="Resultsinputs">
                </div>
            </div>
        </div>
        <button onclick="addAll()" class="btn btn-primary w-auto">
            Ekle
        </button>
    </div>

    <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">Betik Ayarları</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="form-group">
                            <h3>Adı</h3>
                            <input id="name" type="text" class="form-control" placeholder="Betik kısa adı" data-validation="length" data-validation-length="min4">
                        </div>
                        <div class="form-group">
                            <h3>Özellik</h3>
                            <select class="form-control" id="feature">
                                @foreach ($features as $feature)
                                    <option value="{{$feature->_id}}">{{$feature->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>Versiyon</h3>
                            <input id="version" type="text" class="form-control" placeholder="Betik Versiyonu" value="1" data-validation="custom"  data-validation-regexp="^[0-9]" data-validation-error-msg="Versiyon sayı olmalı.">
                        </div>
                        <div class="form-group">
                            <h3>Açıklama</h3>
                            <input id="description" type="text" class="form-control" placeholder="Anahtar Kullanıcı Adı">
                        </div>
                        <div class="form-group">
                            <h3>Mail Adresi</h3>
                            <input id="email" type="email" class="form-control" placeholder="Destek verilecek Email Adresi" data-validation="email" data-validation-error-msg="Geçerli bir e-mail address girin.">
                        </div>
                        <div class="form-group">
                            <h3>Betik Türü</h3>
                            <select class="form-control" name="inputs" id="betiktype">
                                <option value="1">Sorgulama</option>
                                <option value="2">Çalıştırma</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-success" onclick="add()">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $.validate({
            lang: 'tr',
            addValidClassOnAll : true
        });
        $('#inputType').on('change', function() {
            if(this.value=="string"){
                $(this).css("backgroundColor", "#5cb85c");
                $(this).css("color", "white");
            }
            else if(this.value=="number"){
                $(this).css("backgroundColor", "#428bca");
                $(this).css("color", "white");
            }
            else if(this.value=="ip"){
                $(this).css("backgroundColor", "#f0ad4e");
                $(this).css("color", "white");
            }
            else{
                $(this).css("backgroundColor", "white");
            }
        });
        $('#inputTypeResult').on('change', function() {
            if(this.value=="string"){
                $(this).css("backgroundColor", "#5cb85c");
                $(this).css("color", "white");
            }
            else if(this.value=="number"){
                $(this).css("backgroundColor", "#428bca");
                $(this).css("color", "white");
            }
            else if(this.value=="ip"){
                $(this).css("backgroundColor", "#f0ad4e");
                $(this).css("color", "white");
            }
            else{
                $(this).css("backgroundColor", "white");
            }
        });
        function addInput() {
            var name = $("#inputName").val();
            var type = $("#inputType").val();
            data["NeededParameter"]=$("#inputName").val()+":"+$("#inputType").val();

            var value2=data["parameterName"] +":"+data["inputType"];

            var r= $('<button class="btn btn-success" onclick="sil(this)" id="">value2</button>');
            r.id=name;
            r.text(name);
            if(type=="string"){
                r.css("backgroundColor", "#5cb85c");
                r.css("color", "white");}
            else if(type=="number"){
                r.css("backgroundColor", "#428bca");
                r.css("color", "white");}
            else if(type=="ip"){
                r.css("backgroundColor", "#f0ad4e");
                r.css("color", "white");}
            $(".inputs").append(r);
        }
        function addResultParameters(){
            var name= $("#ResultParameterName").val();
            var type = $("#inputTypeResult").val();
            data["ResultParameter"]=$("#ResultParameterName").val()+":"+$("#inputTypeResult").val();
            var r= $('<button class="btn btn-success" onclick="sil(this)" id=""></button>');
            r.id=name;
            r.text(name);
            if(type=="string"){
                r.css("backgroundColor", "#5cb85c");
                r.css("color", "white");}
            else if(type=="number"){
                r.css("backgroundColor", "#428bca");
                r.css("color", "white");}
            else if(type=="ip"){
                r.css("backgroundColor", "#f0ad4e");
                r.css("color", "white");}
            $(".Resultsinputs").append(r);
        }
        function add(){
            data["name"]=$("#name").val();
            data["feature"]=$( "#feature option:selected" ).text();
            data["version"]=$("#version").val();
            data["description"]=$("#description").val();
            data["email"]=$("#email").val();
            data["type"]=$( "#betiktype" ).val();
            if(data["name"]!="" && data["feature"]!="" && data["version"]!="" && data["description"]!="" && data["email"]!="" && data["type"]!="" ){
                $("#settingsModal").modal('hide');
                $(".modal-backdrop").remove();
            }
        }
        function addAll(){
            var command=document.getElementById("editor");
            data["command"]=command.textContent;
            if( 'name' in data){
                $.post("" ,{
                    data:data
                },function (data,status) {
                    if(data["result"] === 200){
                        //window.location.replace("{{route('users')}}" + "/" + data["id"]);
                    }else{
                        alert("Hata!");
                    }
                });
            }
            else{
                $("#settingsModal").modal("show");
            }
        }
        function sil(id){
            $(id).remove();
        }
    </script>
    <script>
        var editor = ace.edit("editor");
        editor.setTheme("ace/theme/monokai");
        editor.session.setMode("ace/mode/javascript");
    </script>
@endsection