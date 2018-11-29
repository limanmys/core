@if(is_array($data["dns_zone_add"]))
    @foreach($data["dns_zone_add"] as $zone)
        <div class="card">
            <div class="card-title">
                <span onclick="request('zone_details',refresh,'name:{{$zone["name"]}}')">{{$zone["name"]}}</span>
            </div>
            <div class="card-body">

            </div>
        </div>
    @endforeach

    <script>
        function refresh() {
            location.reload();
        }
    </script>
@endif