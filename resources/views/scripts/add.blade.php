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
        .column {
            float: left;
            width: 50%;
            padding: 10px;
            height: 500px; /* Should be removed. Only for demonstration */
        }
    </style>
    <script>
        var data=[];
        var check=true;
        var check1=true;
        var check2=true;
    </script>

    <link href="../js/form-validator/theme-default.min.css" rel="stylesheet" type="text/css"/>
    <script src="../js/src/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="../js/src/mode-javascript.js" type="text/javascript" charset="utf-8"></script>
    <script src="../js/form-validator/jquery.form-validator.min.js"></script>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">{{ __("Betik Oluşturma") }}</h1>
    </div>
    <button class="btn btn-success" onclick="history.back();">{{ __("Geri dön") }}</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#settingsModal">
        {{ __("Ayarlar") }}
    </button><br><br>
    <div class="cards">
        <div class="card w-auto">
            <div class="card-body">
                <h4 class="card-title">{{ __("Gerekli Parametreler") }}</h4>
                <h6>{{ __("Kullanıcıya input olarak gösterilecek parametreler, parametre adları") }} <b>{{ __("aynı yazıldığı gibi") }}</b> {{ __("arayüzde gösterilecektir") }}.</h6>
                <div class="form-inline">
                    <table>
                        <tr>
                            <td style="margin:10px;">
                                <div class="form-group">
                                    <input id="inputName" type="text" class="form-control" placeholder="{{ __("Parametre Adı") }}" data-validation="length" data-validation-length="min0">
                                </div>
                            </td>
                            <td style="margin:10px;">
                                <select class="form-control" name="inputs" style="color:white;background-color:#5cb85c;" id="inputType">
                                    <option value="string" style="color:white;background-color:#5cb85c;">{{ __("Metin") }}</option>
                                    <option value="number" style="color:white;background-color:#428bca;">{{ __("Sayı") }}</option>
                                    <option value="ip" style="color:white;background-color:#f0ad4e;">{{ __("İp Adresi") }}</option>
                                </select>
                            </td>
                            <td style="margin:10px;">
                                <button class="btn btn-primary" onclick="addInput()">{{ __("Ekle") }}</button>
                            </td>
                            <div class="alert alert-danger alert-dismissable" id="alert3">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                Aynı parametre girilemez!
                            </div>
                        </tr>
                    </table>

                </div>
                <br>
                <div class="inputs">

                </div>
            </div>
        </div>
        <div class="card w-auto" style="width: 18rem;  height: 18rem;">
            <div class="card-body">
                <div class="form-group">
                    <label for="exampleFormControlTextarea1">{{ __("Kodu buraya yazınız") }}</label>
                    <div id="editor"></div>
                </div>
            </div>
        </div>
        <div class="card w-auto">
            <div class="card-body">
                <h5 class="card-title">{{ __("Sonuç Parametreleri") }}</h5>
                <div class="form-inline">
                    <table>
                        <tr>
                            <td style="margin:10px;">
                                <div class="form-group">
                                    <input id="ResultParameterName" type="text" class="form-control" placeholder="{{ __("Parametre Adı") }}" data-validation="length" data-validation-length="min0">
                                </div>
                            </td>
                            <td style="margin:10px;">
                                <select class="form-control" name="inputs" id="inputTypeResult" style="color:white;background-color:#5cb85c;">
                                    <option value="string" style="color:white;background-color:#5cb85c;">{{ __("Metin") }}</option>
                                    <option value="number" style="color:white;background-color:#428bca;">{{ __("Sayı") }}</option>
                                    <option value="ip" style="color:white;background-color:#f0ad4e;">{{ __("İp Adresi") }}</option>
                                </select>
                            </td>
                            <td style="margin:10px;">
                                <button class="btn btn-primary" onclick="addResultParameters()">{{ __("Ekle") }}</button>
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
                    <h3>{{ __("Sorumluluk Reddi") }}</h3>
                    {{ __("Bu dosyayı kaydetmenin sorumluluğunu üstleniyorum") }}.<br><br>
        <button onclick="addAll()" class="btn btn-primary w-auto">
            {{ __("Ekle") }}
        </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="exampleModalLabel">{{ __("Betik Ayarları") }}</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form>
                    <div class="modal-body">
                        <div class="column">
                        <div class="form-group">
                            <h3>{{ __("Adı") }}</h3>
                            <input id="name" type="text" class="form-control" placeholder="{{ __("Betik kısa adı") }}" required>
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Özellik") }}</h3>
                            <select class="form-control" id="feature">
                                @foreach ($features as $feature)
                                    <option value="{{$feature->_id}}">{{$feature->name}}</option>
                                @endforeach
                            </select>
                        </div>
                            <div class="form-group">
                                <h3>{{ __("Versiyon") }}</h3>
                                <input id="version" type="text" class="form-control" placeholder="{{ __("Betik Versiyonu") }}" value="1" data-validation="custom"  data-validation-regexp="^[0-9]" data-validation-error-msg="{{ __("Versiyon Sayı Olmalı") }}.">
                            </div>
                            <div class="form-group">
                                <h5>{{ __("Language") }}</h5>
                                <input id="code_language" type="text" class="form-control"
                                       placeholder="{{ __("Language") }}">
                            </div>
                            <div class="form-group">
                                <h5>{{ __("Kuruluş") }}</h5>
                                <input id="company" type="text" class="form-control" placeholder="{{ __("Kuruluş") }}" data-validation="required" data-validation-error-msg="Bu alan girilmesi zorunludur.">
                            </div>
                        </div>
                        <div class="column">

                        <div class="form-group">
                            <h3>{{ __("Açıklama") }}</h3>
                            <input id="description" type="text" class="form-control" placeholder="{{ __("Anahtar Kullanıcı Adı") }}">
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Mail Adresi") }}</h3>
                            <input id="email" type="email" class="form-control"  placeholder="{{ __("Destek Verilecek ") }}{{ __("Mail Adresi") }}" data-validation="email" data-validation-error-msg="{{ __("Geçerli bir e-mail address girin") }}.">
                        </div>
                        <div class="form-group">
                            <h3>{{ __("Betik Türü") }}</h3>
                            <select class="form-control" name="inputs" id="betiktype">
                                <option value="query">{{ __("Sorgulama") }}</option>
                                <option value="query">{{ __("Çalıştırma") }}</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __("İptal") }}</button>
                        <button type="button" class="btn btn-success" onclick="add()">{{ __("Kaydet") }}</button>
                    </div>
                    <div class="alert alert-danger alert-dismissable" id="alert1">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        {{ __("Alanların doğru doldurulması gerekiyor!") }}
                    </div>
                    <div class="alert alert-success alert-dismissable" id="alert2">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        {{ __("Doğru Yönlendiriliyor") }}.
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
            if(data["inputs"]) {
                data["inputs"] = data["inputs"] + "," + $("#inputName").val() + ":" + $("#inputType").val();
                //console.log(data["inputs"].substr(0, data["inputs"].indexOf(':')));
            }
            else
                data["inputs"]=$("#inputName").val()+":"+$("#inputType").val();
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
            editor.session.setMode("ace/mode/$( \"#code_language\" ).val()");
            if($( "#code_language" ).val()=="python3")
                data["language"]="!/usr/bin/env/"+$( "#code_language" ).val();
            else
                data["language"]=$( "#code_language" ).val();
            data["company"]=$( "#company" ).val();
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
            data["code"]=command.textContent;
            console.log(data["inputs"]);
            console.log(data["outputs"]);
            if( 'name' in data){
                $.post("{{route('script_create')}}" ,{
                    data:data
                },function (data,status) {
                    if(data["result"] === 200){
                        //window.location.replace("{{route('users')}}" + "/" + data["id"]);
                        console.log(data);
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

    </script>
@endsection