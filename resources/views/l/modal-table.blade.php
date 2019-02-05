<div class="modal fade" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document" style="height:70%;width:70%">
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
                <div class="modal-body" style="display:block; width:100%; height:100%;">
                    @include('l.table',$table)
                </div>
            </form>
        </div>
    </div>
</div>