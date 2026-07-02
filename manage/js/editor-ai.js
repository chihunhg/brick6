/**
 * CKEditor AI 產文（CSP：無 inline handler）
 */
(function ($) {
    'use strict';

    var DEFAULT_API_URL = '../generate_editor.php';
    var DIALOG_BUSY = 'data-editor-ai-dialog';
    var LOADING_OVERLAY_ID = 'EditorAi_Loading';
    var LOADING_STATUS_MESSAGES = [
        'AI 正在產生內容，請稍候…',
        '正在分析提示詞與排版格式…',
        '正在撰寫 HTML 內文…',
        '即將完成，請勿關閉頁面…'
    ];
    var loadingStatusTimer = null;
    var loadingStatusIndex = 0;

    function resolveApiUrl() {
        var form = document.getElementById('form1');
        if (form) {
            var fromData = form.getAttribute('data-editor-ai-url');
            if (fromData) {
                return fromData;
            }
        }
        var path = window.location.pathname || '';
        var manageIdx = path.indexOf('/manage/');
        if (manageIdx >= 0) {
            return path.substring(0, manageIdx + 8) + 'generate_editor.php';
        }
        return DEFAULT_API_URL;
    }

    function parseJsonResponse(raw) {
        var text = String(raw || '').replace(/^\uFEFF/, '').trim();
        if (!text) {
            return null;
        }
        var start = text.indexOf('{');
        var end = text.lastIndexOf('}');
        if (start >= 0 && end > start) {
            text = text.substring(start, end + 1);
        }
        return JSON.parse(text);
    }

    function resolveAjaxErrorMessage(xhr, textStatus, rawText) {
        var body = String(rawText || xhr.responseText || '');
        if (body) {
            try {
                var parsed = parseJsonResponse(body);
                if (parsed && parsed.error) {
                    return String(parsed.error);
                }
            } catch (ignoreErr) {
                if (/^\s*</.test(body)) {
                    return '伺服器回傳 HTML 而非 JSON，請確認 manage/generate_editor.php 與 _api_inc.php 已上傳（HTTP ' + xhr.status + '）';
                }
                if (xhr.status === 404) {
                    return '找不到 generate_editor.php，請確認檔案已上傳至 manage 目錄';
                }
                if (xhr.status === 401) {
                    return '登入已逾時，請重新登入後台';
                }
                if (textStatus === 'parsererror' || textStatus === 'error') {
                    return '伺服器回應非 JSON（HTTP ' + xhr.status + '），請開啟 manage/ai_ping.php 檢查部署';
                }
            }
        }
        if (xhr.responseJSON && xhr.responseJSON.error) {
            return String(xhr.responseJSON.error);
        }
        if (xhr.status === 0) {
            return '網路連線失敗，請稍後再試';
        }
        return '產生失敗，請稍後再試';
    }

    function resolveLangSlotFromEditorId(editorId) {
        var match = String(editorId || '').match(/_(\d+)$/);
        return match ? (parseInt(match[1], 10) || 0) : 0;
    }

    function buildDefaultPrompt(editorId, hasSourceUrl, formatMode) {
        var langSlot = resolveLangSlotFromEditorId(editorId);
        var title = langSlot > 0 ? $.trim($('#strName' + langSlot).val() || '') : '';
        var prefix = hasSourceUrl ? '請依參考網址內容，' : '請';
        var subject = title ? '為「' + title + '」' : '';
        var formatHints = {
            prose: '撰寫圖文段落內文：以 h2、h3 分段，p 段落說明，strong/em 標示重點。',
            table: '撰寫內文並以 HTML table 呈現規格、方案比較或重點整理；表頭用 thead/th、資料用 tbody/td，搭配 h2 引言。',
            list: '撰寫條列式內文：以 h2 小標搭配 ul/ol 條列重點，strong 標示關鍵字。',
            auto: '撰寫官網內文：依內容混合 h2/h3、p、ul/ol、strong/em，有數據比較時使用 table。'
        };
        var formatHint = formatHints[formatMode] || formatHints.auto;
        return prefix + subject + formatHint;
    }

    function isValidHttpUrl(url) {
        if (!url) {
            return false;
        }
        try {
            var parsed = new URL(url);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (err) {
            return false;
        }
    }

    function resolveFormatMode(el) {
        var $toolbar = $(el).closest('.editor-ai-toolbar');
        if ($toolbar.length) {
            var $select = $toolbar.find('[data-editor-ai-format-select]');
            if ($select.length) {
                return $.trim(String($select.val() || '')) || 'auto';
            }
        }
        return 'auto';
    }

    function resolveIndustry(el) {
        var $toolbar = $(el).closest('.editor-ai-toolbar');
        if ($toolbar.length) {
            var $select = $toolbar.find('[data-editor-ai-industry-select]');
            if ($select.length) {
                return $.trim(String($select.val() || '')) || 'general';
            }
        }
        return $.trim(String(el.getAttribute('data-editor-industry') || 'general')) || 'general';
    }

    function resolvePresetSourceUrl(el) {
        return $.trim(String(el.getAttribute('data-editor-source-url') || ''));
    }

    /**
     * @returns {{cancelled: boolean, url: string}}
     */
    function askSourceUrl(preset) {
        if (preset) {
            if (!isValidHttpUrl(preset)) {
                window.alert('按鈕預設的參考網址格式不正確。');
                return { cancelled: true, url: '' };
            }
            return { cancelled: false, url: preset };
        }

        while (true) {
            var url = window.prompt(
                '請輸入參考網址（選填，留空則略過；按「取消」中止整個操作）：',
                ''
            );
            if (url === null) {
                return { cancelled: true, url: '' };
            }
            url = $.trim(url);
            if (url === '') {
                return { cancelled: false, url: '' };
            }
            if (isValidHttpUrl(url)) {
                return { cancelled: false, url: url };
            }
            window.alert('參考網址格式不正確，請重新輸入、留空略過，或按「取消」中止。');
        }
    }

    function setEditorHtml(editorId, html) {
        if (window.CKEDITOR && CKEDITOR.instances[editorId]) {
            CKEDITOR.instances[editorId].setData(html);
            return;
        }
        var $field = $('#' + editorId);
        if ($field.length) {
            $field.val(html);
        }
    }

    function ensureLoadingOverlay() {
        var $overlay = $('#' + LOADING_OVERLAY_ID);
        if (!$overlay.length) {
            $('body').append(
                '<div class="load-wrapp editor-ai-load-wrapp" id="' + LOADING_OVERLAY_ID + '" style="display:none;"' +
                ' role="alertdialog" aria-modal="true" aria-labelledby="EditorAi_LoadingMsg" aria-busy="true">' +
                '<div class="loading">' +
                '<div class="spinner"><div class="bubble-1"></div><div class="bubble-2"></div></div>' +
                '<span id="EditorAi_LoadingMsg" class="editor-ai-load-msg">' + LOADING_STATUS_MESSAGES[0] + '</span>' +
                '</div></div>'
            );
            $overlay = $('#' + LOADING_OVERLAY_ID);
        }
        return $overlay;
    }

    function clearLoadingStatusTimer() {
        if (loadingStatusTimer) {
            window.clearInterval(loadingStatusTimer);
            loadingStatusTimer = null;
        }
        loadingStatusIndex = 0;
    }

    function setLoadingOverlay(busy, message) {
        var $overlay = ensureLoadingOverlay();
        var $msg = $overlay.find('.editor-ai-load-msg');
        clearLoadingStatusTimer();

        if (!busy) {
            $overlay.hide();
            $('body').removeClass('editor-ai-page-busy');
            return;
        }

        if (message) {
            $msg.text(message);
        } else {
            $msg.text(LOADING_STATUS_MESSAGES[0]);
            loadingStatusTimer = window.setInterval(function () {
                loadingStatusIndex = (loadingStatusIndex + 1) % LOADING_STATUS_MESSAGES.length;
                $msg.text(LOADING_STATUS_MESSAGES[loadingStatusIndex]);
            }, 3200);
        }

        $overlay.show();
        $('body').addClass('editor-ai-page-busy');
    }

    function setToolbarBusy($btn, busy) {
        var $toolbar = $btn.closest('.editor-ai-toolbar');
        if (!$toolbar.length) {
            return;
        }
        $toolbar.find('select').prop('disabled', busy);
        $toolbar.toggleClass('is-busy', busy);
    }

    function setEditorAreaBusy(editorId, busy) {
        var $target = $('#cke_' + editorId);
        if (!$target.length) {
            $target = $('#' + editorId);
        }
        if (!$target.length) {
            return;
        }
        $target.toggleClass('editor-ai-editor-busy', busy);
    }

    function setButtonBusy($btn, busy) {
        if (busy) {
            $btn.prop('disabled', true).attr('aria-busy', 'true');
            if (!$btn.data('editor-ai-label')) {
                $btn.data('editor-ai-label', $btn.html());
            }
            $btn.html('<i class="bi bi-arrow-repeat editor-ai-btn-spin" aria-hidden="true"></i> 產生中…');
            return;
        }
        $btn.prop('disabled', false).removeAttr('aria-busy');
        var label = $btn.data('editor-ai-label');
        if (label) {
            $btn.html(label);
        }
    }

    function setAiBusyState($btn, editorId, busy) {
        setButtonBusy($btn, busy);
        setToolbarBusy($btn, busy);
        setEditorAreaBusy(editorId, busy);
        setLoadingOverlay(busy);
    }

    function endDialog(el) {
        el.removeAttribute(DIALOG_BUSY);
    }

    function handleGenerateClick(el, e) {
        if (el.getAttribute(DIALOG_BUSY) === '1' || el.disabled) {
            if (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
            return;
        }

        var editorId = String(el.getAttribute('data-editor-target') || '').trim();
        if (!editorId || !document.getElementById(editorId)) {
            window.alert('找不到指定的編輯器欄位。');
            return;
        }

        el.setAttribute(DIALOG_BUSY, '1');

        var presetUrl = resolvePresetSourceUrl(el);
        var sourceResult = askSourceUrl(presetUrl);
        if (sourceResult.cancelled) {
            endDialog(el);
            return;
        }

        var sourceUrl = sourceResult.url;
        var formatMode = resolveFormatMode(el);
        var userPrompt = window.prompt(
            '請輸入 AI 產文提示詞：',
            buildDefaultPrompt(editorId, sourceUrl !== '', formatMode)
        );
        if (userPrompt === null) {
            endDialog(el);
            return;
        }
        userPrompt = $.trim(userPrompt);
        if (!userPrompt) {
            window.alert('請輸入提示詞。');
            endDialog(el);
            return;
        }

        var industry = resolveIndustry(el);
        var confirmMsg = sourceUrl !== ''
            ? 'AI 將依參考網址改寫內容並覆寫「' + editorId + '」編輯器，確定繼續？'
            : 'AI 將產生內容並覆寫「' + editorId + '」編輯器，確定繼續？';

        if (!window.confirm(confirmMsg)) {
            endDialog(el);
            return;
        }

        endDialog(el);
        var $btn = $(el);
        setAiBusyState($btn, editorId, true);

        var payload = {
            prompt: userPrompt,
            source_url: sourceUrl,
            industry: industry,
            format_mode: formatMode,
            editor_target: editorId
        };

        $.ajax({
            type: 'POST',
            url: resolveApiUrl(),
            dataType: 'text',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(payload)
        })
            .done(function (raw) {
                var res;
                try {
                    res = parseJsonResponse(raw);
                } catch (parseErr) {
                    window.alert(resolveAjaxErrorMessage({ status: 200, responseText: raw }, 'parsererror', raw));
                    return;
                }
                if (!res || typeof res !== 'object') {
                    window.alert('產生失敗：回應格式錯誤');
                    return;
                }
                if (res.success === false || res.error) {
                    window.alert(String(res.error || '產生失敗，請稍後再試'));
                    return;
                }
                if (!res.html_content) {
                    window.alert('產生失敗：模型未回傳 HTML 內容');
                    return;
                }
                setEditorHtml(editorId, String(res.html_content));
            })
            .fail(function (xhr, textStatus) {
                window.alert(resolveAjaxErrorMessage(xhr, textStatus, xhr.responseText));
            })
            .always(function () {
                setAiBusyState($btn, editorId, false);
            });
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-manage-action="editor-ai-generate"]');
        if (!el) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        if (el.disabled || el.getAttribute(DIALOG_BUSY) === '1') {
            return;
        }
        handleGenerateClick(el, e);
    }, true);
}(window.jQuery));
