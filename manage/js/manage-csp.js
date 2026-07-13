/**
 * 後台 UI 行為（CSP 相容：不使用 inline event handler / javascript: URL）
 */
(function () {
    'use strict';

    function manageFormValidationEscapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function manageFormValidationUniqueErrors(errors) {
        var unique = [];
        (errors || []).forEach(function (msg) {
            if (!msg) {
                return;
            }
            if (unique.indexOf(msg) === -1) {
                unique.push(msg);
            }
        });
        return unique;
    }

    /** 將焦點移到錯誤訊息區塊（需 tabindex="-1" 才可 focus） */
    function manageFocusErrorArea(errorArea, scroll) {
        if (!errorArea) {
            return;
        }
        if (!errorArea.hasAttribute('tabindex')) {
            errorArea.setAttribute('tabindex', '-1');
        }
        if (scroll !== false) {
            errorArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        try {
            errorArea.focus({ preventScroll: scroll === false });
        } catch (e) {
            errorArea.focus();
        }
    }

    /** 清除欄位旁 span（id 結尾 _txt）與 .input__errorTxt 的內容，錯誤僅顯示於 #formErrorArea */
    function manageClearInlineFieldErrors(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('[id$="_txt"]').forEach(function (el) {
            el.textContent = '';
        });
        scope.querySelectorAll('.input__errorTxt').forEach(function (el) {
            el.textContent = '';
        });
    }

    /**
     * 表單驗證失敗：集中顯示於 #formErrorArea / #formErrorList
     * @param {string[]} errors
     * @param {{focusField?: string, viewTab?: number, form?: HTMLElement, scroll?: boolean, stopLoading?: boolean}} options
     * @returns {false}
     */
    function manageFormValidationFail(errors, options) {
        options = options || {};
        if (typeof loading === 'function' && options.stopLoading !== false) {
            loading(0);
        }
        var formRoot = options.form || document.getElementById('form1');
        manageClearInlineFieldErrors(formRoot || document);

        var unique = manageFormValidationUniqueErrors(errors);
        var errorArea = document.getElementById('formErrorArea');
        var errorList = document.getElementById('formErrorList');
        if (errorArea && errorList && unique.length) {
            errorList.innerHTML = unique.map(function (msg) {
                return '<li>' + manageFormValidationEscapeHtml(msg) + '</li>';
            }).join('');
            errorArea.classList.remove('is-hidden');
            if (options.viewTab && typeof window.view_ans === 'function') {
                window.view_ans(options.viewTab);
            }
            manageFocusErrorArea(errorArea, options.scroll);
        } else if (unique.length) {
            window.alert(unique.join('\n'));
            var focusId = options.focusField;
            if (focusId) {
                var focusEl = document.getElementById(focusId);
                if (focusEl && typeof focusEl.focus === 'function') {
                    focusEl.focus();
                }
            }
        }
        return false;
    }

    /** 表單驗證通過：隱藏錯誤區並清除欄位旁提示 */
    function manageFormValidationOk(form) {
        var formRoot = form || document.getElementById('form1');
        manageClearInlineFieldErrors(formRoot || document);
        var okArea = document.getElementById('formErrorArea');
        var okList = document.getElementById('formErrorList');
        if (okArea && okList) {
            okList.innerHTML = '';
            okArea.classList.add('is-hidden');
        }
        return true;
    }

    window.manageFormValidationFail = manageFormValidationFail;
    window.manageFormValidationOk = manageFormValidationOk;
    window.manageClearInlineFieldErrors = manageClearInlineFieldErrors;

    var isSidebarOpen = true;
    var hasOrderChanged = false;

    /**
     * 帶 data-manage-action 的原生欄位：點擊僅用於聚焦／展開選單，行為改由 change 或 keydown 觸發
     */
    function isManageActionOnFormField(el) {
        if (!el) {
            return false;
        }
        var tag = (el.tagName || '').toUpperCase();
        if (tag === 'TEXTAREA' || tag === 'SELECT') {
            return true;
        }
        if (tag === 'INPUT') {
            var type = (el.getAttribute('type') || 'text').toLowerCase();
            return type !== 'button' && type !== 'submit' && type !== 'reset'
                && type !== 'checkbox' && type !== 'radio';
        }
        return false;
    }

    function updateToolbar() {
        var updateSortBtn = document.getElementById('update-sort-btn');
        if (!updateSortBtn) {
            return;
        }
        if (hasOrderChanged) {
            updateSortBtn.classList.add('--isAnim');
        } else {
            updateSortBtn.classList.remove('--isAnim');
        }
    }

    function toggleSidebar() {
        isSidebarOpen = !isSidebarOpen;
        var sidebar = document.getElementById('sidebar');
        var icon = document.getElementById('sidebarToggle-icon');
        if (!sidebar || !icon) {
            return;
        }
        if (isSidebarOpen) {
            sidebar.classList.remove('closed');
            sidebar.classList.add('open');
            icon.className = 'bi bi-chevron-left sidebar__toggleIcon';
        } else {
            sidebar.classList.remove('open');
            sidebar.classList.add('closed');
            icon.className = 'bi bi-chevron-right sidebar__toggleIcon';
        }
    }

    function doToggleSubmenu(id) {
        var menu = document.getElementById('submenu-' + id);
        var arrow = document.getElementById('arrow-' + id);
        var trigger = document.querySelector('[data-submenu-id="' + id + '"]');
        if (!menu || !arrow) {
            return;
        }
        var collapsed = menu.classList.toggle('is-collapsed');
        if (collapsed) {
            arrow.classList.remove('is-open');
        } else {
            arrow.classList.add('is-open');
        }
        if (trigger) {
            trigger.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    }

    function toggleMenu(id) {
        if (!isSidebarOpen) {
            toggleSidebar();
            setTimeout(function () {
                doToggleSubmenu(id);
            }, 50);
            return;
        }
        doToggleSubmenu(id);
    }

    function toggleExpand(id) {
        var detailRow = document.getElementById('detail-' + id);
        if (!detailRow) {
            return;
        }
        if (detailRow.classList.contains('is-collapsed')) {
            detailRow.classList.remove('is-collapsed');
            detailRow.classList.add('--isShow');
        } else {
            detailRow.classList.add('is-collapsed');
            detailRow.classList.remove('--isShow');
        }
    }

    function applyUploadToggleUi(el, uploadVal) {
        var isYes = uploadVal === 'Yes';
        el.setAttribute('data-upload', isYes ? 'Yes' : 'No');
        el.setAttribute('aria-pressed', isYes ? 'true' : 'false');
        el.setAttribute('aria-label', isYes ? '下架' : '上架');
        if (isYes) {
            el.classList.remove('--inactive');
            el.classList.add('--active');
        } else {
            el.classList.remove('--active');
            el.classList.add('--inactive');
        }
    }

    function toggleListUpload(el) {
        if (!el || el.disabled) {
            return;
        }
        var pkey = el.getAttribute('data-pkey') || '';
        if (!pkey) {
            return;
        }
        var current = el.getAttribute('data-upload') || 'No';
        var next = current === 'Yes' ? 'No' : 'Yes';
        var url = el.getAttribute('data-upload-url') || '_upload.php';
        var body = new URLSearchParams();
        body.set('PKey', pkey);
        body.set('Upload', next);

        el.disabled = true;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (res) {
                return res.text();
            })
            .then(function (text) {
                var msg = (text || '').trim();
                if (msg !== '' && msg !== 'OK') {
                    alert(msg);
                    return;
                }
                applyUploadToggleUi(el, next);
            })
            .catch(function () {
                alert('更新上下架失敗');
            })
            .finally(function () {
                el.disabled = false;
            });
    }

    /** 舊版列表：Upload{n} checkbox + PKey{n} */
    function chgUpload(rowIndex) {
        var form = getListForm();
        if (!form) {
            return;
        }
        var cb = form.querySelector('#Upload' + rowIndex);
        var pk = form.querySelector('#PKey' + rowIndex);
        if (!cb || !pk) {
            return;
        }
        var uploadVal = cb.checked ? 'Yes' : 'No';
        var url = form.getAttribute('data-upload-url') || '_upload.php';
        var body = new URLSearchParams();
        body.set('PKey', pk.value);
        body.set('Upload', uploadVal);

        cb.disabled = true;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (res) {
                return res.text();
            })
            .then(function (text) {
                var msg = (text || '').trim();
                if (msg !== '' && msg !== 'OK') {
                    alert(msg);
                    cb.checked = uploadVal !== 'Yes';
                    return;
                }
            })
            .catch(function () {
                alert('更新上下架失敗');
                cb.checked = uploadVal !== 'Yes';
            })
            .finally(function () {
                cb.disabled = false;
            });
    }

    function toggleActive(id) {
        var rowItem = document.querySelector('[data-id="' + id + '"]');
        if (!rowItem) {
            return;
        }
        var toggleBtn = rowItem.querySelector('.toggleSwitch[data-manage-action="toggle-upload"]');
        if (toggleBtn) {
            toggleListUpload(toggleBtn);
            return;
        }
        toggleBtn = rowItem.querySelector('.toggleSwitch');
        if (!toggleBtn) {
            return;
        }
        if (toggleBtn.classList.contains('--active')) {
            toggleBtn.classList.remove('--active');
            toggleBtn.classList.add('--inactive');
        } else {
            toggleBtn.classList.remove('--inactive');
            toggleBtn.classList.add('--active');
        }
    }

    function handleOrderChange() {
        hasOrderChanged = true;
        updateToolbar();
    }

    function getListForm() {
        return document.getElementById('form1') || document.forms['form1'];
    }

    function submitListForm() {
        var form = getListForm();
        if (form) {
            form.submit();
        }
    }

    function handleUpdateSort() {
        var form = getListForm();
        if (!form) {
            hasOrderChanged = false;
            updateToolbar();
            return;
        }
        var sortBtn = form.querySelector('[name="SortUpdate"]');
        if (sortBtn && (sortBtn.type === 'submit' || sortBtn.type === 'button')) {
            if (sortBtn.type === 'button') {
                sortBtn.click();
            } else {
                form.submit();
            }
        } else {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'SortUpdate';
            hidden.value = '更新順序';
            form.appendChild(hidden);
            form.submit();
        }
        hasOrderChanged = false;
        updateToolbar();
    }

    function getCheckedNidInputs() {
        return document.querySelectorAll("input[name='nid[]']:checked:not(:disabled)");
    }

    function delSelect() {
        var checked = getCheckedNidInputs();
        if (!checked.length) {
            alert('請至少選擇一個刪除項目！');
            return;
        }
        if (!confirm('刪除無法復原,確定要刪除?')) {
            return;
        }
        var form = getListForm();
        if (!form) {
            return;
        }
        var actionEl = form.querySelector('#Action') || form.querySelector('[name="Action"]');
        if (actionEl) {
            actionEl.value = 'del';
        }
        form.submit();
    }

    function applyBatchUploadToggleUi(checkedInputs, uploadVal) {
        var next = uploadVal === 'Yes' ? 'Yes' : 'No';
        checkedInputs.forEach(function (cb) {
            var rowItem = cb.closest('.tableRow__item')
                || document.querySelector('[data-id="' + cb.value + '"]');
            if (!rowItem) {
                return;
            }
            var toggleBtn = rowItem.querySelector('.toggleSwitch[data-manage-action="toggle-upload"]');
            if (toggleBtn) {
                applyUploadToggleUi(toggleBtn, next);
            }
        });
    }

    function batchUpload(uploadVal) {
        var checked = getCheckedNidInputs();
        if (!checked.length) {
            alert('請至少選擇一個項目！');
            return;
        }
        var form = getListForm();
        var url = (form && form.getAttribute('data-upload-url')) || '_upload.php';
        var body = new URLSearchParams();
        body.set('Batch', '1');
        body.set('Upload', uploadVal === 'Yes' ? 'Yes' : 'No');
        checked.forEach(function (cb) {
            body.append('nid[]', cb.value);
        });

        var batchBtns = document.querySelectorAll('[data-manage-action="batch"][data-batch="publish"], [data-manage-action="batch"][data-batch="archive"]');
        batchBtns.forEach(function (btn) {
            btn.disabled = true;
        });

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body.toString()
        })
            .then(function (res) {
                return res.text();
            })
            .then(function (text) {
                var msg = (text || '').trim();
                if (msg !== '' && msg !== 'OK') {
                    alert(msg);
                    return;
                }
                applyBatchUploadToggleUi(checked, uploadVal);
            })
            .catch(function () {
                alert('批次更新上下架失敗');
            })
            .finally(function () {
                batchBtns.forEach(function (btn) {
                    btn.disabled = false;
                });
            });
    }

    function handleBatchAction(action) {
        if (action === 'delete') {
            delSelect();
            return;
        }
        if (action === 'publish') {
            batchUpload('Yes');
            return;
        }
        if (action === 'archive') {
            batchUpload('No');
            return;
        }
        alert('批次操作: ' + action);
    }

    function selectAll() {
        document.querySelectorAll("input[name='nid[]']").forEach(function (cb) {
            if (!cb.disabled) {
                cb.checked = true;
            }
        });
    }

    function selectNone() {
        document.querySelectorAll("input[name='nid[]']").forEach(function (cb) {
            cb.checked = false;
        });
    }

    function toggleSelect(id) {
        var row = document.querySelector('[data-id="' + id + '"]');
        if (!row) {
            return;
        }
        var cb = row.querySelector('.customCheckbox');
        if (cb) {
            cb.checked = !cb.checked;
        }
    }

    /** 將列表搜尋條件附加至編輯頁 URL（Q_* 供 _submit.php 帶回 list） */
    function appendListSearchToUrl(url, form) {
        if (!form) {
            return url;
        }
        var parts = [];
        var sep = url.indexOf('?') >= 0 ? '&' : '?';

        function add(name, value) {
            if (value !== null && value !== undefined && String(value).trim() !== '') {
                parts.push(encodeURIComponent(name) + '=' + encodeURIComponent(String(value).trim()));
            }
        }

        var kwEl = form.querySelector('[name="Keywords"]');
        if (kwEl) {
            var def = kwEl.getAttribute('data-default-keywords') || '';
            var kw = kwEl.value.trim();
            if (kw !== '' && kw !== def) {
                add('Q_Keywords', kw);
            }
        }

        var i;
        for (i = 1; i <= 4; i++) {
            var cls = form.querySelector('[name="Class' + i + '"]');
            if (cls && cls.value) {
                add('Q_Class' + i, cls.value);
            }
        }

        var pageEl = form.querySelector('[name="Page"]');
        if (pageEl && pageEl.value) {
            add('Page', pageEl.value);
        }
        var sizeEl = form.querySelector('[name="PageSize"]');
        if (sizeEl && sizeEl.value) {
            add('PageSize', sizeEl.value);
        }

        ['OpenDate', 'EndDate'].forEach(function (dateName) {
            var dateEl = form.querySelector('[name="' + dateName + '"]');
            if (dateEl && dateEl.value) {
                add('Q_' + dateName, dateEl.value);
            }
        });

        add('Send', '搜尋');

        if (parts.length === 0) {
            return url;
        }
        return url + sep + parts.join('&');
    }

  /** 列表導向 add.php / update.php（相容舊版 Update 函式） */
    function manageUpdate(page, pkey) {
        var form = document.getElementById('form1') || document.forms['form1'];
        var url = page || 'list.php';
        if (pkey) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'PKey=' + encodeURIComponent(String(pkey));
        }
        if (form) {
            var manNo = form.querySelector('[name="manNo"]');
            var subNo = form.querySelector('[name="subNo"]');
            var qs = [];
            if (manNo && manNo.value) {
                qs.push('manNo=' + encodeURIComponent(manNo.value));
            }
            if (subNo && subNo.value) {
                qs.push('subNo=' + encodeURIComponent(subNo.value));
            }
            if (qs.length) {
                url += (url.indexOf('?') >= 0 ? '&' : '?') + qs.join('&');
            }
            url = appendListSearchToUrl(url, form);
        }
        location.href = url;
    }

    /**
     * 程式化 form.submit() 不會帶上 type=submit 按鈕；後端需 POST Submit=搜尋 才套用條件
     */
    function ensureListSearchSubmit(form) {
        if (!form) {
            return;
        }
        var hidden = form.querySelector('input[type="hidden"][name="Submit"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'Submit';
            form.appendChild(hidden);
        }
        hidden.value = '搜尋';
    }

    function validateListOpenDateRange(form) {
        if (!form) {
            return true;
        }
        var fromEl = form.querySelector('[name="OpenDate"]');
        var toEl = form.querySelector('[name="EndDate"]');
        if (!fromEl || !toEl) {
            return true;
        }
        var from = String(fromEl.value || '').trim();
        var to = String(toEl.value || '').trim();
        if (from === '' || to === '') {
            return true;
        }
        if (to < from) {
            window.alert('刊登日期（迄）不可小於刊登日期（起）');
            if (toEl.focus) {
                toEl.focus();
            }
            return false;
        }
        return true;
    }

    function listSearch(formId, workFile) {
        var form = document.getElementById(formId) || document.forms[formId];
        if (!form) {
            return;
        }
        if (!validateListOpenDateRange(form)) {
            return;
        }
        ensureListSearchSubmit(form);
        form.action = workFile || form.action;
        form.submit();
    }

    function getModulePKeyFromForm(form) {
        if (!form) {
            return 0;
        }
        var manNo = form.querySelector('[name="manNo"]');
        if (manNo && manNo.value) {
            var n = parseInt(manNo.value, 10);
            if (!isNaN(n) && n > 0) {
                return n;
            }
        }
        var mpk = form.querySelector('[name="Module_PKey"]');
        if (mpk && mpk.value) {
            var m = parseInt(mpk.value, 10);
            if (!isNaN(m) && m > 0) {
                return m;
            }
        }
        return 0;
    }

    function clearClassSelectOptions(selectEl) {
        if (!selectEl) {
            return;
        }
        var opts = selectEl.querySelectorAll('option');
        for (var i = opts.length - 1; i >= 1; i--) {
            opts[i].remove();
        }
    }

    function searchClassChange(el) {
        var form = document.getElementById(el.getAttribute('data-form-id') || 'form1')
            || document.forms['form1'];
        if (!form) {
            return;
        }
        var level = parseInt(el.getAttribute('data-class-level') || '1', 10);
        var moduleKey = getModulePKeyFromForm(form);

        for (var i = level + 1; i <= 4; i++) {
            var child = form.querySelector('#Class' + i);
            if (child) {
                child.value = '';
                child.disabled = true;
                if (typeof window.removeOptions === 'function' && typeof jQuery !== 'undefined') {
                    window.removeOptions('Class' + i);
                } else {
                    clearClassSelectOptions(child);
                }
            }
        }

        var nextId = 'Class' + (level + 1);
        var next = form.querySelector('#' + nextId);
        if (next && el.value) {
            next.disabled = false;
            if (typeof window.AjaxLoadSuccess === 'function') {
                window.AjaxLoadSuccess(nextId, el.value, moduleKey);
            }
        }

        if (el.getAttribute('data-auto-search') === '1') {
            listSearch(
                el.getAttribute('data-form-id') || 'form1',
                el.getAttribute('data-work-file') || ''
            );
        }
    }

    function openExternalUrl(url) {
        if (url) {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    }

    function gotoPage(page, formId) {
        var form = document.getElementById(formId) || document.forms[formId];
        if (!form) {
            return;
        }
        var pageEl = form.querySelector('[name="Page"]') || form.querySelector('#Page');
        if (pageEl) {
            pageEl.value = String(page);
        }
        ensureListSearchSubmit(form);
        form.submit();
    }

    function changePageSize(selectEl) {
        var formId = selectEl.getAttribute('data-form-id') || 'form1';
        var form = document.getElementById(formId) || document.forms[formId];
        if (!form) {
            return;
        }
        var sizeEl = form.querySelector('[name="PageSize"]') || form.querySelector('#PageSize');
        if (sizeEl) {
            sizeEl.value = selectEl.value;
        }
        var pageEl = form.querySelector('[name="Page"]') || form.querySelector('#Page');
        if (pageEl) {
            pageEl.value = '1';
        }
        ensureListSearchSubmit(form);
        form.submit();
    }

    function runClass1LangAction(el, mode) {
        if (typeof window.sel_lang === 'function') {
            window.sel_lang(mode === 'all' ? 1 : 2);
            return;
        }
        var checked = mode === 'all';
        document.querySelectorAll("input[id^='Show']").forEach(function (cb) {
            cb.checked = checked;
        });
    }

    function runClass1LangToggle(el) {
        var idx = parseInt(el.getAttribute('data-lang-index') || '0', 10);
        if (typeof window.selLange === 'function' && idx > 0) {
            window.selLange(idx);
        }
    }

    /** 切換多語系分頁（相容舊版 view_ans） */
    function viewLangTab(num) {
        var n = parseInt(String(num || ''), 10);
        if (!n) {
            return;
        }
        var tabNav = document.getElementById('tabNav_' + n);
        if (tabNav) {
            tabNav.click();
            return;
        }
        if (typeof jQuery !== 'undefined') {
            jQuery('#tabNav_' + n).trigger('click');
        }
    }

    /** 全選/取消全選顯示語系（相容舊版 sel_lang） */
    function selLangAll(mode) {
        var checked = parseInt(String(mode), 10) === 1;
        document.querySelectorAll('input[id^="Show"]').forEach(function (cb) {
            if (/^Show\d+$/.test(cb.id)) {
                cb.checked = checked;
            }
        });
    }

    /** 勾選語系時切到對應分頁（相容舊版 selLange） */
    function selLangOne(idx) {
        var n = parseInt(String(idx || ''), 10);
        if (!n) {
            return;
        }
        var cb = document.getElementById('Show' + n);
        if (cb && cb.checked) {
            viewLangTab(n);
        }
    }

    window.view_ans = viewLangTab;
    window.sel_lang = selLangAll;
    window.selLange = selLangOne;

    function runManageAction(action, el) {
        switch (action) {
            case 'toggle-sidebar':
                toggleSidebar();
                break;
            case 'toggle-submenu':
                toggleMenu(el.getAttribute('data-submenu-id') || '');
                break;
            case 'open-external':
                openExternalUrl(el.getAttribute('data-open-url') || '');
                break;
            case 'manage-logout':
                window.location.href = el.getAttribute('data-logout-url')
                    || 'index.php?Action=logout';
                break;
            case 'return-list': {
                var returnForm = document.getElementById('form1');
                if (!returnForm) {
                    break;
                }
                var listEl = returnForm.querySelector('[name="list"]');
                var listPath = listEl && listEl.value ? String(listEl.value).trim() : 'list.php';
                var slashIdx = listPath.lastIndexOf('/');
                if (slashIdx >= 0) {
                    listPath = listPath.slice(slashIdx + 1);
                }
                if (!listPath) {
                    listPath = 'list.php';
                }
                var backUrl = appendListSearchToUrl(listPath, returnForm);
                [
                    'manNo',
                    'subNo',
                    'Album_PKey',
                    'Question_PKey',
                    'Question_D_PKey',
                    'Product_PKey'
                ].forEach(function (name) {
                    var field = returnForm.querySelector('[name="' + name + '"]');
                    if (field && field.value) {
                        backUrl += (backUrl.indexOf('?') >= 0 ? '&' : '?')
                            + name + '=' + encodeURIComponent(field.value);
                    }
                });
                window.location.href = backUrl;
                break;
            }
            case 'select-all':
                selectAll();
                break;
            case 'select-none':
                selectNone();
                break;
            case 'batch':
                handleBatchAction(el.getAttribute('data-batch') || '');
                break;
            case 'update-sort':
                handleUpdateSort();
                break;
            case 'expand-row':
                toggleExpand(el.getAttribute('data-row-id') || '');
                break;
            case 'toggle-select':
                toggleSelect(el.getAttribute('data-row-id') || '');
                break;
            case 'toggle-active':
                toggleActive(el.getAttribute('data-row-id') || '');
                break;
            case 'toggle-upload':
                toggleListUpload(el);
                break;
            case 'manage-update':
                manageUpdate(
                    el.getAttribute('data-page') || '',
                    el.getAttribute('data-pkey') || ''
                );
                break;
            case 'manage-copy':
                manageUpdate(
                    el.getAttribute('data-page') || 'add.php',
                    el.getAttribute('data-pkey') || ''
                );
                break;
            case 'list-search':
                listSearch(
                    el.getAttribute('data-form-id') || 'form1',
                    el.getAttribute('data-work-file') || ''
                );
                break;
            case 'member-export': {
                var exportForm = document.getElementById(el.getAttribute('data-form-id') || 'form1')
                    || document.forms['form1'];
                if (!exportForm) {
                    break;
                }
                if (!validateListOpenDateRange(exportForm)) {
                    break;
                }
                ensureListSearchSubmit(exportForm);
                var frameName = 'member-export-frame';
                var frame = document.getElementById(frameName);
                if (!frame) {
                    frame = document.createElement('iframe');
                    frame.id = frameName;
                    frame.name = frameName;
                    frame.setAttribute('title', '會員匯出');
                    frame.style.display = 'none';
                    document.body.appendChild(frame);
                }
                var prevAction = exportForm.action;
                var prevTarget = exportForm.target;
                exportForm.action = 'output.php';
                exportForm.target = frameName;
                exportForm.submit();
                exportForm.target = prevTarget || '';
                exportForm.action = prevAction || '';
                break;
            }
            case 'order-export': {
                var orderExportForm = document.getElementById(el.getAttribute('data-form-id') || 'form1')
                    || document.forms['form1'];
                if (!orderExportForm) {
                    break;
                }
                if (!validateListOpenDateRange(orderExportForm)) {
                    break;
                }
                ensureListSearchSubmit(orderExportForm);
                var orderFrameName = 'order-export-frame';
                var orderFrame = document.getElementById(orderFrameName);
                if (!orderFrame) {
                    orderFrame = document.createElement('iframe');
                    orderFrame.id = orderFrameName;
                    orderFrame.name = orderFrameName;
                    orderFrame.setAttribute('title', '訂單匯出');
                    orderFrame.style.display = 'none';
                    document.body.appendChild(orderFrame);
                }
                var orderPrevAction = orderExportForm.action;
                var orderPrevTarget = orderExportForm.target;
                orderExportForm.action = 'output.php';
                orderExportForm.target = orderFrameName;
                orderExportForm.submit();
                orderExportForm.target = orderPrevTarget || '';
                orderExportForm.action = orderPrevAction || '';
                break;
            }
            case 'search-class-change':
                searchClassChange(el);
                break;
            case 'goto-page':
                gotoPage(
                    parseInt(el.getAttribute('data-page') || '1', 10),
                    el.getAttribute('data-form-id') || 'form1'
                );
                break;
            case 'page-size':
                changePageSize(el);
                break;
            case 'class1-lang-select':
                runClass1LangAction(el, el.getAttribute('data-lang-mode') || 'all');
                break;
            case 'class1-lang-toggle':
                runClass1LangToggle(el);
                break;
            case 'toggle-password':
                if (typeof window.show_pw === 'function') {
                    window.show_pw(el, el.getAttribute('data-target-id') || 'strPW');
                }
                break;
            case 'preview-help-toggle':
                toggleTtpShowZone(el);
                break;
            case 'preview-help-close':
                closeTtpShowZones();
                break;
            case 'show-list-view':
                if (typeof window.showListView === 'function') {
                    window.showListView();
                } else {
                    var listView = document.getElementById('view-list');
                    var editView = document.getElementById('view-edit');
                    if (listView) {
                        listView.classList.remove('is-hidden');
                    }
                    if (editView) {
                        editView.classList.add('is-hidden');
                    }
                }
                break;
            case 'save-edit':
                if (typeof window.saveEdit === 'function') {
                    window.saveEdit();
                } else {
                    alert('已儲存');
                }
                break;
            case 'show-layer':
                if (typeof window.showLayer === 'function') {
                    window.showLayer();
                }
                break;
            default:
                break;
        }
    }

    function bindCheckFileInputs() {
        if (window.__manageCheckFileDelegation) {
            return;
        }
        window.__manageCheckFileDelegation = true;

        /* 允許重選同一檔案時仍觸發 change */
        document.addEventListener('click', function (e) {
            var input = e.target;
            if (input && input.matches && input.matches('input[type=file][data-check-file]')) {
                input.value = '';
            }
        });

        document.addEventListener('change', function (e) {
            var input = e.target;
            if (!input || !input.matches || !input.matches('[data-check-file]')) {
                return;
            }
            if (typeof window.checkFile !== 'function') {
                return;
            }
            var parts = (input.getAttribute('data-check-file') || '').split(',');
            if (parts.length >= 3) {
                window.checkFile(parts[0], parseInt(parts[1], 10), parts[2]);
            }
        });
    }

    function closeTtpShowZones() {
        document.querySelectorAll('.ttpShowZone.active').forEach(function (zone) {
            zone.classList.remove('active');
            var trigger = zone.querySelector('.ttpShowZone__trigger');
            var panel = zone.querySelector('.ttpShow');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            if (panel) {
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    }

    function toggleTtpShowZone(el) {
        var zone = el.closest('.ttpShowZone');
        if (!zone) {
            return;
        }
        var willOpen = !zone.classList.contains('active');
        closeTtpShowZones();
        if (!willOpen) {
            return;
        }
        zone.classList.add('active');
        var trigger = zone.querySelector('.ttpShowZone__trigger');
        var panel = zone.querySelector('.ttpShow');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
        }
        if (panel) {
            panel.setAttribute('aria-hidden', 'false');
        }
    }

    function bindKeywordClear() {
        var kw = document.getElementById('Keywords');
        if (!kw) {
            return;
        }
        var defaultVal = kw.getAttribute('data-default-keywords') || '';
        kw.addEventListener('focus', function () {
            if (defaultVal && kw.value === defaultVal) {
                kw.value = '';
            }
        });
    }

    window.isSidebarOpen = isSidebarOpen;
    window.hasOrderChanged = hasOrderChanged;
    window.updateToolbar = updateToolbar;
    window.toggleSidebar = toggleSidebar;
    window.toggleMenu = toggleMenu;
    window.toggleExpand = toggleExpand;
    window.toggleActive = toggleActive;
    window.toggleListUpload = toggleListUpload;
    window.chgUpload = chgUpload;
    window.handleOrderChange = handleOrderChange;
    window.handleUpdateSort = handleUpdateSort;
    window.handleBatchAction = handleBatchAction;
    window.selectAll = selectAll;
    window.selectNone = selectNone;
    window.toggleSelect = toggleSelect;
    window.Update = manageUpdate;
    window.list_serach = listSearch;
    window.gotoPage = gotoPage;
    window.GotoPage = gotoPage;

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-manage-action]');
        if (!el) {
            return;
        }
        if (isManageActionOnFormField(el)) {
            return;
        }
        var action = el.getAttribute('data-manage-action');
        if (action === 'toggle-submenu' || action === 'open-external'
            || action === 'manage-logout' || action === 'return-list'
            || action === 'preview-help-toggle' || action === 'preview-help-close') {
            e.preventDefault();
        }
        if (action === 'preview-help-toggle' || action === 'preview-help-close') {
            e.stopPropagation();
        }
        runManageAction(action, el);
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.ttpShowZone')) {
            return;
        }
        closeTtpShowZones();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeTtpShowZones();
        }
    });

    function syncCkeditorToForm(form) {
        if (typeof CKEDITOR === 'undefined' || !form) {
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
    }

    function bindCustomFormValidation() {
        document.querySelectorAll('form[data-manage-validate]').forEach(function (form) {
            form.setAttribute('novalidate', 'novalidate');
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        syncCkeditorToForm(form);
        var validateFn = form.getAttribute('data-manage-validate');
        if (!validateFn || typeof window[validateFn] !== 'function') {
            return;
        }
        try {
            if (!window[validateFn](form)) {
                e.preventDefault();
                e.stopPropagation();
            }
        } catch (err) {
            console.error('表單驗證失敗:', err);
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    document.addEventListener('change', function (e) {
        var el = e.target.closest('[data-manage-action]');
        if (!el) {
            return;
        }
        var action = el.getAttribute('data-manage-action');
        if (action === 'list-search') {
            var tag = (el.tagName || '').toUpperCase();
            if (tag === 'INPUT' || tag === 'TEXTAREA') {
                return;
            }
        }
        runManageAction(action, el);
    });

    document.addEventListener('keydown', function (e) {
        var el = e.target.closest('[data-manage-action="list-search"]');
        if (!el || e.key !== 'Enter') {
            return;
        }
        e.preventDefault();
        runManageAction('list-search', el);
    });

    document.addEventListener('input', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('tableRow__sortInput')) {
            handleOrderChange();
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.nodeName !== 'FORM') {
            return;
        }
        if (form.id !== 'form1' && form.getAttribute('name') !== 'form1') {
            return;
        }
        if (!form.querySelector('[name="OpenDate"]') || !form.querySelector('[name="EndDate"]')) {
            return;
        }
        if (!validateListOpenDateRange(form)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        bindCustomFormValidation();
        bindCheckFileInputs();
        bindKeywordClear();
        if (typeof jQuery === 'undefined') {
            return;
        }
        var tabNav = jQuery('.tabsGp__tabs li');
        if (!tabNav.length) {
            return;
        }
        function showTab(num) {
            if (!num) {
                return;
            }
            tabNav.each(function () {
                var tabId = jQuery(this).attr('id');
                if (!tabId) {
                    return;
                }
                tabId = tabId.replace('tabNav_', '');
                jQuery('#tabNav_' + tabId).removeClass('--active');
                jQuery('#tabCon_' + tabId).removeClass('--active');
            });
            jQuery('#tabNav_' + num).addClass('--active');
            jQuery('#tabCon_' + num).addClass('--active');
        }
        var firstTab = tabNav.first();
        var firstTabId = firstTab.attr('id');
        if (firstTabId) {
            showTab(firstTabId.replace('tabNav_', ''));
        }
        tabNav.on('click', function () {
            var tabId = jQuery(this).attr('id');
            if (tabId) {
                showTab(tabId.replace('tabNav_', ''));
            }
        });
    });
})();
