@extends('layouts.app')

@section('content')
    @include('title',[
        "title" => "Betik Oluştur"
    ])

    @include('modal-button',[
        "text" => "Ayarlar",
        "target_id" => "settings",
        "class" => "btn-primary"
    ])<br><br>

    @include('modal',[
        "id"=>"settings",
        "title" => "Betik Ayarları",
        "onsubmit" => "settings()",
        "inputs" => [
            "Adı" => "name:text",
            "Açıklama" => "description:text",
            "Versiyon" => "version:number",
            "Eklenti:extensions" => [
                "Sunucu" => "server",
                "DNS" => "dns"
            ],
            "Betik Türü:type" => [
                "Sorgulama" => "1",
                "Çalıştırma" => "2:0",
                "Ekleme" => "2:1",
                "Silme" => "2:2"
            ],
            "Root Yetkisi:root" => [
                "Gerekli" => "1",
                "Gereksiz" => "0"
            ],
            "Destek Maili" => "support_email:email",
            "Kurum" => "company:text",
            "Benzersiz Isim" => "unique_code:text",
            "Regex" => "regex:text"
        ],
        "submit_text" => "Kaydet"
    ])
    <div class="form-row align-items-center">
        <div class="form-row">
        <div class="col">

            <input placeholder="Input Adı" id="i_name" class="form-control">
        </div>
        <div class="col">

            <select id="i_type" class="form-control">
                <option value="string">Yazı</option>
                <option value="number">Sayı</option>
            </select>
        </div>
        <div class="col">
            <button class="btn btn-primary" onclick="addInput()">Ekle</button>
        </div>
        </div>
    </div>
    <br>
    <div id="inputs">

    </div>
        <div class="form-group">
          <label class="h4">Betik Kodu</label>
          <textarea id="code" class="form-control" rows="15"></textarea>
        </div>
        <button class="btn btn-success" onclick="add()">Betiği Oluştur</button>
    <script>
        var inputs = [];
        function settings(){
            document.querySelector('[aria-label="Close"]').click();
            return false;
        }

        function addInput(){
            var name = document.getElementById("i_name").value;
            var type = document.getElementById("i_type").value;
            if(inputs.includes(name + ":string") || inputs.includes(name + ":number")){
                return false;
            }
            inputs.push(name + ":" + type);
            var new_span = document.createElement('span');
            new_span.appendChild(document.createTextNode(name));
            new_span.setAttribute('class',(type === "string") ? 'string' : 'number');
            new_span.setAttribute("onclick","removeInput('" + name + "','" + type + "',this)");
            document.getElementById("inputs").appendChild(new_span);
        }
        function removeInput(name,type,element){
            var index  = inputs.indexOf(name + ":" + type);
            delete inputs[index];
            element.remove();
        }

        function add(){
            var form = new FormData(document.getElementById("settings_form"));
            form.append('inputs',inputs.join(','));
            form.append('code',document.getElementById('code').value);
            request("{{route('script_create')}}",form,redirect);
        }
    </script>
@endsection