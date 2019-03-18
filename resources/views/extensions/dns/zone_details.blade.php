@if(is_array($data["dns_list_record"]))
    {{--@php(dd($data["dns_list_record"]))--}}
    <h3>Zone Detayları</h3>
    <button class="btn btn-primary" data-toggle="modal" data-target="#add_forward_zone">{{__("Kayıt Ekle")}}</button><br><br>

    @include('l.table',[
            "value" => $data["dns_list_record"]["records"],
            "title" => [
                "Kayıt Adı" , "Tipi" , "İp Adresi"
            ],
            "display" => [
                "hostname" , "type", "ip"
            ],
            "menu" => [
                "Düzenle" => [
                    "target" => "update",
                    "icon" => "edit"
                ],
                "Sil" => [
                    "target" => "delete",
                    "icon" => "delete"
                ]
            ],
            "setCurrentVariable" => "current",
            "onclick" => "details"
        ])

@endif

@include('l.modal',[
    "id"=>"add_forward_zone",
    "title" => "Forward Zone Ekle",
    "url" => route('extension_api',['dns_add_record']),
    "next" => "reload",
    "inputs" => [
        "-:" . request('alan_adi') => "zone:hidden",
        "Kayıt Adı" => "hostname:text",
        "Tipi:type" => [
            "SOA" => "SOA",
            "NS" => "NS",
            "A" => "A",
            "AAAA" => "AAAA",
            "MX" => "MX",
            "PTR" => "PTR",
            "CNAME" => "CNAME"
        ],
        "İp Adresi" => "ip:text",
        "-:" . server()->_id => "server_id:hidden",
        "-:$extension->_id" => "extension_id:hidden",

    ],
    "submit_text" => "Ekle"
])

@include('l.modal',[
       "id"=>"delete",
       "title" =>"Kaydı Sil",
       "url" => route('extension_api',['dns_remove_record']),
       "next" => "reload",
       "text" => "Kaydı silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
       "inputs" => [
           "Hostname :null" => "hostname:hidden",
           "Type :null" => "type:hidden",
           "IP:null" => "ip:hidden",
           "Zone Adı:" . request('alan_adi') => "zone:hidden",
           "-:" . server()->_id => "server_id:hidden",
           "-:" . extension()->_id => "extension_id:hidden",
       ],
       "submit_text" => "Kaydı Sil"
   ])

@include('l.modal',[
       "id"=>"update",
       "title" =>"Kaydı Güncelle",
       "onsubmit" => "reload",
       "inputs" => [
           "Kayıt Adı" => "hostname:text",
           "Tipi:type" => [
                "SOA" => "SOA",
                "NS" => "NS",
                "A" => "A",
                "AAAA" => "AAAA",
                "MX" => "MX",
                "CNAME" => "CNAME"
           ],
           "İp Adresi" => "ip:text",
           "Zone Adı:" . request('alan_adi') => "zone:hidden",
           "-:" . server()->_id => "server_id:hidden",
           "-:" . extension()->_id => "extension_id:hidden",
       ],
       "submit_text" => "Kaydı Güncelle"
   ])

<script>
    function updateRecord(data){
        let form = new FormData(data);
        let hostname = form.get('hostname');
        let type = form.get('type');
        let ip = form.get('ip');
        form.delete('hostname');
        form.append('hostname',$("#" + current + " #hostname").html());
        form.append('hostname_new',hostname);
        form.delete('type');
        form.append('type',$("#" + current + " #type").html());
        form.append('type_new',type);
        form.delete('ip');
        form.append('ip',$("#" + current + " #ip").html());
        form.append('ip_new',ip);
        modalData = form;
        request('{{route('extension_api',['dns_edit_record'])}}',form,function(response){
            updateTable('_new');
        });
        return false;
    }
</script>