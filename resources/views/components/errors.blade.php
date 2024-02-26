@if(isset($errors))
    @foreach ($errors->all() as $message)
        <div class="alert alert-danger alert-dismissible">
            <h5><i class="icon fas fa-ban"></i> {{__('Hata!')}}</h5>
            {{$message}}
        </div>
    @endforeach
@endif