<div class="modal fade" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="height:70%">
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
                    <iframe src="{{$url}}" frameborder="0" style="display:block; width:100%; height:100%;background-color:black;"></iframe>
                </div>
        </div>
    </div>
</div>