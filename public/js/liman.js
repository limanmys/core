function request(url,data,next) {

    let old;
    let id = null;
    if(data != null){
        id = data.getAttribute('id');
    }
    if(data instanceof FormData === false){
        data = new FormData(data);
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
    },1000);
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

function navbar(flag) {
    let sidebar = document.getElementsByClassName("sidebar")[0];
    let main = document.getElementsByTagName('main')[0];
    if (localStorage.getItem("state") === "e") {
        if(flag){
            sidebar.style.width = "60px";
            main.style.marginLeft = "70px";
            toggle("hidden");
            localStorage.setItem("state", "m");
        }else{
            sidebar.style.width = "230px";
            main.style.marginLeft = "240px";
            toggle("visible");
        }
    }else{
        if(flag){
            sidebar.style.width = "230px";
            main.style.marginLeft = "240px";
            toggle("visible");
            localStorage.setItem("state", "e");
        }else{
            sidebar.style.width = "60px";
            main.style.marginLeft = "70px";
            toggle("hidden");
        }
    }

    function toggle(target){
        Array.prototype.forEach.call(document.querySelectorAll('.sidebar-name'), function (el) {
            el.style.visibility = target;
        });
    }
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

window.onload = function(){
    navbar(false);
};

function loading(target_element,message){
    target_element.innerHTML = document.getElementsByClassName('loading')[0].innerHTML;
    document.getElementsByClassName('loading_message')[0].innerHTML = message;
}

window.onbeforeunload = function(){
  loading(document.getElementsByTagName('main')[0],'');
};
let csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');