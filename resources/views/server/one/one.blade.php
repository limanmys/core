<div class="col-md-9">
    <div class="card">
        <div class="card-header p-2">
            <ul class="nav nav-tabs" role="tablist">
                @php($firstRendered = false)
                @if(server()->canRunCommand() && server()->isLinux())
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" onclick="getDashboard()" href="#usageTab" role="tab">{{__("Sistem Durumu")}}</a>
                    </li>
                    @php($firstRendered = true)
                @endif
                <li class="nav-item">
                    <a class="nav-link @if(!$firstRendered) active @endif" data-toggle="pill" href="#extensionsTab" role="tab">{{__("Eklentiler")}}</a>
                </li>
                @if(server()->canRunCommand() && server()->isLinux())
                    @if(\App\Models\Permission::can(user()->id,'liman','id','server_services'))
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" onclick="getServices()" href="#servicesTab" role="tab">{{__("Servisler")}}</a>
                        </li>
                    @endif
                    @if(server()->canRunCommand() && server()->isLinux())
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" onclick="getPackages()" href="#packagesTab" role="tab">{{__("Paketler")}}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" onclick="getUpdates()" href="#updatesTab" role="tab">
                                {{__("Güncellemeler")}}
                                <small class="badge bg-danger updateCount" style="display:none;margin-left: 5px;">0</small>
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                              {{ __('Kullanıcı İşlemleri') }} <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 40px, 0px);">
                                <a class="dropdown-item" href="#usersTab" onclick="getLocalUsers()" data-toggle="tab">{{__("Yerel Kullanıcılar")}}</a>
                                <a class="dropdown-item" href="#groupsTab" onclick="getLocalGroups()" data-toggle="tab">{{__("Yerel Gruplar")}}</a>
                                <a class="dropdown-item" href="#sudoersTab" onclick="getSudoers()" data-toggle="tab">{{__("Yetkili Kullanıcılar")}}</a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" onclick="getOpenPorts()" href="#openPortsTab" role="tab">{{__("Açık Portlar")}}</a>
                        </li>
                    @endif
                @endif
                @if(\App\Models\Permission::can(user()->id,'liman','id','view_logs'))
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#logsTab" onclick="getLogs()" role="tab">{{__("Erişim Kayıtları")}}</a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#settingsTab" role="tab">{{__("Sunucu Ayarları")}}</a>
                </li>
                {!! serverModuleButtons() !!}
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                @if(server()->canRunCommand() && server()->isLinux())
                    <div class="tab-pane fade show active" id="usageTab" role="tabpanel">
                        <div class="card card-primary charts-card">
                            <div class="card-header" style="background-color: #007bff; color: #fff;">
                                <h3 class="card-title">{{ __('Kaynak Kullanımı') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="row justify-content-center">
                                    <div class="col-md-3">
                                        <canvas id="cpuChart"></canvas>
                                    </div>
                                    <div class="col-md-3">
                                        <canvas id="ramChart"></canvas>
                                    </div>
                                    <div class="col-md-3">
                                        <canvas id="networkChart"></canvas>
                                    </div>
                                    <div class="col-md-3">
                                        <canvas id="ioChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="overlay">
                                <div class="spinner-border" role="status">
                                    <span class="sr-only">{{ __('Yükleniyor...') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                @include('table-card', [
                                    "title" => __("Cpu Kullanımı"),
                                    "api" => "top_cpu_processes"
                                ])
                            </div>
                            <div class="col-md-4">
                                @include('table-card', [
                                    "title" => __("Ram Kullanımı"),
                                    "api" => "top_memory_processes"
                                ])
                            </div>
                            <div class="col-md-4">
                                @include('table-card', [
                                    "title" => __("Disk Kullanımı"),
                                    "api" => "top_disk_usage"
                                ])
                            </div>
                        </div>
                    </div>
                @endif
                <div class="tab-pane fade show @if(!$firstRendered) active @endif" id="extensionsTab" role="tabpanel">
                    @if(auth()->user()->id == server()->user_id || auth()->user()->isAdmin())
                        <button class="btn btn-success" data-toggle="modal" data-target="#install_extension"><i
                                    data-toggle="tooltip" title="Ekle"
                                    class="fa fa-plus"></i></button>
                        <button onclick="removeExtension()" class="btn btn-danger"><i data-toggle="tooltip" title="Kaldır" class="fa fa-minus"></i>
                        </button><br><br>
                    @endif
                    @include('table',[
                        "id" => "installed_extensions",
                        "value" => $installed_extensions,
                        "title" => [
                            "Eklenti Adı" , "Versiyon", "Düzenlenme Tarihi", "*hidden*"
                        ],
                        "display" => [
                            "name" , "version", "updated_at","id:extension_id"
                        ],
                        "noInitialize" => "true"
                    ])
                    
                </div>
                    {!! serverModuleViews() !!}

                @if($server->canRunCommand())
                    <div class="tab-pane fade show" id="servicesTab" role="tabpanel"></div>
                    <div class="tab-pane fade show right" id="updatesTab" role="tabpanel">
                        <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateAllPackages" onclick="updateAllPackages()">{{ __('Tümünü Güncelle') }}</button>
                        <button type="button" style="display: none; margin-bottom: 5px;" class="btn btn-success updateSelectedPackages" onclick="updateSelectedPackages()">{{ __('Seçilenleri Güncelle') }}</button>
                        <div id="updatesTabTable"></div>
                    </div>

                    @if($server->isLinux())
                            <div class="tab-pane fade show" id="packagesTab" role="tabpanel">
                                <button type="button" data-toggle="modal" data-target="#installPackage" style="margin-bottom: 5px;" class="btn btn-success">
                                    <i class="fas fa-upload"></i> {{ __('Paket Kur') }}
                                </button>
                                <div id="packages">

                                </div>
                            </div>

                            <div class="tab-pane fade show" id="usersTab" role="tabpanel">
                                @include('modal-button',[
                                    "class"     =>  "btn btn-success mb-2",
                                    "target_id" =>  "addLocalUser",
                                    "text"      =>  "Kullanıcı Ekle",
                                    "icon" => "fas fa-plus"
                                ])
                                <div id="users"></div>
                            </div>

                            <div class="tab-pane fade show" id="groupsTab" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        @include('modal-button',[
                                            "class"     =>  "btn btn-success mb-2",
                                            "target_id" =>  "addLocalGroup",
                                            "text"      =>  "Grup Ekle",
                                            "icon" => "fas fa-plus"
                                        ])
                                        <div id="groups"></div>
                                    </div>
                                    <div class="col-md-6 d-none">
                                        @include('modal-button',[
                                            "class"     =>  "btn btn-success mb-2",
                                            "target_id" =>  "addLocalGroupUserModal",
                                            "text"      =>  "Kullanıcı Ekle",
                                            "icon" => "fas fa-plus"
                                        ])
                                        <div id="groupUsers"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade show" id="sudoersTab" role="tabpanel">
                                @include('modal-button',[
                                    "class"     =>  "btn btn-success mb-2",
                                    "target_id" =>  "addSudoers",
                                    "text"      =>  "Tam Yetkili Kullanıcı Ekle",
                                    "icon" => "fas fa-plus"
                                ])
                                <div id="sudoers"></div>
                            </div>
                    @endif
                @endif
                <div class="tab-pane fade show" id="logsTab" role="tabpanel">
                    <div class="form-group">
                            <label>{{__('Arama Terimi')}}</label>
                            <div class="input-group">
                                <input id="logQueryFilter" type="text" class="form-control" placeholder="{{__('Arama Terimi')}}">
                                <span class="input-group-append">
                                    <button type="button" class="btn btn-primary btn-flat" onclick="getLogs()"><i class="fa fa-search" aria-hidden="true"></i></button>
                                </span>
                            </div>
                        </div>
                    <div id="logsWrapper">
                    </div>
                </div>
                <div class="tab-pane fade show" id="openPortsTab" role="tabpanel"> 
                </div>
                
                <div class="tab-pane fade show" id="settingsTab" role="tabpanel">
                    <table class="notDataTable" style="width: 900px;">
                        <tr>
                            <td>
                                <form id="edit_form" onsubmit="return request('{{route('server_update')}}',this,reload)" target="#">
                                    <h5>{{__("Sunucu Adı")}}</h5>
                                    <input type="text" name="name" placeholder="Sunucu Adı" class="form-control " required=""
                                            value="{{server()->name}}"><br>
                                    <h5>{{__("Kontrol Portu")}}</h5>
                                    <input type="number" name="control_port" placeholder="Kontrol Portu" class="form-control "
                                            required="" value="{{server()->control_port}}"><br>
                                    <h5>{{__("Ip Adresi")}}</h5>
                                    <input type="text" name="ip_address" placeholder="Ip Adresi" class="form-control "
                                            required="" value="{{server()->ip_address}}"><br>
                                    <h5>{{__("Şehir")}}</h5>
                                    <select name="city" class="form-control" required="">
                                        @foreach(cities() as $city=>$value)
                                            <option value="{{$value}}" @if($value == server()->city) selected @endif>{{$city}}</option>
                                        @endforeach
                                    </select>
                            </td>
                            <td style="width:300px;text-align:center;padding-left:60px">
                                <button type="submit" class="btn btn-success btn-block">{{__("Bilgileri Güncelle")}}</button><br><br>
                                @include('modal-button',[
                                    "class" => "btn-danger btn-block",
                                    "target_id" => "delete",
                                        "text" => "Sunucuyu Sil"
                                ])
                                </form>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>