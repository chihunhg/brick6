/**
 * 同步產生 SEO TDK 與 CKEditor 內文（單次合併 API）
 */
(function ($) {
    'use strict';

    var DEFAULT_API_URL = '../generate_content_tdk.php';
    var DIALOG_BUSY = 'data-content-tdk-dialog';
    var LOADING_OVERLAY_ID = 'ContentTdkAi_Loading';
    var LOADING_STATUS_MESSAGES = [
        'AI 正在同步產生 TDK 與內文，請稍候…',
        '正在分析主題與產業範本…',
        '正在撰寫 HTML 內文與 SEO 欄位…',
        '即將完成，請勿關閉頁面…'
    ];
    var loadingStatusTimer = null;
    var loadingStatusIndex = 0;
    var PROMPT_MAX_CHARS = 6000;
    var SECTION_MAX_CHARS = 1200;

    function resolveApiUrl() {
        var form = document.getElementById('form1');
        if (form) {
            var fromData = form.getAttribute('data-content-tdk-url');
            if (fromData) {
                return fromData;
            }
        }
        var path = window.location.pathname || '';
        var manageIdx = path.indexOf('/manage/');
        if (manageIdx >= 0) {
            return path.substring(0, manageIdx + 8) + 'generate_content_tdk.php';
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
                    return '伺服器回傳 HTML 而非 JSON，請確認 manage/generate_content_tdk.php 已上傳（HTTP ' + xhr.status + '）';
                }
                if (xhr.status === 404) {
                    return '找不到 generate_content_tdk.php，請確認檔案已上傳至 manage 目錄';
                }
                if (xhr.status === 401) {
                    return '登入已逾時，請重新登入後台';
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

    function truncatePromptText(text, maxChars) {
        var clean = $.trim(String(text || '').replace(/\s+/g, ' '));
        if (clean.length <= maxChars) {
            return clean;
        }
        return $.trim(clean.substring(0, maxChars)) + '…';
    }

    function resolveIndustry() {
        var $select = $('[data-seo-tdk-industry-select]');
        if ($select.length) {
            return $.trim(String($select.val() || 'general')) || 'general';
        }
        return 'general';
    }

    function resolveFormatMode() {
        var $select = $('[data-content-tdk-format-select]');
        if ($select.length) {
            return $.trim(String($select.val() || '')) || 'auto';
        }
        return 'auto';
    }

    function resolveEditorTarget(el, langSlot) {
        var fromData = $.trim(String(el.getAttribute('data-editor-target') || ''));
        if (fromData) {
            return fromData;
        }
        return 'Contents1_' + langSlot;
    }

    function resolveLangPayload(el, langSlot) {
        langSlot = parseInt(langSlot || el.getAttribute('data-lang-slot') || '0', 10);
        var langLabel = $.trim(String(el.getAttribute('data-lang-label') || ''));
        if (langLabel === '' && langSlot > 0) {
            var $tab = $('#tabNav_' + langSlot);
            if ($tab.length) {
                langLabel = $.trim($tab.text());
            }
        }
        return {
            lang_slot: langSlot,
            lang_label: langLabel
        };
    }

    function buildCombinedPrompt(langSlot) {
        var parts = [];
        var title = $.trim($('#strName' + langSlot).val() || '');
        if (title) {
            parts.push('標題：' + title);
        }

        var interview = $.trim($('#Interview' + langSlot).val() || '');
        if (interview) {
            parts.push('簡述：' + truncatePromptText(interview, SECTION_MAX_CHARS));
        }

        if (!parts.length) {
            return '';
        }

        return truncatePromptText(
            '請根據以下主題，同步產出 CKEditor HTML 內文（html_content）以及 SEO 的 title、description、keywords：\n\n'
                + parts.join('\n'),
            PROMPT_MAX_CHARS
        );
    }

    function fillKeywords(langSlot, keywordsStr) {
        var keywords = String(keywordsStr || '')
            .split(/[,，、]/)
            .map(function (item) {
                return $.trim(item);
            })
            .filter(Boolean);

        var i;
        for (i = 0; i < 5; i += 1) {
            $('#Keyword' + (i + 1) + '_' + langSlot).val(keywords[i] || '');
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
                ' role="alertdialog" aria-modal="true" aria-labelledby="ContentTdkAi_LoadingMsg" aria-busy="true">' +
                '<div class="loading">' +
                '<div class="spinner"><div class="bubble-1"></div><div class="bubble-2"></div></div>' +
                '<span id="ContentTdkAi_LoadingMsg" class="editor-ai-load-msg">' + LOADING_STATUS_MESSAGES[0] + '</span>' +
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

    function setLoadingOverlay(busy) {
        var $overlay = ensureLoadingOverlay();
        var $msg = $overlay.find('.editor-ai-load-msg');
        clearLoadingStatusTimer();

        if (!busy) {
            $overlay.hide();
            $('body').removeClass('editor-ai-page-busy');
            return;
        }

        $msg.text(LOADING_STATUS_MESSAGES[0]);
        loadingStatusTimer = window.setInterval(function () {
            loadingStatusIndex = (loadingStatusIndex + 1) % LOADING_STATUS_MESSAGES.length;
            $msg.text(LOADING_STATUS_MESSAGES[loadingStatusIndex]);
        }, 3200);

        $overlay.show();
        $('body').addClass('editor-ai-page-busy');
    }

    function setButtonBusy($btn, busy) {
        if (busy) {
            $btn.prop('disabled', true).attr('aria-busy', 'true');
            if (!$btn.data('content-tdk-label')) {
                $btn.data('content-tdk-label', $btn.html());
            }
            $btn.html('<i class="bi bi-arrow-repeat editor-ai-btn-spin" aria-hidden="true"></i> 產生中…');
            return;
        }
        $btn.prop('disabled', false).removeAttr('aria-busy');
        var label = $btn.data('content-tdk-label');
        if (label) {
            $btn.html(label);
        }
    }

    function setEditorAreaBusy(editorId, busy) {
        var $target = $('#cke_' + editorId);
        if (!$target.length) {
            $target = $('#' + editorId);
        }
        if ($target.length) {
            $target.toggleClass('editor-ai-editor-busy', busy);
        }
    }

    function setAiBusyState($btn, langSlot, editorId, busy) {
        setButtonBusy($btn, busy);
        setEditorAreaBusy(editorId, busy);
        $('[data-manage-action="seo-tdk-generate"][data-lang-slot="' + langSlot + '"]').prop('disabled', busy);
        $('[data-manage-action="content-tdk-generate"][data-lang-slot="' + langSlot + '"]').not($btn).prop('disabled', busy);
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

        var langSlot = parseInt(el.getAttribute('data-lang-slot') || '0', 10);
        if (langSlot <= 0) {
            return;
        }

        var editorId = resolveEditorTarget(el, langSlot);
        var prompt = buildCombinedPrompt(langSlot);
        if (!prompt) {
            window.alert('請先填寫該語系的標題（或簡述），再使用 AI 同步產生 TDK 與內文。');
            return;
        }

        el.setAttribute(DIALOG_BUSY, '1');

        if (!window.confirm('確定同時產生 SEO TDK 與「內容1」（' + editorId + '）？按「取消」可中止。')) {
            endDialog(el);
            return;
        }

        endDialog(el);
        var $btn = $(el);
        var industry = resolveIndustry();
        var formatMode = resolveFormatMode();
        var langPayload = resolveLangPayload(el, langSlot);
        setAiBusyState($btn, langSlot, editorId, true);

        $.ajax({
            type: 'POST',
            url: resolveApiUrl(),
            dataType: 'text',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                prompt: prompt,
                industry: industry,
                format_mode: formatMode,
                lang_slot: langPayload.lang_slot,
                lang_label: langPayload.lang_label
            })
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
                if (!res.html_content || !res.title) {
                    window.alert('產生失敗：模型未回傳完整資料');
                    return;
                }

                $('#Title' + langSlot).val(String(res.title || ''));
                $('#Description' + langSlot).val(String(res.description || ''));
                fillKeywords(langSlot, res.keywords);
                setEditorHtml(editorId, String(res.html_content));
            })
            .fail(function (xhr, textStatus) {
                window.alert(resolveAjaxErrorMessage(xhr, textStatus, xhr.responseText));
            })
            .always(function () {
                setAiBusyState($btn, langSlot, editorId, false);
            });
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-manage-action="content-tdk-generate"]');
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
