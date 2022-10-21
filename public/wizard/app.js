function activeNav() {
    jQuery(function ($) {
        var path = window.location.origin + window.location.pathname;
        $('nav div a').each(function () {
            if (this.href === path) {
                $(this).removeClass('w-full font-thin uppercase text-gray-500 dark:text-gray-200 flex items-center p-4 my-2 transition-colors duration-200 justify-start hover:text-blue-500');
                $(this).addClass('w-full font-thin uppercase text-blue-500 flex items-center p-4 my-2 transition-colors duration-200 justify-start bg-gradient-to-l from-gray-50 to-blue-100 border-l-4 border-blue-500 dark:from-gray-700 dark:to-gray-800 border-l-4 border-blue-500');
                $(this).find("svg").addClass("-ml-1")
            }
        });
    });
}
activeNav();

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
            title: loadingText(),
            toast: true,
            showConfirmButton: false
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
    r.send(data);
    r.onreadystatechange = function () {
        if (r.readyState === 4) {
            if (
                !url.includes("bildirimler") &&
                !url.includes("kontrol") &&
                id != null
            ) {

            }
            if (r.status == 200 && !r.responseText) {

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

                    break;
                default:
                    if (error) return error(r.responseText);
                    break;
            }
        }
    };
    return false;
}

var csrf = document.getElementsByName("csrf-token")[0].getAttribute("content");
var customRequestData = [];
var limanRecordRequests = false;
var limanRequestList = [];
