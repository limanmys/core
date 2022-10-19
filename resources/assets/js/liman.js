var csrf = document.getElementsByName("csrf-token")[0].getAttribute("content");
var customRequestData = [];
var limanRecordRequests = false;
var limanRequestList = [];

let english = {
  "Yükleniyor...": "Loading...",
  "Sonuç bulunamadı!": "No results found!",
  "Liman ID kopyalandı!": "Liman ID copied!",
  "Liman ID başarıyla kopyalandı.": "Liman ID has been copied successfully!"
}

let turkish = {}

let language = document.getElementsByTagName('html')[0].getAttribute('lang');
let defaultLanguage = "tr"
console.log(`🌟 Liman localization initialized: ${language}`)

let __ = (trans) => {
  if (language == "tr") {
    language = "turkish"
  }

  if (language == "en") {
    language = "english"
  }

  if (language === defaultLanguage && !eval(language).hasOwnProperty(trans) || !eval(language).hasOwnProperty(trans)) {
    return trans
  }

  return eval(language)[trans]
}

function showSwal(message, type, timer = false) {
  var config = {
    position: "bottom-end",
    type: type,
    title: message,
    toast: true,
    showConfirmButton: false,
    animation: false
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
      title: __("Yükleniyor..."),
      toast: true,
      showConfirmButton: false,
      animation: false
    });
  }

  modalData = data;
  if (
    url.startsWith(window.location.origin + "/engine/") ||
    url.startsWith("/engine/")
  ) {
    data.append("lmntargetFunction", url.split("/engine/")[1]);
    url = window.location.origin + "/engine/";
  }

  var server_id = window.$("meta[name=server_id]").attr("content");
  var extension_id = window.$("meta[name=extension_id]").attr("content");

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
  r.send(data);
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
      if (r.getResponseHeader("content-type") !== "application/json") {
        return next(r.responseText);
      }
      var response = JSON.parse(r.responseText);
      switch (r.status) {
        case 200:
          return next(r.responseText);
          break;
        case 300:
          return (window.location = response["message"]);
          break;
        case 403:
          showSwal(response["message"], "error", 2000);
          if (error) return error(r.responseText);
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
  window.$.each(limanRequestList[index]["form"], function (index, value) {
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
  window.$("#" + id).modal("hide");
}

function redirect(url) {
  if (url === "") return;
  window.location.href = url;
}

function nothing() {
  return false;
}

function toogleEdit(selector) {
  window.$(selector).each(function () {
    if (window.$(this).get(0).tagName === "SPAN") {
      window.$(this).changeElementType("input");
    } else {
      window.$(this).changeElementType("span");
    }
  });
}

(function ($) {
  window.$.fn.changeElementType = function (newType) {
    var attrs = {};
    window.$.each(this[0].attributes, function (idx, attr) {
      attrs[attr.nodeName] = attr.nodeValue;
    });

    this.replaceWith(function () {
      if (window.$(this).get(0).tagName === "SPAN") {
        return window.$("<" + newType + "/>", attrs).val(window.$(this).html());
      } else {
        return window.$("<" + newType + "/>", attrs).html(window.$(this).val());
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
    title: __("Yükleniyor..."),
    toast: true,
    showConfirmButton: false,
    animation: false,
  });
};

function message(data) {
  var json = JSON.parse(data);
  var modal = document.getElementsByClassName("modal fade show")[0];
  if (!modal) {
    return;
  }
  var modal_id = modal.getAttribute("id");
  var selector = window.$("#" + modal_id + "_alert");
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

function isJson(str) {
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}

function renderNotifications(data, type, target, exclude) {
  var element = window.$("#" + target + " .menu");
  element.html("");
  //Set Count
  window.$("#" + target + "Count").html(data.length);
  data.forEach((notification) => {
    let notificationTitle = notification["title"];
    let notificationMsg = notification["message"];
    if (isJson(notification["title"])) {
      let temp = JSON.parse(notification["title"])
      notificationTitle = temp[language];
    }
    if (isJson(notification["message"])) {
      let temp = JSON.parse(notification["message"])
      notificationMsg = temp[language];
    }
    var errors = ["error", "health_problem"];
    element.append([...window.$("<div />").addClass("dropdown-divider").append("<a />").find("a").addClass("dropdown-item").attr("href", `/bildirim/${notification["id"]}`).append("<span />").find("span").css("color", errors.includes(notification["type"]) ? "#f56954" : "#00a65a").css("width", "100%").text(notificationTitle).parents()].reverse())
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
      toastElement = toastr.error(notificationMsg, notificationTitle, {
        timeOut: 5000,
      });
    } else if (notification.type == "liman_update") {
      toastElement = toastr.warning(notificationMsg, notificationTitle, {
        timeOut: 5000,
      });
    } else {
      toastElement = toastr.success(notificationMsg, notificationTitle, {
        timeOut: 5000,
      });
    }
    window.$(toastElement).click(function () {
      location.href = "/bildirim/" + notification.id;
    });
    displayedNots.push(notification.id);
    localStorage.displayedNots = JSON.stringify(displayedNots);
  });
}

function activeTab() {
  var element = window.$('a[href="' + window.location.hash + '"]');
  if (element) {
    element.click();
  }
}

function fixer(val) {
  if (!val) return val;
  return val.replace(/</g, "&lt;").replace(/>/g, "&gt;");
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

window.$(function () {
  window.$('[data-toggle="tooltip"]').tooltip({
    container: "body"
  });
  bsCustomFileInput.init();
  window.$(".select2").select2({
    theme: "bootstrap4",
  });

  window.$(".modal").on("show.bs.modal", function (modal) {
    window.$("#" + modal.target.id + " .alert")
      .not(".alert-info")
      .fadeOut(0);
  });
});

function getSearchResults(query) {
  window.$.ajax({
    dataType: "json",
    method: "GET",
    url: "/liman_arama",
    data: {
      search_query: query
    },
    success: function (data, status, xhr) {
      if (data.length == 0) {
        window.$("#liman_search_results").append(`
            <a href="#">${__("Sonuç bulunamadı!")}</a>
          `);
      }

      data.forEach((el, i) => {
        window.$("#liman_search_results").append(window.$("<a />").attr("href", el.url).toggleClass("hovered", i == 0).text(el.name));
      });
    },
    error: function (jqXhr, textStatus, error) {
      console.log(error);
    }
  })
}

function liman_search() {
  let input = window.$("#liman_search_input");
  let result = window.$("#liman_search_results");

  if (input.val().length > 2) {
    result.html("");
    getSearchResults(input.val());
    result.fadeIn(250);
  }

  if (input.val() == "") {
    result.fadeOut(250);
  }
}

window.$(document).ready(function () {
  window.$("body").tooltip({ selector: '[data-toggle=tooltip]', container: 'body' });

  let input = window.$("#liman_search_input");
  let result = window.$("#liman_search_results");

  let idx = 0

  input.on("keydown", function (e) {
    if (e.keyCode == 13) {
      e.preventDefault();
      result.find(".hovered")[0].click();
    }

    if (!(e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 13)) {
      clearTimeout(window.$.data(this, 'timer'));
      let wait = setTimeout(liman_search, 150);
      window.$(this).data('timer', wait);
      idx = 0
    } else {
      e.preventDefault();
      if (!result.html().includes("Sonuç bulunamadı")) {
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

  window.$(document).on("click", function (event) {
    var $trigger = window.$("#liman_search");
    if ($trigger !== event.target && !$trigger.has(event.target).length) {
      result.fadeOut(250);
    }
  });
});

function handleCloseButton(target) {
  let selector = window.$("#" + target);

  selector.find(selector.find(".close")[0]).click(function (e) {
    e.preventDefault();
    e.stopPropagation();
    selector.modal("hide");
  });
}

window.$(document).on("shown.bs.modal", function (e) {
  handleCloseButton(window.$(e.target).attr("id"));
});

function copyToClipboard(elementId) {
  var aux = document.createElement("input");
  aux.setAttribute("value", document.getElementById(elementId).innerHTML);
  document.body.appendChild(aux);
  aux.select();
  document.execCommand("copy");
  document.body.removeChild(aux);
  Swal.fire(
    __('Liman ID kopyalandı!'),
    __('Liman ID başarıyla kopyalandı.'),
    'success'
  );
}

function collapseNav() {
  if (localStorage.getItem("collapse") == "true") {
    localStorage.setItem("collapse", "false");
  } else {
    localStorage.setItem("collapse", "true");
  }
}

window.$(document).ready(function () {
  if (localStorage.getItem("collapse") == "true") {
    window.$("body").addClass("sidebar-collapse");
  }
})