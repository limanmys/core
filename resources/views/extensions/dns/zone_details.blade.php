@if(is_array($data["dns_list_record"]))
    <h3>Zone Detayları</h3>
    <button class="btn btn-primary" data-toggle="modal" data-target="#add_forward_zone">{{__("Kayıt Ekle")}}</button><br><br>

    <table class="hover">
        <thead>
        <tr>
            <th scope="col">{{__("Kayıt İsmi")}}</th>
            <th scope="col">{{__("Tipi")}}</th>
            <th scope="col">{{__("İp Adresi")}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data["dns_list_record"]["records"] as $record)
            @php($arr = explode('	',$record))
            <tr>
                <td id="name">{{$arr[0]}}</td>
                <td id="name">{{$arr[2]}}</td>
                <td id="ip_address">{{$arr[3]}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

@include('l.modal',[
    "id"=>"add_forward_zone",
    "title" => "Forward Zone Ekle",
    "url" => route('extension_api',['dns_add_record']),
    "next" => "reload",
    "inputs" => [
        "-:" . request('alan_adi') => "alan_adi:hidden",
        "Kayıt Adı" => "hostname:text",
        "Tipi:kayit_turu" => [
            "SOA" => "SOA",
            "NS" => "NS",
            "A" => "A",
            "AAAA" => "AAAA",
            "MX" => "MX",
            "CNAME" => "CNAME"
        ],
        "İp Adresi" => "ip:text",
        "-:" . server()->_id => "server_id:hidden",
        "-:$extension->_id" => "extension_id:hidden",

    ],
    "submit_text" => "Ekle"
])