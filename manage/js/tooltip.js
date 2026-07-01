// tooltip.js
// <a href="javascript:;" data-toggle="tooltip" data-html="true" data-original-title="預約人數：0&lt;br&gt;看診位置：16診室&lt;br&gt;診間說明：一般門診&lt;br&gt;診間備註：">賴重佑</a>

(function () {
    'use strict';

    // 初始化所有 tooltip
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-toggle="tooltip"]');

        tooltipElements.forEach(function (element, index) {
            let tooltip = null;
            let isVisible = false;

            // 生成唯一的 ID
            const tooltipId = 'tooltip-' + index + '-' + Date.now();
            const describedById = 'aria-describedby-' + tooltipId;

            // 設置元素的 ARIA 屬性（無障礙）
            if (!element.hasAttribute('aria-describedby')) {
                element.setAttribute('aria-describedby', describedById);
            }

            // 確保元素可以被鍵盤聚焦（無障礙）
            if (element.tagName === 'A' && element.getAttribute('href') === 'javascript:;') {
                element.setAttribute('tabindex', '0');
            } else if (!element.hasAttribute('tabindex')) {
                element.setAttribute('tabindex', '0');
            }

            // tooltip 滑鼠事件處理函數
            let tooltipMouseEnterHandler = null;
            let tooltipMouseLeaveHandler = null;

            // 延遲計時器
            let hideTimeout = null;
            let showTimeout = null;

            // 清除延遲計時器
            function clearHideTimeout() {
                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }
            }

            function clearShowTimeout() {
                if (showTimeout) {
                    clearTimeout(showTimeout);
                    showTimeout = null;
                }
            }

            // 創建 tooltip 元素
            function createTooltip() {
                if (tooltip) return;

                tooltip = document.createElement('div');
                tooltip.className = 'tooltipCt';
                tooltip.id = describedById;
                tooltip.setAttribute('role', 'tooltip');
                tooltip.setAttribute('aria-live', 'polite');
                tooltip.setAttribute('aria-atomic', 'true');
                tooltip.setAttribute('aria-hidden', 'true'); // 初始狀態隱藏

                // 獲取內容
                const content = element.getAttribute('data-original-title') || element.getAttribute('title') || '';
                const allowHtml = element.getAttribute('data-html') === 'true';

                if (allowHtml) {
                    tooltip.innerHTML = content;
                } else {
                    tooltip.textContent = content;
                }

                // 添加箭頭
                const arrow = document.createElement('div');
                arrow.className = 'tooltipCt__arrow';
                arrow.setAttribute('aria-hidden', 'true');
                tooltip.appendChild(arrow);

                // 先添加到 body 才能獲取正確的尺寸
                document.body.appendChild(tooltip);

                // 為 tooltip 綁定滑鼠事件（只綁定一次）
                tooltipMouseEnterHandler = function() {
                    clearHideTimeout();
                };
                tooltipMouseLeaveHandler = function() {
                    hideTooltip();
                };
                tooltip.addEventListener('mouseenter', tooltipMouseEnterHandler);
                tooltip.addEventListener('mouseleave', tooltipMouseLeaveHandler);
            }

            // 顯示 tooltip
            function showTooltip() {
                if (!tooltip) createTooltip();
                if (isVisible) return;

                isVisible = true;

                // 獲取元素位置
                const rect = element.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

                // 計算 tooltip 尺寸（需要先添加到 DOM 才能獲取正確尺寸）
                const tooltipHeight = tooltip.offsetHeight;
                const tooltipWidth = tooltip.offsetWidth;
                const spacing = 8; // tooltip 與元素之間的間距

                // 檢查上方空間是否足夠
                const spaceAbove = rect.top;
                const spaceBelow = window.innerHeight - rect.bottom;
                const showAbove = spaceAbove >= tooltipHeight + spacing || spaceAbove > spaceBelow;

                let tooltipTop, tooltipLeft;

                if (showAbove) {
                    // 顯示在元素上方
                    tooltip.classList.remove('tooltipCt--bottom');
                    tooltipTop = rect.top + scrollTop - tooltipHeight - spacing;
                } else {
                    // 顯示在元素下方
                    tooltip.classList.add('tooltipCt--bottom');
                    tooltipTop = rect.bottom + scrollTop + spacing;
                }

                // 水平置中
                tooltipLeft = rect.left + scrollLeft + (rect.width / 2) - (tooltipWidth / 2);

                tooltip.style.top = tooltipTop + 'px';
                tooltip.style.left = tooltipLeft + 'px';

                // 重新獲取位置（因為可能已經調整過）
                const tooltipRect = tooltip.getBoundingClientRect();
                
                // 確保 tooltip 不會超出視窗左邊
                if (tooltipRect.left < 10) {
                    tooltip.style.left = (rect.left + scrollLeft + 10) + 'px';
                }
                // 確保 tooltip 不會超出視窗右邊
                if (tooltipRect.right > window.innerWidth - 10) {
                    tooltip.style.left = (rect.right + scrollLeft - tooltipWidth - 10) + 'px';
                }
                // 確保 tooltip 不會超出視窗上方（如果顯示在上方）
                if (showAbove && tooltipRect.top < 10) {
                    // 如果上方空間不足，改為顯示在下方
                    tooltip.classList.add('tooltipCt--bottom');
                    tooltip.style.top = (rect.bottom + scrollTop + spacing) + 'px';
                }
                // 確保 tooltip 不會超出視窗下方（如果顯示在下方）
                if (!showAbove && tooltipRect.bottom > window.innerHeight - 10) {
                    // 如果下方空間不足，改為顯示在上方
                    tooltip.classList.remove('tooltipCt--bottom');
                    tooltip.style.top = (rect.top + scrollTop - tooltipHeight - spacing) + 'px';
                }

                // 顯示 tooltip（無障礙：設置 aria-hidden）
                setTimeout(function () {
                    tooltip.classList.add('show');
                    tooltip.setAttribute('aria-hidden', 'false');
                }, 10);
            }

            // 隱藏 tooltip
            function hideTooltip(immediate = false) {
                clearHideTimeout();
                clearShowTimeout();

                if (!tooltip || !isVisible) return;

                if (immediate) {
                    // 立即隱藏
                    isVisible = false;
                    tooltip.classList.remove('show');
                    tooltip.classList.remove('tooltipCt--bottom');
                    tooltip.setAttribute('aria-hidden', 'true');
                } else {
                    // 延遲隱藏，給一點緩衝時間（避免快速移動時誤觸發）
                    hideTimeout = setTimeout(function () {
                        if (tooltip && isVisible) {
                            isVisible = false;
                            tooltip.classList.remove('show');
                            tooltip.classList.remove('tooltipCt--bottom');
                            tooltip.setAttribute('aria-hidden', 'true');
                        }
                    }, 100);
                }
            }

            // 強制隱藏（用於點擊外部區域等情況）
            function forceHideTooltip() {
                clearHideTimeout();
                clearShowTimeout();
                if (!tooltip || !isVisible) return;
                isVisible = false;
                tooltip.classList.remove('show');
                tooltip.classList.remove('tooltipCt--bottom');
                tooltip.setAttribute('aria-hidden', 'true');
            }

            // 移除 tooltip
            function removeTooltip() {
                clearHideTimeout();
                clearShowTimeout();
                if (tooltip) {
                    // 移除事件監聽器
                    if (tooltipMouseEnterHandler) {
                        tooltip.removeEventListener('mouseenter', tooltipMouseEnterHandler);
                    }
                    if (tooltipMouseLeaveHandler) {
                        tooltip.removeEventListener('mouseleave', tooltipMouseLeaveHandler);
                    }
                    tooltip.remove();
                    tooltip = null;
                    isVisible = false;
                    tooltipMouseEnterHandler = null;
                    tooltipMouseLeaveHandler = null;
                }
            }

            // 處理鍵盤事件（無障礙）
            function handleKeydown(event) {
                // Esc 鍵關閉 tooltip
                if (event.key === 'Escape' || event.keyCode === 27) {
                    forceHideTooltip();
                }
            }

            // 檢查滑鼠是否在元素或 tooltip 上
            function isMouseOverElementOrTooltip(event) {
                const target = event.target;
                const relatedTarget = event.relatedTarget;
                
                // 檢查是否在元素上
                if (element.contains(target) || element === target) {
                    return true;
                }
                
                // 檢查是否在 tooltip 上
                if (tooltip && (tooltip.contains(target) || tooltip === target)) {
                    return true;
                }
                
                // 檢查 relatedTarget（移出目標）
                if (relatedTarget) {
                    if (element.contains(relatedTarget) || element === relatedTarget) {
                        return true;
                    }
                    if (tooltip && (tooltip.contains(relatedTarget) || tooltip === relatedTarget)) {
                        return true;
                    }
                }
                
                return false;
            }

            // 處理滑鼠移入
            function handleMouseEnter(event) {
                clearHideTimeout();
                clearShowTimeout();
                showTimeout = setTimeout(function () {
                    showTooltip();
                }, 200); // 稍微延遲顯示，避免快速滑過時閃現
            }

            // 處理滑鼠移出
            function handleMouseLeave(event) {
                clearShowTimeout();
                // 延遲隱藏，給使用者一點時間移動到 tooltip 上
                hideTooltip();
            }

            // 綁定滑鼠事件
            element.addEventListener('mouseenter', handleMouseEnter);
            element.addEventListener('mouseleave', handleMouseLeave);
            element.addEventListener('click', forceHideTooltip);

            // 綁定鍵盤事件（無障礙：Tab 鍵聚焦時顯示 tooltip）
            element.addEventListener('focus', function() {
                clearHideTimeout();
                showTooltip();
            });
            element.addEventListener('blur', function() {
                forceHideTooltip();
            });
            element.addEventListener('keydown', handleKeydown);

            // 點擊頁面其他地方時隱藏 tooltip
            document.addEventListener('click', function(event) {
                if (isVisible && tooltip) {
                    const isClickOnElement = element.contains(event.target);
                    const isClickOnTooltip = tooltip.contains(event.target);
                    if (!isClickOnElement && !isClickOnTooltip) {
                        forceHideTooltip();
                    }
                }
            }, true);

            // 當頁面滾動或視窗大小改變時隱藏 tooltip
            window.addEventListener('scroll', forceHideTooltip, true);
            window.addEventListener('resize', forceHideTooltip);
        });
    }

    // DOM 載入完成後初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTooltips);
    } else {
        initTooltips();
    }
})();
