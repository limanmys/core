@if($server->type == "windows" || $server->type == "windows_powershell")
    @include('extensions.gruplar.index_windows')
@else
    @include('extensions.gruplar.index_linux')
@endif