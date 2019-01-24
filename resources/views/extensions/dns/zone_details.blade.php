@if(is_array($data["dns_list_record"]))
    <h5>Zone Detayları</h5>

    {{print_r($data["dns_list_record"])}}
@else
    Bos
@endif