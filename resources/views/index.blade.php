@extends('layouts.app')

@section('content')
    @include('errors')
    <div class="row">
      <div class="col-md-12">
      <div class="card homepage-widget">
        <div class="card-body p-0">
          <div class="row row-cols-xxl-5 row-cols-md-3 row-cols-1 g-0">
                <div class="col">
                    <div class="mt-3 mt-md-0 py-4 px-3">
                        <h5 class="text-muted text-uppercase fs-13">{{ __('Sunucu Sayısı')}} <i class="fa-solid fa-server text-success fs-18 float-end align-middle"></i></h5>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-exchange-dollar-line display-6 text-muted"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="mb-2"><span class="counter-value">{{ $server_count }}</span></h2>
                                <a href="{{ route('servers') }}" class="text-muted text-uppercase" style="font-size: 11px">{{ __("Tüm Sunucuları Görüntüle") }} <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div><!-- end col -->
                <div class="col">
                    <div class="mt-3 mt-md-0 py-4 px-3">
                        <h5 class="text-muted text-uppercase fs-13">{{ __('Eklenti Sayısı')}} <i class="fa-solid fa-plug text-primary fs-18 float-end align-middle"></i></h5>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-exchange-dollar-line display-6 text-muted"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="mb-2"><span class="counter-value">{{ $extension_count }}</span></h2>
                                <a href="{{ route('settings') }}#extensions" class="text-muted text-uppercase" style="font-size: 11px">{{ __("Tüm Eklentileri Görüntüle") }} <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div><!-- end col -->
                <div class="col">
                    <div class="mt-3 mt-md-0 py-4 px-3">
                        <h5 class="text-muted text-uppercase fs-13">{{ __('Kullanıcı Sayısı')}} <i class="fa-solid fa-users text-navy fs-18 float-end align-middle"></i></h5>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-exchange-dollar-line display-6 text-muted"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="mb-2"><span class="counter-value">{{ $user_count }}</span></h2>
                                <a href="{{ route('settings') }}" class="text-muted text-uppercase" style="font-size: 11px">{{ __("Tüm Kullanıcıları Görüntüle") }} <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div><!-- end col -->
                <div class="col">
                    <div class="mt-3 mt-md-0 py-4 px-3">
                        <h5 class="text-muted text-uppercase fs-13">{{ __('Liman Versiyonu')}} <i class="fa-solid fa-cogs text-indigo fs-18 float-end align-middle"></i></h5>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-exchange-dollar-line display-6 text-muted"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="mb-0"><span class="counter-value">{{ $version }}</span></h2>
                            </div>
                        </div>
                    </div>
                </div><!-- end col -->
            </div><!-- end row -->
          </div><!-- end card body -->
        </div>
      </div>

      @if(user()->isAdmin())
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox" style="padding: 0; padding-top: 12px;">
              <div class="overlay" style="background: white">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="cpuChart" style="min-height: 215px"></div>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox" style="padding: 0; padding-top: 12px;">
              <div class="overlay" style="background: white">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="ramChart" style="min-height: 215px"></div>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox" style="padding: 0; padding-top: 12px;">
              <div class="overlay" style="background: white">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="diskChart" style="min-height: 215px"></div>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox" style="padding: 0; padding-top: 12px;">
              <div class="overlay" style="background: white">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="networkChart" style="min-height: 215px"></div>
              </div>
            </div>
        </div>
        <div class="row row-eq-height" style="width: 100%; margin-left: 0; margin-bottom: 30px;">
          <div class="col-md-12 col-sm-12">
            <div class="card shadow-sm loading online-servers" style="height: 100%; min-height: 200px;">
                <div class="card-header">
                  <h3 class="card-title" style="font-size: 15px">{{ __("Sunucu Durumları") }}</h3>
                </div>
                <div class="overlay">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                    </div>
                </div>
                <div class="card-body" style="padding: 4px;">
                  <ul class="list-group list-group-flush srvlist">
                    
                  </ul>
                  
                </div>
                <div class="noServer" style="height: 100%; display:flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fas fa-info fa-3x mb-4"></i>
                    <h5 class="text-bold">{{ __("Henüz sunucu eklememişsiniz.") }}</h5>
                </div>
            </div>
          </div>
          <div class="col-md-6 col-sm-12">
            
          </div>
        </div>
      @endif
    </div>
   <script>
        @if(user()->isAdmin())
        
        function getOnlineServers() {
            $(".online-servers").find(".noServer").css("display", "none");
            let responsePromise = () => {
                return new Promise((resolve) => {
                    request('{{ route("online_servers") }}', new FormData(), function(response) {
                        let json = JSON.parse(response);
                        json.forEach(function(item, iter) {
                            var el = $(`
                                <li class="list-group-item">
                                <a style="color:#222">
                                    <i class="fab mr-1"></i>
                                    <span style="font-weight: 600"></span>
                                </a>
                                <div class="float-right">
                                    <span class="text-xs"></span>  
                                    <i class="fa-solid fa-circle ml-1" style="font-size: 12px"></i>
                                </div>
                                </li>`
                            );
                            $(el).find("a").attr("href", `/sunucular/${item.id}`).find("i").addClass(item.icon);
                            $(el).find("span").text(item.name);
                            $(el).find("div>span:first-child").text(item.uptime || "");
                            $(el).find(".fa-circle").css(item.status ? {"color": "green"} : {"color": "#ff4444"});

                            $(".srvlist").append(el);
                        });
                        $(".online-servers").find(".overlay").fadeOut(500);
                        resolve(json.length > 0 ? true : false);
                    });
                })
            };
            responsePromise().then((res) => {
                if (!res) {
                    $(".online-servers").find(".noServer").css("display", "flex");
                }
            });
        }
        getOnlineServers(); 
        @endif


        @if (user()->isAdmin())
        function retrieveStats() 
        {
            request('{{ route("liman_stats") }}', new FormData(),
                function(response) {
                    stats = JSON.parse(response);
                    if (!IS_RENDERED) {
                        renderChart(CHARTS.CPU)
                        renderChart(CHARTS.RAM)
                        renderChart(CHARTS.IO)
                        renderChart(CHARTS.NETWORK, true)
                        IS_RENDERED = true;
                        $(".chartbox").find(".overlay").fadeOut(750);
                    }

                    updateChart(CHARTS.CPU)
                    updateChart(CHARTS.RAM)
                    updateChart(CHARTS.IO)
                    updateNetworkChart(CHARTS.NETWORK)
                            
                    setTimeout(() => {
                        retrieveStats();
                    }, CHART_INTERVAL);
                }
            );
        }
        retrieveStats();

        setInterval(function() {
            CHARTS.CPU.data = CHARTS.CPU.data.slice(-20);
            CHARTS.RAM.data = CHARTS.RAM.data.slice(-20);
            CHARTS.IO.data = CHARTS.IO.data.slice(-20);
            CHARTS.NETWORK.data.upload = CHARTS.NETWORK.data.upload.slice(-20);
            CHARTS.NETWORK.data.download = CHARTS.NETWORK.data.download.slice(-20);
        }, 60000)
        @endif
    </script>
@stop
