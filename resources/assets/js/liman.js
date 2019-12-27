let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');

function request(url, data, next, error) {
    let id = null;

    if (data instanceof FormData === false) {
        id = (data !== null && data.hasAttribute('id')) ? data.getAttribute('id') : null;
        data = new FormData(data);
    } else {
        id = (data.has('id')) ? data.get('id') : null;
    }

    if (id != null) {
        Swal.fire({
            position: 'center',
            type: 'info',
            title: 'Yükleniyor...',
            showConfirmButton: false,
            allowOutsideClick : false,
        });
    }

    modalData = data;

    let server_id = $('meta[name=server_id]').attr("content");
    let extension_id = $('meta[name=extension_id]').attr("content");

    (server_id != "") && data.append('server_id',server_id);
    (extension_id != "") && data.append('extension_id',extension_id);

    let r = new XMLHttpRequest();
    r.open("POST", url);
    r.setRequestHeader('X-CSRF-TOKEN', csrf);
    r.setRequestHeader("Accept", "text/json");
    setTimeout(function () {
        r.send(data);
    }, 300);
    r.onreadystatechange = function () {
        if (r.readyState === 4) {
            if(!url.includes('bildirimler') && !url.includes('kontrol')){
                Swal.close();
            }
            if (id != null && (r.status !== 200 || r.status !== 300)) {
                message(r.responseText);
            }
            if( id != null){
                // loading();
            }
            if (r.getResponseHeader("content-type") !== "application/json") {
                return next(r.responseText);
            }
            let response = JSON.parse(r.responseText);
            switch (r.status) {
                case 200:
                    return next(r.responseText);
                    break;
                case 201:
                    if(error)
                      return error(r.responseText);
                    break;
                case 300:
                    return window.location = response["message"];
                    break;
                case 403:
                    Swal.fire({
                        position: 'center',
                        type: 'error',
                        title: response["message"],
                        showConfirmButton: false,
                    });
                    break;
                default:
                  if(error)
                    return error(r.responseText);
                  break;
            }

        }
    };
    return false;
}

function reload() {
  setTimeout(function() {
    location.reload();
  }, 1000);
}

function redirect(url) {
    if (url === "")
        return;
    window.location.href = url;
}

function nothing(){
    return false;
}

function toogleEdit(selector){
    $(selector).each(function(){
        if($(this).get(0).tagName === "SPAN") {
            $(this).changeElementType("input");
        }else{
            $(this).changeElementType("span");
        }
    });
}

(function($) {
    $.fn.changeElementType = function(newType) {
        var attrs = {};
        $.each(this[0].attributes, function(idx, attr) {
            attrs[attr.nodeName] = attr.nodeValue;
        });

        this.replaceWith(function() {
            if($(this).get(0).tagName === "SPAN"){
                return $("<" + newType + "/>", attrs).val($(this).html());
            }else{
                return $("<" + newType + "/>", attrs).html($(this).val());
            }
        });
    }
    
})(jQuery);

function debug(data) {
    console.log(data);
}

function back() {
    history.back();
}

function search() {
    let search_input = document.getElementById('search_input');
    if (search_input.value === "") {
        return;
    }
    let data = new FormData();
    data.append('text', search_input.value);
    request('arama', data, function (response) {
        console.log(response);
    });
}

function checkNotifications(exclude=null) {
    request('/bildirimler', new FormData(), function (response) {
        let json = JSON.parse(response);
        if(response["admin"] !== ""){
            renderNotifications(json["message"]["admin"],'admin','adminNotifications', exclude);
        }
        renderNotifications(json["message"]["user"],'user','userNotifications', exclude);
    });
}

function route(url) {
    window.location.href = window.location.href + "/" + url;
}

window.onbeforeunload = function () {
    Swal.fire({
        position: 'center',
        type: 'info',
        title: 'Yükleniyor...',
        showConfirmButton: false,
        allowOutsideClick : false,
    });
};

function message(data) {
    let json = JSON.parse(data);
    let modal = document.getElementsByClassName("modal fade show")[0];
    if (!modal) {
        return;
    }
    let modal_id = modal.getAttribute("id");
    let selector = $("#" + modal_id + "_alert");
    let color = "alert-info";
    switch (json["status"]) {
        case 200:
            color = "alert-success";
            break;
        case 300:
        case 301:
            color = "alert-success";
            break;
        default:
            color = "alert-danger";
    }
    if(json["message"]){
        selector.removeClass('alert-danger').removeAttr('hidden').removeClass('alert-success').addClass(color).html(json["message"]).fadeIn();
    }
}

function readNotifications(id) {
    let data = new FormData();
    request('/bildirimler/oku', data, function () {
        checkNotifications();
    });
}

function readSystemNotifications(id) {
    let data = new FormData();
    request('/bildirim/adminOku', data, function () {
        checkNotifications();
    });
}

let inputs =[];
let modalData = [];

function updateTable(extraVariableName = null){
    reload();
}

function addToTable(){
    reload();
}

function renderNotifications(data,type,target, exclude){
    let element = $("#" + target + " .menu");
    element.html("");
    //Set Count
    $("#" + target + "Count" ).html(data.length);
    data.forEach(notification => {
        let errors = [
            "error" , "health_problem", "liman_update"
        ];
        let color = (errors.includes(notification["type"])) ? "#f56954" : "#00a65a";
        element.append("<div class='dropdown-divider'></div><a class='dropdown-item' href='/bildirim/" + notification["id"] + "'>" + 
                "<span style='color: " + color + ";width: 100%'>"+ notification["title"] + "</span></a>");
        let displayedNots = [];
        if(localStorage.displayedNots){
            displayedNots = JSON.parse(localStorage.displayedNots);
        } 
        if(notification.id == exclude){
            return;
        }
        if(displayedNots.includes(notification.id)){
            return;
        }
        if(errors.includes(notification.type)){
            toastElement = toastr.error(notification.message, notification.title, {timeOut: 5000})
        }else{
            toastElement = toastr.success(notification.message, notification.title, {timeOut: 5000})
        }
        $(toastElement).click(function(){
            location.href = "/bildirim/" + notification.id
        });
        displayedNots.push(notification.id);
        localStorage.displayedNots = JSON.stringify(displayedNots);
    });
}

function activeTab(){
    let element = $('a[href="'+ window.location.hash +'"]');
    if(element){
        element.click();
    }
}

function fixer(val){
    if(!val)
        return val;
    return val.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'liman-key',
    wsHost: window.location.hostname,
    wssPort: 443,
    disableStats: true,
    encrypted: true,
    enabledTransports: ['ws', 'wss'],
    disabledTransports: ['sockjs', 'xhr_polling', 'xhr_streaming']
});

$(function () {
    bsCustomFileInput.init();
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    $(".modal").on('show.bs.modal', function(modal) {
        $("#" + modal.target.id + " .alert").fadeOut(0);
    });


    $(".dropdown-menu").on('click', 'a.dropdown-item', function(){
        $(this).closest('.dropdown').find('.dropdown-toggle').html($(this).text() + '<span class="caret"></span>');
    });

});
