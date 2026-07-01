/*--------------------------------

使用方式：
<!-- 觸發按鈕 -->
<button data-popup-trigger="popUp_1">預設打開</button>
<button data-popup-trigger="popUp_cart1">購物車</button>
<button data-popup-trigger="popUp_login">登入</button>
<!-- 對應彈窗 -->
<div id="popUp_1" class="popUpWrap --isOpen" role="dialog" aria-labelledby="預設打開彈窗" aria-hidden="false"><div class="popUpWrap__inner">預設打開<a href="#" class="XX" data-close title="關閉">X</a></div></div>
<div id="popUp_cart1" class="popUpWrap" role="dialog" aria-labelledby="購物車彈窗" aria-hidden="true"><div class="popUpWrap__inner">購物車內容<a href="#" class="close" title="關閉">X</a></div></div>
<div id="popUp_login" class="popUpWrap" role="dialog" aria-labelledby="登入彈窗" aria-hidden="true"><div class="popUpWrap__inner">登入表單<a href="#" class="close" title="關閉">X</a></div></div>

--------------------------------*/

$(document).ready(function () {
    'use strict';

    /**
     * 通用彈窗系統 - 使用 AccessibilityManager 統一管理
     * 支援格式：data-popup-trigger="popUp_xx" 觸發 #popUp_xx
     * 符合源碼檢測、弱掃、滲透測試標準
     * 整合無障礙功能：鍵盤導航、聚焦管理、ARIA 屬性
     */

    // 定義彈窗相關的 CSS 選擇器
    var POPUP_SELECTORS = {
        containers: '.popUpWrap', // 支援多種類別名稱
        popups: '[id^="popUp_"]', // 支援多種類別名稱
        triggers: '[data-popup-trigger^="popUp_"]', // 使用 data 屬性觸發
        closeButtons: '.close, [data-close]'
    };

    // 使用漸進式無障礙系統
    if (typeof window.ProgressiveA11y !== 'undefined') {
        // console.log('使用漸進式無障礙系統處理彈窗');
        
        // 如果有完整的 AccessibilityManager，使用進階功能
        if (typeof AccessibilityManager !== 'undefined' && window.ProgressiveA11y.enabled) {
            // console.log('啟用進階彈窗無障礙功能');
            // 進階功能由 ProgressiveA11y 自動處理
        } else {
            // console.log('使用基礎彈窗無障礙功能');
            // 基礎功能由 ProgressiveA11y 自動處理
        }
    } else {
        // console.warn('ProgressiveA11y 未載入，使用基本彈窗無障礙功能');
    }

    // 遮罩元素會在初始化時添加，這裡不需要重複添加

    // 統一的開啟彈窗函數
    function openPopup($targetPopup, $trigger) {
        // 安全地關閉所有現有彈窗
        $(POPUP_SELECTORS.containers).each(function () {
            if ($(this).is(':visible')) {
                $(this).fadeOut(300, function() {
                    $(this).removeClass('--isOpen');
                    // 使用無障礙管理系統處理關閉
                    if (typeof window.popupAccessibility !== 'undefined') {
                        window.popupAccessibility.handleMenuStateChange($(this), $(this), false);
                    }
                });
            }
        });

        // 顯示目標彈窗
        $targetPopup.fadeIn(300).css('display', 'flex').addClass('--isOpen');
        
        // 確保遮罩存在並顯示
        var $mask = $('body').find('.js-mask');
        if ($mask.length === 0) {
            $mask = $('<span class="js-mask"></span>').hide();
            $('body').append($mask);
        }
        if ($mask.is(':hidden')) {
            $mask.fadeIn(300);
        }

        // 使用無障礙管理系統處理聚焦
        if (typeof window.popupAccessibility !== 'undefined') {
            window.popupAccessibility.handleMenuStateChange($trigger, $targetPopup, true);
        }

        // 設定 ARIA 屬性
        $targetPopup.attr('aria-hidden', 'false');
        if ($trigger) {
            $trigger.attr('aria-expanded', 'true');
        }

        // 為可訪問性添加焦點管理 - 聚焦到第一個可選取的元素
        setTimeout(function() {
            var $focusableElements = $targetPopup.find('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if ($focusableElements.length > 0) {
                $focusableElements.first().focus();
                // console.log('無障礙：彈窗開啟時聚焦到第一個可選取元素');
            } else {
                // 如果沒有可選取元素，則聚焦到彈窗本身
                $targetPopup.attr('tabindex', '-1').focus();
                // console.log('無障礙：彈窗開啟時聚焦到彈窗本身');
            }
        }, 350); // 等待淡入動畫完成

        // 防止背景滾動
        document.body.style.overflow = 'hidden';
        
        // console.log('彈窗已開啟:', $targetPopup.attr('id'));
    }

    // 統一的關閉彈窗函數
    function closePopup($targetPopup) {
        // 隱藏彈窗
        $targetPopup.fadeOut(300, function() {
            $(this).removeClass('--isOpen');
        });
        
        // 隱藏遮罩
        var $mask = $('body').find('.js-mask');
        if ($mask.length > 0) {
            $mask.fadeOut(300);
        }
        
        // 使用無障礙管理系統處理聚焦
        if (typeof window.popupAccessibility !== 'undefined') {
            window.popupAccessibility.handleMenuStateChange($targetPopup, $targetPopup, false);
        }
        
        // 設定 ARIA 屬性
        $targetPopup.attr('aria-hidden', 'true');
        
        // 恢復背景滾動
        document.body.style.overflow = '';
        
        // console.log('彈窗已關閉:', $targetPopup.attr('id'));
    }

    // 彈窗觸發器點擊事件處理
    $(document).on('click', POPUP_SELECTORS.triggers, function (e) {
        e.preventDefault();
        e.stopPropagation();

        // console.log('觸發器被點擊:', $(this).attr('data-popup-trigger')); // 調試輸出

        try {
            // 安全獲取觸發器的 data-popup-trigger 值
            var triggerValue = $(this).attr('data-popup-trigger');

            // 基本驗證
            if (!triggerValue || typeof triggerValue !== 'string' || triggerValue.length === 0) {
                console.error('PopUp System: 無效的觸發器值');
                return false;
            }

            // 嚴格驗證觸發器值格式：popUp_ + 允許的字符
            var validTriggerPattern = /^popUp_([a-zA-Z0-9_]+)$/;
            var matches = triggerValue.match(validTriggerPattern);

            if (!matches || matches.length < 2) {
                console.error('PopUp System: 觸發器值格式錯誤:', triggerValue);
                return false;
            }

            // 提取並驗證目標 ID
            var targetId = matches[1];

            // 驗證目標 ID 安全性（防止 XSS 和路徑穿越）
            if (!/^[a-zA-Z0-9_]+$/.test(targetId) || targetId.length > 50) {
                console.error('PopUp System: 目標 ID 包含非法字符或過長:', targetId);
                return false;
            }

            // 構建安全的彈窗選擇器
            var popupSelector = '#' + triggerValue;
            var $targetPopup = $(popupSelector);

            // 驗證目標彈窗是否存在
            if ($targetPopup.length === 0) {
                console.warn('PopUp System: 找不到對應的彈窗:', popupSelector);
                return false;
            }

            // 驗證目標元素是否為有效的彈窗
            if (!$targetPopup.hasClass('popUpWrap')) {
                console.error('PopUp System: 目標元素不是有效的彈窗:', popupSelector);
                return false;
            }

            // 使用統一的開啟函數
            openPopup($targetPopup, $(this));

            // 記錄操作（調試用，生產環境可移除）
            if (typeof console !== 'undefined' && console.log) {
                // console.log('PopUp System: 顯示彈窗', popupSelector);
            }

        } catch (error) {
            console.error('PopUp System: 處理觸發事件時發生錯誤:', error);
            return false;
        }

        return false;
    });

    // 遮罩點擊關閉功能 - 點擊彈窗背景（非內容區域）時關閉
    $(document).on('click', '.popUpWrap', function (e) {
        // 如果點擊的是彈窗本身（背景），而不是內容區域，則關閉彈窗
        if ($(e.target).is('.popUpWrap')) {
            // console.log('彈窗背景被點擊，關閉彈窗'); // 調試輸出
            closePopup($(this));
        }
    });
    
    // 遮罩點擊關閉功能（備用方案，如果遮罩在 body 下且可見）
    $(document).on('click', '.js-mask', function (e) {
        // console.log('遮罩被點擊，關閉彈窗'); // 調試輸出
        // 找到當前打開的彈窗（遮罩在 body 下，不能用 closest）
        var $popup = $('.popUpWrap.--isOpen:visible').first();
        if ($popup.length > 0) {
            closePopup($popup);
        }
    });

    // ESC 鍵關閉彈窗功能
    $(document).on('keydown', function (e) {
        if (e.keyCode === 27 || e.which === 27) { // ESC 鍵
            // console.log('ESC 鍵被按下'); // 調試輸出
            var $visiblePopups = $('.popUpWrap:visible');
            if ($visiblePopups.length > 0) {
                // console.log('關閉', $visiblePopups.length, '個可見彈窗'); // 調試輸出
                $visiblePopups.each(function() {
                    closePopup($(this));
                });
            }
        }
    });

    // Tab 鍵無障礙聚焦管理 - 使用 AccessibilityManager 處理
    if (typeof window.popupAccessibility !== 'undefined') {
        // 如果 AccessibilityManager 可用，使用其內建的 Tab 鍵處理
        // console.log('使用 AccessibilityManager 處理 Tab 鍵聚焦管理');
    } else {
        // 備用方案：基本的 Tab 鍵聚焦管理
        $(document).on('keydown', function (e) {
            if (e.keyCode === 9 || e.which === 9) { // Tab 鍵
                var $openPopup = $('.popUpWrap.--isOpen:visible').first();
                
                if ($openPopup.length > 0) {
                    // 獲取彈窗內所有可聚焦的元素
                    var $focusableElements = $openPopup.find('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
                    
                    if ($focusableElements.length > 0) {
                        var $firstElement = $focusableElements.first();
                        var $lastElement = $focusableElements.last();
                        var $activeElement = $(document.activeElement);
                        
                        // 如果當前焦點不在彈窗內，強制聚焦到第一個元素
                        if (!$openPopup.find($activeElement).length) {
                            e.preventDefault();
                            $firstElement.focus();
                            // console.log('無障礙：強制聚焦到彈窗第一個元素');
                        }
                        // 如果按 Shift+Tab 且焦點在第一個元素，則聚焦到最後一個元素
                        else if (e.shiftKey && $activeElement.is($firstElement)) {
                            e.preventDefault();
                            $lastElement.focus();
                            // console.log('無障礙：Shift+Tab 聚焦到彈窗最後一個元素');
                        }
                        // 如果按 Tab 且焦點在最後一個元素，則聚焦到第一個元素
                        else if (!e.shiftKey && $activeElement.is($lastElement)) {
                            e.preventDefault();
                            $firstElement.focus();
                            // console.log('無障礙：Tab 聚焦到彈窗第一個元素');
                        }
                    }
                }
            }
        });
    }

    // 防止彈窗內容點擊時關閉彈窗
    $(document).on('click', '.popUpWrap .popUp', function (e) {
        e.stopPropagation();
    });
    
    // 防止彈窗內容區域點擊時關閉彈窗
    $(document).on('click', '.popUpWrap__inner', function (e) {
        e.stopPropagation();
    });

    // 關閉按鈕點擊事件
    $(document).on('click', '.close, [data-close]', function (e) {
        e.preventDefault();
        e.stopPropagation();

        // console.log('關閉按鈕被點擊'); // 調試輸出

        // 直接找最近的 .popUpWrap 並關閉
        var $popup = $(this).closest('.popUpWrap');
        if ($popup.length > 0) {
            // console.log('找到彈窗，準備關閉:', $popup.attr('id')); // 調試輸出
            closePopup($popup);
        } else {
            console.warn('找不到要關閉的彈窗');
        }
    });

    // 初始化時隱藏所有彈窗並確保遮罩存在，若.popUpWrap.--isOpen則不隱藏
    $('.popUpWrap.--isOpen').each(function () {
        var $popup = $(this);
        var $body = $('body');
        
        // 使用統一的開啟函數處理預設開啟的彈窗
        if (typeof window.popupAccessibility !== 'undefined') {
            // 使用 AccessibilityManager 處理
            $popup.fadeIn(300).css('display', 'flex').addClass('--isOpen');
            window.popupAccessibility.handleMenuStateChange($popup, $popup, true);
        } else {
            // 備用方案：基本處理
            $popup.fadeIn(300).css('display', 'flex').addClass('--isOpen');
        }
        
        // 確保遮罩存在並顯示
        var $mask = $body.find('.js-mask');
        if ($mask.length === 0) {
            $mask = $('<span class="js-mask"></span>').hide();
            $body.append($mask);
        }
        if ($mask.is(':hidden')) {
            $mask.fadeIn(300);
        }
        
        // 設定 ARIA 屬性
        $popup.attr('aria-hidden', 'false');
        
        // 為預設開啟的彈窗添加聚焦管理
        setTimeout(function() {
            var $focusableElements = $popup.find('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if ($focusableElements.length > 0) {
                $focusableElements.first().focus();
                // console.log('無障礙：預設開啟彈窗聚焦到第一個可選取元素');
            } else {
                // 如果沒有可選取元素，則聚焦到彈窗本身
                $popup.attr('tabindex', '-1').focus();
                // console.log('無障礙：預設開啟彈窗聚焦到彈窗本身');
            }
        }, 350); // 等待淡入動畫完成
    });
    
    $('.popUpWrap:not(.--isOpen)').each(function () {
        var $popup = $(this);
        var $body = $('body');
        $popup.hide().removeClass('--isOpen');

        // 如果沒有打開的彈窗，則隱藏遮罩
        if ($('.popUpWrap.--isOpen:visible').length === 0) {
            var $mask = $body.find('.js-mask');
            if ($mask.length > 0) {
                $mask.hide();
            }
        }
        
        // 設定 ARIA 屬性
        $popup.attr('aria-hidden', 'true');
        
    });

    // console.log('彈窗系統已初始化，找到', $('.popUpWrap').length, '個彈窗');
    if (typeof window.popupAccessibility !== 'undefined') {
        // console.log('使用 AccessibilityManager 統一管理彈窗無障礙功能');
    } else {
        // console.log('使用基本彈窗無障礙功能');
    }
});