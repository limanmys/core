var csrf = document.getElementsByName("csrf-token")[0].getAttribute("content");
var customRequestData = [];
var modalData = [];

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
};

let language = document.getElementsByTagName("html")[0].getAttribute("lang");
let defaultLanguage = "tr";

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
  let id = null;

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
  if (url.includes("/engine/") || url.includes("/extensionRun/")) {
    data.append("lmntargetFunction", url.split("/engine/")[1]);
    url = window.location.origin + "/engine/";
  }

  const server_id = window.$("meta[name=server_id]").attr("content");
  const extension_id = window.$("meta[name=extension_id]").attr("content");

  server_id != "" && data.append("server_id", server_id);
  extension_id != "" && data.append("extension_id", extension_id);

  for (const [key, value] of Object.entries(customRequestData)) {
    data.append(key, value);
  }
  data.append("lmnbaseurl", window.location.origin);
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.forEach(function (value, key) {
    data.append(key, value);
  });

  const r = new XMLHttpRequest();
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
        case 300:
          return (window.location = response["message"]);
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
  const json = JSON.parse(data);
  const modal = document.getElementsByClassName("modal fade show")[0];
  if (!modal) {
    return;
  }
  const modal_id = modal.getAttribute("id");
  const selector = window.$("#" + modal_id + "_alert");
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

window.$(function () {
  window.$('[data-toggle="tooltip"]').tooltip({
    container: "body",
  });
  bsCustomFileInput.init();
  window.$(".select2").select2({
    theme: "bootstrap4",
    language: document.getElementsByTagName("html")[0].getAttribute("lang"),
  });

  window.$(".modal").on("show.bs.modal", function (modal) {
    window
      .$("#" + modal.target.id + " .alert")
      .not(".alert-info")
      .fadeOut(0);
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
    name: "de",
    options: {
      months: [
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
        "Dezember",
      ],
      shortMonths: [
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
        "Dez",
      ],
      days: [
        "Sonntag",
        "Montag",
        "Dienstag",
        "Mittwoch",
        "Donnerstag",
        "Freitag",
        "Samstag",
      ],
      shortDays: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
      toolbar: {
        exportToSVG: "SVG speichern",
        exportToPNG: "PNG speichern",
        exportToCSV: "CSV speichern",
        menu: "Menü",
        selection: "Auswahl",
        selectionZoom: "Auswahl vergrößern",
        zoomIn: "Vergrößern",
        zoomOut: "Verkleinern",
        pan: "Verschieben",
        reset: "Zoom zurücksetzen",
      },
    },
  },
];
