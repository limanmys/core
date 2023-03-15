<div style="font-family: sans-serif; display: block; margin: auto; max-width: 900px;" class="main">
    <p>Merhaba,</p><br>
    @php
      $notificationTitle = json_decode($notification->title);
      if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($notificationTitle->{app()->getLocale()})) {
            $notificationTitle = $notificationTitle->{app()->getLocale()};
        } else {
            $notificationTitle = $notificationTitle->en;
        }
      } else {
          $notificationTitle = $notification->title;
      }

      $notificationContent = json_decode($notification->message);
      if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($notificationContent->{app()->getLocale()})) {
            $notificationContent = $notificationContent->{app()->getLocale()};
        } else {
            $notificationContent = $notificationContent->en;
        }
      } else {
          $notificationContent = $notification->message;
      }
    @endphp
    <pre>{{ $notificationContent }}</pre><br>
    <p>Bilginize.</p>
    <br><br>
    <p>Bu email <a href="https://liman.dev">Liman MYS</a> dış bildirim sisteminde <b>{{ explode("->", $notificationTitle)[1] ? explode("->", $notificationTitle)[1] : 'Liman' }}</b> tarafından oluşturulmuştur.</p>
</div>

<style>
  .main { background-color: white; }
  a:hover { border-left-width: 1em; min-height: 2em; }
</style>