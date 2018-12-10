@extends('layouts.app')

@section('content')
    <script>
        var data=[];
        var color=["#5cb85c","#428bca","#f0ad4e"];
    </script>
    <!-- Styles -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">

    <button class="btn btn-success" onclick="history.back();">Geri Don</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#settingsModal">
        Ayarlar
    </button><br><br>
    <div class="cards">
        <div class="card w-auto">
            <div class="card-body">
                <h4 class="card-title">Gerekli Parametreler</h4>
                <h6>Kullanıcıya input olarak gösterilecek parametreler, parametre adları <b>aynı yazıldığı gibi</b> arayüzde gösterilecektir.</h6>
                <div class="form-inline">
                    <table>
                        <tr>
                            <td style="margin:10px;">
                                <div class="form-group">
                                    <input id="inputName" type="text" class="form-control" placeholder="Parametre Adı" data-validation="length" data-validation-length="min0">
                                </div>
                            </td>
                            <td style="margin:10px;">
                                <select class="form-control" name="inputs" style="color:white;background-color:#5cb85c;" id="inputType" onchange="myInputs()">
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
                <div class="inputs" id="inputs">

                </div>
            </div>
        </div>
        <div class="card w-auto" style="width: 18rem; height: 20rem;">
            <div class="card-body">
                <div class="form-group">
                    <label for="exampleFormControlTextarea1">Kodu buraya yazınız</label>
                    <div>
                    <textarea id="code"></textarea>
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
                                <select class="form-control" name="inputs" id="inputTypeResult" style="color:white;background-color:#5cb85c;" onchange="myResults()">
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
                        <div class="column">
                        <div class="form-group">
                            <h3>Adı</h3>
                            <input id="name" type="text" class="form-control" placeholder="Betik kısa adı" >
                        </div>

                        <div class="form-group">
                            <h3>Özellik</h3>
                            <select class="form-control" id="feature">
                                @foreach ($extensions as $extension)
                                    <option value="{{$extension->_id}}">{{$extension->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <h3>Versiyon</h3>
                            <input id="username" type="text" class="form-control" placeholder="Betik Versiyonu" value="1">
                        </div>
                            <div class="form-group">
                                <h3>Dil Seçimi</h3>
                                <select class="form-control" name="inputs" id="languagetype">
                                    <option value="query">Python</option>
                                    <option value="query">Bash</option>
                                </select>
                            </div>
                        </div>
                        <div class="column">
                        <div class="form-group">
                            <h3>Açıklama</h3>
                            <input id="description" type="text" class="form-control" placeholder="Anahtar Kullanıcı Adı">
                        </div>
                        <div class="form-group">
                            <h3>Mail Adresi</h3>
                            <input id="email" type="email" class="form-control"  placeholder="Destek verilecek Email Adresi">
                        </div>
                        <div class="form-group">
                            <h3>Betik Türü</h3>
                            <select class="form-control" name="inputs" id="betiktype">
                                <option value="query">Sorgulama</option>
                                <option value="query">Çalıştırma</option>
                            </select>
                        </div>
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
        function myInputs(){
            var x = document.getElementById("inputType");
            x.style.backgroundColor=color[x.selectedIndex];
        }
        function myResults() {
            var x = document.getElementById("inputTypeResult");
            x.style.backgroundColor=color[x.selectedIndex];
        }
        function addInput() {
            if(data["inputs"])
                data["inputs"]=data["inputs"]+","+document.getElementById("inputName").value+":"+document.getElementById("inputType").value;
            else
                data["inputs"]=document.getElementById("inputName").value+":"+document.getElementById("inputType").value;
            var r= createButton(document.getElementById("inputName").value,document.getElementById("inputType").selectedIndex);
            document.getElementById('inputs').appendChild(r);
        }
        function addResultParameters(){
            var name= $("#ResultParameterName").val();
            var type = $("#inputTypeResult").val();
            if(data["outputs"])
                data["outputs"]=data["outputs"]+","+document.getElementById("ResultParameterName").value+":"+document.getElementById("inputTypeResult").value;
            else
                data["outputs"]=document.getElementById("ResultParameterName").value+":"+document.getElementById("inputTypeResult").value;
            var r= createButton(document.getElementById("ResultParameterName").value,document.getElementById("inputTypeResult").selectedIndex);
            document.getElementById('Resultsinputs').appendChild(r);
        }
        function add(){
            data["name"]=$("#name").val();
            data["extension"]=$( "#feature option:selected" ).text();
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
            var command=document.getElementById("code");
            data["code"]=command;
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
            var elem = document.getElementById(id);
            elem.parentNode.removeChild(elem);
        }
        function createButton(name,index){
            var button=document.createElement("BUTTON");
            button.id=name;
            button.className="btn btn-success";
            button.innerHTML=name;
           // button.onclick=sil(this);
            button.style.margin="10px";
            button.style.backgroundColor=color[index];
            return button;
        }
    </script>


@endsection