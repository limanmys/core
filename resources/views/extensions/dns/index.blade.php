<h3>Zone Listesi</h3>

<div class="input-group-btn">
    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    {{__("Zone Ekle")}} <span class="fa fa-caret-down"></span></button>
    <ul class="dropdown-menu">
        <li><a data-toggle="modal" data-target="#add_forward_zone">Forward Zone</a></li>
        <li><a data-toggle="modal" data-target="#add_reverse_zone">Reverse Zone</a></li>
    </ul>
</div>
<br>
@if(is_array($data["dns_list_zone"]))
    @foreach($data["dns_list_zone"] as $zone)
        <div class="box box-solid box-primary" style="width:30%;min-width:300px;float:left;margin: 20px;">
            <div class="box-header">
                <h3 class="box-title">{{$zone["name"]}}</h3>
            </div>
            <div class="box-body">
                Type : {{$zone["type"]}}<br><br>
                <table class="notDataTable">
                    <tr>
                        <td style="width:50px;text-align:center">NS</td>
                        <td style="width:50px;text-align:center">A</td>
                        <td style="width:50px;text-align:center">CNAME</td>
                        <td style="width:50px;text-align:center">AAAA</td>
                        <td style="width:50px;text-align:center">MX</td>
                        <td style="width:50px;text-align:center">SOA</td>
                        <td style="width:50px;text-align:center">TOTAL</td>
                    </tr>
                    <tr>
                        <td style="width:50px;text-align:center">{{$zone["records"]["NS"]}}</td>
                        <td style="width:50px;text-align:center">{{$zone["records"]["A"]}}</td>
                        <td style="width:50px;text-align:center">{{$zone["records"]["CNAME"]}}</td>
                        <td style="width:50px;text-align:center">{{$zone["records"]["AAAA"]}}</td>
                        <td style="width:50px;text-align:center">{{$zone["records"]["MX"]}}</td>
                        <td style="width:50px;text-align:center">{{$zone["records"]["SOA"]}}</td>
                        <td style="width:50px;text-align:center">{{$zone["records"]["total"]}}</td>
                    </tr>
                </table>
            </div>
            <div class="box-footer">
                <button class="btn btn-primary" onclick="route('zone_details?&alan_adi={{$zone['name']}}')">{{__("Kayıtları Gör")}}</button>
                <button class="btn btn-danger" onclick="route('zone_details?&alan_adi={{$zone['name']}}')" style="float:right;">{{__("Zone'u Sil")}}</button>
            </div>
        </div>
    @endforeach
@endif

@include('modal',[
    "id"=>"add_forward_zone",
    "title" => "Forward Zone Ekle",
    "url" => route('extension_api',['dns_add_forward_zone']),
    "next" => "reload",
    "inputs" => [
        "Zone Adı" => "alan_adi:text",
        "-:$server->_id" => "server_id:hidden",
        "-:$extension->_id" => "extension_id:hidden"
    ],
    "submit_text" => "Ekle"
])

@include('modal',[
    "id"=>"add_reverse_zone",
    "title" => "Reverse Zone Ekle",
    "url" => route('extension_api',['dns_add_reverse_zone']),
    "next" => "reload",
    "inputs" => [
        "Zone Adı" => "ters_alan_adi:text",
        "-:$server->_id" => "server_id:hidden",
        "-:$extension->_id" => "extension_id:hidden"
    ],
    "submit_text" => "Ekle"
])

@include('modal',[
   "id"=>"delete",
   "title" =>"Zone'u Sil",
   "url" => route('extension_api',['dns_delete_zone']),
   "text" => "Bu zone'u silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
   "next" => "reload",
   "inputs" => [
       "Sunucu Id:'null'" => "server_id:hidden"
   ],
   "submit_text" => "Sunucuyu Sil"
])