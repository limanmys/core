function read_as_text_with_decoder(file, callback, decoder) {
    let reader = new window.FileReader();
    if (decoder === undefined) {
        decoder = new window.TextDecoder('utf-8', {'fatal': true});
    }

    reader.onload = function () {
        let text;
        try {
            text = decoder.decode(reader.result);
        } catch (TypeError) {
            console.log('Decoding error happened.');
        } finally {
            if (callback) {
                callback(text);
            }
        }
    };

    reader.onerror = function (e) {
        console.error(e);
    };

    reader.readAsArrayBuffer(file);
}


function read_as_text_with_encoding(file, callback, encoding) {
    let reader = new window.FileReader();

    if (encoding === undefined) {
        encoding = 'utf-8';
    }

    reader.onload = function () {
        if (callback) {
            callback(reader.result);
        }
    };

    reader.onerror = function (e) {
        console.error(e);
    };

    reader.readAsText(file, encoding);
}


function read_file_as_text(file, callback, decoder) {
    if (!window.TextDecoder) {
        read_as_text_with_encoding(file, callback, decoder);
    } else {
        read_as_text_with_decoder(file, callback, decoder);
    }
}

function start(id) {
    let url = 'wss://' + window.location.hostname + '/webssh/ws?id=' + id,
        sock = new window.WebSocket(url),
        encoding = 'utf-8',
        decoder = window.TextDecoder ? new window.TextDecoder(encoding) : encoding,
        terminal = document.getElementById('#terminal'),
        term = new window.Terminal({
            cursorBlink: true,
            rows : 30
        });

    function term_write(text) {
        if (term) {
            term.write(text);
        }
    }

    term.on('data', function (data) {
        sock.send(JSON.stringify({'data': data}));
    });

    sock.onopen = function () {
        term.open(terminal, true);
    };

    sock.onmessage = function (msg) {
        read_file_as_text(msg.data, term_write, decoder);
    };

    sock.onerror = function (e) {
        console.error(e);
    };

    sock.onclose = function (e) {
        term.destroy();
        term = undefined;
        sock = undefined;
    };

}
