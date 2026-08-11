/**
 * 文章 AI 搜尋核心摘要產生（CSP：無 inline handler）
 */
(function ($) {
    'use strict';

    var DEFAULT_API_URL = '../generate_summary.php';
    var DIALOG_BUSY = 'data-summary-ai-dialog';
    var LOADING_OVERLAY_ID = 'SummaryAi_Loading';
    var LOADING_STATUS_MESSAGES = [
        'AI 正在產生搜尋核心摘要，請稍候…',
        '正在分析標題與內容…',
        '正在整理搜尋核心重點…',
        '即將完成，請勿關閉頁面…'
    ];
    var loadingStatusTimer = null;
    var loadingStatusIndex = 0;
    var SUMMARY_PROMPT_MAX_CHARS = 6000;
    var SUMMARY_SECTION_MAX_CHARS = 1200;

    function resolveApiUrl() {
        var form = document.getElementById('form1');
        if (form) {
            var fromData = form.getAttribute('data-summary-ai-url');
            if (fromData) {
                return fromData;
            }
        }
        var path = window.location.pathname || '';
        var manageIdx = path.indexOf('/manage/');
        if (manageIdx >= 0) {
            return path.substring(0, manageIdx + 8) + 'generate_summary.php';
        }
        return DEFAULT_API_URL;
    }

    function stripHtml(html) {
        var text = String(html || '');
        if (!text) {
            return '';
        }
        var $tmp = $('<div></div>').html(text);
        return $.trim($tmp.text().replace(/\s+/g, ' '));
    }

    function readFieldText(fieldId) {
        if (window.CKEDITOR && CKEDITOR.instances[fieldId]) {
            return stripHtml(CKEDITOR.instances[fieldId].getData());
        }
        var $el = $('#' + fieldId);
        if (!$el.length) {
            return '';
        }
        if ($el.is('textarea,input')) {
            return $.trim($el.val() || '');
        }
        return stripHtml($el.val() || $el.html() || '');
    }

    function truncatePromptText(text, maxChars) {
        var clean = $.trim(String(text || '').replace(/\s+/g, ' '));
        if (clean.length <= maxChars) {
            return clean;
        }
        return $.trim(clean.substring(0, maxChars)) + '…';
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

    function buildSummaryPrompt(langSlot) {
        var parts = [];
        var title = $.trim($('#strName' + langSlot).val() || '');
        if (title) {
            parts.push('標題：' + title);
        }

        var interview = readFieldText('Interview' + langSlot);
        if (interview) {
            parts.push('簡述：' + interview);
        }

        var n;
        for (n = 1; n <= 6; n += 1) {
            var content = readFieldText('Contents' + n + '_' + langSlot);
            if (content) {
                parts.push('內容' + n + '：' + truncatePromptText(content, SUMMARY_SECTION_MAX_CHARS));
            }
        }

        if (!parts.length) {
            return '';
        }

        return truncatePromptText(
            '請根據以下網頁資料，產出精簡的 AI 搜尋核心摘要：\n\n' + parts.join('\n'),
            SUMMARY_PROMPT_MAX_CHARS
        );
    }

    function ensureLoadingOverlay() {
        var $overlay = $('#' + LOADING_OVERLAY_ID);
        if (!$overlay.length) {
            $('body').append(
                '<div class="load-wrapp summary-ai-load-wrapp" id="' + LOADING_OVERLAY_ID + '" style="display:none;"' +
                ' role="alertdialog" aria-modal="true" aria-labelledby="SummaryAi_LoadingMsg" aria-busy="true">' +
                '<div class="loading">' +
                '<div class="spinner"><div class="bubble-1"></div><div class="bubble-2"></div></div>' +
                '<span id="SummaryAi_LoadingMsg" class="summary-ai-load-msg">' + LOADING_STATUS_MESSAGES[0] + '</span>' +
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
        var $msg = $overlay.find('.summary-ai-load-msg');
        clearLoadingStatusTimer();

        if (!busy) {
            $overlay.hide();
            $('body').removeClass('summary-ai-page-busy');
            return;
        }

        if (message) {
            $msg.text(message);
        } else {
            $msg.text(LOADING_STATUS_MESSAGES[0]);
        }

        loadingStatusTimer = window.setInterval(function () {
            loadingStatusIndex = (loadingStatusIndex + 1) % LOADING_STATUS_MESSAGES.length;
            $msg.text(LOADING_STATUS_MESSAGES[loadingStatusIndex]);
        }, 3500);

        $overlay.show();
        $('body').addClass('summary-ai-page-busy');
    }

    function setButtonBusy($btn, busy) {
        if (busy) {
            $btn.prop('disabled', true).attr('aria-busy', 'true');
            if (!$btn.data('summary-ai-label')) {
                $btn.data('summary-ai-label', $btn.html());
            }
            $btn.html('<i class="bi bi-arrow-repeat editor-ai-btn-spin" aria-hidden="true"></i> 產生中…');
            return;
        }
        $btn.prop('disabled', false).removeAttr('aria-busy');
        var label = $btn.data('summary-ai-label');
        if (label) {
            $btn.html(label);
        }
    }

    function setAiBusyState($btn, langSlot, busy) {
        setButtonBusy($btn, busy);
        $('#Summary' + langSlot).prop('readonly', busy);
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

        var prompt = buildSummaryPrompt(langSlot);
        if (!prompt) {
            window.alert('請先填寫標題或內容，再使用 AI 產生搜尋核心摘要。');
            return;
        }

        el.setAttribute(DIALOG_BUSY, '1');

        if (!window.confirm('確定使用 AI 產生搜尋核心摘要？按「取消」可中止。')) {
            endDialog(el);
            return;
        }

        endDialog(el);
        var $btn = $(el);
        var langPayload = resolveLangPayload(el, langSlot);
        setAiBusyState($btn, langSlot, true);

        if (typeof window.manageGeminiStreamPost !== 'function') {
            window.alert('缺少 gemini-sse-client.js，請重新整理頁面或確認 manage/js 已部署。');
            setAiBusyState($btn, langSlot, false);
            return;
        }

        window.manageGeminiStreamPost(resolveApiUrl(), {
            prompt: prompt,
            lang_slot: langPayload.lang_slot,
            lang_label: langPayload.lang_label
        }, {
            onStart: function () {
                setLoadingOverlay(true, 'AI 正在串流產生搜尋核心摘要…');
            },
            onDelta: function (delta, accumulated) {
                var partialSummary = window.manageGeminiTryPartialField(accumulated, 'summary');
                if (partialSummary !== null) {
                    $('#Summary' + langSlot).val(partialSummary);
                }
                setLoadingOverlay(true, '搜尋核心摘要產文中…（' + accumulated.length + ' 字元）');
            },
            onDone: function (res) {
                if (!res || typeof res !== 'object') {
                    window.alert('產生失敗：回應格式錯誤');
                    return;
                }
                if (res.success === false || res.error) {
                    window.alert(String(res.error || '產生失敗，請稍後再試'));
                    return;
                }
                if (!res.summary) {
                    window.alert('產生失敗：模型未回傳完整資料');
                    return;
                }
                $('#Summary' + langSlot).val(String(res.summary || ''));
            }
        })
            .catch(function (err) {
                window.alert(err && err.message ? err.message : '產生失敗，請稍後再試');
            })
            .finally(function () {
                setAiBusyState($btn, langSlot, false);
            });
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-manage-action="summary-ai-generate"]');
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
