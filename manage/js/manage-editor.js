/**
 * 後台富文本相容層（Phase 0：CKEditor 4；Phase 1 PoC：Summernote）
 *
 * window.ManageEditor = {
 *   engine, initAll, init(id), setHtml, getHtml, syncToForm, insertImage, destroy
 * }
 */
(function (window, $) {
    'use strict';

    var ENGINE_CK = 'ckeditor';
    var ENGINE_SN = 'summernote';
    var instances = Object.create(null);
    var elfinderListenBound = false;

    // 避免 CKEditor 預設自動 replace，改由 ManageEditor 統一初始化
    if (typeof window.CKEDITOR !== 'undefined') {
        window.CKEDITOR.replaceClass = '';
        window.CKEDITOR.disableAutoInline = true;
    }

    function resolveEngine() {
        var cfg = window.MANAGE_EDITOR_CONFIG || {};
        var eng = String(cfg.engine || ENGINE_CK).toLowerCase();
        return eng === ENGINE_SN ? ENGINE_SN : ENGINE_CK;
    }

    function elfinderBrowseUrl(editorId) {
        var cfg = window.MANAGE_EDITOR_CONFIG || {};
        var base = String(cfg.elfinderUrl || '../elFinder/elfinder_cke.html');
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        return base + sep + 'Type=Images&CKEditor=' + encodeURIComponent(editorId || '');
    }

    function openElfinder(editorId) {
        var url = elfinderBrowseUrl(editorId);
        window.__manageEditorElfinderTarget = editorId || '';
        window.open(url, 'manage_elfinder', 'width=980,height=640,scrollbars=yes,resizable=yes');
    }

    function bindElfinderListener() {
        if (elfinderListenBound) {
            return;
        }
        elfinderListenBound = true;
        window.addEventListener('message', function (ev) {
            var data = ev && ev.data;
            if (!data || data.type !== 'elfinder:selected') {
                return;
            }
            var url = String(data.url || '');
            if (!url) {
                return;
            }
            var targetId = window.__manageEditorElfinderTarget || '';
            ManageEditor.insertImage(url, targetId);
        });
        window.onElfinderFileSelected = function (url) {
            ManageEditor.insertImage(String(url || ''), window.__manageEditorElfinderTarget || '');
        };
    }

    /* ── CKEditor 4 ─────────────────────────────────────── */
    var ckAdapter = {
        init: function (id) {
            if (!id || typeof CKEDITOR === 'undefined') {
                return false;
            }
            var el = document.getElementById(id);
            if (!el) {
                return false;
            }
            if (CKEDITOR.instances[id]) {
                instances[id] = { engine: ENGINE_CK };
                return true;
            }
            CKEDITOR.replace(id);
            instances[id] = { engine: ENGINE_CK };
            return true;
        },
        setHtml: function (id, html) {
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[id]) {
                CKEDITOR.instances[id].setData(html);
                return true;
            }
            return false;
        },
        getHtml: function (id) {
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[id]) {
                return CKEDITOR.instances[id].getData();
            }
            return null;
        },
        sync: function () {
            if (typeof CKEDITOR === 'undefined') {
                return;
            }
            var id;
            for (id in CKEDITOR.instances) {
                if (!Object.prototype.hasOwnProperty.call(CKEDITOR.instances, id)) {
                    continue;
                }
                var inst = CKEDITOR.instances[id];
                if (inst && typeof inst.updateElement === 'function') {
                    inst.updateElement();
                }
            }
        },
        insertImage: function (id, url) {
            if (typeof CKEDITOR === 'undefined' || !CKEDITOR.instances[id]) {
                return false;
            }
            var ed = CKEDITOR.instances[id];
            var safe = String(url).replace(/"/g, '&quot;');
            if (ed.mode === 'wysiwyg') {
                ed.insertHtml('<img src="' + safe + '" alt="">');
            } else {
                ed.setData(ed.getData() + '\n<img src="' + safe + '" alt="">');
            }
            return true;
        },
        destroy: function (id) {
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[id]) {
                CKEDITOR.instances[id].destroy(true);
            }
            delete instances[id];
        }
    };

    /* ── Summernote ─────────────────────────────────────── */
    var snAdapter = {
        init: function (id) {
            if (!id || typeof $ === 'undefined' || !$.fn || !$.fn.summernote) {
                return false;
            }
            var $el = $('#' + id);
            if (!$el.length) {
                return false;
            }
            if ($el.next('.note-editor').length) {
                instances[id] = { engine: ENGINE_SN };
                return true;
            }
            $el.summernote({
                height: 200,
                lang: 'zh-TW',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'elfinderPicture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                fontNames: [
                    '新細明體', '標楷體', '微軟正黑體',
                    'Arial', 'Courier New', 'Georgia', 'Verdana', 'Tahoma', 'Times New Roman'
                ],
                fontSizes: ['12', '14', '16', '18', '20', '24', '28', '36'],
                buttons: {
                    elfinderPicture: function () {
                        var ui = $.summernote.ui;
                        return ui.button({
                            contents: '<i class="note-icon-picture"></i>',
                            tooltip: '圖片（檔案管理員）',
                            click: function () {
                                openElfinder(id);
                            }
                        }).render();
                    }
                }
            });
            instances[id] = { engine: ENGINE_SN };
            return true;
        },
        setHtml: function (id, html) {
            var $el = $('#' + id);
            if ($el.length && $el.next('.note-editor').length) {
                $el.summernote('code', html);
                return true;
            }
            return false;
        },
        getHtml: function (id) {
            var $el = $('#' + id);
            if ($el.length && $el.next('.note-editor').length) {
                return $el.summernote('code');
            }
            return null;
        },
        sync: function () {
            Object.keys(instances).forEach(function (id) {
                if (instances[id].engine !== ENGINE_SN) {
                    return;
                }
                var $el = $('#' + id);
                if ($el.length && $el.next('.note-editor').length) {
                    $el.val($el.summernote('code'));
                }
            });
            $('textarea.ckeditor').each(function () {
                var $el = $(this);
                if ($el.next('.note-editor').length) {
                    $el.val($el.summernote('code'));
                }
            });
        },
        insertImage: function (id, url) {
            var $el = $('#' + id);
            if ($el.length && $el.next('.note-editor').length) {
                $el.summernote('insertImage', url, function ($image) {
                    $image.attr('alt', '');
                });
                return true;
            }
            return false;
        },
        destroy: function (id) {
            var $el = $('#' + id);
            if ($el.length && $el.next('.note-editor').length) {
                $el.summernote('destroy');
            }
            delete instances[id];
        }
    };

    function adapterFor(id) {
        var meta = instances[id];
        if (meta && meta.engine === ENGINE_SN) {
            return snAdapter;
        }
        if (meta && meta.engine === ENGINE_CK) {
            return ckAdapter;
        }
        return resolveEngine() === ENGINE_SN ? snAdapter : ckAdapter;
    }

    var ManageEditor = {
        get engine() {
            return resolveEngine();
        },

        initAll: function () {
            bindElfinderListener();
            if (resolveEngine() === ENGINE_CK && typeof CKEDITOR !== 'undefined') {
                // 關掉 CK 預設自動 replace，統一由本層初始化
                CKEDITOR.replaceClass = '';
            }
            var nodes = document.querySelectorAll('textarea.ckeditor');
            var i;
            for (i = 0; i < nodes.length; i++) {
                if (nodes[i].id) {
                    ManageEditor.init(nodes[i].id);
                }
            }
        },

        init: function (id) {
            bindElfinderListener();
            var eng = resolveEngine();
            if (eng === ENGINE_SN) {
                return snAdapter.init(id);
            }
            return ckAdapter.init(id);
        },

        setHtml: function (id, html) {
            html = String(html == null ? '' : html);
            if (adapterFor(id).setHtml(id, html)) {
                return;
            }
            if (ckAdapter.setHtml(id, html)) {
                return;
            }
            if (snAdapter.setHtml(id, html)) {
                return;
            }
            var $field = $('#' + id);
            if ($field.length) {
                $field.val(html);
            }
        },

        getHtml: function (id) {
            var html = adapterFor(id).getHtml(id);
            if (html !== null) {
                return html;
            }
            html = ckAdapter.getHtml(id);
            if (html !== null) {
                return html;
            }
            html = snAdapter.getHtml(id);
            if (html !== null) {
                return html;
            }
            var $field = $('#' + id);
            return $field.length ? String($field.val() || '') : '';
        },

        syncToForm: function (/* form */) {
            ckAdapter.sync();
            snAdapter.sync();
        },

        insertImage: function (url, editorId) {
            url = String(url || '');
            if (!url) {
                return;
            }
            editorId = String(editorId || window.__manageEditorElfinderTarget || '');
            if (editorId && adapterFor(editorId).insertImage(editorId, url)) {
                return;
            }
            // fallback：找到第一個已初始化的編輯器
            var id;
            for (id in instances) {
                if (Object.prototype.hasOwnProperty.call(instances, id) && adapterFor(id).insertImage(id, url)) {
                    return;
                }
            }
        },

        destroy: function (id) {
            adapterFor(id).destroy(id);
        },

        openElfinder: openElfinder
    };

    window.ManageEditor = ManageEditor;

    function boot() {
        ManageEditor.initAll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, window.jQuery);
