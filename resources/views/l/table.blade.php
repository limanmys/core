@if(!isset($title) && !isset($value) && !isset($display))
    @php(__("Tablo Oluşturulamadı."))
@else
    @isset($id)
        @php($rand = $id)
    @else
        @php($rand = str_random(10))
    @endisset
    @if(!isset($startingNumber))
        @php($startingNumber = 0)
    @endisset

<div class="table-responsive">
    <table class="table table-bordered table-hover dataTable @isset($noInitialize){{"notDataTable"}}@endisset" id="{{$rand}}" style="width: 100%">
        <thead>
        <tr>
            @if(isset($sortable) && $sortable)
              <th scope="col">Taşı</th>
            @endif
            <th scope="col">#</th>
            @foreach($title as $i)
                @if($i == "*hidden*")
                    <th scope="col" hidden>{{ __($i) }}</th>
                @else
                    <th scope="col">{{ __($i) }}</th>
                @endif
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($value as $k)
            <tr class="tableRow" @if(isset($k->id)) data-id="{{$k->id}}" @endif id="{{str_random(10)}}" @isset($onclick)style="cursor: pointer;" onclick="{{$onclick}}(this)" @endisset>
                @if(isset($sortable) && $sortable)
                  <td style="width: 10px; text-align: center;"><i class="fas fa-arrows-alt"></i></td>
                @endif
                <td style="width: 10px" class="row-number">{{$loop->iteration + $startingNumber}}</td>
                @foreach($display as $item)
                    @if(count(explode(':',$item)) > 1)
                        @if(is_array($k))
                            <td id="{{explode(':',$item)[1]}}" hidden>{{$k[explode(':',$item)[0]]}}</td>
                        @else
                            <td id="{{explode(':',$item)[1]}}" hidden>{{$k->__get(explode(':',$item)[0])}}</td>
                        @endif
                    @else
                        @if(is_array($k))
                            <td id="{{$item}}">{{array_key_exists($item,$k) ? $k[$item] : ""}}</td>
                        @else
                        <!-- Find Optimal Solution for objects. -->
                            <td id="{{$item}}">{{$k->__get($item)}}</td>
                        @endif
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
    @if(isset($menu))
        <script>

            @if(isset($sortable) && $sortable)
              $('#{{$rand}}').find('tbody').sortable({
                  stop: function(event, ui) {
                      var data = [];
                      $('#{{$rand}}').find('tbody').find('tr').each(function(i, el){
                          $(el).attr('data-order', $(el).index());
                          $(el).find('.row-number').text($(el).index()+1);
                          data.push({
                            id: $(el).attr('data-id'),
                            order:  $(el).index()
                          });
                      });
                      @if(isset($sortUpdateUrl) && $sortUpdateUrl)
                        var form = new FormData();
                        form.append('data', JSON.stringify(data));
                        request('{{$sortUpdateUrl}}', form, function(response){
                          {{$afterSortFunction}}();
                        });
                      @endif
                  }
              });
            @endif

            @isset($setCurrentVariable)
            var {{$setCurrentVariable}};
            @endisset
            @if(count($value) > 0)
            $.contextMenu({
                selector: '#{{$rand}} tbody tr',
                callback: function (key, options) {
                    @isset($setCurrentVariable)
                    {{$setCurrentVariable}} = options.$trigger[0].getAttribute("id");
                    @endisset
                    var target = $("#" + key);
                    if(target.length === 0){
                        window[key](options.$trigger[0]);
                        return;
                    }
                    inputs =[];
                    $("#" + key + " input , #" + key + ' select').each(function (index, value) {
                        var element_value = $("#" + options.$trigger[0].getAttribute("id") + " #" + value.getAttribute('name')).text();
                        if(element_value){
                            inputs.push($("#" + options.$trigger[0].getAttribute("id") + " #" + value.getAttribute('name')));
                            $("#" + key + " select[name='" + value.getAttribute('name') + "']" + " , "
                                + "#" + key + " input[name='" + value.getAttribute('name') + "']").val(element_value).prop('checked', true);
                        }
                    });
                    target.modal('show');
                },
                items: {
                    @foreach($menu as $name=>$config)
                        "{{$config['target']}}" : {name: "{{$name}}" , icon: "{{$config['icon']}}"},
                    @endforeach
                }
            });
            @endif
        </script>
    @endif
@endif
