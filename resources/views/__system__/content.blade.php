@foreach($scripts as $script)
    <tr>
        <td>{{$script->name}}</td>
        <td> {{$script->description}}</td>
        <?php
             $random=rand();
        ?>
        <td> @include('modal-button',[
            "class" => "btn-danger",
            "target_id" => "delete".$random,
             "text" => "Betik Sil"
        ])
            @include('modal',[
           "id"=> "delete".$random,
           "title" => $script->name,
           //"url" => route('server_remove'),
           "text" => "Bu Betik'i silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
           "next" => "reload",
           "inputs" => [
               "Extension Id:$extension->id" => "extension_id:hidden",
               "Script Id:$script->id" => "script_id:hidden"
           ],
           "submit_text" => "Betik Sil"
       ])
        </td>
    </tr>
@endforeach
