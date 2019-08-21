@php($random = str_random(20))

<div class="modal fade" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h3 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset
                </h3>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer">
                @isset($footer)
                    <button class="btn {{$footer["class"]}}" onclick="{{$footer["onclick"]}}">{{$footer["text"]}}</button>
                @endisset
                <button type="button" class="btn btn-default" data-dismiss="modal">{{__('Kapat')}}</button>
            </div>
        </div>
    </div>
</div>