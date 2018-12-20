@foreach ($files as $key => $files)
    @if(is_string($key))
            <div class="panel-group" id="accordion2">
        <div class="panel panel-default">
            <div class="panel-heading" >
                <h5 class="panel-title">
                    <i style="margin:5px; color:#DCDCDC;" class="fa fa-folder" aria-hidden="true"></i>
                    <a id="deneme1" style="text-decoration: none; color: white" data-toggle="collapse" data-parent="#accordion2" href="#<?php echo $key; ?>">{{$key}}</a>
                    <i class="arrow down"></i>
                </h5>
            </div>
            <div id="<?php echo $key;?>" class="panel-collapse collapse in">
                <div class="panel-body" > @include('__system__.dropdown',$files)</div>
            </div>
        </div>
        </div>
    @else
        <div style="background-color:#EEE;">
            <div>
                <i style="margin:5px; color: black"class="fa fa-file" aria-hidden="true"></i>
        <a  style="color:black;" class="btn btn-default glyphicon glyphicon-hand-up" onclick="details(this)">{{$files}}</a>
            </div>
        </div>
    @endif
@endforeach



