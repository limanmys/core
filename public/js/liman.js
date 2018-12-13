function request(url,data,next) {
    if(data != null)
        var form = new FormData(data);
    var r = new XMLHttpRequest();
    r.open("POST",url);
    r.setRequestHeader('X-CSRF-TOKEN', csrf);
    r.setRequestHeader("Accept","text/json");
    r.send(form);
    r.onreadystatechange = function(){
        if(r.status == 200 && r.readyState == 4){
            return next(r.responseText);
        }
    }
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

function debug(data){
    console.log(data);
}

function back(){
    history.back();
}

function navbar(flag) {
    var sidebar = document.getElementsByClassName("sidebar")[0];
    var main = document.getElementsByTagName('main')[0];
    if (localStorage.getItem("state") === "e") {
        sidebar.style.marginLeft = "-270px";
        main.classList.remove('ml-sm-auto');
        main.classList.add('ml-md-5');
        (flag) ? localStorage.setItem("state", "m") : null;
    }else{
        sidebar.style.marginLeft = "0px";
        main.classList.remove('ml-md-5');
        main.classList.add('ml-sm-auto');
        (flag) ? localStorage.setItem("state", "e") : null;
    }
}

window.onload = function(){
    navbar(false);
}

var csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');