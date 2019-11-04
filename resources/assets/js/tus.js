function upload(option){
    if (typeof option.file === "undefined") {
        return;
    }
    let server_id = $('meta[name=server_id]').attr("content");
    let extension_id = $('meta[name=extension_id]').attr("content");
    var upload = new tus.Upload(option.file, {
        endpoint: "/upload?extension_id="+extension_id,
        retryDelays: [0, 1000, 3000, 5000, 10000],
        overridePatchMethod: true,
        chunkSize: 1000 * 1000,
        metadata: {
            filename: option.file.name,
            filetype: option.file.type
        },
        onError: option.onError,
        onProgress: option.onProgress,
        onSuccess: function(){
            option.onSuccess(upload);
        },
        headers: {
            "server_id": server_id,
            "extension_id": extension_id
        }
    });
    upload.start();
}