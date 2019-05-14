let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');

function request(url, data, next) {
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
            Swal.close();
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
                case 300:
                    return window.location = response["message"];
            }
        }
    };
    return false;
}

function reload() {
    location.reload();
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

function checkNotifications() {
    request('/bildirimler', new FormData(), function (response) {
        document.getElementById("notifications-menu").innerHTML = response;
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

$(".modal").on('show.bs.modal', function(modal) {
    $("#" + modal.target.id + " .alert").fadeOut(0);
});

function message(data) {
    let json = JSON.parse(data);
    let modal = document.getElementsByClassName("modal fade in")[0];
    if (!modal) {
        return;
    }
    let modal_id = modal.getAttribute("id");
    let selector = $("#" + modal_id + "_alert");
    let color = "alert-info";
    switch (json["status"]) {
        case 200:
        case 300:
        case 301:
            color = "alert-success";
            break;
        default:
            color = "alert-danger";
    }
    if(json["message"]){
        selector.removeClass('alert-danger').removeClass('alert-success').addClass(color).html(json["message"]).fadeIn();
    }
}

function readNotifications(id) {
    let data = new FormData();
    request('/bildirimler/oku', data, function () {
        checkNotifications();
    });
}

let inputs =[];
let modalData = [];

function updateTable(extraVariableName = null){
    for(let i = 0;i< inputs.length ; i++){
        let element = inputs[i][0];
        if(!extraVariableName.startsWith('{')){
            $(element).html(modalData.get(element.id + extraVariableName));
        }else{
            $(element).html(modalData.get(element.id));
        }
    }
    let current_modal = $('.modal:visible');
    if(current_modal.length){
        current_modal.modal('hide');
    }
}

function addToTable(){
    let newRow = [];
    let selector = $('table tr:eq(1) td');
    if(selector.length === 0){
        location.reload();
    }
    console.log(selector);
    selector.each(function(){
        newRow.push(modalData.get(this.id));
    });
    $('table').not('.notDataTable').DataTable().row.add(newRow).draw(true);
    let current_modal = $('.modal:visible');
    if(current_modal.length){
        current_modal.modal('hide');
    }
}