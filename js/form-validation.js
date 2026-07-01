/**
 * 前台表單驗證錯誤集中顯示（與後台 manage-csp.js 相同 API）
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

    function manageClearInlineFieldErrors(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('[id$="_txt"]').forEach(function (el) {
            el.textContent = '';
        });
        scope.querySelectorAll('.input__errorTxt, .errorTxt').forEach(function (el) {
            el.textContent = '';
        });
        scope.querySelectorAll('.errorLine').forEach(function (el) {
            el.classList.remove('errorLine');
        });
    }

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
            if (options.errorFields && options.errorFields.length) {
                options.errorFields.forEach(function (fieldId) {
                    var fieldEl = document.getElementById(fieldId);
                    if (fieldEl) {
                        fieldEl.classList.add('errorLine');
                    }
                });
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
})();
