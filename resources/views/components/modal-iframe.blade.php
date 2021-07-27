<div class="modal fade" id="@isset($id){{$id}}@endisset">
    <div class="modal-dialog modal-dialog-centered modal-xl">
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
            <div class="modal-body" style="height: 100%">
                <iframe src="{{$url}}" style="width:100%; height:100%;background-color:black; border: none; outline: none;"></iframe>
            </div>
        </div>
    </div>
</div>