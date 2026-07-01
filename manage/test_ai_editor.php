<?php
declare(strict_types=1);

/**
 * Gemini AI 產文／TDK 前端測試頁（Tailwind + Fetch API）
 * 需後台登入後存取。
 */
require_once __DIR__ . '/_inc.php';
require_once dirname(__DIR__) . '/include/gemini_editor_helpers.php';

$pageTitle = 'AI 產文測試';
$industryOptions = gemini_industry_options();
$ckeJs = __DIR__ . '/ckeditor/ckeditor.js';
$ckeCfg = __DIR__ . '/ckeditor/config.js';
$ckeVer = is_file($ckeJs) ? (string)filemtime($ckeJs) : '1';
$cfgVer = is_file($ckeCfg) ? (string)filemtime($ckeCfg) : '1';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="bg-slate-50 text-slate-800">
<div class="max-w-6xl mx-auto p-6 space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-slate-900">Gemini AI 產文測試</h1>
        <p class="text-sm text-slate-500 mt-1">同時呼叫 <code class="bg-slate-200 px-1 rounded">generate_editor.php</code> 與 <code class="bg-slate-200 px-1 rounded">generate_tdk.php</code></p>
    </header>

    <form id="aiTestForm" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label for="industry" class="block text-sm font-medium mb-1">產業類別</label>
                <select id="industry" name="industry"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($industryOptions as $industryValue => $industryLabel) { ?>
                    <option value="<?php echo htmlspecialchars($industryValue, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $industryValue === 'general' ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($industryLabel, ENT_QUOTES, 'UTF-8'); ?>
                        （<?php echo htmlspecialchars($industryValue, ENT_QUOTES, 'UTF-8'); ?>）
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label for="source_url" class="block text-sm font-medium mb-1">參考網址（選填）</label>
                <input type="url" id="source_url" name="source_url"
                    placeholder="https://example.com/page（可留空）"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label for="prompt" class="block text-sm font-medium mb-1">提示詞 <span class="text-red-500">*</span></label>
            <textarea id="prompt" name="prompt" rows="4" required
                placeholder="請依參考網址改寫為官網內文…"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">請依參考網址內容改寫為企業官網介紹，保留重點並符合所選產業規範。</textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" id="btnSubmit"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-white font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                提交產生
            </button>
            <span id="statusText" class="text-sm text-slate-500"></span>
        </div>
    </form>

    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h2 class="text-lg font-semibold">SEO TDK 欄位</h2>
        <div class="grid gap-3">
            <div>
                <label for="seo_title" class="block text-sm font-medium mb-1">Title</label>
                <input type="text" id="seo_title" readonly
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="seo_description" class="block text-sm font-medium mb-1">Description</label>
                <textarea id="seo_description" rows="3" readonly
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm"></textarea>
            </div>
            <div>
                <label for="seo_keywords" class="block text-sm font-medium mb-1">Keywords</label>
                <input type="text" id="seo_keywords" readonly
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            </div>
        </div>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-3">
        <h2 class="text-lg font-semibold">CKEditor 預覽</h2>
        <textarea id="editor_preview" name="editor_preview" class="ckeditor w-full"></textarea>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-3">
        <h2 class="text-lg font-semibold">API 回應 JSON</h2>
        <div>
            <p class="text-xs font-medium text-slate-500 mb-1">generate_editor.php</p>
            <pre id="json_editor" class="text-xs bg-slate-900 text-green-300 rounded-lg p-4 overflow-x-auto min-h-[4rem]">（尚未請求）</pre>
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500 mb-1">generate_tdk.php</p>
            <pre id="json_tdk" class="text-xs bg-slate-900 text-green-300 rounded-lg p-4 overflow-x-auto min-h-[4rem]">（尚未請求）</pre>
        </div>
    </section>
</div>

<script src="ckeditor/ckeditor.js?ver=<?php echo htmlspecialchars($ckeVer, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="ckeditor/config.js?ver=<?php echo htmlspecialchars($cfgVer, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
(function () {
    'use strict';

    var editorInstance = null;

    function initCkeditor() {
        if (typeof CKEDITOR === 'undefined') {
            return;
        }
        if (CKEDITOR.instances.editor_preview) {
            editorInstance = CKEDITOR.instances.editor_preview;
            return;
        }
        editorInstance = CKEDITOR.replace('editor_preview');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCkeditor);
    } else {
        initCkeditor();
    }

    function setStatus(msg, isError) {
        var el = document.getElementById('statusText');
        el.textContent = msg || '';
        el.className = 'text-sm ' + (isError ? 'text-red-600' : 'text-slate-500');
    }

    function prettyJson(obj) {
        try {
            return JSON.stringify(obj, null, 2);
        } catch (e) {
            return String(obj);
        }
    }

    function fillTdk(data) {
        document.getElementById('seo_title').value = data.title || '';
        document.getElementById('seo_description').value = data.description || '';
        document.getElementById('seo_keywords').value = data.keywords || '';
    }

    function setEditorHtml(html) {
        if (editorInstance) {
            editorInstance.setData(html || '');
            return;
        }
        if (window.CKEDITOR && CKEDITOR.instances.editor_preview) {
            CKEDITOR.instances.editor_preview.setData(html || '');
            return;
        }
        document.getElementById('editor_preview').value = html || '';
    }

    async function postJson(url, payload) {
        var res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        var data = await res.json().catch(function () { return null; });
        if (!res.ok) {
            var errMsg = (data && data.error) ? data.error : ('HTTP ' + res.status);
            throw new Error(errMsg);
        }
        return data;
    }

    document.getElementById('aiTestForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        var btn = document.getElementById('btnSubmit');
        var industry = document.getElementById('industry').value;
        var sourceUrl = document.getElementById('source_url').value.trim();
        var prompt = document.getElementById('prompt').value.trim();

        if (!prompt) {
            setStatus('請填寫提示詞', true);
            return;
        }

        btn.disabled = true;
        setStatus('產生中，請稍候…');

        var editorPayload = { prompt: prompt, source_url: sourceUrl, industry: industry };
        var tdkPayload = { prompt: prompt };

        try {
            var results = await Promise.allSettled([
                postJson('generate_editor.php', editorPayload),
                postJson('generate_tdk.php', tdkPayload)
            ]);

            if (results[0].status === 'fulfilled') {
                var editorData = results[0].value;
                document.getElementById('json_editor').textContent = prettyJson(editorData);
                if (editorData.html_content) {
                    setEditorHtml(editorData.html_content);
                }
            } else {
                document.getElementById('json_editor').textContent = '錯誤：' + results[0].reason.message;
            }

            if (results[1].status === 'fulfilled') {
                var tdkData = results[1].value;
                document.getElementById('json_tdk').textContent = prettyJson(tdkData);
                fillTdk(tdkData);
            } else {
                document.getElementById('json_tdk').textContent = '錯誤：' + results[1].reason.message;
            }

            var hasError = results.some(function (r) { return r.status === 'rejected'; });
            setStatus(hasError ? '部分請求失敗，請查看 JSON 區塊' : '產生完成', hasError);
        } catch (err) {
            setStatus(err.message || '請求失敗', true);
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
</body>
</html>
