@extends('layouts.app')

@section('content')
    @include('errors')
    <br><div class="callout callout-info">
        <h5>{{__("Liman MYS'ye Hoşgeldiniz")}}</h5>
        {{__("Kullanım rehberine ulaşmak için")}} <a href="https://rehber.liman.dev/" target="_blank">https://rehber.liman.dev/</a> {{__("adresini ziyaret edebilirsiniz.")}}
    </div>
    <div class="row" style="padding-top: 15px;">
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Sunucu Sayısı")}}</span>
              <span class="info-box-number">{{$server_count}}</span>
            </div>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-plus"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Eklenti Sayısı")}}</span>
              <span class="info-box-number">{{$extension_count}}</span>
            </div>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Kullanıcı Sayısı")}}</span>
              <span class="info-box-number">{{$user_count}}</span>
            </div>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-cog"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Liman Versiyonu")}}</span>
              <span class="info-box-number">{{$version}}</span>
            </div>
          </div>
      </div>
      @if(user()->isAdmin())
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box loading">
              <span class="info-box-icon bg-info"><i class="fas fa-microchip"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">{{__("Cpu Kullanımı")}}</span>
                <span class="info-box-number" id="cpuUsage">
                  <div class="overlay" style="background: rgba(255,255,255,.9);">
                        <div class="spinner-border" role="status">
                          <span class="sr-only">{{__("Yükleniyor")}}</span>
                        </div>
                    </div></span>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box">
              <span class="info-box-icon bg-info"><i class="fas fa-memory"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">{{__("Ram Kullanımı")}}</span>
                <span class="info-box-number" id="ramUsage">
                  <div class="overlay" style="background: rgba(255,255,255,.9);">
                        <div class="spinner-border" role="status">
                          <span class="sr-only">{{__("Yükleniyor")}}</span>
                        </div>
                  </div>
                </span>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box">
              <span class="info-box-icon bg-info"><i class="far fa-hdd"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">{{__("Disk Kullanımı")}}</span>
                <span class="info-box-number" id="diskUsage">
                    <div class="overlay" style="background: rgba(255,255,255,.9);">
                        <div class="spinner-border" role="status">
                          <span class="sr-only">{{__("Yükleniyor")}}</span>
                        </div>
                    </div>
                </span>
              </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-xs-12">
            <div class="info-box">
              <span class="info-box-icon bg-info"><i class="fas fa-network-wired"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">{{__("Ağ Kullanımı")}}</span>
                <span class="info-box-number" id="networkUsage">
                <div class="overlay" style="background: rgba(255,255,255,.9);">
                        <div class="spinner-border" role="status">
                          <span class="sr-only">{{__("Yükleniyor")}}</span>
                        </div>
                    </div>
                </span>
              </div>
            </div>
        </div>
      @endif
    </div>
    <div class="row sortable-widget">
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
    <style>
    .sortable-widget{
      cursor: default;
    }
    </style>
    <script>
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
        function retrieveStats(){
          if(!limanEnableWidgets){
            return;
          }
            request('{{route('liman_stats')}}', new FormData(), function(response){
              var json = JSON.parse(response);
                $("#ramUsage").text(json.ram);
                $("#diskUsage").text(json.disk);
                $("#cpuUsage").text(json.cpu);
                $("#networkUsage").text(json.network);
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

        function createChart(element, labels, data) {
          $("#" + element + ' .overlay').remove();
          window[element + "Chart"] = new Chart($("#" + element+' .card-body canvas'), {
            type: 'bar',
            data: {
              datasets: [{
                data: data,
              }],
              labels: labels
            },
            options: {
              animation: false,
              responsive: true,
              legend: false,
              scales: {
                yAxes: [{
                  ticks: {
                    beginAtZero: true,
                  }
                }]
              },
            }
          })
        }
    </script>
@stop
