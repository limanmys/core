@extends('layouts.app')

@section('content')
    @include('l.errors')
    <div class="row" style="padding-top: 15px;">
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Sunucu Sayısı")}}</span>
              <span class="info-box-number">{{\App\Server::all()->count()}}</span>
            </div>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-plus"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Eklenti Sayısı")}}</span>
              <span class="info-box-number">{{\App\Extension::all()->count()}}</span>
            </div>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Kullanıcı Sayısı")}}</span>
              <span class="info-box-number">{{\App\User::all()->count()}}</span>
            </div>
          </div>
      </div>
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-cog"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">{{__("Limandaki Ayar Sayısı")}}</span>
              <span class="info-box-number">{{\App\UserSettings::all()->count()}}</span>
            </div>
          </div>
      </div>
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
                  <div class="overlay">
                      <div class="spinner-border" role="status">
                        <span class="sr-only">{{__("Yükleniyor")}}</span>
                      </div>
                  </div>
                </div>
            </div>
          @elseif ($widget->type==="chart")
            <div class="col-md-6 limanCharts" id="{{$widget->id}}" data-server-id="{{$widget->server_id}}">
                <div class="card" id="{{$widget->id}}Chart">
                  <div class="card-header ui-sortable-handle" style="cursor: move;">
                    <h3 class="card-title">
                      <i class="fas fa-chart-pie mr-1"></i>
                      {{$widget->server_name . " " . __("Sunucusu")}} {{__($widget->title)}}
                    </h3>
                  </div>
                  <div class="card-body">
                    <canvas class="chartjs-render-monitor"></canvas>
                  </div>
                  <div class="overlay">
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
        $(".sortable-widget").sortable({
            stop: function(event, ui) {
                let data = [];
                $(".sortable-widget > div").each(function(i, el){
                    $(el).attr('data-order', $(el).index());
                    data.push({
                      id: $(el).attr('id'),
                      order:  $(el).index()
                    });
                });
                let form = new FormData();
                form.append('widgets', JSON.stringify(data));
                request('{{route('update_orders')}}', form, function(response){});
            }
        });
        $(".sortable-widget").disableSelection();
        let intervals = [];
        let widgets = [];
        let currentWidget = 0;

        $(".limanWidget").each(function(){
            let element = $(this);
            widgets.push({
              'element': element,
              'type': 'countBox'
            });
        });
        $('.limanCharts').each(function(){
            let element = $(this);
            widgets.push({
              'element': element,
              'type': 'chart'
            });
        });
        startQueue()
        setInterval(function(){
            startQueue()
        },{{env("WIDGET_REFRESH_TIME")}});

        function startQueue(){
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
            let info_box = element.closest('.info-box');
            let form = new FormData();
            form.append('widget_id',element.attr('id'));
            form.append('server_id',element.attr('data-server-id'));
            request('{{route('widget_one')}}', form, function(response){
                try {
                  let json = JSON.parse(response);
                  element.html(json["message"]);
                  info_box.find('.info-box-icon').show();
                  info_box.find('.info-box-content').show();
                  info_box.find('.overlay').remove();
                } catch(e) {
                  info_box.find('.overlay i').remove();
                  info_box.find('.overlay span').remove();
                  info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip(e.message)+'" style="color: red;"></i><span style="font-size: 1.2rem;">'+e.message+'</span>');
                }
                if(next){
                  next();
                }
            }, function(error) {
                let json = {};
                try{
                  json = JSON.parse(error);
                }catch(e){
                  json = e;
                }
                info_box.find('.overlay i').remove();
                info_box.find('.overlay span').remove();
                info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip(json.message)+'" style="color: red;"></i><span style="font-size: 1.2rem;">'+json.message+'</span>');
                if(next){
                  next();
                }
              });
        }

        function retrieveCharts(element, next){
            let id = element.attr('id');
            let form = new FormData();
            form.append('widget_id', id);
            form.append('server_id',element.attr('data-server-id'));
            request('{{route('widget_one')}}', form, function(res){
                try {
                  let response =  JSON.parse(res);
                  let data =  response.message;
                  createChart(id+'Chart',data.labels, data.data);
                } catch(e) {
                  info_box.find('.overlay i').remove();
                  info_box.find('.overlay span').remove();
                  info_box.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip(e.message)+'" style="color: red;"></i><span style="font-size: 1.2rem;">'+e.message+'</span>');
                }
                if(next){
                  next();
                }
            }, function(error) {
                let json = {};
                try{
                  json = JSON.parse(error);
                }catch(e){
                  json = e;
                }
                element.find('.overlay i').remove();
                element.find('.overlay span').remove();
                element.find('.overlay').prepend('<i class="fa fa-exclamation-circle" title="'+strip(json.message)+'" style="color: red;"></i><span style="font-size: 1.2rem;">'+json.message+'</span>');
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

        function createChart(element, labels, data) {
          $("#" + element + ' .overlay').remove();
          window[element + "Chart"] = new Chart($("#" + element+' .card-body canvas'), {
            type: 'line',
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
