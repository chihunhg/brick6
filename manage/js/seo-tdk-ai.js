/**
 * SEO TDK AI 產生（CSP：無 inline handler）
 */
(function ($) {
    'use strict';

    var DEFAULT_API_URL = '../generate_tdk.php';
    var DIALOG_BUSY = 'data-seo-tdk-dialog';
    var LOADING_OVERLAY_ID = 'SeoTdkAi_Loading';
    var LOADING_STATUS_MESSAGES = [
        'AI 正在產生 TDK，請稍候…',
        '正在分析標題與內容…',
        '正在產出 SEO 標題與描述…',
        '正在整理關鍵字…',
        '即將完成，請勿關閉頁面…'
    ];
    var loadingStatusTimer = null;
    var loadingStatusIndex = 0;
    var TDK_PROMPT_MAX_CHARS = 6000;
    var TDK_SECTION_MAX_CHARS = 1200;

    function resolveApiUrl() {
        var form = document.getElementById('form1');
        if (form) {
            var fromData = form.getAttribute('data-seo-tdk-url');
            if (fromData) {
                return fromData;
            }
        }
        var path = window.location.pathname || '';
        var manageIdx = path.indexOf('/manage/');
        if (manageIdx >= 0) {
            return path.substring(0, manageIdx + 8) + 'generate_tdk.php';
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
                    return '伺服器回傳 HTML 而非 JSON，請確認 manage/generate_tdk.php 與 _api_inc.php 已上傳（HTTP ' + xhr.status + '）';
                }
                if (xhr.status === 404) {
                    return '找不到 generate_tdk.php，請確認檔案已上傳至 manage 目錄';
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

    function stripHtml(html) {
        var text = String(html || '');
        if (!text) {
            return '';
        }
        var $tmp = $('<div></div>').html(text);
        return $.trim($tmp.text().replace(/\s+/g, ' '));
    }

    function readFieldText(fieldId) {
        if (window.ManageEditor && typeof window.ManageEditor.getHtml === 'function') {
            var fromMe = window.ManageEditor.getHtml(fieldId);
            if (fromMe !== '' && fromMe != null) {
                return stripHtml(fromMe);
            }
        }
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

    function resolveIndustry(el) {
        var $select = $('[data-seo-tdk-industry-select]');
        if ($select.length) {
            return $.trim(String($select.val() || 'general')) || 'general';
        }
        return $.trim(String(el.getAttribute('data-seo-tdk-industry') || 'general')) || 'general';
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

    function buildSeoTdkPrompt(langSlot) {
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
                parts.push('內容' + n + '：' + truncatePromptText(content, TDK_SECTION_MAX_CHARS));
            }
        }

        if (!parts.length) {
            return '';
        }

        return truncatePromptText(
            '請根據以下網頁資料，產出 SEO 的 title、description、keywords：\n\n' + parts.join('\n'),
            TDK_PROMPT_MAX_CHARS
        );
    }

    function truncatePromptText(text, maxChars) {
        var clean = $.trim(String(text || '').replace(/\s+/g, ' '));
        if (clean.length <= maxChars) {
            return clean;
        }
        return $.trim(clean.substring(0, maxChars)) + '…';
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

    function ensureLoadingOverlay() {
        var $overlay = $('#' + LOADING_OVERLAY_ID);
        if (!$overlay.length) {
            $('body').append(
                '<div class="load-wrapp seo-tdk-load-wrapp" id="' + LOADING_OVERLAY_ID + '" style="display:none;"' +
                ' role="alertdialog" aria-modal="true" aria-labelledby="SeoTdkAi_LoadingMsg" aria-busy="true">' +
                '<div class="loading">' +
                '<div class="spinner"><div class="bubble-1"></div><div class="bubble-2"></div></div>' +
                '<span id="SeoTdkAi_LoadingMsg" class="seo-tdk-load-msg">' + LOADING_STATUS_MESSAGES[0] + '</span>' +
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
        var $msg = $overlay.find('.seo-tdk-load-msg');
        clearLoadingStatusTimer();

        if (!busy) {
            $overlay.hide();
            $('body').removeClass('seo-tdk-page-busy');
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
        $('body').addClass('seo-tdk-page-busy');
    }

    function setSeoBlockBusy(langSlot, busy) {
        var $btn = $('[data-manage-action="seo-tdk-generate"][data-lang-slot="' + langSlot + '"]');
        if (!$btn.length) {
            return;
        }
        var $blocks = $btn.closest('.formGrid')
            .add($btn.closest('.formGrid').nextAll('.formGrid').slice(0, 3));
        $blocks.toggleClass('seo-tdk-busy-block is-busy', busy);
    }

    function setButtonBusy($btn, busy) {
        if (busy) {
            $btn.prop('disabled', true).attr('aria-busy', 'true');
            if (!$btn.data('seo-tdk-label')) {
                $btn.data('seo-tdk-label', $btn.html());
            }
            $btn.html('<i class="bi bi-arrow-repeat editor-ai-btn-spin" aria-hidden="true"></i> 產生中…');
            return;
        }
        $btn.prop('disabled', false).removeAttr('aria-busy');
        var label = $btn.data('seo-tdk-label');
        if (label) {
            $btn.html(label);
        }
    }

    function setAiBusyState($btn, langSlot, busy) {
        setButtonBusy($btn, busy);
        setSeoBlockBusy(langSlot, busy);
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

        var prompt = buildSeoTdkPrompt(langSlot);
        if (!prompt) {
            window.alert('請先填寫標題或內容，再使用 AI 產生 TDK。');
            return;
        }

        el.setAttribute(DIALOG_BUSY, '1');

        if (!window.confirm('確定使用 AI 產生 SEO 標題、內文與關鍵字？按「取消」可中止。')) {
            endDialog(el);
            return;
        }

        endDialog(el);
        var $btn = $(el);
        var industry = resolveIndustry(el);
        var langPayload = resolveLangPayload(el, langSlot);
        setAiBusyState($btn, langSlot, true);

        if (typeof window.manageGeminiStreamPost !== 'function') {
            window.alert('缺少 gemini-sse-client.js，請重新整理頁面或確認 manage/js 已部署。');
            setAiBusyState($btn, langSlot, false);
            return;
        }

        window.manageGeminiStreamPost(resolveApiUrl(), {
            prompt: prompt,
            industry: industry,
            lang_slot: langPayload.lang_slot,
            lang_label: langPayload.lang_label
        }, {
            onStart: function () {
                setLoadingOverlay(true, 'AI 正在串流產生 TDK…');
            },
            onDelta: function (delta, accumulated) {
                var partialTitle = window.manageGeminiTryPartialField(accumulated, 'title');
                var partialDesc = window.manageGeminiTryPartialField(accumulated, 'description');
                var partialKw = window.manageGeminiTryPartialField(accumulated, 'keywords');
                if (partialTitle !== null) {
                    $('#Title' + langSlot).val(partialTitle);
                }
                if (partialDesc !== null) {
                    $('#Description' + langSlot).val(partialDesc);
                }
                if (partialKw !== null) {
                    fillKeywords(langSlot, partialKw);
                }
                setLoadingOverlay(true, 'TDK 產文中…（' + accumulated.length + ' 字元）');
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
                if (!res.title) {
                    window.alert('產生失敗：模型未回傳完整資料');
                    return;
                }
                $('#Title' + langSlot).val(String(res.title || ''));
                $('#Description' + langSlot).val(String(res.description || ''));
                fillKeywords(langSlot, res.keywords);
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
        var el = e.target.closest('[data-manage-action="seo-tdk-generate"]');
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
