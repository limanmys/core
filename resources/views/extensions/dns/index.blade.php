<div class="btn-group">
  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {{__("Zone Ekle")}}
  </button>
  <div class="dropdown-menu">
    <button class="dropdown-item" data-toggle="modal" data-target="#add_forward_zone">Forward Zone Ekle</button>
    <button class="dropdown-item" data-toggle="modal" data-target="#add_reverse_zone">Reverse Zone Ekle</button>
  </div>
</div>

@if(is_array($data["dns_list_zone"]))
    @foreach($data["dns_list_zone"] as $zone)
        <div class="card">
            <div class="card-title">
                <span onclick="route('zone_details?&alan_adi={{$zone["name"]}}')">{{$zone["name"]}}</span>
            </div>
            <div class="card-body">
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