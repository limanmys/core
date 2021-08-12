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
            <div class="small-box-footer">
              &nbsp;
            </div>
          </div>
      </div>
      @if(user()->isAdmin())
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
              </div>
              <div class="info-box-content">
                <canvas id="cpuChart"></canvas>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
              </div>
              <div class="info-box-content">
                <canvas id="ramChart"></canvas>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
              </div>
              <div class="info-box-content">
                <canvas id="diskChart"></canvas>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box shadow-sm loading chartbox">
              <div class="overlay">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
              </div>
              <div class="info-box-content">
                <canvas id="networkChart"></canvas>
              </div>
            </div>
        </div>
        <div class="row row-eq-height" style="width: 100%; margin-left: 0;">
          <div class="col-md-6 col-sm-12">
            <div class="card shadow-sm loading online-servers" style="height: 100%; min-height: 220px;">
                <div class="card-header">
                  <h3 class="card-title">{{ __("Sunucu Durumları") }}</h3>
                </div>
                <div class="overlay">
                  <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                </div>
                <div class="card-body" style="padding: 4px;">
                  <ul class="list-group list-group-flush srvlist">
                    
                  </ul>
                  
                </div>
                <div class="noServer" style="height: 100%; display:flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fas fa-info fa-3x mb-4"></i>
                    <h5 class="text-bold">Henüz sunucu eklememişsiniz.</h5>
                </div>
            </div>
          </div>
          <div class="col-md-6 col-sm-12">
            <div class="card shadow-sm loading market-widget" style="height: 100%; min-height: 220px;">
                <div class="card-header p-0">
                  <h3 class="card-title" style="padding: 12px; padding-left: 1.25rem">{{ __("Önerilen Eklentiler") }}</h3>
                  <div class="float-right">
                    <button style="margin: 5px" class="btn btn-sm btn-success" onclick="window.location.href='/market'"><i class="fas fa-shopping-cart mr-1"></i>{{ __("Eklenti Mağazası") }}</button>
                  </div>
                </div>
                <div class="overlay">
                  <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                </div>
                <div class="card-body" style="padding: 4px;">
                  <div class="row row-eq-height market-col-1">
                  </div>
                  <div class="row row-eq-height market-col-2">
                  </div>
                </div>
                <div class="noApp" style="height: 100%; display:flex; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fas fa-info fa-3x mb-4"></i>
                    <h5 class="text-bold">Market bağlantınızı kontrol edin.</h5>
                </div>
            </div>
          </div>
        </div>
      @endif
    </div>
    <div class="row sortable-widget mt-4">
      @if($widgets->count())
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
      @endif
    </div>
    <div class="row my-2"></div>
    <style>
    .sortable-widget{
      cursor: default;
    }
    </style>
    <script>
        @if(user()->isAdmin())
        function appendApp(item) {
          return (`<div class="col-md-6 col-sm-12">
                      <div class="row p-2">
                        <div class="col-lg-4 col-5">
                            <a href="{{ route('market') }}"><img src="https://market.liman.dev/${item.iconPath}" alt="${item.name}" class="img-fluid mb-3"></a>
                        </div>
                        <div class="col-lg-8 col-7">
                            <a href="{{ route('market') }}" class="text-dark"><h4 style="font-weight: 600;">${item.name}</h4></a>
                            <p class="mb-0">${item.shortDescription}</p>
                        </div>
                      </div>
                    </div>`);
        }

        function getHomepageApps() {
          $(".market-widget").find(".noApp").css("display", "none");
          request('{{route('market_widget')}}', new FormData(), function(response){
              var json = JSON.parse(response);
              let a = 0;
              json.forEach(function (item) {
                if (a++ < 2) {
                  $(".market-col-1").append(appendApp(item));
                } else {
                  $(".market-col-2").append(appendApp(item));
                }
              });
              if (json.length < 1) {
                $(".market-widget").find(".noApp").css("display", "flex");
              }
              $(".market-widget").find(".overlay").hide();
          });
        }
        getHomepageApps();

        function getOnlineServers() {
          $(".online-servers").find(".noServer").css("display", "none");
          request('{{route('online_servers')}}', new FormData(), function(response){
              var json = JSON.parse(response);
              json.forEach(function (item) {
                $(".srvlist").append(`
                  <li class="list-group-item">
                    <a href="/sunucular/${item.id}" style="color:#222">
                      <i class="fab ${item.icon} mr-1"></i> <span class="text-bold">${item.name}</span>
                    </a>
                    <div class="float-right">
                      <span class="text-xs">${item.uptime ? item.uptime : ""}</span>  
                      <span class="ml-1 badge ${item.badge_class}">${item.status ? "Online" : "Offline"}</span>
                    </div>
                  </li>
                `);
              });
              if (json.length < 1) {
                $(".online-servers").find(".noServer").css("display", "flex");
              }
              $(".online-servers").find(".overlay").hide();
          });
        }
        getOnlineServers();
        @endif

        var limanEnableWidgets = true;
        $(".sortable-widget").sortable({
            stop: function(event, ui) {
                var data = [];
                $(".sortable-widget > div").each(function(i, el){
                    $(el).attr('data-order', $(el).index());
                    data.push({
                      id: $(el).attr('id'),
                      order:  $(el).index()
                    });
                });
                var form = new FormData();
                form.append('widgets', JSON.stringify(data));
                request('{{route('update_orders')}}', form, function(response){});
            }
        });
        $(".sortable-widget").disableSelection();
        var intervals = [];
        var widgets = [];
        var currentWidget = 0;

        $(".limanWidget").each(function(){
            var element = $(this);
            widgets.push({
              'element': element,
              'type': 'countBox'
            });
        });
        $('.limanCharts').each(function(){
            var element = $(this);
            widgets.push({
              'element': element,
              'type': 'chart'
            });
        });
        startQueue()
        setInterval(function(){
            startQueue()
        },{{config('liman.widget_refresh_time')}});

        function startQueue(){
          if(!limanEnableWidgets){
            return;
          }
          currentWidget = 0;
          if(currentWidget >= widgets.length || widgets.length === 0){
            return;
          }
          if(widgets[currentWidget].type === 'countBox'){
            retrieveWidgets(widgets[currentWidget].element, nextWidget)
          }else if(widgets[currentWidget].type === 'chart'){
            retrieveCharts(widgets[currentWidget].element, nextWidget)
          }
        }
        @if(user()->isAdmin())
        var stats;
        function retrieveStats(){
          if(!limanEnableWidgets){
            return;
          }
            request('{{route('liman_stats')}}', new FormData(), function(response){
              stats = JSON.parse(response);
              resourceChart('{{__("Cpu Kullanımı")}}', "cpuChart", stats.time, stats.cpu);
              resourceChart('{{__("Ram Kullanımı")}}', "ramChart", stats.time, stats.ram);
              resourceChart('{{__("IO Kullanımı")}}', "diskChart", stats.time, stats.io);
              networkChart('{{__("Network")}}', "networkChart", stats.time, stats.network);
              $(".chartbox").find(".overlay").hide();
              setTimeout(() => {
                retrieveStats();
              }, 2500);
            });
        }
        retrieveStats();
        @endif
        function nextWidget(){
          currentWidget++;
          if(currentWidget >= widgets.length || widgets.length === 0){
            return;
          }
          if(widgets[currentWidget].type === 'countBox'){
            retrieveWidgets(widgets[currentWidget].element, nextWidget)
          }else if(widgets[currentWidget].type === 'chart'){
            retrieveCharts(widgets[currentWidget].element, nextWidget)
          }
        }

        function retrieveWidgets(element, next){
            var info_box = element.closest('.info-box');
            var form = new FormData();
            form.append('widget_id',element.attr('id'));
            form.append('token',"{{$token}}");
            form.append('server_id',element.attr('data-server-id'));
            request(API('widget_one'), form, function(response){
                try {
                  var json = JSON.parse(response);
                  element.html(json["message"]);
                  info_box.find('.info-box-icon').show();
                  info_box.find('.info-box-content').show();
                  info_box.find('.overlay').remove();
                } catch(e) {
                  info_box.find('.overlay i').remove();
                  info_box.find('.overlay .spinner-border').remove();
                  info_box.find('.overlay span').remove();
                  info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip("Bir Hata Oluştu!")+'" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">'+"Bir Hata Oluştu!"+'</span>');
                }
                if(next){
                  next();
                }
            }, function(error) {
                var json = {};
                try{
                  json = JSON.parse(error);
                }catch(e){
                  json = e;
                }
                info_box.find('.overlay .spinner-border').remove();
                info_box.find('.overlay i').remove();
                info_box.find('.overlay span').remove();
                info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip("Bir Hata Oluştu!")+'" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">'+"Bir Hata Oluştu!"+'</span>');
                if(next){
                  next();
                }
              });
        }

        function retrieveCharts(element, next){
            var id = element.attr('id');
            var form = new FormData();
            form.append('widget_id', id);
            form.append('server_id',element.attr('data-server-id'));
            form.append('token',"{{$token}}");
            request(API('widget_one'), form, function(res){
                try {
                  var response =  JSON.parse(res);
                  var data =  response.message;
                  createChart(id+'Chart',data.labels, data.data);
                } catch(e) {
                  element.find('.overlay .spinner-border').remove();
                  element.find('.overlay i').remove();
                  element.find('.overlay span').remove();
                  element.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip("Bir Hata Oluştu!")+'" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">'+"Bir Hata Oluştu!"+'</span>');
                }
                if(next){
                  next();
                }
            }, function(error) {
                var json = {};
                try{
                  json = JSON.parse(error);
                }catch(e){
                  json = e;
                }
                element.find('.overlay .spinner-border').remove();
                element.find('.overlay i').remove();
                element.find('.overlay span').remove();
                element.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip("Bir Hata Oluştu!")+'" style="color: red; margin-left: 15px; margin-right: 10px;"></i><span style="word-break: break-word;">'+"Bir Hata Oluştu!"+'</span>');
                if(next){
                  next();
                }
              });
        }

        function strip(html)
        {
           var tmp = document.createElement("DIV");
           tmp.innerHTML = html;
           return tmp.textContent || tmp.innerText || "";
        }

        function API(target)
        {
            return "{{route('home')}}/extensionRun/" + target;
        }

        function resourceChart(title, chart, time, data, prefix=true, postfix="")
        {
          if(!window[`${chart}-element`]){
              window[`${chart}-element`] = new Chart($(`#${chart}`), {
                  type: 'line',
                  data: {
                      datasets: [{
                          data: [data, data],
                          steppedLine: false,
                          borderColor: 'rgb(255, 159, 64)',
                          backgroundColor: 'rgba(255, 159, 64, .5)',
                          fill: true,
                          pointRadius: 0
                      }],
                      labels: [time, time]
                  },
                  options: {
                      responsive: true,
                      legend: false,
                      tooltips: {
                          mode: 'index',
                          intersect: false,
                      },
                      hover: {
                          mode: 'nearest',
                          intersect: true
                      },
                      title: {
                        display: true,
                        text: `${title} ` + (prefix ? `%${data} ${postfix}` : `${data} ${postfix}`),
                      },
                      scales: {
                          xAxes: [{
                              display: false
                          }],
                          yAxes: [{
                              ticks: {
                                  beginAtZero: true,
                                  max: 100
                              }
                          }]
                      },
                      animation: {
                        onComplete: () => {
                          delayed = true;
                        },
                        delay: (context) => {
                          let delay = 0;
                          if (context.type === 'data' && context.mode === 'default' && !delayed) {
                            delay = context.dataIndex * 500 + context.datasetIndex * 200;
                          }
                          return delay;
                        },
                      },
                  }
              });
          }else{ 
              window[`${chart}-element`].options.title.text = `${title} ` + (prefix ? `%${data} ${postfix}` : `${data} ${postfix}`);
              window[`${chart}-element`].data.labels.push(time);
              window[`${chart}-element`].data.datasets.forEach((dataset) => {
                  dataset.data.push(data);
              });
              $('.charts-card').find('.overlay').hide();
              window[`${chart}-element`].update(); 
          }
      }

      function networkChart(title, chart, time, data)
      {
          if(!window[`${chart}-element`]){
              window[`${chart}-element`] = new Chart($(`#${chart}`), {
                  type: 'line',
                  data: {
                      datasets: [{
                          label: '{{__('Download')}}',
                          data: [data.down, data.down],
                          steppedLine: false,
                          borderColor: 'rgb(255, 159, 64)',
                          backgroundColor: 'rgba(255, 159, 64, .5)',
                          fill: true,
                          pointRadius: 0
                      },{
                          label: '{{__('Upload')}}',
                          data: [data.up, data.up],
                          steppedLine: false,
                          borderColor: 'rgb(54, 162, 235)',
                          backgroundColor: 'rgba(54, 162, 235, .5)',
                          fill: true,
                          pointRadius: 0
                      }],
                      labels: [time, time]
                  },
                  options: {
                      responsive: true,
                      legend: false,
                      tooltips: {
                          mode: 'index',
                          intersect: false,
                      },
                      hover: {
                          mode: 'nearest',
                          intersect: true
                      },
                      title: {
                        display: true,
                        text: `${title} Down: ${data.down} kb/s Up: ${data.up} kb/s`,
                      },
                      scales: {
                          xAxes: [{
                              display: false 
                          }],
                          yAxes: [{
                              ticks: {
                                  beginAtZero: true
                              }
                          }]
                      },
                      animation: {
                        onComplete: () => {
                          delayed = true;
                        },
                        delay: (context) => {
                          let delay = 0;
                          if (context.type === 'data' && context.mode === 'default' && !delayed) {
                            delay = context.dataIndex * 500 + context.datasetIndex * 200;
                          }
                          return delay;
                        },
                      },
                  }
              });
          }else{
              window[`${chart}-element`].options.title.text = `${title} Down: ${data.down} kb/s Up: ${data.up} kb/s`;
              window[`${chart}-element`].data.labels.push(time);
              window[`${chart}-element`].data.datasets[0].data.push(data.down);
              window[`${chart}-element`].data.datasets[1].data.push(data.up);
              window[`${chart}-element`].update();
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
