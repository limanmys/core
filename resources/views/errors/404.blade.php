@if(request()->wantsJson() || request()->ip() == "127.0.0.1")
@php(respond(__($exception->getMessage()),201))
@else
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
   <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>{{ __("Liman Merkezi Yönetim Sistemi") }}</title>
      <link rel="stylesheet" href="{{ mix('css/liman.css') }}">
      <style>
:root {
  --blue: #0e0620;
  --white: #fff;
  --green: #2ccf6d;
}
html,
body {
  height: 100%;
}
body {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--blue);
  font-size: 1em;
}
button {
  font-family: "Nunito Sans";
}
ul {
  list-style-type: none;
  -webkit-padding-start: 35px;
  padding-inline-start: 35px;
}
h1 {
  font-size: 7.5em;
  margin: 15px 0px;
  font-weight: bold;
}
h2 {
  font-weight: bold;
}
.btn {
  z-index: 1;
  overflow: hidden;
  background: transparent;
  position: relative;
  padding: 8px 50px;
  border-radius: 30px;
  cursor: pointer;
  font-size: 1em;
  letter-spacing: 2px;
  transition: 0.2s ease;
  font-weight: bold;
  margin: 5px 0px;
}
.btn.green {
  border: 4px solid var(--green);
  color: var(--blue);
}
.btn.green:before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 0%;
  height: 100%;
  background: var(--green);
  z-index: -1;
  transition: 0.2s ease;
}
.btn.green:hover {
  color: var(--white);
  background: var(--green);
  transition: 0.2s ease;
}
.btn.green:hover:before {
  width: 100%;
}
@media screen and (max-width: 768px) {
  body {
    display: block;
  }
  .container {
    margin-top: 70px;
    margin-bottom: 70px;
  }
}
      </style>
   </head>
   <body>
      <main style="width: 70vw">
         <div class="container">
            <div class="row">
               <div class="col-md-6 align-self-center">
                  <a href="https://liman.havelsan.com.tr">
                     <img style="filter: invert(1); width: 200px; float: right; margin-right: 50px" src="{{ asset('images/limanlogo.svg') }}" alt="Liman MYS">
                  </a>
               </div>
               <div class="col-md-6 align-self-center">
                  <h1>404</h1>
                  <h2>{{__("Sayfa bulunamadı.")}}</h2>
                  <p>{{ $exception->getMessage() ? $exception->getMessage() : __("Önceki sayfaya geri dönerek işleminize devam edebilirsiniz.") }}
                  </p>
                  <button class="btn green" onclick="history.back()"><i class="fas fa-chevron-left"></i> {{ __("Geri") }} </button>
               </div>
            </div>
         </div>
      </main>
   </body>
</html>
@endif