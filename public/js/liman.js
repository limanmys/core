function navbar(flag) {
    let sidebar = document.getElementsByClassName("sidebar")[0];
    if(flag){
        sidebar.style.width = "230px";
        toggle("visible");
    }else{
        sidebar.style.width = "60px";
        toggle("hidden");
    }

    function toggle(target){
        Array.prototype.forEach.call(document.querySelectorAll('.sidebar-name'), function (el) {
            el.style.visibility = target;
        });
    }
}

function request(url,data,next) {

    let old;

    let id = null;

    if(data instanceof FormData === false){
        id = (data !== null && data.hasAttribute('id')) ? data.getAttribute('id') : null;
        data = new FormData(data);
    }else{
        id = (data.has('id')) ? data.get('id') : null;
    }

    if(id != null){
        // Grab the element.
        let element = document.getElementById(id);

        //
        old = element.innerHTML;
        loading(element,"Yukleniyor");
    }
    let r = new XMLHttpRequest();
    r.open("POST",url);
    r.setRequestHeader('X-CSRF-TOKEN', csrf);
    r.setRequestHeader("Accept","text/json");
    setTimeout(function () {
        r.send(data);
    },300);
    r.onreadystatechange = function(){
        if(r.readyState === 4 && id != null){
            document.getElementById(id).innerHTML = old;
        }
        if(r.status === 200 && r.readyState === 4){
            return next(r.responseText);
        }
    };
    return false;
}

function reload(){
    location.reload();
}

function redirect(url){
    if(url === "")
        return;
    window.location.href = url;
}

function route(url){
    window.location.href = window.location.href + "/" + url;
}

function debug(data){
    console.log(data);
}

function back(){
    history.back();
}

function search(){
    let search_input = document.getElementById('search_input');
    if(search_input.value === ""){
        return;
    }
    let data = new FormData();
    data.append('text',search_input.value);
    request('arama',data,function(response){
        console.log(response);
    });
}

function loading(target_element,message){
    target_element.innerHTML = document.getElementsByClassName('loading')[0].innerHTML;
    document.getElementsByClassName('loading_message')[0].innerHTML = message;
}

function background(){
    console.log('hello');
}

function checkNotifications(){
    request('/bildirimler',null,function(response){
        document.getElementById("notificationDiv").innerHTML = response;
    });
}

window.onbeforeunload = function(){
  loading(document.getElementsByTagName('main')[0],'Liman Çalışıyor');
};

window.onload = function(){
    document.getElementById('notificationDiv').addEventListener('click',function(e){
        e.stopPropagation();
    });
    setInterval(checkNotifications,3000);
};

function alert(data) {
    let json = JSON.parse(data);
    document.getElementById(json["target_id"] + "_alert").innerHTML = json["message"];
    document.getElementById(json["target_id"] + "_alert").removeAttribute('hidden');
}

function dismissNotification(id){
    let data = new FormData();
    data.append('notification_id',id);
    request('/bildirim/oku',data,function(){
        checkNotifications();
    });
}

let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');