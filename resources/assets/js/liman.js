var csrf = document.getElementsByName("csrf-token")[0].getAttribute("content");
var customRequestData = [];
var limanRecordRequests = false;
var limanRequestList = [];

let english = {
  "Yükleniyor...": "Loading...",
  "Sonuç bulunamadı!": "No results found!",
  "Liman ID kopyalandı!": "Liman ID copied!",
  "Liman ID başarıyla kopyalandı.": "Liman ID has been copied successfully!",
  "Okunmamış bildiriminiz bulunmamaktadır.":
    "You have been read all notifications.",
  "/turkce.json": "/english.json",
  "Tümünü Seç": "Select All",
  "Tümünü Kaldır": "Remove All",
  "CPU Kullanımı": "CPU Usage",
  "RAM Kullanımı": "RAM Usage",
  "Disk Kullanımı": "Disk Usage",
};

let turkish = {};

let deutsch = {
  "Yükleniyor...": "Laden...",
  "Sonuç bulunamadı!": "Keine Ergebnisse gefunden!",
  "Liman ID kopyalandı!": "Liman-ID kopiert!",
  "Liman ID başarıyla kopyalandı.": "Liman-ID wurde erfolgreich kopiert!",
  "Okunmamış bildiriminiz bulunmamaktadır.":
    "Sie haben alle Benachrichtigungen gelesen.",
  "/turkce.json": "/deutsch.json",
  "Tümünü Seç": "Alles auswählen",
  "Tümünü Kaldır": "Alles entfernen",
  "CPU Kullanımı": "CPU-Auslastung",
  "RAM Kullanımı": "RAM-Auslastung",
  "Disk Kullanımı": "Disk-Auslastung",
}

let language = document.getElementsByTagName("html")[0].getAttribute("lang");
let defaultLanguage = "tr";
console.log(`🌟 Liman localization initialized: ${language}`);

let __ = (trans) => {
  if (language == "tr") {
    language = "turkish";
  }

  if (language == "en") {
    language = "english";
  }

  if (language == "de") {
    language = "deutsch";
  }

  if (
    (language === defaultLanguage && !eval(language).hasOwnProperty(trans)) ||
    !eval(language).hasOwnProperty(trans)
  ) {
    return trans;
  }

  return eval(language)[trans];
};

function showSwal(message, type, timer = false) {
  var config = {
    position: "bottom-end",
    type: type,
    title: message,
    toast: true,
    showConfirmButton: false,
    animation: false,
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
      animation: false,
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
  request("/bildirimler/oku", data, function () {});
  setTimeout(function () {
    var element = window.$("#userNotifications .menu");
    element
      .parent()
      .find(".notif-action")
      .addClass("d-none")
      .removeClass("d-block");
    element.html(`<a class="dropdown-item d-flex align-items-start no-notif">
        <div class="text" style="width: 100% !important; padding: 15px 0">
            <h4 style="text-align: center; color: grey; font-size: 12px; text-transform: uppercase">${__(
              "Okunmamış bildiriminiz bulunmamaktadır."
            )}</h4>
        </div>
    </a>`);
  }, 200);
}

function readSystemNotifications(id) {
  var data = new FormData();
  request("/bildirim/adminOku", data, function () {});
  setTimeout(function () {
    var element = window.$("#adminNotifications .menu");
    element
      .parent()
      .find(".notif-action")
      .addClass("d-none")
      .removeClass("d-block");
    element.html(`<a class="dropdown-item d-flex align-items-start no-notif">
        <div class="text" style="width: 100% !important; padding: 15px 0">
            <h4 style="text-align: center; color: grey; font-size: 12px; text-transform: uppercase">${__(
              "Okunmamış bildiriminiz bulunmamaktadır."
            )}</h4>
        </div>
    </a>`);
  }, 200);
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

const limanEscapeHtml = (unsafe) => {
  if (typeof unsafe === "string" || unsafe instanceof String)
    return unsafe
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  else return unsafe;
};

function renderNotifications(data, type, target, exclude) {
  var element = window.$("#" + target + " .menu");
  element.html("");
  //Set Count
  window.$("#" + target + "Count").html(data.length);
  let language = document.getElementsByTagName("html")[0].getAttribute("lang");
  data.forEach((notification) => {
    element
      .parent()
      .find(".notif-action")
      .removeClass("d-none")
      .addClass("d-block");
    element.parent().find(".no-notif").removeClass("d-flex").addClass("d-none");
    let notificationTitle = notification["title"];
    let notificationMsg = notification["message"];
    if (isJson(notification["title"])) {
      let temp = JSON.parse(notification["title"]);
      if (temp[language] != undefined) {
        notificationTitle = temp[language];
      } else {
        notificationTitle = temp["en"];
      }
    }
    if (isJson(notification["message"])) {
      let temp = JSON.parse(notification["message"]);
      if (temp[language] != undefined) {
        notificationMsg = temp[language];
      } else {
        notificationMsg = temp["en"];
      }
    }
    var errors = ["error", "health_problem"];
    let color = errors.includes(notification["type"]) ? "color: #ff4444" : "";
    let html = `<a class="dropdown-item d-flex align-items-start" onclick="window.location.href = '/bildirim/${
      notification["id"]
    }'" href="/bildirim/${notification["id"]}">
        <div class="text">
            <h4 style="${color}">${limanEscapeHtml(notificationTitle)}</h4>
            <span class="time">${notification["humanDate"]}</span>
        </div>
    </a>`;
    element.append(html);
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

    let toastOptions = {
      title: notificationTitle,
      subtitle: "Liman",
      body: notificationMsg,
      delay: 5000,
      autohide: true,
    };

    if (errors.includes(notification.type)) {
      $(document).Toasts("create", {
        ...toastOptions,
        icon: "fa-solid fa-triangle-exclamation",
        class: "bg-danger",
      });
    } else if (notification.type == "liman_update") {
      $(document).Toasts("create", {
        ...toastOptions,
        icon: "fa-solid fa-triangle-exclamation",
        class: "bg-warning",
      });
    } else {
      $(document).Toasts("create", {
        ...toastOptions,
        icon: "fas fa-check",
        class: "bg-success",
      });
    }

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
    container: "body",
  });
  bsCustomFileInput.init();
  window.$(".select2").select2({
    theme: "bootstrap4",
    language: document.getElementsByTagName("html")[0].getAttribute("lang")
  });

  window.$(".modal").on("show.bs.modal", function (modal) {
    window
      .$("#" + modal.target.id + " .alert")
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
      search_query: query,
    },
    success: function (data, status, xhr) {
      if (data.length == 0) {
        window.$("#liman_search_results").append(`
            <a href="#">${__("Sonuç bulunamadı!")}</a>
          `);
      }

      data.forEach((el, i) => {
        window.$("#liman_search_results").append(
          window
            .$("<a />")
            .attr("href", el.url)
            .toggleClass("hovered", i == 0)
            .text(el.name)
        );
      });
    },
    error: function (jqXhr, textStatus, error) {
      console.log(error);
    },
  });
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
  window
    .$("body")
    .tooltip({ selector: "[data-toggle=tooltip]", container: "body" });

  let input = window.$("#liman_search_input");
  let result = window.$("#liman_search_results");

  let idx = 0;

  input.on("keydown", function (e) {
    if (e.keyCode == 13) {
      e.preventDefault();
      result.find(".hovered")[0].click();
    }

    if (!(e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 13)) {
      clearTimeout(window.$.data(this, "timer"));
      let wait = setTimeout(liman_search, 150);
      window.$(this).data("timer", wait);
      idx = 0;
    } else {
      e.preventDefault();
      if (!result.html().includes("Sonuç bulunamadı")) {
        let results = result.find("a");
        let len = results.length - 1;
        if (e.keyCode == 38) {
          if (idx <= 0) {
            idx = 0;
          } else {
            idx--;
            result.find(results[idx + 1]).removeClass("hovered");
            result.find(results[idx]).addClass("hovered");
          }
        }

        if (e.keyCode == 40) {
          if (idx >= len) {
            idx = len;
          } else {
            idx++;
            result.find(results[idx - 1]).removeClass("hovered");
            result.find(results[idx]).addClass("hovered");
          }
        }
      }
    }
  });

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
    __("Liman ID kopyalandı!"),
    __("Liman ID başarıyla kopyalandı."),
    "success"
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
});

function isJson(str) {
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}

function dataTablePresets(type) {
  if (type == "normal") {
    return {
      bFilter: true,
      language: {
        url: __("/turkce.json"),
      },
    };
  } else if (type == "multiple") {
    return {
      bFilter: true,
      select: {
        style: "multi",
        selector: "td:not(.table-menu)",
      },
      dom: "Blfrtip",
      buttons: {
        buttons: [
          { extend: "selectAll", className: "btn btn-xs btn-primary mr-1" },
          { extend: "selectNone", className: "btn btn-xs btn-primary mr-1" },
        ],
        dom: {
          button: { className: "btn" },
        },
      },
      language: {
        url: __("/turkce.json"),
        buttons: {
          selectAll: __("Tümünü Seç"),
          selectNone: __("Tümünü Kaldır"),
        },
      },
    };
  }
}

const ApexChartLocalization = [
  {
    name: "en",
    options: {
      months: [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ],
      shortMonths: [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ],
      days: [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ],
      shortDays: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
      toolbar: {
        exportToSVG: "Download SVG",
        exportToPNG: "Download PNG",
        menu: "Menu",
        selection: "Selection",
        selectionZoom: "Selection Zoom",
        zoomIn: "Zoom In",
        zoomOut: "Zoom Out",
        pan: "Panning",
        reset: "Reset Zoom",
      },
    },
  },
  {
    name: "tr",
    options: {
      months: [
        "Ocak",
        "Şubat",
        "Mart",
        "Nisan",
        "Mayıs",
        "Haziran",
        "Temmuz",
        "Ağustos",
        "Eylül",
        "Ekim",
        "Kasım",
        "Aralık",
      ],
      shortMonths: [
        "Oca",
        "Şub",
        "Mar",
        "Nis",
        "May",
        "Haz",
        "Tem",
        "Ağu",
        "Eyl",
        "Eki",
        "Kas",
        "Ara",
      ],
      days: [
        "Pazar",
        "Pazartesi",
        "Salı",
        "Çarşamba",
        "Perşembe",
        "Cuma",
        "Cumartesi",
      ],
      shortDays: ["Paz", "Pzt", "Sal", "Çar", "Per", "Cum", "Cmt"],
      toolbar: {
        exportToSVG: "SVG İndir",
        exportToPNG: "PNG İndir",
        exportToCSV: "CSV İndir",
        menu: "Menü",
        selection: "Seçim",
        selectionZoom: "Seçim Yakınlaştır",
        zoomIn: "Yakınlaştır",
        zoomOut: "Uzaklaştır",
        pan: "Kaydır",
        reset: "Yakınlaştırmayı Sıfırla",
      },
    },
  },
  {
    "name": "de",
    "options": {
      "months": [
        "Januar",
        "Februar",
        "März",
        "April",
        "Mai",
        "Juni",
        "Juli",
        "August",
        "September",
        "Oktober",
        "November",
        "Dezember"
      ],
      "shortMonths": [
        "Jan",
        "Feb",
        "Mär",
        "Apr",
        "Mai",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Okt",
        "Nov",
        "Dez"
      ],
      "days": [
        "Sonntag",
        "Montag",
        "Dienstag",
        "Mittwoch",
        "Donnerstag",
        "Freitag",
        "Samstag"
      ],
      "shortDays": ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
      "toolbar": {
        "exportToSVG": "SVG speichern",
        "exportToPNG": "PNG speichern",
        "exportToCSV": "CSV speichern",
        "menu": "Menü",
        "selection": "Auswahl",
        "selectionZoom": "Auswahl vergrößern",
        "zoomIn": "Vergrößern",
        "zoomOut": "Verkleinern",
        "pan": "Verschieben",
        "reset": "Zoom zurücksetzen"
      }
    }
  }
];

/* === INDEX CHARTS START === */

var stats;
var server_stats;
const CHART_INTERVAL = 2500;
const CHART_NOW = new Date();
CHART_NOW.setSeconds(CHART_NOW.getSeconds() - 5);
let IS_RENDERED = false;
let CHART_FAST_LOAD = 0;
var CHARTS = {
  CPU: {
    title: __("CPU Kullanımı"),
    id: "cpuChart",
    key: "cpu",
    chart: null,
    data: [[CHART_NOW, 0]],
    colors: ["#06d48b"],
  },
  RAM: {
    title: __("RAM Kullanımı"),
    id: "ramChart",
    key: "ram",
    chart: null,
    data: [[CHART_NOW, 0]],
    colors: ["#06b6d4"],
  },
  IO: {
    title: __("Disk Kullanımı"),
    id: "diskChart",
    key: "io",
    chart: null,
    data: [[CHART_NOW, 0]],
    colors: ["#064fd4"],
  },
  NETWORK: {
    title: __("Network"),
    id: "networkChart",
    key: "network",
    chart: null,
    data: {
      upload: [[CHART_NOW, 0]],
      download: [[CHART_NOW, 0]],
    },
    colors: ["#008ffb", "#00e396"],
  },
};

function renderChart(obj, network = false) {
  var options = {
    series: [
      !network
        ? {
            data: obj.data,
            name: obj.title,
          }
        : ({
            data: obj.data.upload,
            name: "Up",
          },
          {
            data: obj.data.download,
            name: "Down",
          }),
    ],
    chart: {
      locales: ApexChartLocalization,
      defaultLocale: document.documentElement.lang,
      height: 200,
      type: "area",
      fontFamily: "Inter",
      animations: {
        enabled: true,
        easing: "linear",
        dynamicAnimation: {
          enabled: true,
          speed: 1,
        },
      },
      toolbar: {
        show: false,
      },
      zoom: {
        enabled: false,
      },
    },
    fill: {
      colors: obj.colors,
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      curve: "smooth",
    },
    title: {
      text: `${obj.title} %${stats[obj.key]}`,
      align: "left",
      style: {
        fontWeight: 600,
      },
    },
    legend: {
      show: false,
    },
    markers: {
      size: 0,
    },
    xaxis: {
      type: "datetime",
      range: 60000,
      labels: {
        show: false,
      },
      tooltip: {
        enabled: false,
      },
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return `%${val}`;
        },
      },
    },
    yaxis: {
      max: 100,
      min: 0,
      tickAmount: 5,
      labels: {
        formatter: (value) => {
          return value;
        },
      },
    },
  };
  var chart = new ApexCharts(document.querySelector(`#${obj.id}`), options);
  chart.render();
  obj.chart = chart;
}

function updateChart(obj) {
  obj.data.push([Date.now(), stats[obj.key]]);

  let options = {
    title: {
      text: `${obj.title} %${stats[obj.key]}`,
    },
    series: [
      {
        data: obj.data,
      },
    ],
  };

  if (CHART_FAST_LOAD < 4) {
    obj.chart.updateOptions(options);
    CHART_FAST_LOAD++;
  } else {
    obj.chart.updateOptions({
      ...options,
      chart: {
        animations: {
          dynamicAnimation: {
            speed: 3500,
          },
        },
      },
    });
  }
}

function updateNetworkChart(obj) {
  let date = Date.now();

  obj.data.download.push([date, stats[obj.key].download]);
  obj.data.upload.push([date, stats[obj.key].upload]);

  let options = {
    title: {
      text: `${obj.title} Up: ${stats[obj.key].upload} kb/s Down: ${
        stats[obj.key].download
      } kb/s`,
    },
    yaxis: {
      min: 0,
      tickAmount: 6,
      labels: {
        formatter: (value) => {
          return value.toFixed(0);
        },
      },
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return `${val} kb/s`;
        },
      },
    },
    legend: {
      show: false,
    },
    series: [
      {
        data: obj.data.upload,
        name: "Upload",
      },
      {
        data: obj.data.download,
        name: "Download",
      },
    ],
  };

  if (CHART_FAST_LOAD < 4) {
    obj.chart.updateOptions(options);
    CHART_FAST_LOAD++;
  } else {
    obj.chart.updateOptions({
      ...options,
      chart: {
        animations: {
          dynamicAnimation: {
            speed: 3500,
          },
        },
      },
    });
  }
}

/* === INDEX CHARTS END === */
