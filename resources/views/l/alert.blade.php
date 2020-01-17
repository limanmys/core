<div class="alert alert-{{ isset($type) ? $type : 'info' }} alert-dismissible">
    <h5><i class="icon fas fa-info"></i> {{ __($title) }}</h5>
    {!! __($message) !!}
</div>