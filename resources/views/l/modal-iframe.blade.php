<div class="modal fade" id="@isset($id){{$id}}@endisset">
    <div class="modal-dialog modal-lg">
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
            <div class="modal-body" style="height: 70%">
                <iframe src="{{$url}}" style="width:100%; height:100%;background-color:black;"></iframe>
            </div>
        </div>
    </div>
</div>