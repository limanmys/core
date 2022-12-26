@php
    $random = str_random(20)
@endphp
<div class="card card-primary table-card" id="card-{{ $random }}">
    <div class="card-header" style="background-color: #f9fafb;">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="card-tools">
            <button type="button" onclick="func_{{ $random }}()" class="btn btn-tool refresh-button"><i class="fas fa-sync-alt" style="color: #2a2e32;"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="{{ $random }}"></div>
    </div>
    <div class="overlay">
        <div class="spinner-border" role="status">
            <span class="sr-only">{{ __('Yükleniyor...') }}</span>
        </div>
    </div>
</div>

<style>
    .table-card table.dataTable{
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
</style>

<script>
    function dataTableCustomTablePreset(){
        return Object.assign(
            dataTablePresets('normal'),
            {
                "paging": false,
                "info": false,
                "searching": false
            }
        );
    }
    var var_{{ $random }}Timeout;
    function func_{{ $random }}(noSpinner = false)
    {
        !noSpinner && $('#card-{{ $random }}').find('.overlay').show();
        request("{{route($api)}}", new FormData(), function(response) {
            $('#{{ $random }}').html(response).find('table').DataTable(dataTableCustomTablePreset());
            !noSpinner && $('#card-{{ $random }}').find('.overlay').hide();
            var_{{ $random }}Timeout && clearTimeout(var_{{ $random }}Timeout);
            var_{{ $random }}Timeout = setTimeout(function(){
                if($("a[href=\"#usageTab\"]").hasClass("active")){
                    func_{{ $random }}(true);
                }
            }, 15000);
        }, function(response){
            let error = JSON.parse(response);
            showSwal(error.message, 'error', 2000);
            var_{{ $random }}Timeout && clearTimeout(var_{{ $random }}Timeout);
            var_{{ $random }}Timeout = setTimeout(function(){
                if($("a[href=\"#usageTab\"]").hasClass("active")){
                    func_{{ $random }}(true);
                }
            }, 15000);
        });
    }
</script>