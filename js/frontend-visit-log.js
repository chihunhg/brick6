$(function () {
    var $beacon = $('#frontend-visit-log');
    if (!$beacon.length) {
        return;
    }

    var modulePKey = parseInt($beacon.data('module-pkey'), 10) || 0;
    var pageLink = String($beacon.data('page-link') || window.location.pathname || '');
    var logUrl = String($beacon.data('log-url') || '');

    if (logUrl === '') {
        return;
    }

    var storageKey = 'frontendVisitLog:' + pageLink;
    try {
        if (window.sessionStorage && sessionStorage.getItem(storageKey) === '1') {
            return;
        }
    } catch (ignore) {
        // sessionStorage 不可用時仍記錄
    }

    $.ajax({
        url: logUrl,
        method: 'POST',
        data: {
            Module_PKey: modulePKey,
            strLink: pageLink,
        },
        dataType: 'json',
    }).done(function () {
        try {
            if (window.sessionStorage) {
                sessionStorage.setItem(storageKey, '1');
            }
        } catch (ignore2) {
            // 忽略
        }
    }).fail(function () {
        // 靜默失敗，不影響瀏覽
    });
});
