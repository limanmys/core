@extends('layouts.app')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Ana Sayfa') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Kasa') }}</li>
        </ol>
    </nav>
    @include('errors')
    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <h3 class="profile-username text-center font-weight-bold">{{ __('Kasa') }}</h3>
                    <p class="text-muted text-center mb-0">
                        {{ __('Bu sayfadan mevcut verilerini görebilirsiniz. Buradaki veriler, eklentiler tarafından kullanılmaktadır.') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="buttons">
                            <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#add_settings"><i
                                class="fa fa-key "></i> {{ __('Anahtar Ekle') }}</button>
                            
                            @if (user()->isAdmin())
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#add_setting"><i
                                class="fa fa-cogs "></i> {{ __('Ayar Ekle') }}</button>
                            @endif
                        </div>
                        
                        @if (user()->isAdmin())
                        <div class="selectbox d-flex align-items-center">
                            <span class="mr-2" style="font-weight: 600">{{ __("Kullanıcı") }}</span>
                            <select class="select2" onchange="handleSelect(this)">
                                @foreach ($users as $user)
                                <option 
                                    value="{{ $user->id }}" 
                                    @if($selected_user == $user->id) selected @endif
                                >
                                    {{ $user->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                    
                    <div class="tab-pane active" id="settings" style="margin-top: 15px;">
                        <div class="alert alert-info alert-dismissible">
                            <h5><i class="icon fas fa-info"></i> {{ __('Bilgilendirme!') }}</h5>
                            {{ __('Güvenliğiniz için varolan verileriniz gösterilmemektedir.') }}
                        </div>
                        @include('table', [
                            'value' => $settings,
                            'title' => ['Veri Adı', 'Sunucu', '*hidden*', '*hidden*'],
                            'display' => ['name', 'server_name', 'id:id', 'type:type'],
                            'menu' => [
                                'Güncelle' => [
                                    'target' => 'updateSetting',
                                    'icon' => ' context-menu-icon-edit',
                                ],
                                'Sil' => [
                                    'target' => 'delete_settings',
                                    'icon' => ' context-menu-icon-delete',
                                ],
                            ],
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
    @component('modal-component', [
        'id' => 'add_settings',
        'title' => 'Anahtar Ekle',
        ])
        <div class="modal-body">
            <label>{{ __('Sunucu') }}</label>
            <select name="targetServer" id="targetServer" class="select2 form-control" onchange="setIPAdress()">
                @foreach (servers() as $server)
                    <option value="{{ $server->ip_address . ':' . $server->id }}">{{ $server->name }}</option>
                @endforeach
            </select>
            <input type="text" id="serverHostName" hidden>
        </div>
        @include('keys.add', ['success' => 'addServerKey()'])
    @endcomponent

    @if (user()->isAdmin())
        @component('modal-component', [
            'id' => 'add_setting',
            'title' => 'Ayar Ekle',
            ])
                <label>{{ __('Sunucu') }}</label>
                <select name="server_id" id="add_server_id" class="select2 form-control mb-2">
                    @foreach (servers() as $server)
                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                    @endforeach
                </select>
                <div class="form-group mt-3">
                    <label>Ayar Adı</label>
                    <input type="name" name="setting_name" id="add_setting_name" class="form-control" required>                                                
                </div>
                <div class="form-group">
                    <label>Ayar Değeri</label>
                    <input type="password" name="setting_value" id="add_setting_value" class="form-control" required>                                                
                </div>
                <button type="submit" class="btn btn-success" onclick="createSetting()">{{ __("Ayar Ekle") }}</button>
        @endcomponent
    @endif

    @include('modal', [
        'id' => 'update_settings',
        'title' => 'Veriyi Güncelle',
        'url' => route('user_setting_update'),
        'next' => 'reload',
        'inputs' => [
            'Yeni Değer' => 'new_value:password',
            'id:-' => 'setting_id:hidden',
            'user_id:-' => 'user_id:hidden', 
            'type:-' => 'type:hidden',
        ],
        'submit_text' => 'Veriyi Güncelle',
    ])

    @include('modal', [
        'id' => 'delete_settings',
        'title' => 'Veriyi Sil',
        'url' => route('user_setting_remove'),
        'text' => "Veri'yi silmek istediğinize emin misiniz? Bu işlem geri alınamayacaktır.",
        'next' => 'reload',
        'inputs' => [
            "Setting Id:'null'" => 'id:hidden',
            '-:-' => 'type:hidden',
        ],
        'submit_text' => 'Veriyi Sil',
    ])

    <script>
        const user = '{{ $selected_user }}';

        function handleSelect(element) {
            window.location = "/kasa/" + element.value;
        }

        $("#useKeyLabel").fadeOut();
        keySettingsChanged();

        function createSetting() {
            showSwal('{{ __('Ekleniyor...') }}', "info");
            let form = new FormData();
            form.append('server_id', $("#add_server_id").val());
            form.append('setting_name', $("#add_setting_name").val());
            form.append('setting_value', $("#add_setting_value").val());
            form.append('user_id', user);
            request("{{ route('user_setting_create') }}", form, function() {
                showSwal("{{ __('Başarılı') }}", 'success', '2000');
                setTimeout(() => {
                    reload();
                }, 1500);
            }, function(error) {
                let json = JSON.parse(error);
                showSwal(json.message, 'error', 2000);
            });
        }

        function updateSetting(element) {
            var type = element.querySelector('#type').innerHTML;
            var id = element.querySelector('#id').innerHTML;
            if (type == "key") {
                showSwal("{{ __('Sunucu anahtarını güncellemek için yeniden anahtar eklemelisiniz.') }}", "info", 2000);
                $("#add_settings").modal('show');
            } else {
                $("#update_settings").find('input[name=setting_id]').val(id);
                $("#update_settings").find('input[name=type]').val(type);
                $("#update_settings").modal('show');
            }
        }

        function setIPAdress() {
            $("#serverHostName").val($("#targetServer").val().split(":")[0]);
        }

        function addServerKey() {
            if (isKeyOK == false) {
                showSwal('{{ __('Lütfen önce anahtarınızı doğrulayın!') }}', 'error', 2000);
                return;
            }
            showSwal('{{ __('Ekleniyor...') }}', "info");
            let form = new FormData();
            form.append('username', $("#keyUsername").val());
            if ($("#keyPassword").val() != "") {
                form.append('password', $("#keyPassword").val());
            } else {
                form.append('password', $("#keyPasswordCert").val());
            }
            form.append('user_id', user);
            form.append('type', $("#keyType").val());
            form.append('key_port', $("#port").val());
            form.append('server_id', $("#targetServer").val().split(":")[1]);
            form.append('shared', $("#sharedKey").is(':checked'));
            request("{{ route('key_add') }}", form, function(success) {
                let json = JSON.parse(success);
                showSwal(json.message, 'success', '2000');
                setTimeout(() => {
                    reload();
                }, 1500);
            }, function(error) {
                let json = JSON.parse(error);
                showSwal(json.message, 'error', 2000);
            });
        }
        $("#keySubmitButton").text("{{ __('Anahtarı Ekle') }}");
        setIPAdress();
    </script>
@endsection
