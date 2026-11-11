$(function () {
    var $table = $('#files-table');
    var $tbody = $table.find('tbody');
    var listUrl = $table.data('list-url');
    var modal = new bootstrap.Modal('#delete-modal');
    var pendingDelete = null;
    var files = [];

    function timeLeft(expiresAt) {
        var ms = new Date(expiresAt) - new Date();
        if (ms <= 0) return '<span class="badge text-bg-secondary">expired</span>';
        var h = Math.floor(ms / 3600000);
        var m = Math.floor(ms % 3600000 / 60000);
        var cls = h < 1 ? 'text-bg-danger' : (h < 6 ? 'text-bg-warning' : 'text-bg-success');
        return '<span class="badge ' + cls + '">' + h + 'h ' + m + 'm</span>';
    }

    function icon(ext) {
        return ext === 'pdf'
            ? '<i class="bi bi-file-earmark-pdf text-danger"></i>'
            : '<i class="bi bi-file-earmark-word text-primary"></i>';
    }

    function render() {
        if (!files.length) {
            $tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No files uploaded yet.</td></tr>');
            return;
        }

        $tbody.html($.map(files, function (f, i) {
            return '<tr>' +
                '<td class="text-break">' + icon(f.extension) + ' ' + App.escape(f.name) + '</td>' +
                '<td class="text-uppercase">' + App.escape(f.extension) + '</td>' +
                '<td class="text-end text-nowrap">' + App.formatSize(f.size) + '</td>' +
                '<td class="text-nowrap">' + new Date(f.uploaded_at).toLocaleString() + '</td>' +
                '<td>' + timeLeft(f.expires_at) + '</td>' +
                '<td class="text-end text-nowrap">' +
                '  <a class="btn btn-sm btn-outline-primary" href="' + App.escape(f.download_url) + '" title="Download"><i class="bi bi-download"></i></a> ' +
                '  <button class="btn btn-sm btn-outline-danger js-delete" data-index="' + i + '" title="Delete"><i class="bi bi-trash"></i></button>' +
                '</td>' +
                '</tr>';
        }).join(''));
    }

    function load() {
        $.getJSON(listUrl).done(function (res) {
            files = res.data;
            render();
        }).fail(function (xhr) {
            $tbody.html('<tr><td colspan="6" class="text-center text-danger py-4"></td></tr>');
            $tbody.find('td').text(App.errorMessage(xhr));
        });
    }

    $tbody.on('click', '.js-delete', function () {
        pendingDelete = files[$(this).data('index')];
        $('#delete-name').text(pendingDelete.name);
        modal.show();
    });

    $('#delete-confirm').on('click', function () {
        if (!pendingDelete) return;
        var $btn = $(this).prop('disabled', true);

        $.ajax({url: pendingDelete.delete_url, method: 'DELETE'})
            .done(function (res) {
                App.toast(res.message, 'success');
                load();
            })
            .fail(function (xhr) {
                App.toast(App.errorMessage(xhr), 'danger');
                if (xhr.status === 404) load();
            })
            .always(function () {
                $btn.prop('disabled', false);
                pendingDelete = null;
                modal.hide();
            });
    });

    $('#refresh').on('click', load);

    load();
    setInterval(load, 30000);
});
