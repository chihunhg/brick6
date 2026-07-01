$(function () {
    var $main = $('[data-paper-detail]');
    if (!$main.length) {
        return;
    }

    var pkey = parseInt($main.data('pkey'), 10) || 0;
    var url = String($main.data('pageview-url') || '');
    if (pkey <= 0 || url === '') {
        return;
    }

    var sent = false;

    setTimeout(function () {
        if (sent) {
            return;
        }
        sent = true;

        $.ajax({
            url: url,
            method: 'POST',
            data: { PKey: pkey },
            dataType: 'json',
        }).done(function () {
            // 靜默成功
        }).fail(function () {
            // 靜默失敗
        });
    }, 10000);
});
