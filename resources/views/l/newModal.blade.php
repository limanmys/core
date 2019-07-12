<div class="modal fade" id="{{ isset($id) ? $id : "" }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title">{{ isset($title) ? $title : "" }}</h4>
            </div>
            @isset($onsubmit)
                <form @isset($id)id="{{$id}}_form"@endisset onsubmit="return {{$onsubmit}}(this)" target="#">
            @else
                <form @isset($id)id="{{$id}}_form"@endisset onsubmit="return @isset($url)request('{{$url}}',this,@isset($next){{$next}}@endisset)"@endisset target="#">
            @endif
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>