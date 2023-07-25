@component('mail::layout')

  @slot('header')
    @component('mail::header', ['url' => config('app.url')])
    @endcomponent
  @endslot

  Merhaba,<br /><br />
  {{$notification['content']}}<br /><br />
  Bilginize.

  @slot('footer')
    @component('mail::footer')
      Bu email <a href="https://liman.works">Liman MYS</a> dış bildirim sisteminde <b>{{ isset(explode("->", $notification['title'])[1]) ? explode("->", $notification['title'])[1] : 'Liman' }}</b> tarafından oluşturulmuştur.
    @endcomponent
  @endslot

@endcomponent
