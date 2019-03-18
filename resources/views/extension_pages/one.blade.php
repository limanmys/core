@extends('layouts.app')

@section('content')
    @php($flag = ($extension->serverless == true) ? true : false)
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('home')}}">{{__("Ana Sayfa")}}</a></li>
            <li class="breadcrumb-item" aria-current="page"><a
                        href="{{route('extensions_settings')}}">{{__("Eklenti Yönetimi")}}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $extension->name }}</li>
        </ol>
    </nav>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">{{__("Genel Ayarlar")}}</a></li>
            @if($flag)
            <li id="server_type"><a href="#tab_2" data-toggle="tab"
                                    aria-expanded="false">{{__("Kurulum Parametreleri")}}</a></li>
            @endif
            <li id="server_type"><a href="#tab_2_2" data-toggle="tab" aria-expanded="false">{{__("Sayfa Ayarları")}}</a>
            </li>
            <li class=""><a href="#tab_3" data-toggle="tab" aria-expanded="false">{{__("Widgetlar")}}</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1">
                <button class="btn btn-primary btn-sm" onclick="updateExtension('general')">{{__("Kaydet")}}</button>
                <h3>{{__("Eklenti Adı")}}</h3>
                <input id="extensionName" type="text" name="name" class="form-control" value="{{$extension->name}}">
                <h3>{{__("Yayınlayan")}}</h3>
                <input type="text" name="name" class="form-control" value="{{$extension->publisher}}" disabled>
                <h3>{{__("Destek Email'i")}}</h3>
                <input id="support" type="text" name="email" class="form-control" value="{{$extension->support}}">
                <h3>{{__("Logo (Font Awesome Ikon)")}}</h3>
                <input id="icon" type="text" name="icon" class="form-control" value="{{$extension->icon}}">
                <h3>{{__("Eklenti için sunucuda betik çalıştırılması gerekiyor mu?")}}</h3>
                <div class="bd-example">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" onchange="alert('geldi')" type="radio" name="serverless"
                               value="true" @if(!$flag)checked @endif>
                        <label class="form-check-label" for="inlineRadio1">{{__("Evet")}}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" onchange="alert('qwe')" type="radio" name="serverless"
                               value="false" @if($flag)checked @endif>
                        <label class="form-check-label" for="inlineRadio2">{{__("Hayır")}}</label>
                    </div>
                </div>
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane" id="tab_2">
                <button class="btn btn-primary btn-sm" onclick="updateExtension('setup','setupTable')">{{__("Kaydet")}}</button>
                <button class="btn btn-success btn-sm" onclick="addRow('setup','setupTable','{{__("Kurulum Parametresi")}}')">{{__("Ekle")}}</button><br><br>
                <table class="table table-striped table-hover" id="setupTable">
                    <thead>
                    <tr>
                        <th scope="col" variable-name="name">{{ __("İnput Adı") }}</th>
                        <th scope="col" variable-name="type">{{ __("Türü") }}</th>
                        <th scope="col" variable-name="variable">{{ __("Veri Adı") }}</th>
                    </tr>
                    </thead>
                    <tbody class="table-striped">
                    @foreach($extension->setup as $key=>$item)
                        <tr class="tableRow">
                            <td>{{$item["name"]}}</td>
                            <td>{{$item["type"]}}</td>
                            <td>{{$key}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="tab-pane" id="tab_2_2">
                <button class="btn btn-primary btn-sm" onclick="updateExtension('view','viewTable')">{{__("Kaydet")}}</button>
                <button class="btn btn-success btn-sm" onclick="addRow('view','viewTable','{{__("Sayfa")}}')">{{__("Ekle")}}</button><br><br>
                <table class="table table-striped table-hover" id="viewTable">
                    <thead>
                    <tr>
                        <th scope="col" variable-name="name">{{ __("Sayfa Adı") }}</th>
                        <th scope="col" variable-name="variable">{{ __("Çalışacak Betik/Fonksiyon") }}</th>
                        <th scope="col">{{ __("#") }}</th>
                    </tr>
                    </thead>
                    <tbody class="table-striped">
                    @foreach($extension->views as $key=>$view)
                        <tr class="tableRow">
                            <td>{{$key}}</td>
                            <td>{{implode(",",$view)}}</td>
                            <td>
                                @if($key != "install")
                                    <button class="btn btn-info btn-sm form-inline"
                                            onclick="location.href = location.href + '/{{$key}}'">{{__("Kodu Düzenle")}}</button>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.tab-pane -->
            <div class="tab-pane" id="tab_3">
                <button class="btn btn-primary btn-sm" onclick="updateExtension('widget','widgetTable')">{{__("Kaydet")}}</button>
                <button class="btn btn-success btn-sm" onclick="addRow('widget','widgetTable','{{__("Widget")}}')">{{__("Ekle")}}</button><br><br>
                <table class="table table-striped table-hover" id="widgetTable">
                    <thead>
                    <tr>
                        <th scope="col" variable-name="variable">{{ __("Çalışacak Betik/Fonksiyon") }}</th>
                        <th scope="col" variable-name="type">{{ __("Türü") }}</th>
                        <th scope="col" variable-name="name">{{ __("Widget Adı") }}</th>
                    </tr>
                    </thead>
                    <tbody class="table-striped">
                    @foreach($extension->widgets as $key=>$widget)
                        <tr class="tableRow">
                            <td>{{$key}}</td>
                            <td>{{$widget["type"]}}</td>
                            <td>{{$widget["name"]}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.tab-pane -->
        </div>
        <!-- /.tab-content -->
    </div>
    <script>
        let currentTable = "";
        function addRow(type,tableId,title){
            currentTable = $("#" + tableId);
            let modal = $("#add_record");
            modal.find('.modal-title').html(title + " {{__("Ekle")}}");
            modal.find('.modal-body').html('');
            currentTable.find('thead th').each(function(){
                if($(this).html() === "#"){
                    return;
                }
                $('<label>').attr({
                    class : 'form-check-label h5',
                }).html($(this).html()).appendTo(modal.find('.modal-body'));
                $('<input>').attr({
                    class : 'form-control',
                    placeholder: $(this).html(),
                    name: $(this).attr('variable-name'),
                }).appendTo(modal.find('.modal-body'));
                $('<br>').appendTo(modal.find('.modal-body'));
            });
            $('<input>').attr({
                value : '',
                type : 'hidden',
                name: 'type',
                required : true,
            }).appendTo(modal.find('.modal-body'));
            modal.modal('show');
        }

        function addTable(dummy){
            let data = new FormData(dummy);
            $("#add_record").modal('hide');
            let table = currentTable.DataTable();
            let row = [];
            for(var pair of data.entries()) {
                row.push(pair[1]);
            }
            table.row.add(row).draw(true);
            return false;
        }

        function updateExtension(type,tableId = null){
            let data = new FormData();
            data.append('extension_id','{{extension()->_id}}');
            data.append('type',type);
            if(type === "general"){
                data.append('name',$("#extensionName").val());
                data.append('icon',$("#icon").val());
                data.append('support',$("#support").val());
                data.append('serverless',$("input[name=serverless]:checked").val());
            }else{
                let table = $("#" + tableId);
                let array = [];
                table.find('.tableRow').each(function(){
                    let currentRow = [];
                    if(type === "view"){
                        $(this).find('td').each(function(){
                            let value = this.innerHTML.trim();
                            if(value === "" || value.startsWith('<button')){
                                return;
                            }
                            currentRow.push(value);
                        });
                    }else{

                    }
                   array.push({
                       [currentRow[0]] : currentRow.shift()
                   });
                });
                console.log(array);
                return false;
            }
            request('{{route('extension_settings_update')}}',data,function(){
                location.reload();
            });
        }
    </script>
    @include('l.modal',[
        "id"=>"add_record",
        "title" => "",
        "onsubmit" => "addTable",
        "next" => "debug",
        "inputs" => [
            "-:" . extension()->_id => "extension_id:hidden"
        ],
        "submit_text" => "Düzenle"
    ])
@endsection