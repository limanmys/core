function upload(option) {
  if (typeof option.file === "undefined") {
    return;
  }
  const server_id = $("meta[name=server_id]").attr("content");
  const extension_id = $("meta[name=proxy_to]").attr("content") ? $("meta[name=proxy_to]").attr("content") : $("meta[name=extension_id]").attr("content");
  const token = $("meta[name=csrf-token]").attr("content");

  var upload = new tus.Upload(option.file, {
    endpoint:
      "/upload?extension_id=" +
      extension_id +
      "&server_id=" +
      server_id +
      "&x-csrf-token=" +
      token,
    retryDelays: [0, 1000, 3000, 5000, 10000],
    overridePatchMethod: true,
    chunkSize: 1000 * 1000,
    resume: false,
    metadata: {
      filename: option.file.name,
      filetype: option.file.type,
    },
    onError: option.onError,
    onProgress: option.onProgress,
    onSuccess: function () {
      let url_parts = upload.url.split("/");
      let key = url_parts[url_parts.length - 1];
      let data = new FormData();
      data.append("key", key);
      request("/upload_info", data, function (response) {
        try {
          json = JSON.parse(response);
          upload.info = json;
          option.onSuccess(upload);
        } catch (e) {}
      });
    },
    headers: {
      server_id: server_id,
      extension_id: extension_id,
      "x-csrf-token": token,
    },
  });
  upload.start();
}
