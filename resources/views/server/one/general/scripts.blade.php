<script>
    customRequestData["server_id"] = '{{server()->id}}';

    $('#install_extension table').DataTable(dataTablePresets('multiple'));


    function server_extension(){
        showSwal('{{__("Okunuyor...")}}','info');

        var items = [];
        var table = $("#install_extension table").DataTable();
        table.rows( { selected: true } ).data().each(function(element){
            items.push(element[2]);
        });

        if(items.length === 0){
            showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error');
            return false;
        }

        var data = new FormData();
        data.append("extensions", JSON.stringify(items));

        request('{{route('server_extension')}}', data, function (response) {
            Swal.close();
            reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }


    @if(server()->canRunCommand() && server()->isLinux())
        if(location.hash !== "#updatesTab"){
            getUpdates();
            Swal.close();
        }
    @endif

    function errorSwal(){
        showSwal('{{__("Ayarlarınız doğrulanamadı!")}}','error',2000);
    }

    function checkStatus(id) {
        var data = new FormData();
        if (!id) {
            return false;
        }
        data.append('extension_id', id);
        request('{{route('server_check')}}', data, function (response) {
            var json = JSON.parse(response);
            var element = $(".status_" + id);
            element.removeClass('btn-secondary').removeClass('btn-danger').removeClass('btn-success').addClass(json["message"]);
        });
    }

    @if($installed_extensions->count() > 0)
        @foreach($installed_extensions as $service)
        checkStatus('{{$service->id}}');
        @endforeach
    @endif

    @if(server()->canRunCommand())

    function getDashboard()
    {
        retrieveStats();
        $('.table-card').find('.refresh-button').click();
        setInterval(function() {
            CHARTS.CPU.data = CHARTS.CPU.data.slice(-20);
            CHARTS.RAM.data = CHARTS.RAM.data.slice(-20);
            CHARTS.IO.data = CHARTS.IO.data.slice(-20);
            CHARTS.NETWORK.data.upload = CHARTS.NETWORK.data.upload.slice(-20);
            CHARTS.NETWORK.data.download = CHARTS.NETWORK.data.download.slice(-20);
        }, 60000)
    }

    var firstStats = true;
    var statTimeout;

    function retrieveStats() {
        @if(server()->isLinux())
        request('{{ route("server_stats") }}', new FormData(),
            function(response) {
                    stats = JSON.parse(response);
                    if (!IS_RENDERED) {
                        renderChart(CHARTS.CPU)
                        renderChart(CHARTS.RAM)
                        renderChart(CHARTS.IO)
                        renderChart(CHARTS.NETWORK, true)
                        IS_RENDERED = true;
                        $('.charts-card').find('.overlay').hide();
                    }

                    updateChart(CHARTS.CPU)
                    updateChart(CHARTS.RAM)
                    updateChart(CHARTS.IO)
                    updateNetworkChart(CHARTS.NETWORK)
                            
                    setTimeout(() => {
                        retrieveStats();
                    }, CHART_INTERVAL);
                }
        );
        @endif
    }

    function downloadFile(form) {
        window.location.assign('/sunucu/indir?path=' + form.getElementsByTagName('input')[0].value + '&server_id=' + form.getElementsByTagName('input')[1].value);
        return false;
    }

    @endif
    function logDetails(element) {
        var log_id = element.querySelector('#_id').innerHTML;
        window.location.href = "/logs/" + log_id;
    }

    function favorite(action) {
        var form = new FormData();
        form.append('server_id', '{{server()->id}}');
        form.append('action', action);
        request('{{route('server_favorite')}}', form, function (response) {
            location.reload();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function getSudoers(){
        $('.modal').modal('hide');
        showSwal('{{__("Okunuyor...")}}','info');

        request('{{route('server_sudoers_list')}}', new FormData(), function (response) {
            Swal.close();
            $("#sudoersTab #sudoers").html(response);
            $("#sudoersTab #sudoers table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function deleteSudoers(row){
        Swal.fire({
            title: "{{ __('Onay') }}",
            text: "{{ __('Yetkili kullanıcıyı silmek istediğinizden emin misiniz?') }}",
            type: 'warning',
            animation: false,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: "{{ __('İptal') }}",
            confirmButtonText: "{{ __('Sil') }}"
        }).then((result) => {
            if (result.value) {
                showSwal('{{__("Yükleniyor...")}}','info');
                var data = new FormData();
                data.append('name',$(row).find("#name").text());
                
                request('{{route('server_delete_sudoers')}}',data,function(response){
                    Swal.close();
                    getSudoers();
                }, function(response){
                    var error = JSON.parse(response);
                    showSwal(error.message,'error',2000);
                });
            }
        });
    }

    function getLocalUsers(){
        showSwal('{{__("Okunuyor...")}}','info');

        request('{{route('server_local_user_list')}}', new FormData(), function (response) {
            Swal.close();
            $("#usersTab #users").html(response);
            $("#usersTab #users table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function getLocalGroups(){
        showSwal('{{__("Okunuyor...")}}','info');

        request('{{route('server_local_group_list')}}', new FormData(), function (response) {
            Swal.close();
            $("#groupsTab #groups").html(response);
            $("#groupsTab #groups table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    var activeLocalGroup = "";
    var activeLocalGroupElement = "";

    function localGroupDetails(element){
        $('#groups').closest('.col-md-12').removeClass("col-md-12").addClass('col-md-6');
        $('#groupUsers').closest('.col-md-6').removeClass('d-none');
        $(element).parent().find('tr').css('fontWeight','normal');
        $(element).parent().find('tr').css('backgroundColor','');
        $(element).css('backgroundColor','#b0bed9');
        $(element).css('fontWeight','bolder');
        showSwal('{{__("Okunuyor...")}}','info');
        var group = element.querySelector('#group').innerHTML;
        activeLocalGroup = group;
        activeLocalGroupElement = element;
        var data = new FormData();
        data.append('group', group);

        request('{{route('server_local_group_users_list')}}', data, function (response) {
            Swal.close();
            $("#groupsTab #groupUsers").html(response);
            $("#groupsTab #groupUsers table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function addLocalGroupUser(){
        showSwal('{{__("Okunuyor...")}}','info');

        var form = new FormData();
        form.append('group',activeLocalGroup);
        form.append('user',$('#addLocalGroupUserModal').find("input[name=user]").val());

        request('{{route('server_add_local_group_user')}}',form,function(response){
            var json = JSON.parse(response);
            showSwal(json.message,'info',2000);
            localGroupDetails(activeLocalGroupElement);
            $('#addLocalGroupUserModal').modal('hide');
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function getPackages() {
        showSwal('{{__("Okunuyor...")}}','info');
        request('{{route('server_package_list')}}', new FormData(), function (response) {
            Swal.close();
            $("#packagesTab #packages").html(response);
            $("#packagesTab #packages table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function getOpenPorts() {
        showSwal('{{__("Okunuyor...")}}','info');
        request('{{route('server_get_open_ports')}}', new FormData(), function (response) {
            var json = JSON.parse(response);
            $("#openPortsTab").html(json.message);
            $("#openPortsTab table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            $("#openPortsTab").html(error.message);
            Swal.close();
        })
    }

    function installLsof() {
        index = 0;
        packages = [];
        packages.push('lsof');
        modes['lsof'] = "install";
        installPackage();
        $("#updateLogs").on("hidden.bs.modal", function () {
            setTimeout(() => {
                getOpenPorts();
            }, 1000);
        });
    }

    function statusService(element) {
        var name = element.querySelector('#name').innerHTML;
        showSwal('{{__("Okunuyor...")}}','info');
        var form = new FormData();
        form.append('name',name);
        request('{{route('server_service_status')}}', form, function (response) {
            var json = JSON.parse(response);
            let wrapper = $("#serviceStatusWrapper");
            wrapper.html(json.message);
            if(json.message.includes("Active: active (running)")) {
                wrapper.css("color","green");
            }else if(json.message.includes("Active: inactive (dead)")) {
                wrapper.css("color","grey");
            }else if(json.message.includes("Active: failed")) {
                wrapper.css("color","red");
            }
            $("#serviceStatusModal").modal('show');
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    $("#logQueryFilter").on('keyup', function (e) {
        if (e.keyCode === 13) {
            getLogs();
        }
    });

    $("#log_extension").on('change', function() {
        getLogs();
    });

    $("#log_user").on('change', function() {
        getLogs();
    });

    function getLogs(page = 1) {
        showSwal('{{__("Okunuyor...")}}','info');
        var form = new FormData();
        form.append('page',page);
        var query = $("#logQueryFilter").val();
        if(query.length !== 0){
            form.append('query',query);
        }
        if ($("#log_user").val()) {
            form.append("log_user_id", $("#log_user").val());
        }
        if ($("#log_extension").val()) {
            form.append("log_extension_id", $("#log_extension").val());
        }
        request('{{route('server_get_logs')}}', form, function (response) {
            var json = JSON.parse(response);
            $("#logsWrapper").html(json.message.table);
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function getLogDetails(element){
        var log_id = element.querySelector('#id').innerHTML;
        showSwal('{{__("Okunuyor...")}}','info');
        var form = new FormData();
        form.append('log_id',log_id);
        request('{{route('server_get_log_details')}}', form, function (response) {
            var json = JSON.parse(response);
            var modal = $("#logDetailModal");
            var logTitleWrapper = $("#logTitleWrapper");
            var logContentWrapper = $("#logContentWrapper");
            logTitleWrapper.html("");
            logContentWrapper.html("");
            $.each(json.message,function (index,current) {
                current.id =  "a" + Math.random().toString(36).substr(2, 9);
                let titleEl = "";
                if (current.title == "---------------------") {
                    current.title = `<center><hr /></center>`
                    titleEl = $("<a class='disabled list-group-item list-group-item-action' id='"+ current.id + "_title' href='#" + current.id + "_content' data-toggle='list' role='tab' aria-controls='home' />").html(current.title);
                    logTitleWrapper.append(titleEl);
                } else {
                    titleEl = $("<a class='list-group-item list-group-item-action' id='"+ current.id + "_title' href='#" + current.id + "_content' data-toggle='list' role='tab' aria-controls='home' />").html(current.title);
                    logTitleWrapper.append(titleEl);
                }
                const contentEl = $("<div style='background: #1a202c;border-radius: 5px;height: 100%;' class='tab-pane fade' id='" + current.id + "_content' role='tabpanel' aria-labelledby='" + current.id +"_title'><pre style='white-space:pre-wrap; color: limegreen' /></div>");
                contentEl.find("pre").text(current.message);
                logContentWrapper.append(contentEl);

                if (index == 0) {
                    titleEl.click();
                }
            });
            modal.modal("show");
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function getServices() {
        showSwal('{{__("Okunuyor...")}}','info');
        request('{{route('server_service_list')}}', new FormData(), function (response) {
            $("#servicesTab").html(response);
            $("#servicesTab table").DataTable(dataTablePresets('normal'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }
    var index = 0;
    var packages = [];
    var modes = {};

    function installPackageButton(){
        if($('#installPackage').find('[href="#fromRepo"]').hasClass('active')){
            console.log("repo tab");
            index = 0;
            packages = [];
            var package_name = $('#installPackage').find('input[name=package]').val();
            if(package_name){
                packages.push(package_name);
                modes[package_name] = "install";
                installPackage();
            }
        }else if($('#installPackage').find('[href="#fromDeb"]').hasClass('active')){
            if(!packages.length){
                showSwal("{{__('Lütfen önce bir deb paketi yükleyin.')}}",'error');
                return;
            }
            index = 0;
            installPackage();
        }
    }

    function onDebUploadSuccess(upload){
        showSwal('{{__("Yükleniyor...")}}','info');
        var data = new FormData();
        data.append('filePath', upload.info.file_path);
        request('{{route('server_upload_deb')}}', data, function (response) {
            Swal.close();
            response = JSON.parse(response);
            if(response.message){
                index = 0;
                packages = [];
                packages.push(response.message);
                modes[response.message] = "install";
            }
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        });
    }


    function updateAllPackages(){
        index = 0;
        $('#updateLogs').find('.updateLogsBody').text("");
        getUpdates(function(package_list){
            var package_list_tmp = [];
            package_list.forEach(function(pkg){
                var package_name = pkg.name.split('/')[0];
                package_list_tmp.push(package_name);
            });
            packages = package_list_tmp;
            installPackage();
        });
    }

    function updateSelectedPackages(){
        index = 0;
        packages = [];
        var table = $("#updatesTabTable table").DataTable();
        table.rows( { selected: true } ).data().each(function(element){
            packages.push(element[1].split('/')[0]);
        });
        if(packages.length === 0){
            showSwal("{{__('Lütfen önce seçim yapınız.')}}",'error');
            return false;
        }
        installPackage();
    }

    function updateSinglePackage(row){
        index = 0;
        packages = [];
        packages.push($(row).find("#name").text().split('/')[0]);
        installPackage();
    }

    function installPackage(){
        updateProgress();
        $('#updateLogs').modal('show');
        var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
        scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
        var data = new FormData();
        data.append("package_name", packages[index]);
        if(modes[packages[index]]){
            data.append("mode", modes[packages[index]]);
        }
        $('#updateLogs').find('.updateLogsBody').append("\n"+packages[index]+" {{ __("paketi kuruluyor. Lütfen bekleyin...") }}<span id='"+packages[index]+"'></span>");
        request('{{route('server_install_package')}}', data, function (response) {
            checkPackage();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function updateProgress(){
        $('#updateLogs').find('.progress-info').text(index+"/"+packages.length+" "+packages[index]+" {{ __("paketi kuruluyor.") }}");
        var percent = (index/packages.length)*100;
        $('div[role=progressbar]').attr('aria-valuenow', percent);
        $('div[role=progressbar]').attr('style', 'width: '+percent+'%');
        if(packages.length !== index){
            $('div[role=progressbar]').closest('.progress').addClass('active');
        }else{
            $('div[role=progressbar]').closest('.progress').removeClass('active');
            $('#updateLogs').find('.progress-info').text("{{ __("Tüm işlemler bitti.") }}");
        }

    }

    function checkPackage(){
        var data = new FormData();
        data.append("package_name", packages[index]);
        if(modes[packages[index]]){
            data.append("mode", modes[packages[index]]);
        }
        request('{{route('server_check_package')}}', data, function (response) {
            response = JSON.parse(response);
            if(response.message.output){
                $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.output);
                var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
            }
            $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.status);
            var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
            scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
            index++;
            if(packages.length !== index){
                installPackage();
            }else{
                updateProgress();
                getUpdates();
                $('#updateLogs').find('.updateLogsBody').append("\n"+"{{ __("Tüm işlemler bitti.") }}");
            }
        }, function(response){
            response = JSON.parse(response);
            if(response.message.output){
                $('#updateLogs').find('.updateLogsBody').append("\n"+response.message.output);
                var scroll = $('#updateLogs').find('.updateLogsBody').closest('pre');
                scroll.animate({ scrollTop: scroll.prop("scrollHeight") }, 'slow');
            }
            setTimeout(function(){
                checkPackage();
            },5000);
        });
    }

    function getUpdates(getList) {
        showSwal('{{__("Okunuyor...")}}','info');
        request('{{route('server_update_list')}}', new FormData(), function (response) {
            var updates = JSON.parse(response);
            if(getList){
                getList(updates.list);
            }
            $('.updateCount').text(updates.count);
            if(updates.count>0){
                $('.updateCount').show();
                $('.updateAllPackages').show();
                $('.updateSelectedPackages').show();
            }else{
                $('.updateCount').hide();
                $('.updateAllPackages').hide();
                $('.updateSelectedPackages').hide();
            }
            $("#updatesTabTable").html(updates.table);
            $("#updatesTabTable table").DataTable(dataTablePresets('multiple'));
            setTimeout(function () {
                Swal.close();
            }, 1500);
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function removeExtension(){
        var data = [];
        var table = $("#installed_extensions").DataTable();
        table.rows( { selected: true } ).data().each(function(element){
            data.push(element[4]);
        });
        if(data.length === 0){
            showSwal('{{__("Lütfen önce seçim yapınız.")}}','error',2000);
            return false;
        };
        $("#delete_extensions").modal('show');
    }

    function removeExtensionFunc() {
      var data = [];
      var table = $("#installed_extensions").DataTable();
      table.rows( { selected: true } ).data().each(function(element){
          data.push(element[4]);
      });
      if(data.length === 0){
          showSwal('{{__("Lütfen önce seçim yapınız.")}}','error',2000);
          return false;
      }
      showSwal('{{__("Siliniyor...")}}','info');
      var form = new FormData();
      form.append('extensions',JSON.stringify(data));
      request('{{route('server_extension_remove')}}', form, function (response) {
          var json = JSON.parse(response);
          showSwal(json["message"],'success',2000);
          setTimeout(function () {
                  location.reload();
          },2000);
      }, function(response){
        var error = JSON.parse(response);
        showSwal(error.message,'error',2000);
      });
      return false;
    }

    $(function () {
        $("#installed_extensions").DataTable(dataTablePresets('multiple'));
        getDashboard();
    });

    
</script>