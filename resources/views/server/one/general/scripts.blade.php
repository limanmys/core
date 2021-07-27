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
        setInterval(function () {
            checkStatus('{{$service->id}}');
        }, 3000);
        @endforeach
    @endif

    @if(server()->canRunCommand())

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
						text: `${title} Down: ${data.down} mb/s Up: ${data.up} mb/s`,
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

    function updateChart(element, time, data) {
        // First, Update Text
        $("#" + element + "Text").text("%" + data);
        window[element + "Chart"].data.labels.push(time);
        window[element + "Chart"].data.datasets.forEach((dataset) => {
            dataset.data.push(data);
        });
        window[element + "Chart"].update();
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
    function getDashboard()
    {
        stats();
        $('.table-card').find('.refresh-button').click();
    }

    var firstStats = true;
    var statTimeout;
    function stats(noSpinner = false) {
        !noSpinner && $('.charts-card').find('.overlay').show();
        var form = new FormData();
        form.append('server_id', '{{server()->id}}');
        var time = "{{\Carbon\Carbon::now()->format("H:i:s")}}";
        request('{{route('server_stats')}}', form, function (response) {
            data = JSON.parse(response);
            @if(server()->isLinux())
                resourceChart('{{__("Cpu Kullanımı")}}', "cpuChart", data.time, data.cpuPercent);
                resourceChart('{{__("Ram Kullanımı")}}', "ramChart", data.time, data.ramPercent);
                resourceChart('{{__("Disk Kullanımı")}}', "diskChart", data.time, data.diskPercent);
                resourceChart('{{__("Disk I/O")}}', "ioChart", data.time, data.ioPercent);
                networkChart('{{__("Network")}}', "networkChart", data.time, data.network);
            @else
                if(firstStats){
                    firstStats = false;
                    createChart("ram", time, [data['ram']]);
                    createChart("cpu", time, [data['cpu']]);
                    createChart("disk", time, [data['disk']]);
                }
                updateChart("disk", data['time'], data['disk']);
                updateChart("ram", data['time'], data['ram']);
                updateChart("cpu", data['time'], data['cpu']);
            @endif
            !noSpinner && $('.charts-card').find('.overlay').hide();
            statTimeout && clearTimeout(statTimeout);
            statTimeout = setTimeout(function(){
                if($("a[href=\"#usageTab\"]").hasClass("active")){
                    stats(true);
                }
            }, 500);
        })
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
            showSwal(error.message,'error',2000);
        })
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
    function getLogs(page = 1) {
        showSwal('{{__("Okunuyor...")}}','info');
        var form = new FormData();
        form.append('page',page);
        var query = $("#logQueryFilter").val();
        if(query.length !== 0){
            form.append('query',query);
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
                logTitleWrapper.append("<a class='list-group-item list-group-item-action' id='"+ current.id + "_title' href='#" + current.id + "_content' data-toggle='list' role='tab' aria-controls='home'>" + current.title + "</a>");
                logContentWrapper.append("<div class='tab-pane fade' id='" + current.id + "_content' role='tabpanel' aria-labelledby='" + current.id +"_title'><pre style='white-space:pre-wrap;'>" + current.message + "</pre></div>");
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
        $('#updateLogs').find('.updateLogsBody').append("\n"+packages[index]+" paketi kuruluyor. Lütfen bekleyin...<span id='"+packages[index]+"'></span>");
        request('{{route('server_install_package')}}', data, function (response) {
            checkPackage();
        }, function(response){
            var error = JSON.parse(response);
            showSwal(error.message,'error',2000);
        })
    }

    function updateProgress(){
        $('#updateLogs').find('.progress-info').text(index+"/"+packages.length+" "+packages[index]+" paketi kuruluyor...");
        var percent = (index/packages.length)*100;
        $('div[role=progressbar]').attr('aria-valuenow', percent);
        $('div[role=progressbar]').attr('style', 'width: '+percent+'%');
        if(packages.length !== index){
            $('div[role=progressbar]').closest('.progress').addClass('active');
        }else{
            $('div[role=progressbar]').closest('.progress').removeClass('active');
            $('#updateLogs').find('.progress-info').text("Tüm işlemler bitti.");
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
                $('#updateLogs').find('.updateLogsBody').append("\n"+"Tüm işlemler bitti.");
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