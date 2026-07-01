document.addEventListener('DOMContentLoaded', function () {
    // =====================側選單-巢狀選單開合_sidebar.php=====================
    // 使用共用的無障礙管理系統
    if (typeof AccessibilityManager !== 'undefined') {
        // 初始化側選單無障礙功能
        window.sideNavAccessibility = new AccessibilityManager({
            selectors: {
                sideNavLink: '.--hasSub .togSubBtn, .--hasSub:not(:has(.togSubBtn))',
                sideNavDrop: '.sideNavDrop'
            },
            enableKeyboardNavigation: true,
            enableFocusManagement: true,
            enableAriaAttributes: true
        });

        // 側選單點擊和鍵盤事件處理（支援多層選單）
        document.querySelectorAll('.--hasSub .togSubBtn, .--hasSub:not(:has(.togSubBtn))').forEach(function (link) {
            // 點擊事件
            link.addEventListener('click', function (e) {
                e.preventDefault();
                toggleMenu(this);
            });

            // 鍵盤事件
            link.addEventListener('keydown', function (e) {
                // Enter 鍵或 Space 鍵觸發
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter 或 Space
                    e.preventDefault();
                    toggleMenu(this);
                }
            });
        });

        // ESC 鍵處理已由 AccessibilityManager 統一管理

        // 初始化選單聚焦狀態
        function initMenuFocus() {
            document.querySelectorAll('.sideNavDrop').forEach(function (drop, index) {
                var menuItem = drop.closest('.sideNav__item, .sideNavSub__item');
                var hasSubElement = menuItem ? menuItem.querySelector('.--hasSub') : null;
                var isDropOpen = drop.classList.contains('--isOpen');
                var isHasSubOpen = hasSubElement && hasSubElement.classList.contains('--isOpen');
                
                if (isDropOpen || isHasSubOpen) {
                    // 預設展開的選單：確保相關元素有正確的類別
                    if (hasSubElement) {
                        hasSubElement.classList.add('--isOpen');
                    }
                    drop.classList.add('--isOpen');
                }
            });
            
            // 使用重新初始化函數來確保正確的聚焦狀態
            reinitializeAllMenuFocus();
        }
        
        // 立即執行初始化
        initMenuFocus();
        
        // 如果 DOM 還沒完全載入，等待載入完成後再執行
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMenuFocus);
        }

        // 選單切換函數
        function toggleMenu(linkElement) {
            // 找到最近的選單項目父元素（支援多層結構）
            var menuItem = linkElement.closest('.sideNav__item, .sideNavSub__item');
            var hasSubElement = linkElement.closest('.--hasSub'); // 找到包含 togSubBtn 的 --hasSub 元素
            var sub = menuItem ? menuItem.querySelector('.sideNavDrop') : null;
            
            if (sub && hasSubElement) {
                var $sub = $(sub);
                var $link = $(linkElement);

                // 直接檢查 --isOpen 類別來判斷選單狀態
                const isCurrentlyOpen = sub.classList.contains('--isOpen');

                if (isCurrentlyOpen) {
                    // 收合
                    sub.classList.remove('--isOpen');
                    hasSubElement.classList.remove('--isOpen');
                    
                    // 重新初始化所有選單的聚焦狀態
                    reinitializeAllMenuFocus();
                    // 使用共用系統處理聚焦
                    window.sideNavAccessibility.handleMenuStateChange($link, $sub, false);
                } else {
                    // 展開
                    sub.classList.add('--isOpen');
                    hasSubElement.classList.add('--isOpen');
                    
                    // 重新初始化所有選單的聚焦狀態
                    reinitializeAllMenuFocus();
                    // 使用共用系統處理聚焦
                    window.sideNavAccessibility.handleMenuStateChange($link, $sub, true);
                }
            }
        }

        // 禁用選單聚焦（支援多層選單）
        function disableMenuFocus(dropElement) {
            if (dropElement) {
                // 禁用當前層級的所有可聚焦元素（包括所有 ul 層級）
                var currentElements = $(dropElement).find('ul li a, ul li button, ul li input, ul li select, ul li textarea, ul li [tabindex], .txt');
                currentElements.attr('tabindex', '-1');
                
                // 遞歸處理子層級的 sideNavDrop
                $(dropElement).find('.sideNavDrop').each(function() {
                    var subDrop = $(this);
                    
                    if (!subDrop.hasClass('--isOpen')) {
                        // 如果子層級沒有 --isOpen，禁用其內部元素
                        var subElements = subDrop.find('a, button, input, select, textarea, [tabindex], .txt');
                        subElements.attr('tabindex', '-1');
                    } else {
                        // 如果子層級有 --isOpen，遞歸處理
                        disableMenuFocus(subDrop[0]);
                    }
                });
            }
        }

        // 啟用選單聚焦（支援多層選單）
        function enableMenuFocus(dropElement) {
            if (dropElement) {
                // 只啟用直接子層級的可聚焦元素（不包含更深層的選單）
                var directChildren = $(dropElement).children('ul').find('> li > div > a, > li > div > button, > li > div > input, > li > div > select, > li > div > textarea, > li > div > [tabindex], > li > div > .txt');
                directChildren.attr('tabindex', '0');
                
                // 遞歸處理子層級的 sideNavDrop
                $(dropElement).find('.sideNavDrop').each(function() {
                    var subDrop = $(this);
                    
                    if (subDrop.hasClass('--isOpen')) {
                        // 如果子層級有 --isOpen，遞歸處理
                        enableMenuFocus(subDrop[0]);
                    } else {
                        // 如果子層級沒有 --isOpen，禁用其內部元素
                        var subElements = subDrop.find('a, button, input, select, textarea, [tabindex], .txt');
                        subElements.attr('tabindex', '-1');
                    }
                });
            }
        }
        
        // 重新初始化所有選單的聚焦狀態（新邏輯）
        function reinitializeAllMenuFocus() {
            // 找到所有可聚焦的元素
            var allFocusableElements = document.querySelectorAll('.sideNavDrop a, .sideNavDrop button, .sideNavDrop input, .sideNavDrop select, .sideNavDrop textarea, .sideNavDrop [tabindex], .sideNavDrop .txt');
            
            allFocusableElements.forEach(function(element, index) {
                // 往上找到第一個 sideNavDrop
                var parentSideNavDrop = element.closest('.sideNavDrop');
                var isParentOpen = parentSideNavDrop ? parentSideNavDrop.classList.contains('--isOpen') : false;
                
                if (isParentOpen) {
                    element.setAttribute('tabindex', '0');
                } else {
                    element.setAttribute('tabindex', '-1');
                }
            });
        }

    } else {
        // 備用方案：如果 AccessibilityManager 未載入，使用原本的程式碼
        // console.warn('AccessibilityManager 未載入，使用備用無障礙功能');

        document.querySelectorAll('.--hasSub .togSubBtn, .--hasSub:not(:has(.togSubBtn))').forEach(function (link) {
            // 點擊事件
            link.addEventListener('click', function () {
                toggleMenuFallback(this);
            });

            // 鍵盤事件
            link.addEventListener('keydown', function (e) {
                // Enter 鍵或 Space 鍵觸發
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter 或 Space
                    e.preventDefault();
                    toggleMenuFallback(this);
                }
            });
        });

        // 選單切換函數（備用方案）
        function toggleMenuFallback(linkElement) {
            // 找到最近的選單項目父元素（支援多層結構）
            var menuItem = linkElement.closest('.sideNav__item, .sideNavSub__item');
            var hasSubElement = linkElement.closest('.--hasSub'); // 找到包含 togSubBtn 的 --hasSub 元素
            var sub = menuItem ? menuItem.querySelector('.sideNavDrop') : null;
            
            if (sub && hasSubElement) {
                // 直接檢查 --isOpen 類別來判斷選單狀態
                const isCurrentlyOpen = sub.classList.contains('--isOpen');
                
                if (isCurrentlyOpen) {
                    // 收合
                    sub.classList.remove('--isOpen');
                    hasSubElement.classList.remove('--isOpen');
                    // 收合時禁用內部元素的鍵盤選取
                    disableMenuFocus(sub);
                } else {
                    // 展開
                    sub.classList.add('--isOpen');
                    hasSubElement.classList.add('--isOpen');
                    // 展開時啟用內部元素的鍵盤選取
                    enableMenuFocus(sub);
                }
            }
        }

        // 重新初始化所有選單的聚焦狀態（新邏輯）
        function reinitializeAllMenuFocus() {
            // 找到所有可聚焦的元素
            var allFocusableElements = document.querySelectorAll('.sideNavDrop a, .sideNavDrop button, .sideNavDrop input, .sideNavDrop select, .sideNavDrop textarea, .sideNavDrop [tabindex], .sideNavDrop .txt');
            
            allFocusableElements.forEach(function(element, index) {
                // 往上找到第一個 sideNavDrop
                var parentSideNavDrop = element.closest('.sideNavDrop');
                var isParentOpen = parentSideNavDrop ? parentSideNavDrop.classList.contains('--isOpen') : false;
                
                if (isParentOpen) {
                    element.setAttribute('tabindex', '0');
                } else {
                    element.setAttribute('tabindex', '-1');
                }
            });
        }

        // 初始化選單聚焦狀態
        function initMenuFocus() {
            document.querySelectorAll('.sideNavDrop').forEach(function (drop, index) {
                var menuItem = drop.closest('.sideNav__item, .sideNavSub__item');
                var hasSubElement = menuItem ? menuItem.querySelector('.--hasSub') : null;
                var isDropOpen = drop.classList.contains('--isOpen');
                var isHasSubOpen = hasSubElement && hasSubElement.classList.contains('--isOpen');
                
                if (isDropOpen || isHasSubOpen) {
                    // 預設展開的選單：確保相關元素有正確的類別
                    if (hasSubElement) {
                        hasSubElement.classList.add('--isOpen');
                    }
                    drop.classList.add('--isOpen');
                }
            });
            
            // 使用重新初始化函數來確保正確的聚焦狀態
            reinitializeAllMenuFocus();
        }
        
        // 立即執行初始化
        initMenuFocus();
        
        // 如果 DOM 還沒完全載入，等待載入完成後再執行
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initMenuFocus);
        }

    }

});