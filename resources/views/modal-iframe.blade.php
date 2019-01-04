<div class="modal fade modal-lg m-auto" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true" style="max-width: 1000px;max-height: 1000px">
    <div class="modal-dialog" role="document" style="max-width: 1000px;height: 80%">
        <div class="modal-content" style="height: 100%;">
            <div class="modal-header">
                <h4>
                    {{$title}}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color:white">&times;</span>
                </button>
            </div>
                <div class="modal-body" style="height: 90%;">
                    @include('__system__.loading',['show' =>'1'])
                    <iframe src="{{$url}}" frameborder="0" style="display:block; width:100%; height:60vh;" onload="document.querySelector('#{{$id}} .loader').style.display = 'none';"></iframe>
                </div>
        </div>
    </div>
</div>