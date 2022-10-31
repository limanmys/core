<div class="row">
    <div class="col-md-6" style="display: flex;
            text-align: right;
            align-items: center;
            justify-content: flex-start;">
        {{ __('Toplam :count kayıttan, sayfa :current/:page gösteriliyor', [
            'count' => isset($total_count) ? $total_count : $count * 10 + 1,
            'current' => $current,
            'page' => $count,
        ]) }}
    </div>
    <div class="col-md-6" style="display: flex;
            text-align: right;
            align-items: center;
            justify-content: flex-end;">
        <ul class="pagination" style="display: flex; flex-wrap: nowrap; flex-direction: row;">
            <li class="paginate_button page-item previous" @if($current != 1) onclick="{{$onclick . '(' . ($current - 1 ). ')'}}" @else disabled @endif>
                <a href="#" tabindex="0" class="page-link @if($current == 1) disabled @endif">
                    {{ __('Önceki') }}
                </a>
            </li>
            <select style="margin-top: 1px" onchange="{{$onclick . '(this.value)'}}" class="custom-select custom-select-sm form-control form-control-sm mr-1">
                @for($i = 1 ; $i <= intval($count); $i++)
                    <option value="{{$i}}"@if($i == $current) selected @endif">{{$i}}</option>
                @endfor
            </select>
            <li class="paginate_button page-item next" @if($current != $count) onclick="{{$onclick . '(' . ($current + 1 ). ')'}}" @else disabled @endif>
                <a href="#" tabindex="0" class="page-link @if($current == $count) disabled @endif">
                    {{ __('Sonraki') }}
                </a>
            </li>
        </ul>
    </div>
</div>
