<div id="accordion">
    @if (isset(collect($extension["database"])->keyBy("required")[1]))
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title w-100">
                <a class="d-block w-100" data-toggle="collapse" href="#reqSettings">
                    {{ __('Zorunlu Ayarlar') }}
                </a>
            </h3>
        </div>
        <div id="reqSettings" class="collapse show" data-parent="#accordion">
            <form name="reqSettings"
                action="{{ route('extension_server_settings', [
                    'extension_id' => request()->route('extension_id'),
                    'server_id' => request()->route('server_id'),
                ]) }}"
                method="POST">
                @csrf
                <div class="card-body">
                    @if (!empty($errors) && count($errors))
                        <div class="alert alert-danger" role="alert">
                            {!! $errors->getBag('default')->first('message') !!}
                        </div>
                    @elseif(count($similar))
                        <div class="alert alert-info" role="alert">
                            {{ __('Önceki ayarlarınızdan sizin için birkaç veri eklendi.') }}
                        </div>
                    @endif

                    @if (count($globalVars))
                        <div class="alert alert-info" role="alert">
                            {{ __('Bazı ayarlar sadece eklentiyi kuran kullanıcı tarafından değiştirilebilir.') }}
                        </div>
                    @endif

                    @if ($extension['database'])
                        @foreach ($extension['database'] as $item)
                            @if (in_array($item['variable'], $globalVars))
                                @continue
                            @endif

                            @if ($item['required'] == false)
                                @continue
                            @endif

                            @if ($item['variable'] == 'certificate')
                                <div class="form-group">
                                    <label>{{ $item['name'] }}</label>
                                    <textarea name="certificate" cols="30" rows="10" class="form-control"
                                        @if (!isset($item['required']) || $item['required'] === true) required @endif></textarea><br>
                                </div>
                            @elseif($item['type'] == 'extension')
                                <div class="form-group">
                                    <label>{{ $item['name'] }}</label>
                                    <select class="form-control" name="{{ $item['variable'] }}"
                                        @if (!isset($item['required']) || $item['required'] === true) required @endif>
                                        <option>{{ $item['name'] }}</option>
                                        @foreach (extensions() as $extension)
                                            <option value="{{ $extension->id }}" @if ($extension->id == old($item['variable'], extensionDb($item['variable']))) selected @endif>
                                                {{ $extension->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($item['type'] == 'server')
                                <div class="form-group">
                                    <label>{{ $item['name'] }}</label>
                                    <select class="form-control" name="{{ $item['variable'] }}"
                                        @if (!isset($item['required']) || $item['required'] === true) required @endif>
                                        <option>{{ $item['name'] }}</option>
                                        @foreach (servers() as $server)
                                            <option value="{{ $server->id }}" @if ($server->id == old($item['variable'], extensionDb($item['variable']))) selected @endif>
                                                {{ $server->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div class="form-group">
                                    <label>{{ __($item['name']) }}</label>
                                    <input @if (!isset($item['required']) || $item['required'] === true) required @endif class="form-control" type="{{ $item['type'] }}"
                                        name="{{ $item['variable'] }}" placeholder="{{ __($item['name']) }}"
                                        @if ($item['type'] != 'password')
                                    @if (extensionDb($item['variable']))
                                        value="{{ old($item['variable'], extensionDb($item['variable'])) }}"
                                    @elseif(array_key_exists($item['variable'], $similar))
                                        value="{{ old($item['variable'], $similar[$item['variable']]) }}"
                                    @endif
                            @endif
                            >
                </div>
                @if ($item['type'] == 'password')
                    <div class="form-group">
                        <label>{{ __($item['name']) }} {{ __('Tekrar') }}</label>
                        <input @if (!isset($item['required']) || $item['required'] === true) required @endif class="form-control" type="{{ $item['type'] }}"
                            name="{{ $item['variable'] }}_confirmation"
                            placeholder="{{ __($item['name']) }} {{ __('Tekrar') }}">
                    </div>
                @endif
                @endif
                @endforeach
            @else
                <div>{{ __('Bu eklentinin hiçbir ayarı yok.') }}</div>
                @endif
        </div>
        @if ($extension['database'])
            <div class="card-footer">
                <button type="submit" class="btn btn-success">{{ __('Kaydet') }}</button>
            </div>
        @endif
        </form>
    </div>
    @endif
</div>
@if (isset(collect($extension["database"])->keyBy("required")[0]))
<div class="card card-danger">
    <div class="card-header">
        <h4 class="card-title w-100">
            <a class="d-block w-100" data-toggle="collapse" href="#advSettings">
                {{ __('Gelişmiş Ayarlar') }}
            </a>
        </h4>
    </div>
    <div id="advSettings" class="collapse @if (!isset(collect($extension['database'])->keyBy('required')[1])) show @endif" data-parent="#accordion">
        <form name="advSettings"
            action="{{ route('extension_server_settings', [
                'extension_id' => request()->route('extension_id'),
                'server_id' => request()->route('server_id'),
            ]) }}"
            method="POST">
            @csrf
            <div class="card-body">
                @if (!empty($errors) && count($errors))
                    <div class="alert alert-danger" role="alert">
                        {!! $errors->getBag('default')->first('message') !!}
                    </div>
                @elseif(count($similar))
                    <div class="alert alert-info" role="alert">
                        {{ __('Önceki ayarlarınızdan sizin için birkaç veri eklendi.') }}
                    </div>
                @endif

                @if (count($globalVars))
                    <div class="alert alert-info" role="alert">
                        {{ __('Bazı ayarlar sadece eklentiyi kuran kullanıcı tarafından değiştirilebilir.') }}
                    </div>
                @endif

                @if ($extension['database'])
                    @foreach ($extension['database'] as $item)
                        @if (in_array($item['variable'], $globalVars))
                            @continue
                        @endif

                        @if ($item['required'])
                            @continue
                        @endif

                        @if ($item['variable'] == 'certificate')
                            <div class="form-group">
                                <label>{{ $item['name'] }}</label>
                                <textarea name="certificate" cols="30" rows="10" class="form-control"
                                    @if (!isset($item['required']) || $item['required'] === true) required @endif></textarea><br>
                            </div>
                        @elseif($item['type'] == 'extension')
                            <div class="form-group">
                                <label>{{ $item['name'] }}</label>
                                <select class="form-control" name="{{ $item['variable'] }}" @if (!isset($item['required']) || $item['required'] === true) required @endif>
                                    <option>{{ $item['name'] }}</option>
                                    @foreach (extensions() as $extension)
                                        <option value="{{ $extension->id }}" @if ($extension->id == old($item['variable'], extensionDb($item['variable']))) selected @endif>
                                            {{ $extension->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($item['type'] == 'server')
                            <div class="form-group">
                                <label>{{ $item['name'] }}</label>
                                <select class="form-control" name="{{ $item['variable'] }}"
                                    @if (!isset($item['required']) || $item['required'] === true) required @endif>
                                    <option>{{ $item['name'] }}</option>
                                    @foreach (servers() as $server)
                                        <option value="{{ $server->id }}" @if ($server->id == old($item['variable'], extensionDb($item['variable']))) selected @endif>
                                            {{ $server->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="form-group">
                                <label>{{ __($item['name']) }}</label>
                                <input @if (!isset($item['required']) || $item['required'] === true) required @endif class="form-control" type="{{ $item['type'] }}"
                                    name="{{ $item['variable'] }}" placeholder="{{ __($item['name']) }}" @if ($item['type'] != 'password')
                                @if (extensionDb($item['variable']))
                                    value="{{ old($item['variable'], extensionDb($item['variable'])) }}"
                                @elseif(array_key_exists($item['variable'], $similar))
                                    value="{{ old($item['variable'], $similar[$item['variable']]) }}"
                                @endif
                        @endif
                        >
            </div>
            @if ($item['type'] == 'password')
                <div class="form-group">
                    <label>{{ __($item['name']) }} {{ __('Tekrar') }}</label>
                    <input @if (!isset($item['required']) || $item['required'] === true) required @endif class="form-control" type="{{ $item['type'] }}"
                        name="{{ $item['variable'] }}_confirmation"
                        placeholder="{{ __($item['name']) }} {{ __('Tekrar') }}">
                </div>
            @endif
            @endif
            @endforeach
        @else
            <div>{{ __('Bu eklentinin hiçbir ayarı yok.') }}</div>
            @endif
    </div>
    @if ($extension['database'])
        <div class="card-footer">
            <button type="submit" class="btn btn-success">{{ __('Kaydet') }}</button>
        </div>
    @endif
    </form>
</div>
@endif
</div>
</div>
