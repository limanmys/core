@php($random = str_random(20))
<div class="modal fade" id="@isset($id){{$id}}@endisset">
    <div class="modal-dialog @if(!isset($notSized) || !$notSized) modal-xl @endif {{ isset($modalDialogClasses) ? $modalDialogClasses : ''}}">
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
        <div class="modal-footer justify-content-right">
            @isset($footer)
                <button class="btn {{$footer["class"]}}" onclick="{{$footer["onclick"]}}">{{__($footer["text"])}}</button>
            @endisset
        </div>
        </div>
    </div>
</div>