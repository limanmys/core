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