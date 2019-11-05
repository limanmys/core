@isset($id)
    @php($rand = $id)
@else
    @php($rand = str_random(10))
@endisset
<div class="form-group">
    <label>{{ isset($title) ? __($title) : '' }}</label>
    <div class="input-group" id="{{ $rand }}-file-input">
        <input type="text" id="{{ $rand }}-selected-file" placeholder="{{ isset($title) ? __($title) : '' }}" class="form-control" readonly>
        <span class="input-group-btn">
            <button class="btn btn-labeled btn-secondary" id="{{ $rand }}-browse" style="border-radius: 0px;">{{ __('Gözat') }}</button>
        </span>
        <span class="input-group-btn">
            <button class=" btn btn-labeled btn-primary" id="{{ $rand }}-upload" disabled style="border-radius: 0px;">{{ __('Yükle') }}</button>
        </span>
        <input type="file" name="{{ isset($name) ? $name : '' }}" id="{{ $rand }}-upload-file" style="display:none;"/>
    </div>

</div>
<div class="progress active" id="{{ $rand }}-progress" style="display:none; margin-top: 5px;">
    <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
        <span class="progress-txt"></span>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var uploadButton = $('#{{ $rand }}-upload'),
        selectedFile = $('#{{ $rand }}-selected-file');
        
        $('#{{ $rand }}-file-input').on('change', function (e) {
        var name = e.target.value.split('\\').reverse()[0];
    
        if (name) {
            selectedFile.val(name);
            uploadButton.attr('disabled', false);
        } else {
            selectedFile.val('');
            uploadButton.attr('disabled', true);
        }
        });

        $('#{{ $rand }}-browse, #{{ $rand }}-selected-file').click(function(){
            $('#{{ $rand }}-upload-file').click();
        });

    });

    $( "#{{ $rand }}-upload" ).click(function() {
        let selectedFile = $('#{{ $rand }}-upload-file').prop('files');
        upload({
            file: selectedFile[0],
            onError: function(error){
                Swal.fire({
                    position: 'center',
                    type: 'error',
                    title: error,
                    showConfirmButton: false,
                });
            },
            onProgress: function(bytesUploaded, bytesTotal){
                let percent = (bytesUploaded/bytesTotal)*100;
                $('#{{ $rand }}-progress').show();
                $('#{{ $rand }}-progress').addClass('active');
                $('#{{ $rand }}-progress').find('.progress-bar').attr('aria-valuenow', percent);
                $('#{{ $rand }}-progress').find('.progress-bar').attr('style', 'width: '+percent+'%');
                $('#{{ $rand }}-progress').find('.progress-txt').text(Math.round(percent)+"%");
            },
            onSuccess: function(upload){
                @isset($callback)
                    {{$callback}}(upload);
                @endisset
                $('#{{ $rand }}-progress').removeClass('active');
                $('#{{ $rand }}-progress').find('.progress-txt').text("{{ __('Yükleme tamamlandı') }}");
            },
        });
    });

</script>
