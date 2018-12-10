function request(url,data,next) {
    // var form = new FormData(data);
    var r = new XMLHttpRequest();
    r.open("POST",url);
    r.setRequestHeader('X-CSRF-TOKEN', csrf);
    r.send(data);
    r.onload = function(response){
        next(response);
    }
    return false;
}

function navbar(flag) {
    var sidebar = document.getElementsByClassName("sidebar")[0];
    var main = document.getElementsByTagName('main')[0];
    if (localStorage.getItem("state") === "expanded") {
        sidebar.style.marginLeft = "-270px";
        main.classList.remove('ml-sm-auto');
        main.classList.add('ml-md-5');
        (flag) ? localStorage.setItem("state", "minimized") : null;
    }else{
        sidebar.style.marginLeft = "0px";
        main.classList.remove('ml-md-5');
        main.classList.add('ml-sm-auto');
        (flag) ? localStorage.setItem("state", "expanded") : null;
    }
}

window.onload = function(){
    navbar(false);
}

function language(locale){
    var data = new FormData();
    data.append('locale',locale);
    request('/locale',data);
}

var csrf = document.getElementsByName('csrf-token')[0].getAttribute('content');