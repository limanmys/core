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
                <canvas id="cpuChart"></canvas>
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
                <canvas id="ramChart"></canvas>
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
                <canvas id="diskChart"></canvas>
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
                <canvas id="networkChart"></canvas>
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
    @if($widgets->count())
    <div class="row sortable-widget mt-4">
      
        @foreach($widgets as $widget)
          @if($widget->type==="count_box" || $widget->type==="")
            <div class="col-md-3 col-sm-4 col-xs-12" id="{{$widget->id}}" data-server-id="{{$widget->server_id}}">
                <div class="info-box" title="{{$widget->server_name . " " . __("Sunucusu")}} -> {{$widget->title}}">
                  <span class="info-box-icon bg-info"><i class="fas fa-{{$widget->text}}"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">{{__($widget->title)}}</span>
                    <span class="info-box-number limanWidget" id="{{$widget->id}}" title="{{__($widget->title)}}" data-server-id="{{$widget->server_id}}">{{__('Yükleniyor..')}}</span>
                    <span class="progress-description" title="{{$widget->server_name . " " . __("Sunucusu")}}">{{$widget->server_name . " " . __("Sunucusu")}}</span>
                  </div>
                  <div class="overlay" style="background: rgba(255,255,255,.9);">
                      <div class="spinner-border" role="status">
                        <span class="sr-only">{{__("Yükleniyor")}}</span>
                      </div>
                  </div>
                </div>
            </div>
          @elseif ($widget->type==="chart")
            <div class="col-md-6 limanCharts mb-3" id="{{$widget->id}}" data-server-id="{{$widget->server_id}}">
                <div class="card h-100" id="{{$widget->id}}Chart">
                  <div class="card-header ui-sortable-handle" style="cursor: move;">
                    <h3 class="card-title">
                      <i class="fas fa-chart-pie mr-1"></i>
                      {{$widget->server_name . " " . __("Sunucusu")}} {{__($widget->title)}}
                    </h3>
                  </div>
                  <div class="card-body">
                    <canvas class="chartjs-render-monitor"></canvas>
                  </div>
                  <div class="overlay" style="background: rgba(255,255,255,.9);">
                      <div class="spinner-border" role="status">
                        <span class="sr-only">{{__("Yükleniyor")}}</span>
                      </div>
                  </div>
                </div>
            </div>
          @endif
        @endforeach
    </div>
    <div class="row my-2"></div>
    @endif
    <style>
    .sortable-widget{
      cursor: default;
    }
    </style>
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
                                    <span class="ml-1 badge"></span>
                                </div>
                                </li>`
                            );
                            $(el).find("a").attr("href", `/sunucular/${item.id}`).find("i").addClass(item.icon);
                            $(el).find("span").text(item.name);
                            $(el).find("div>span:first-child").text(item.uptime || "");
                            $(el).find("div>span:last-child").addClass(item.badge_class).text(item.status ? "Online" : "Offline");

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

        var limanEnableWidgets = true;
        $(".sortable-widget").sortable({
            stop: function(event, ui) {
                var data = [];
                $(".sortable-widget > div").each(function(i, el) {
                    $(el).attr('data-order', $(el).index());
                    data.push({
                        id: $(el).attr('id'),
                        order: $(el).index()
                    });
                });
                var form = new FormData();
                form.append('widgets', JSON.stringify(data));
                request('{{ route("update_orders") }}', form, function(response) {});
            }
        });
        $(".sortable-widget").disableSelection();
        var intervals = [];
        var widgets = [];
        var currentWidget = 0;

        $(".limanWidget").each(function() {
            var element = $(this);
            widgets.push({
                'element': element,
                'type': 'countBox'
            });
        });
        $('.limanCharts').each(function() {
            var element = $(this);
            widgets.push({
                'element': element,
                'type': 'chart'
            });
        });
        startQueue()
        setInterval(function() {
            startQueue()
        }, {{ config("liman.widget_refresh_time") }});

        function startQueue() {
            if (!limanEnableWidgets) {
                return;
            }
            currentWidget = 0;
            if (currentWidget >= widgets.length || widgets.length === 0) {
                return;
            }
            if (widgets[currentWidget].type === 'countBox') {
                retrieveWidgets(widgets[currentWidget].element, nextWidget)
            } else if (widgets[currentWidget].type === 'chart') {
                retrieveCharts(widgets[currentWidget].element, nextWidget)
            }
        }
        @if (user()->isAdmin())
        var stats;
        const CHART_INTERVAL = 2500;
        const CHART_DELAY = 4500;
        const CHART_SPEED = 12;

        function retrieveStats() {
            if (!limanEnableWidgets) {
                return;
            }

            request('{{ route("liman_stats") }}', new FormData(),
                function(response) {
                    stats = JSON.parse(response);

                    if (!window[`networkChart-element`]) {
                        resourceChart('{{ __("Cpu Kullanımı") }}', "cpuChart", 'cpu', true, '', '6, 212, 139');
                        resourceChart('{{ __("Ram Kullanımı") }}', "ramChart", 'ram', true, '', '6, 182, 212');
                        resourceChart('{{ __("IO Kullanımı") }}', "diskChart", 'io', true, '', '6, 79, 212');
                        networkChart('{{ __("Network") }}', "networkChart");
                    }

                    $(".chartbox").find(".overlay").fadeOut(500);
                    setTimeout(() => {
                        retrieveStats();
                    }, CHART_INTERVAL);
                }
            );
        }
        retrieveStats();
        @endif

        function nextWidget() {
            currentWidget++;
            if (currentWidget >= widgets.length || widgets.length === 0) {
                return;
            }
            if (widgets[currentWidget].type === 'countBox') {
                retrieveWidgets(widgets[currentWidget].element, nextWidget)
            } else if (widgets[currentWidget].type === 'chart') {
                retrieveCharts(widgets[currentWidget].element, nextWidget)
            }
        }

        function retrieveWidgets(element, next) {
            var info_box = element.closest('.info-box');
            var form = new FormData();
            form.append('widget_id', element.attr('id'));
            form.append('token', "{{ $token }}");
            form.append('server_id', element.attr('data-server-id'));
            request(API('widget_one'), form, function(response) {
                try {
                    var json = JSON.parse(response);
                    element.html(json["message"]);
                    info_box.find('.info-box-icon').show();
                    info_box.find('.info-box-content').show();
                    info_box.find('.overlay').remove();
                } catch (e) {
                    info_box.find('.overlay i').remove();
                    info_box.find('.overlay .spinner-border').remove();
                    info_box.find('.overlay span').remove();
                    info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="' + strip(
                            "Bir Hata Oluştu!") +
                        '" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">' +
                        "Bir Hata Oluştu!" + '</span>');
                }
                if (next) {
                    next();
                }
            }, function(error) {
                var json = {};
                try {
                    json = JSON.parse(error);
                } catch (e) {
                    json = e;
                }
                info_box.find('.overlay .spinner-border').remove();
                info_box.find('.overlay i').remove();
                info_box.find('.overlay span').remove();
                info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="' + strip(
                        "Bir Hata Oluştu!") +
                    '" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">' +
                    "Bir Hata Oluştu!" + '</span>');
                if (next) {
                    next();
                }
            });
        }

        function retrieveCharts(element, next) {
            var id = element.attr('id');
            var form = new FormData();
            form.append('widget_id', id);
            form.append('server_id', element.attr('data-server-id'));
            form.append('token', "{{ $token }}");
            request(API('widget_one'), form, function(res) {
                try {
                    var response = JSON.parse(res);
                    var data = response.message;
                    createChart(id + 'Chart', data.labels, data.data);
                } catch (e) {
                    element.find('.overlay .spinner-border').remove();
                    element.find('.overlay i').remove();
                    element.find('.overlay span').remove();
                    element.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="' + strip(
                            "Bir Hata Oluştu!") +
                        '" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">' +
                        "Bir Hata Oluştu!" + '</span>');
                }
                if (next) {
                    next();
                }
            }, function(error) {
                var json = {};
                try {
                    json = JSON.parse(error);
                } catch (e) {
                    json = e;
                }
                element.find('.overlay .spinner-border').remove();
                element.find('.overlay i').remove();
                element.find('.overlay span').remove();
                element.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="' + strip(
                        "Bir Hata Oluştu!") +
                    '" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">' +
                    "Bir Hata Oluştu!" + '</span>');
                if (next) {
                    next();
                }
            });
        }

        function strip(html) {
            var tmp = document.createElement("DIV");
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || "";
        }

        function API(target) {
            return "{{ route('home') }}/engine/" + target;
        }

        function resourceChart(title, chart, varname, prefix = true, postfix = "", color = "6, 182, 212") {
            let time = new Date();

            if (!window[`${chart}-element`]) {
                window[`${chart}-element`] = new Chart($(`#${chart}`), {
                    type: 'line',
                    data: {
                        datasets: [{
                            cubicInterpolationMode: 'monotone',
                            data: [{
                                    x: time - CHART_INTERVAL * 2,
                                    y: 0
                                },
                                {
                                    x: time,
                                    y: stats[varname]
                                }
                            ],
                            steppedLine: false,
                            borderColor: `rgb(${color})`,
                            backgroundColor: `rgba(${color}, .2)`,
                            fill: true,
                            pointRadius: 0
                        }, ],
                    },
                    options: {
                        plugins: {
                            responsive: true,
                            legend: false,
                            tooltips: {
                                mode: 'index',
                                intersect: false,
                            },
                            title: {
                                display: true,
                                text: `${title} ` + (prefix ? `%${stats[varname]} ${postfix}` :
                                    `${stats[varname]} ${postfix}`),
                            },
                            hover: {
                                mode: 'nearest',
                                intersect: true
                            },
                        },

                        scales: {
                            x: {
                                display: false,
                                type: "realtime",
                                realtime: {
                                    duration: CHART_INTERVAL * CHART_SPEED,
                                    refresh: CHART_INTERVAL,
                                    delay: CHART_DELAY,
                                    onRefresh: chart => {
                                        let time = new Date();

                                        let data0 = chart.data.datasets[0].data;

                                        if (data0[data0.length - 1].x.getTime() > time.getTime())
                                            return;

                                        data0.push({
                                            x: time,
                                            y: stats[varname]
                                        });


                                        if (data0.length > 100) {
                                            data0 = data0.slice(1000 - 15, 15);
                                        }

                                        chart.options.plugins.title.text = `${title} ` + (prefix ?
                                            `%${stats[varname]} ${postfix}` : `${stats[varname]} ${postfix}`
                                        );
                                    }

                                },
                            },
                            y: {
                                suggestedMax: 100,
                                suggestedMin: 0,
                            }
                        },
                    },
                    interaction: {
                        intersect: false
                    }
                });
            }
        }

        function networkChart(title, chart) {
            let time = new Date();

            if (!window[`${chart}-element`]) {
                window[`${chart}-element`] = new Chart($(`#${chart}`), {
                    type: 'line',
                    data: {
                        datasets: [{
                            cubicInterpolationMode: 'monotone',
                            label: '{{ __("Download") }}',
                            data: [{
                                    x: time - CHART_INTERVAL * 2,
                                    y: 0
                                },
                                {
                                    x: time,
                                    y: stats.network.down
                                }
                            ],
                            steppedLine: false,
                            borderColor: 'rgb(6, 182, 212)',
                            backgroundColor: 'rgba(6, 182, 212, .2)',
                            fill: true,
                            pointRadius: 0
                        }, {
                            cubicInterpolationMode: 'monotone',
                            label: '{{ __("Upload") }}',
                            data: [{
                                x: time - CHART_INTERVAL * 3,
                                y: 0
                            }, {
                                x: time,
                                y: stats.network.up
                            }],
                            steppedLine: false,
                            borderColor: 'rgb(6, 212, 139)',
                            backgroundColor: 'rgba(6, 212, 139, .2)',
                            fill: true,
                            pointRadius: 0
                        }],
                    },
                    options: {
                        plugins: {
                            responsive: true,
                            legend: false,
                            tooltips: {
                                mode: 'index',
                                intersect: false,
                            },
                            title: {
                                display: true,
                                text: `${title} Down: ${stats.network.down} kb/s Up: ${stats.network.up} kb/s`,
                            },
                        },

                        scales: {
                            x: {
                                display: false,
                                type: "realtime",
                                realtime: {
                                    duration: CHART_INTERVAL * CHART_SPEED,
                                    refresh: CHART_INTERVAL,
                                    delay: CHART_DELAY + 2500,
                                    onRefresh: chart => {
                                        let time = new Date();

                                        let data0 = chart.data.datasets[0].data;
                                        let data1 = chart.data.datasets[1].data;

                                        if (data0[data0.length - 1].x.getTime() > time.getTime())
                                            return;

                                        data0.push({
                                            x: time,
                                            y: stats.network.down
                                        });
                                        data1.push({
                                            x: time,
                                            y: stats.network.up
                                        });

                                        if (data0.length > 100) {
                                            data0 = data0.slice(
                                                1000 - 15, 15);
                                            data1 = data1.slice(
                                                1000 - 15, 15);
                                        }

                                        chart.options.plugins.title.text =
                                            `${title} Down: ${stats.network.down} kb/s Up: ${stats.network.up} kb/s`;
                                    }
                                }

                            },
                            y: {
                                ticks: {
                                    beginAtZero: true
                                }
                            }
                        },
                    },
                    interaction: {
                        intersect: false
                    }
                });
            }
        }


        function createChart(element, time, data) {
            $("#" + element + "Text").text("%" + data[0]);
            window[element + "Chart"] = new Chart($("#" + element), {
                type: 'line',
                data: {
                    datasets: [{
                        data: data,
                    }],
                    labels: [
                        time,
                    ]
                },
                options: {
                    animation: false,
                    responsive: true,
                    legend: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                min: 0,
                                max: 100
                            }
                        }]
                    },
                }
            })
        }
    </script>
@stop
