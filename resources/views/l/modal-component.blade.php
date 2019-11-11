@php($random = str_random(20))
<div class="modal fade" id="@isset($id){{$id}}@endisset">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">
                @isset($title)
                    {{__($title)}}
                @endisset
            </h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            {{ $slot }}
        </div>
        <div class="modal-footer justify-content-between">
            @isset($footer)
                <button class="btn {{$footer["class"]}}" onclick="{{$footer["onclick"]}}">{{$footer["text"]}}</button>
            @endisset
            <button type="button" class="btn btn-default" data-dismiss="modal">{{__('Kapat')}}</button>
        </div>
        </div>
    </div>
</div>