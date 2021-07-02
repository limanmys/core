var csrf = document.getElementsByName("csrf-token")[0].getAttribute("content");
var customRequestData = [];
var limanRecordRequests = false;
var limanRequestList = [];

function showSwal(message, type, timer = false) {
  var config = {
    position: "bottom-end",
    type: type,
    title: message,
    toast: true,
    showConfirmButton: false,
  };
  if (timer) {
    config["timer"] = timer;
  }
  Swal.fire(config);
}

function request(url, data, next, error, requestType = "POST") {
  var id = null;

  if (data instanceof FormData === false) {
    id =
      data !== null && data.hasAttribute("id") ? data.getAttribute("id") : null;
    data = new FormData(data);
  } else {
    id = data.has("id") ? data.get("id") : null;
  }

  if (id != null) {
    Swal.fire({
      position: "bottom-end",
      type: "info",
      title: "Yükleniyor...",
      toast: true,
      showConfirmButton: false
    });
  }

  modalData = data;
  if (
    url.startsWith(window.location.origin + "/extensionRun/") ||
    url.startsWith("/extensionRun/")
  ) {
    data.append("lmntargetFunction", url.split("/extensionRun/")[1]);
    url = window.location.origin + "/extensionRun/";
  }

  var server_id = $("meta[name=server_id]").attr("content");
  var extension_id = $("meta[name=extension_id]").attr("content");

  server_id != "" && data.append("server_id", server_id);
  extension_id != "" && data.append("extension_id", extension_id);

  for (const [key, value] of Object.entries(customRequestData)) {
    data.append(key, value);
  }
  data.append("lmnbaseurl", window.location.origin);
  data.append("limanJSRequest", true);
  var urlParams = new URLSearchParams(window.location.search);
  urlParams.forEach(function (value, key) {
    data.append(key, value);
  });

  if (limanRecordRequests) {
    var parsed = {};
    for (var pair of data.entries()) {
      parsed[pair[0]] = pair[1];
    }
    limanRequestList.push({
      target: data.get("lmntargetFunction"),
      url: url,
      form: parsed,
    });
  }

  var r = new XMLHttpRequest();
  r.open(requestType, url);
  r.setRequestHeader("X-CSRF-TOKEN", csrf);
  r.setRequestHeader("Accept", "text/json");
  setTimeout(function () {
    r.send(data);
  }, 300);
  r.onreadystatechange = function () {
    if (r.readyState === 4) {
      if (
        !url.includes("bildirimler") &&
        !url.includes("kontrol") &&
        id != null
      ) {
        Swal.close();
      }
      if (r.status == 200 && !r.responseText) {
        showSwal("İstek zaman aşımına uğradı!", "error", 2000);
      }
      if (id != null && (r.status !== 200 || r.status !== 300)) {
        message(r.responseText);
      }
      if (id != null) {
        // loading();
      }
      if (r.getResponseHeader("content-type") !== "application/json") {
        return next(r.responseText);
      }
      var response = JSON.parse(r.responseText);
      switch (r.status) {
        case 200:
          return next(r.responseText);
          break;
        case 201:
          if (error) return error(r.responseText);
          break;
        case 300:
          return (window.location = response["message"]);
          break;
        case 403:
          showSwal(response["message"], "error", 2000);
          break;
        default:
          if (error) return error(r.responseText);
          break;
      }
    }
  };
  return false;
}

function handlerCleanup(name) {
  setTimeout(() => {
    window[name] = undefined;
    delete window[name];
  }, 1000);
}

function isJson(str) {
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}

function limanRequestBuilder(index, token) {
  var str = "curl";
  $.each(limanRequestList[index]["form"], function (index, value) {
    str += " -F '" + index + "=" + value + "'";
  });

  return (
    str +
    ' -H "Content-Type: multipart/form-data" -H "Accept: application/json" -H "liman-token: ' +
    token +
    '" -X POST ' +
    limanRequestList[index]["url"]
  );
}

function reload() {
  setTimeout(function () {
    location.reload();
  }, 1000);
}

function closeCurrentModal(id) {
  $("#" + id).modal("hide");
}

function redirect(url) {
  if (url === "") return;
  window.location.href = url;
}

function nothing() {
  return false;
}

function toogleEdit(selector) {
  $(selector).each(function () {
    if ($(this).get(0).tagName === "SPAN") {
      $(this).changeElementType("input");
    } else {
      $(this).changeElementType("span");
    }
  });
}

(function ($) {
  $.fn.changeElementType = function (newType) {
    var attrs = {};
    $.each(this[0].attributes, function (idx, attr) {
      attrs[attr.nodeName] = attr.nodeValue;
    });

    this.replaceWith(function () {
      if ($(this).get(0).tagName === "SPAN") {
        return $("<" + newType + "/>", attrs).val($(this).html());
      } else {
        return $("<" + newType + "/>", attrs).html($(this).val());
      }
    });
  };
})(jQuery);

function debug(data) {
  console.log(data);
}

function back() {
  history.back();
}

function search() {
  var search_input = document.getElementById("search_input");
  if (search_input.value === "") {
    return;
  }
  var data = new FormData();
  data.append("text", search_input.value);
  request("arama", data, function (response) {
    console.log(response);
  });
}

function checkNotifications(exclude = null) {
  request("/bildirimler", new FormData(), function (response) {
    var json = JSON.parse(response);
    if (response["admin"] !== "") {
      renderNotifications(
        json["message"]["admin"],
        "admin",
        "adminNotifications",
        exclude
      );
    }
    renderNotifications(
      json["message"]["user"],
      "user",
      "userNotifications",
      exclude
    );
  });
}

function route(url) {
  window.location.href = window.location.href + "/" + url;
}

window.onbeforeunload = function () {
  Swal.fire({
    position: "bottom-end",
    type: "info",
    title: "Yükleniyor...",
    toast: true,
    showConfirmButton: false
  });
};

function message(data) {
  var json = JSON.parse(data);
  var modal = document.getElementsByClassName("modal fade show")[0];
  if (!modal) {
    return;
  }
  var modal_id = modal.getAttribute("id");
  var selector = $("#" + modal_id + "_alert");
  var color = "alert-info";
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
  if (json["message"]) {
    selector
      .removeClass("alert-danger")
      .removeAttr("hidden")
      .removeClass("alert-success")
      .addClass(color)
      .html(json["message"])
      .fadeIn();
  }
}

function readNotifications(id) {
  var data = new FormData();
  request("/bildirimler/oku", data, function () {
    checkNotifications();
  });
}

function readSystemNotifications(id) {
  var data = new FormData();
  request("/bildirim/adminOku", data, function () {
    checkNotifications();
  });
}

var inputs = [];
var modalData = [];

function updateTable(extravariableName = null) {
  reload();
}

function addToTable() {
  reload();
}

function renderNotifications(data, type, target, exclude) {
  var element = $("#" + target + " .menu");
  element.html("");
  //Set Count
  $("#" + target + "Count").html(data.length);
  data.forEach((notification) => {
    var errors = ["error", "health_problem"];
    var color = errors.includes(notification["type"]) ? "#f56954" : "#00a65a";
    element.append(
      "<div class='dropdown-divider'></div><a class='dropdown-item' href='/bildirim/" +
        notification["id"] +
        "'>" +
        "<span style='color: " +
        color +
        ";width: 100%'>" +
        notification["title"] +
        "</span></a>"
    );
    var displayedNots = [];
    if (localStorage.displayedNots) {
      displayedNots = JSON.parse(localStorage.displayedNots);
    }
    if (notification.id == exclude) {
      return;
    }
    if (displayedNots.includes(notification.id)) {
      return;
    }
    if (errors.includes(notification.type)) {
      toastElement = toastr.error(notification.message, notification.title, {
        timeOut: 5000,
      });
    } else if (notification.type == "liman_update") {
      toastElement = toastr.warning(notification.message, notification.title, {
        timeOut: 5000,
      });
    } else {
      toastElement = toastr.success(notification.message, notification.title, {
        timeOut: 5000,
      });
    }
    $(toastElement).click(function () {
      location.href = "/bildirim/" + notification.id;
    });
    displayedNots.push(notification.id);
    localStorage.displayedNots = JSON.stringify(displayedNots);
  });
}

function activeTab() {
  var element = $('a[href="' + window.location.hash + '"]');
  if (element) {
    element.click();
  }
}

function fixer(val) {
  if (!val) return val;
  return val.replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
var currentlyDark =
  window.localStorage.getItem("dark") == "true" ? true : false;

function toggleDarkMode() {
  currentlyDark = !currentlyDark;
  if (currentlyDark == true) {
    setDarkMode();
  } else {
    setLightMode();
  }
}

function setDarkMode() {
  document.getElementById("darkModeIcon").className = "fas fa-moon";
  var link = document.createElement("link");
  link.href = "/css/dark.css";
  link.type = "text/css";
  link.id = "darkModeCss";
  link.rel = "stylesheet";
  link.media = "screen,print";
  document.getElementsByTagName("head")[0].appendChild(link);
  window.localStorage.setItem("dark", "true");
}

function setLightMode() {
  document.getElementById("darkModeIcon").className = "fas fa-sun";
  $("#darkModeCss").remove();
  window.localStorage.setItem("dark", "false");
}

window.Echo = new Echo({
  broadcaster: "pusher",
  key: "liman-key",
  wsHost: window.location.hostname,
  wssPort: 443,
  disableStats: true,
  encrypted: true,
  enabledTransports: ["ws", "wss"],
  disabledTransports: ["sockjs", "xhr_polling", "xhr_streaming"],
});

$(function () {
  $('[data-toggle="tooltip"]').tooltip();
  bsCustomFileInput.init();
  $(".select2").select2({
    theme: "bootstrap4",
  });

  $(".modal").on("show.bs.modal", function (modal) {
    $("#" + modal.target.id + " .alert")
      .not(".alert-info")
      .fadeOut(0);
  });
});

function getSearchResults (query) {
    $.ajax({
      dataType: "json",
      method: "GET",
      url: "/liman_arama",
      data: {
        search_query: query
      },
      success: function (data, status, xhr) 
      {
        if (data.length == 0) {
          $("#liman_search_results").append(`
            <a href="#">Sonuç bulunamadı</a>
          `);
        }

        let firstone = 0
        data.forEach(el => {
          if (firstone == 0) {
            $("#liman_search_results").append(`
              <a href="${el.url}" class="hovered">${el.name}</a>
            `);
            firstone++
          } else {
            $("#liman_search_results").append(`
              <a href="${el.url}">${el.name}</a>
            `);
          }
        });
      },
      error: function (jqXhr, textStatus, error) 
      {
        console.log(error);
      }
    })
}

function liman_search() {
  let input = $("#liman_search_input");
  let result = $("#liman_search_results");
  
  if (input.val().length > 2)
  {
    result.html("");
    getSearchResults(input.val());
    result.fadeIn(250);
  }

  if (input.val() == "") 
  {
    result.fadeOut(250);
  }
}

$(document).ready(function() {
  $("body").tooltip({ selector: '[data-toggle=tooltip]' });
  
  let input = $("#liman_search_input");
  let result = $("#liman_search_results");

  let idx = 0

  input.on("keydown", function ( e ) {
    if (e.keyCode == 13)
    {
      e.preventDefault();
      result.find(".hovered")[0].click();
    }

    if(!(e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 13)) {
      clearTimeout($.data(this, 'timer'));
      let wait = setTimeout(liman_search, 150);
      $(this).data('timer', wait);
      idx = 0
    } else {
      e.preventDefault();
      if(!result.html().includes("Sonuç bulunamadı")) {
        let results = result.find("a")
        let len = results.length - 1
        if (e.keyCode == 38) {
          if (idx <= 0) {
            idx = 0
          } else {
            idx--
            result.find(results[idx + 1]).removeClass("hovered");
            result.find(results[idx]).addClass("hovered");
          }
        }

        if (e.keyCode == 40) {
          if (idx >= len) {
            idx = len
          } else {
            idx++
            result.find(results[idx - 1]).removeClass("hovered");
            result.find(results[idx]).addClass("hovered");
          }
        }
      }
    }
    
  })

  $(document).on("click", function(event){
    var $trigger = $("#liman_search");
    if($trigger !== event.target && !$trigger.has(event.target).length)
    {
      result.fadeOut(250);
    }            
  });
});