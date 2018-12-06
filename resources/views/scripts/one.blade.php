@extends('layouts.app')

@section('content')
    <style type="text/css" media="screen">
        #editor {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            font-size: 15px;

        }
        .alert{
            display: none;
        }
    </style>
    <script>
        var data=[];
        var check=true;
        var check1=true;
        var check2=true;
    </script>
    <script src="../js/src/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="../js/src/mode-javascript.js" type="text/javascript" charset="utf-8"></script>
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
                                <select class="form-control" name="inputs" style="color:white;background-color:#5cb85c;" id="inputType">
                                    <option value="string" style="color:white;background-color:#5cb85c;">Metin</option>
                                    <option value="number" style="color:white;background-color:#428bca;">Sayı</option>
                                    <option value="ip" style="color:white;background-color:#f0ad4e;">Ip Adresi</option>
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
        <div class="card w-auto" style="width: 18rem; height: 18rem;">
            <div class="card-body">
                <div class="form-group">
                    <label for="exampleFormControlTextarea1">Kodu buraya yazınız</label>
                    <div id="editor" ></div>
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
                                <select class="form-control" name="inputs" id="inputTypeResult" style="color:white;background-color:#5cb85c;">
                                    <option value="string" style="color:white;background-color:#5cb85c;">Metin</option>
                                    <option value="number" style="color:white;background-color:#428bca;">Sayı</option>
                                    <option value="ip" style="color:white;background-color:#f0ad4e;">Ip Adresi</option>
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
        <div class="card w-auto" style="width: 18rem;">
            <div class="card-body">
                <div class="form-group">
                    <h3>Sorumluluk Reddi</h3>
                    Bu dosyayı kaydetmenin sorumluluğunu üstleniyorum.<br><br>
                    <button onclick="addAll()"  class="btn btn-primary">
                        Ekle
                    </button>
                </div>
            </div>
        </div>
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
                            <select class="form-control" id="extension">
                                @foreach ($extensions as $extension)
                                    <option value="{{$extension->_id}}">{{$extension->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>Versiyon</h3>
                            <input id="username" type="text" class="form-control" placeholder="Betik Versiyonu" value="1" data-validation="custom"  data-validation-regexp="^[0-9]" data-validation-error-msg="Versiyon sayı olmalı.">
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
                            <select class="form-control" name="inputs" id="inputType">
                                <option value="query">Sorgulama</option>
                                <option value="query">Çalıştırma</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-success" onclick="add()">Kaydet</button>
                    </div>
                    <div class="alert alert-danger alert-dismissable" id="alert1">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        Alanların doğru doldurulması gerekiyor!
                    </div>
                    <div class="alert alert-success alert-dismissable" id="alert2">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        Doğru! yönlendiriliyor.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $.validate({
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
            if(data["inputs"])
                data["inputs"]=data["NeededParameter"]+","+$("#inputName").val()+":"+$("#inputType").val();
            else
                data["inputs"]=$("#inputName").val()+":"+$("#inputType").val();
            console.log(data["inputs"]);
            var r= $('<button class="btn btn-success" onclick="sil(this)" id="">value2</button>');
            r.id=name;
            r.text(name);
            r.css("margin","8px");
            r.css("display","inline-block");
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
            if(data["outputs"])
                data["outputs"]=data["outputs"]+","+$("#ResultParameterName").val()+":"+$("#inputTypeResult").val();
            else
                data["outputs"]=$("#ResultParameterName").val()+":"+$("#inputTypeResult").val();
            var r= $('<button class="btn btn-success" onclick="sil(this)" id=""></button>');
            r.id=name;
            r.text(name);
            r.css("margin","8px");
            r.css("display","inline-block");
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
            $('#name').validate(function(valid, elem) {
                check=valid;
            });
            $('#version').validate(function(valid, elem) {
                check1=valid;
            });
            $('#email').validate(function(valid, elem) {
                check2=valid;
            });
            if(check&& check1&& check2){
                $('#alert1').hide();
                $('#alert2').show();
                setTimeout(function() {
                    console.log("geldim");
                    $('#settingsModal').modal('hide');
                    $(".modal-backdrop").remove();
                }, 1000);

            }
            else
            {
                $('#alert2').hide();
                $('#alert1').show();
            }
        }
        function addAll(){
            var command=document.getElementById("editor");
            data["command"]=command.textContent;
            console.log("geldim");
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
        editor.session.setMode("ace/mode/python");
    </script>
@endsection