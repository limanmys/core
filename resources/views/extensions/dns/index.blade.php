@if(is_array($data["dns_zone_add"]))
    @foreach($data["dns_zone_add"] as $zone)
        <div class="card">
            <div class="card-title">
                <span onclick="redirect('zone_details','alan_adi:{{$zone["name"]}}')">{{$zone["name"]}}</span>
            </div>
            <div class="card-body">

            </div>
        </div>
    @endforeach
@endif