<div class="col-md-3">
    <div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('Sunucu Bilgileri') }}</h3>
    </div>
    <div class="card-body">
        @if(server()->canRunCommand())
            <strong>{{ __('Hostname') }}</strong>
            <p class="text-muted">{{$outputs["hostname"]}}</p>
            <hr>
            <strong>{{ __('İşletim Sistemi') }}</strong>
            <p class="text-muted">{{$outputs["version"]}}</p>
            <hr>
        @endif
        <strong>{{ __('IP Adresi') }}</strong>
        <p class="text-muted">
            {{ $server->ip_address }}
        </p>
        <hr>
        <strong>{{ __('Şehir') }}</strong>
        <p class="text-muted">
            {{ cities($server->city) }}
        </p>
        <hr>
        <strong>{{ __('Eklenti Durumları') }}</strong>
        <p class="text-muted">
            @if($installed_extensions->count() > 0)
                @foreach($installed_extensions as $extension)
                    <span 
                        class="badge btn-secondary status_{{$extension->id}}"
                        style="cursor:pointer;font-size: 18px; margin-bottom: 5px;"
                        onclick="window.location.href = '{{route('extension_server',["extension_id" => $extension->id, "city" => $server->city, "server_id" => $server->id])}}'">
                        {{$extension->display_name}}
                    </span>
                @endforeach
            @else
                {{__("Yüklü eklenti yok.")}}
            @endif
        </p>
        <hr>
    </div>
    </div>
</div>