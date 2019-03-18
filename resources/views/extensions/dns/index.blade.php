<h3>{{__("Zone Listesi")}}</h3>
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
                @php($rand = str_random(3))
                Type : <b>{{$zone["type"]}}</b><br><br>
                <canvas id="{{$rand}}_pie"></canvas>
            </div>
            <div class="box-footer">
                <button class="btn btn-primary" onclick="route('zone_details?&alan_adi={{$zone['name']}}')">{{__("Kayıtları Gör")}}</button>
                <button class="btn btn-danger" onclick="removeZone('{{$zone["name"]}}')" style="float:right;">{{__("Zone'u Sil")}}</button>
            </div>
            <script>

                let config_{{$rand}} = {
                    type: 'pie',
                    data: {
                        datasets: [{
                            data: [
                                @foreach($zone["records"] as $record)
                                {{$record}},
                                @endforeach
                            ],
                            backgroundColor: [
                                '#f303a0',
                                '#8df53f',
                                '#157298',
                                '#243f32',
                                '#8dd9b0',
                                '#15aeff',
                                '#731bc8',
                                '#c33b9e',
                                '#a56559',
                                '#9cda73',
                                '#bc6d21'
                            ],
                            label: 'Dataset 1'
                        }],
                        labels: [
                            @foreach($zone["records"] as $key=>$record)
                            '{{$key}}',
                            @endforeach
                        ]
                    },
                    options: {
                        responsive: true
                    }
                };
                let ctx_{{$rand}} = document.getElementById('{{$rand}}_pie').getContext('2d');
                window.myPie = new Chart(ctx_{{$rand}}, config_{{$rand}});
            </script>
        </div>
    @endforeach
@endif

@include('l.modal',[
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

@include('l.modal',[
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

@include('l.modal',[
   "id"=>"delete",
   "title" =>"Zone'u Sil",
   "url" => route('extension_api',['dns_remove_zone']),
   "text" => "Bu zone'u silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
   "next" => "reload",
   "inputs" => [
       "Sunucu Id:" . server()->_id => "server_id:hidden",
       "extension_id:" . extension()->_id => "extension_id:hidden",
       "zone:zone" => "zone:hidden"
   ],
   "submit_text" => "Zone'u Sil"
])

<script>
    function removeZone(zone){
        $("#delete [name='zone']").val(zone);
        $("#delete").modal('show');
    }
</script>