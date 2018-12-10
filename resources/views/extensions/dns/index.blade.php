<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addZone">
    Zone Ekle
</button>
@if(is_array($data["dns_generate"]))
    @foreach($data["dns_generate"] as $zone)
        <div class="card">
            <div class="card-title">
                <span onclick="redirect('zone_details','alan_adi:{{$zone["name"]}}')">{{$zone["name"]}}</span>
            </div>
            <div class="card-body">

            </div>
        </div>
    @endforeach
@endif

<div class="modal fade" id="addZone" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Zone Ekle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <h3>Adı</h3>
                    <input id="zone_name" type="text" class="form-control" placeholder="Zone Adı"><br>
                    <h3>Zone Tipi</h3>
                    <select class="form-control" id="zone_type" name="Zone Tipi">
                        <option value="forward">Forward</option>
                        <option value="reverse">Reverse</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-success" onclick="addZone()">Zone Ekle</button>
            </div>
        </div>
    </div>
</div>

<script>
    function addZone() {
        refresh('add_' + $("#zone_type").val()  + '_zone','alan_adi:' + $("#zone_name").val());
    }
</script>