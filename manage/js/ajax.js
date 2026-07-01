/** 從列表表單取得模組主鍵（manNo 或 Module_PKey） */
function getModulePKey() {
    var form = document.getElementById('form1') || document.forms['form1'];
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

// ✅ 安全移除下拉選單中除了第一個選項之外的內容
function removeOptions(selectboxId) {
    $('#' + selectboxId + ' option:not(:first)').remove();
}

// ✅ 處理 AJAX 載入成功後的動作
function onComplete(selectboxId, responseJson) {
    if (!responseJson || typeof responseJson !== 'object' || !Array.isArray(responseJson.data)) {
        console.warn("Invalid response format:", responseJson);
        return;
    }

    removeOptions(selectboxId);

    const data = responseJson.data;
    const seen = new Set();

    for (let i = 0; i < data.length; i++) {
        const rawId = String(data[i].ID ?? '');
        if (rawId === '' || seen.has(rawId)) {
            continue;
        }
        seen.add(rawId);

        const ID = encodeURIComponent(rawId);
        const Name = decodeURIComponent(encodeURIComponent(data[i].Name ?? '')); // 雙層防 XSS

        $('#' + selectboxId).append(
            $('<option>', {
                value: ID,
                text: Name
            })
        );
    }
}

// ✅ AJAX 載入選單資料
function AjaxLoadSuccess(selectboxId, PKey, moduleKey = 0) {
    const postData = {
        PKey: PKey,
        RType: selectboxId,
        Module_PKey: moduleKey
    };

    $.ajax({
        type: 'POST',
        url: '../ajax/ajax.php',
        dataType: 'json', // ✅ 期待正確 JSON 格式
        data: postData,
        success: function (json) {
            onComplete(selectboxId, json);
        },
        error: function (xhr, status, error) {
            console.error("AJAX 錯誤：", status, error);
        }
    });
}

// ✅ 綁定下拉選單變更事件（含編輯頁；列表若用 data-manage-action 則由 manage-csp 處理）
$(function () {
    $('#Class1').on('change', function () {
        if (this.getAttribute('data-manage-action') === 'search-class-change') {
            return;
        }
        const PKey = $(this).val();
        const moduleKey = getModulePKey();
        removeOptions('Class2');
        removeOptions('Class3');
        removeOptions('Product_PKey');
        AjaxLoadSuccess('Class2', PKey, moduleKey);
    });

    $('#Class2').on('change', function () {
        if (this.getAttribute('data-manage-action') === 'search-class-change') {
            return;
        }
        const PKey = $(this).val();
        const moduleKey = getModulePKey();
        removeOptions('Class3');
        AjaxLoadSuccess('Class3', PKey, moduleKey);
    });

    $('#Class3').on('change', function () {
        if (this.getAttribute('data-manage-action') === 'search-class-change') {
            return;
        }
        const PKey = $(this).val();
        const moduleKey = getModulePKey();
        removeOptions('Class4');
        AjaxLoadSuccess('Class4', PKey, moduleKey);
    });
});

window.getModulePKey = getModulePKey;
window.removeOptions = removeOptions;
window.AjaxLoadSuccess = AjaxLoadSuccess;
