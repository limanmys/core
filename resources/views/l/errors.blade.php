@if($errors)
    @foreach ($errors->all() as $message)
        <div class="alert alert-danger">
            <h4><i class="icon fa fa-ban"></i> {{__('Hata!')}}</h4>
            {{$message}}
        </div>
    @endforeach
@endif