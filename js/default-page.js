// 通行碼登入頁面專用 JavaScript（CSP 安全版本）
document.addEventListener('DOMContentLoaded', function() {
    // 移除 page-fade class
    document.body.classList.remove('page-fade');

    // 檢查是否有錯誤訊息需要顯示（從 data 屬性或隱藏元素讀取）
    var errorMsg = document.body.getAttribute('data-error-msg');
    if (errorMsg) {
        alert(errorMsg);
        document.body.removeAttribute('data-error-msg');
    }

    // 表單提交函數
    window.sbForm = function() {
        var theForm = document.form1;
        if (fieldCheck0(theForm)) {
            document.getElementById('Send').value = 'OK';
            document.getElementById('form1').submit();
        }
    };

    // 表單驗證函數
    window.fieldCheck0 = function(theForm) {
        var array = [];
        var flag = true;
        var passInput = document.getElementById('pass');
        var strPWTxt = document.getElementById('strPW_txt');

        if (passInput && passInput.value === '' && passInput.length > 0) {
            if (strPWTxt) {
                strPWTxt.textContent = "驗證通行碼空白";
            }
            array.push("pass");
            flag = false;
        } else {
            if (strPWTxt) {
                strPWTxt.textContent = "";
            }
        }

        if (flag === false) {
            var field = array[0];
            alert('發生錯誤，請填寫下列欄位');
            if (document.getElementById(field)) {
                document.getElementById(field).focus();
            }
        } else {
            return true;
        }
    };

    // 綁定表單提交按鈕
    var submitBtn = document.getElementById('Submit');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sbForm();
        });
    }

    // 綁定 Enter 鍵事件
    var passInput = document.getElementById('pass');
    if (passInput) {
        passInput.addEventListener('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                sbForm();
            }
        });
    }
});
