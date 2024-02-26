@component('mail::layout')

  @slot('header')
    @component('mail::header', ['url' => config('app.url')])
    @endcomponent
  @endslot

  {{__("Merhaba")}},<br /><br />
  {{$notification['content']}}<br /><br />
  {{__("Bilginize")}}.

  @slot('footer')
    @component('mail::footer')
      Bu email <a href="https://liman.havelsan.com.tr">Liman MYS</a> dış bildirim sisteminde <b>{{ isset(explode("->", $notification['title'])[1]) ? explode("->", $notification['title'])[1] : 'Liman' }}</b> tarafından oluşturulmuştur.
    @endcomponent
  @endslot

@endcomponent
