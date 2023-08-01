@component('mail::layout')

  @slot('header')
    @component('mail::header', ['url' => config('app.url')])
    @endcomponent
  @endslot

  Merhaba,<br /><br />
  {{$content}}<br /><br />
  Bilginize.

  @slot('footer')
    @component('mail::footer')
      &copy; Liman MYS
    @endcomponent
  @endslot

@endcomponent
