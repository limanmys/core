@if($server->type == "windows" || $server->type == "windows_powershell")
    @include('extensions.bilgisayarlar.index_windows')
@else
    @include('extensions.bilgisayarlar.index_linux')
@endif