$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json'
    }
});

window.App = {
    escape: function (str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    },

    formatSize: function (bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1024 / 1024).toFixed(2) + ' MB';
    },

    errorMessage: function (xhr) {
        if (xhr.status === 413) return 'File is too large.';
        var json = xhr.responseJSON;
        if (json && json.errors) {
            return Object.values(json.errors).flat().join(' ');
        }
        if (json && json.message) return json.message;
        return 'Request failed (' + (xhr.status || 'network error') + ').';
    },

    toast: function (message, type) {
        var $toast = $(
            '<div class="toast align-items-center text-bg-' + (type || 'success') + ' border-0" role="alert">' +
            '<div class="d-flex"><div class="toast-body"></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>'
        );
        $toast.find('.toast-body').text(message);
        $('#toasts').append($toast);
        var toast = new bootstrap.Toast($toast[0], {delay: 4000});
        $toast.on('hidden.bs.toast', function () { $toast.remove(); });
        toast.show();
    }
};
