
function request(url, data, next) {
    let id = null;

    if (data instanceof FormData === false) {
        id = (data !== null && data.hasAttribute('id')) ? data.getAttribute('id') : null;
        data = new FormData(data);
    } else {
        id = (data.has('id')) ? data.get('id') : null;
    }

    if (id != null) {
        // loading();
    }

    modalData = data;
    let r = new XMLHttpRequest();
    r.open("POST", url);
    r.setRequestHeader('X-CSRF-TOKEN', csrf);
    r.setRequestHeader("Accept", "text/json");
    setTimeout(function () {
        r.send(data);
    }, 300);
    r.onreadystatechange = function () {
        if (r.readyState === 4) {
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
        document.getElementById("notificationDiv").innerHTML = response;
    });
}

function route(url) {
    window.location.href = window.location.href + "/" + url;
}

window.onbeforeunload = function () {
    // loading();
};

window.onload = function () {
    // loading();
    // document.getElementById('notificationDiv').addEventListener('click', function (e) {
        // e.stopPropagation();
    // });
    // setInterval(checkNotifications, 3000);
};

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
            color = "alert-success";
            break;
        default:
            color = "alert-danger";
    }
    if(json["message"]){
        selector.removeClass('alert-danger').removeClass('alert-success').addClass(color).html(json["message"]).fadeIn();
    }
}

function dismissNotification(id) {
    let data = new FormData();
    data.append('notification_id', id);
    request('/bildirim/oku', data, function () {
        checkNotifications();
    });
}
let inputs =[];
let modalData = [];

function updateTable(){
    for(var i = 0;i< inputs.length ; i++){
        let element = inputs[i][0];
        $(element).html(modalData.get(element.id));
    }
    let current_modal = $('.modal:visible');
    if(current_modal.length){
        current_modal.modal('hide');
    }
}

function deleteFromTable(){

}


let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');