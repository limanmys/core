<div class="col-md-3">
    <div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title" style="width: 100%;">
            <div class="float-left">
                {{$server->name}}
            </div>
            <div class="float-right">
            @if($favorite)
                <a onclick="favorite('false')" data-toggle="tooltip" title="{{ __('Sabitlemeyi kaldır') }}" style="color: orange;">
                    <i class="fas fa-star"></i>
                </a>
            @else
                <a onclick="favorite('true')" data-toggle="tooltip" title="{{ __('Sunucuyu sabitle') }}" style="color: orange;">
                    <i class="far fa-star"></i>
                </a>
            @endif
            </div>
        </h3>
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
        @isset($outputs["user"])
        <strong>{{ __('Giriş Yapmış Kullanıcı') }}</strong>
        <p class="text-muted">
            {{ $outputs["user"] }}
        </p>
        <hr>
        @endisset
        @if(server()->canRunCommand())
            <strong>{{ __('Açık Kalma') }}</strong>
            <p class="text-muted">{{ $outputs["uptime"] }}</p>
            <hr>
            <strong>{{ __('Servis Sayısı') }}</strong>
            <p class="text-muted">{{$outputs["nofservices"]}}</p>
            <hr>
            <strong>{{ __('İşlem Sayısı') }}</strong>
            <p class="text-muted">{{$outputs["nofprocesses"]}}</p>
        @endif
    </div>
    </div>
</div>