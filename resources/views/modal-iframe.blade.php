<div class="modal fade modal-lg m-auto" id="@isset($id){{$id}}@endisset" tabindex="-1" role="dialog" aria-hidden="true" style="max-width: 1000px;max-height: 1000px">
    <div class="modal-dialog" role="document" style="max-width: 1000px;height: 80%">
        <div class="modal-content" style="height: 100%;">
            <div class="modal-header">
                <h4>
                    Terminal
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color:white">&times;</span>
                </button>
            </div>
                <div class="modal-body">
                    <iframe src="https://localhost:4433/" seamless="seamless"  frameborder="0" style="display:block; width:100%; height:60vh;"></iframe>
                </div>
                <div class="modal-footer">
                    <h6>Yazdığınız tüm komutlar kayıt altıntadır.</h6>
                </div>
        </div>
    </div>
</div>