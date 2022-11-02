@extends('layouts.app')

@section('content')
    @include('errors')
    <div class="callout callout-info shadow-sm">
        <h5>{{__("Liman MYS'ye Hoşgeldiniz")}}</h5>
        {{__("Kullanım rehberine ulaşmak için")}} <a href="https://docs.liman.dev/" target="_blank">https://docs.liman.dev/</a> {{__("adresini ziyaret edebilirsiniz.")}}
    </div>
    <div class="row" style="padding-top: 15px;">
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="small-box shadow-sm bg-info">
            <div class="inner">
              <h3>{{$server_count}}</h3>
              <p>{{__("Limandaki Sunucu Sayısı")}}</p>
            </div>
            <div class="icon">
              <i class="fas fa-server"></i>
            </div>
            <a href="/sunucular" class="small-box-footer">
              {{ __("Sunucuları listele") }} <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
      </div>
      
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="small-box shadow-sm bg-info">
            <div class="inner">
              <h3>{{$extension_count}}</h3>
              <p>{{__("Limandaki Eklenti Sayısı")}}</p>
            </div>
            <div class="icon">
              <i class="fas fa-plug"></i>
            </div>
            <a href="/ayarlar#extensions" class="small-box-footer">
              {{ __("Eklentileri yönet") }} <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="small-box shadow-sm bg-info">
            <div class="inner">
              <h3>{{$user_count}}</h3>
              <p>{{__("Limandaki Kullanıcı Sayısı")}}</p>
            </div>
            <div class="icon">
              <i class="fas fa-users"></i>
            </div>
            <a href="/ayarlar#users" class="small-box-footer">
              {{ __("Kullanıcıları yönet") }} <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="small-box shadow-sm bg-info">
            <div class="inner">
              <h3>{{$version}}</h3>
              <p>{{__("Liman Versiyonu")}}</p>
            </div>
            <div class="icon">
              <i class="fas fa-cogs"></i>
            </div>
            <div class="small-box-footer" style="background-color: #17a2b8; border-radius: 5px">
              &nbsp;
            </div>
          </div>
      </div>
      @if(user()->isAdmin())
        <script src="{{ asset('js/chart3.min.js') }}"></script>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="cpuChart"></div>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="ramChart"></div>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="diskChart"></div>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <div class="spinner-border" role="status">
                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                </div>
              </div>
              <div class="info-box-content">
                <div id="networkChart"></div>
              </div>
            </div>
        </div>
        <div class="row row-eq-height" style="width: 100%; margin-left: 0; margin-bottom: 30px;">
          <div class="col-md-6 col-sm-12">
            <div class="card shadow-sm loading online-servers" style="height: 100%; min-height: 200px;">
                <div class="card-header">
                  <h3 class="card-title">{{ __("Sunucu Durumları") }}</h3>
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
            <div class="card shadow-sm loading market-widget" style="height: 100%; min-height: 200px;">
                <div class="card-header p-0">
                  <h3 class="card-title" style="padding: 12px; padding-left: 1.25rem">{{ __("Önerilen Eklentiler") }}</h3>
                  <div class="float-right">
                    <button style="margin: 5px" class="btn btn-sm btn-success" onclick="window.location.href='/market'"><i class="fas fa-shopping-cart mr-1"></i>{{ __("Eklenti Mağazası") }}</button>
                  </div>
                </div>
                <div class="overlay">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                    </div>
                </div>
                <div class="card-body" style="padding: 4px;">
                  <div class="row row-eq-height market-col-1">
                  </div>
                  <div class="row row-eq-height market-col-2" style="margin-bottom: -15px;">
                  </div>
                </div>
                <div class="noApp" style="height: 100%; display:flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fas fa-info fa-3x mb-4"></i>
                    <h5 class="text-bold">{{ __("Market bağlantınızı kontrol edin.") }}</h5>
                </div>
            </div>
          </div>
        </div>
      @endif
    </div>
   <script>
        @if(user()->isAdmin())
        function appendApp(item) {
          const el = $(`
          <div class="col-md-6 col-sm-12">
            <div class="row p-2">
              <div class="col-lg-4 col-5">
                  <a href="{{ route('market') }}"><img class="img-fluid mb-3"></a>
              </div>
              <div class="col-lg-8 col-7">
                  <a href="{{ route('market') }}" class="text-dark"><h4 style="font-weight: 600;"></h4></a>
                  <p class="mb-0"></p>
              </div>
            </div>
          </div>`);
          $(el).find("img").attr("src", `https://market.liman.dev/${item.iconPath}`).attr("alt", item.name);
          $(el).find("h4").text(item.name);
          $(el).find("p").text(item.shortDescription);
          return el;
        }

        function getHomepageApps() {
            $(".market-widget").find(".noApp").css("display", "none");
            request('{{ route("market_widget") }}', new FormData(), function(response) {
                var json = JSON.parse(response);
                let a = 0;
                json.forEach(function(item) {
                    if (a++ < 2) {
                        $(".market-col-1").append(appendApp(item));
                    } else {
                        $(".market-col-2").append(appendApp(item));
                    }
                    $(".market-widget").find(".noApp").css("display", "none");
                });
                if (json.length < 1) {
                    $(".market-widget").find(".noApp").css("display", "flex");
                }
                $(".market-widget").find(".overlay").fadeOut(500);
            });
        }
        getHomepageApps();

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
                                    <span class="text-bold"></span>
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

        var intervals = [];
        var widgets = [];
        var currentWidget = 0;

        $('.limanCharts').each(function() {
            var element = $(this);
            widgets.push({
                'element': element,
                'type': 'chart'
            });
        });
        @if (user()->isAdmin())
        var stats;
        const CHART_INTERVAL = 2500;
        const CHART_DELAY = 4500;
        const CHART_SPEED = 12;
        var CHARTS = {
            CPU : {
                title: '{{ __("Cpu Kullanımı") }}',
                id: "cpuChart",
                key: "cpu",
                chart : null,
                data: [ [Date.now(), 0]],
                colors: ["#06d48b"]
            },
            RAM : {
                title: '{{ __("Ram Kullanımı") }}',
                id: "ramChart",
                key: "ram",
                chart : null,
                data: [ [Date.now(), 0]],
                colors: ["#06b6d4"]

            },
            IO : {
                title: '{{ __("IO Kullanımı") }}',
                id: "diskChart",
                key: "io",
                chart : null,
                data: [ [Date.now(), 0]],
                colors: ["#064fd4"]
            },
            NETWORK : {
                title: '{{ __("Network") }}',
                id: "networkChart",
                key: "network",
                chart : null,
                data: {
                    up: [ [Date.now(), 0]],
                    down: [ [Date.now(), 0]]
                },
                colors: ["#008ffb", "#00e396"]
            }
        }

        function retrieveStats() {
            request('{{ route("liman_stats") }}', new FormData(),
                function(response) {
                    stats = JSON.parse(response);
                    if (!window[`networkChart-element`]) {
                        renderChart(CHARTS.CPU)
                        renderChart(CHARTS.RAM)
                        renderChart(CHARTS.IO)
                        renderChart(CHARTS.NETWORK, true)
                    }
                    updateChart(CHARTS.CPU)
                    updateChart(CHARTS.RAM)
                    updateChart(CHARTS.IO)
                    updateNetworkChart(CHARTS.NETWORK)
                    $(".chartbox").find(".overlay").fadeOut(500);
                    setTimeout(() => {
                        retrieveStats();
                    }, CHART_INTERVAL);
                }
            );
        }
        retrieveStats();
        @endif

        function renderChart(obj, network=false) {
            var options = {
                series: [
                    (!network) ?
                    (
                        {
                            data: obj.data,
                            name: obj.title
                        }
                    )
                    :
                    (
                        {
                            data: obj.data.up,
                            name: "Up"
                        },
                        {
                            data: obj.data.down,
                            name: "Down"
                        }
                    )
                ],
                chart: {
                    id: 'realtime',
                    height: 200,
                    type: 'area',
                    fontFamily: "Inter",
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: {
                            speed: 500
                        }
                    },
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                fill: {
                    colors: obj.colors
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth'
                },
                title: {
                    text: `${obj.title} %${stats[obj.key]}`,
                    align: 'left'
                },
                markers: {
                    size: 0
                },
                xaxis: {
                    type: 'datetime',
                    range: 60000,
                    labels: {
                        show: false
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return `%${val}`
                        }
                    },
                    x: {
                        show: false
                    }
                },
                yaxis: {
                    max: 100,
                    min: 0,
                    tickAmount: 5,
                    labels: {
                        formatter: (value) => { return value }
                    }
                }
            };
            var chart = new ApexCharts(document.querySelector(`#${obj.id}`), options);
            chart.render();
            obj.chart = chart;
        }

        function updateChart(obj) {
            
            obj.data.push([Date.now(), stats[obj.key]])
            if(obj.data.length > 20) {
                obj.data.shift()
            }
            obj.chart.updateOptions({
                title: {
                    text: `${obj.title} %${stats[obj.key]}`
                },
                series: [
                    {
                        data: obj.data
                    }
                ]
            })
        }

        function updateNetworkChart(obj) {
            obj.data.up.push([Date.now(), stats[obj.key].up])
            obj.data.down.push([Date.now(), stats[obj.key].down])
            if(obj.data.up.length > 20) {
                obj.data.up.shift()
                obj.data.down.shift()
            }
            obj.chart.updateOptions({
                title: {
                    text: `${obj.title} Up: ${stats[obj.key].up} kb/s Down: ${stats[obj.key].down} kb/s`
                },
                yaxis: {
                    max:100,
                    min: 0,
                    tickAmount: 5,
                    labels: {
                        formatter: (value) => { return value }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return `${val} kb/s`
                        }
                    }
                },
                legend: {
                    show: false
                },
                series:[
                    {
                        data: obj.data.up,
                        name: "Up"
                    },
                    {
                        data: obj.data.down,
                        name: "Down"
                    }
                ]
                
            })
        }
    </script>
@stop
