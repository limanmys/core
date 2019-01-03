@if($server->type == "windows" || $server->type == "windows_powershell")
    @include('extensions.kullanıcılar.index_windows')
@else
    @include('extensions.kullanıcılar.index_linux')
@endif