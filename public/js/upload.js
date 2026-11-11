$(function () {
    var $dropzone = $('#dropzone');
    var $input = $('#file-input');
    var $list = $('#upload-list');
    var uploadUrl = $dropzone.data('upload-url');
    var maxSize = parseInt($dropzone.data('max-size'), 10);
    var extensions = String($dropzone.data('extensions')).split(',');

    $dropzone.on('click', function (e) {
        if (e.target !== $input[0]) $input.trigger('click');
    });

    $input.on('change', function () {
        handleFiles(this.files);
        this.value = '';
    });

    $dropzone.on('dragenter dragover', function (e) {
        e.preventDefault();
        $dropzone.addClass('dragover');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $dropzone.removeClass('dragover');
    }).on('drop', function (e) {
        handleFiles(e.originalEvent.dataTransfer.files);
    });

    function handleFiles(files) {
        $.each(files, function (_, file) {
            upload(file);
        });
    }

    function createItem(file) {
        var $item = $(
            '<li class="list-group-item">' +
            '  <div class="d-flex justify-content-between">' +
            '    <span class="text-truncate me-2"><i class="bi bi-file-earmark"></i> <span class="name"></span></span>' +
            '    <small class="text-muted text-nowrap">' + App.formatSize(file.size) + '</small>' +
            '  </div>' +
            '  <div class="progress mt-2" style="height: 6px"><div class="progress-bar" style="width: 0"></div></div>' +
            '  <small class="status text-muted">Waiting…</small>' +
            '</li>'
        );
        $item.find('.name').text(file.name);
        $list.prepend($item);
        return $item;
    }

    function setStatus($item, text, cls) {
        $item.find('.status').attr('class', 'status ' + cls).text(text);
    }

    function fail($item, message) {
        $item.find('.progress-bar').addClass('bg-danger').css('width', '100%');
        setStatus($item, message, 'text-danger');
    }

    function upload(file) {
        var $item = createItem(file);
        var ext = file.name.split('.').pop().toLowerCase();

        if (extensions.indexOf(ext) === -1) {
            return fail($item, 'Only ' + extensions.join(', ').toUpperCase() + ' files are allowed.');
        }
        if (file.size > maxSize) {
            return fail($item, 'File exceeds the ' + App.formatSize(maxSize) + ' limit.');
        }

        var data = new FormData();
        data.append('file', file);

        $.ajax({
            url: uploadUrl,
            method: 'POST',
            data: data,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var pct = Math.round(e.loaded / e.total * 100);
                        $item.find('.progress-bar').css('width', pct + '%');
                        setStatus($item, 'Uploading… ' + pct + '%', 'text-muted');
                    }
                });
                return xhr;
            }
        }).done(function (res) {
            $item.find('.progress-bar').addClass('bg-success').css('width', '100%');
            var expires = new Date(res.data.expires_at).toLocaleString();
            setStatus($item, 'Uploaded. Will be deleted at ' + expires + '.', 'text-success');
        }).fail(function (xhr) {
            fail($item, App.errorMessage(xhr));
        });
    }
});
