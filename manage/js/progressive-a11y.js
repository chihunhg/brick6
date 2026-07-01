/**
 * 漸進式無障礙系統 - 接案型專案範本
 * 設計原則：沒有無障礙也要可以正常使用
 * 效能優化：最小化影響
 */

(function() {
    'use strict';
    
    // 全域設定
    window.ProgressiveA11y = {
        // 無障礙功能開關
        enabled: false,
        
        // 效能監控
        performance: {
            startTime: performance.now(),
            loadTime: 0
        },
        
        // 基礎功能（所有專案都有，效能影響極小）
        baseFeatures: {
            // 基本的 ESC 鍵支援（< 1KB）
            escKeySupport: true,
            // 基本的聚焦管理（< 2KB）
            basicFocusManagement: true,
            // 基本的 ARIA 屬性（< 1KB）
            basicAriaAttributes: true
        },
        
        // 進階功能（只有無障礙專案才有）
        advancedFeatures: {
            // 完整的鍵盤導航
            fullKeyboardNavigation: false,
            // 完整的聚焦管理
            fullFocusManagement: false,
            // 完整的 ARIA 屬性管理
            fullAriaManagement: false,
            // 螢幕閱讀器支援
            screenReaderSupport: false
        },
        
        // 初始化
        init: function(options) {
            options = options || {};
            
            // 檢查是否有無障礙需求
            this.enabled = options.enabled || this.detectAccessibilityNeeds();
            
            // 記錄載入時間
            this.performance.loadTime = performance.now() - this.performance.startTime;
            
            if (this.enabled) {
                this.initAdvancedFeatures();
                // console.log('🚀 漸進式無障礙：進階模式啟用', this.performance.loadTime + 'ms');
            } else {
                this.initBaseFeatures();
                // console.log('⚡ 漸進式無障礙：基礎模式啟用', this.performance.loadTime + 'ms');
            }
        },
        
        // 自動檢測無障礙需求（效能優化：只檢查一次）
        detectAccessibilityNeeds: function() {
            // 檢查 HTML 屬性（最快）
            if (document.body.hasAttribute('data-a11y') || 
                document.body.hasAttribute('data-accessibility')) {
                return true;
            }
            
            // 檢查 CSS 類別
            if (document.body.classList.contains('a11y-enabled') ||
                document.body.classList.contains('accessibility-enabled')) {
                return true;
            }
            
            // 檢查全域變數
            if (window.A11Y_ENABLED === true || window.ACCESSIBILITY_ENABLED === true) {
                return true;
            }
            
            // 檢查 URL 參數（最後檢查，因為比較慢）
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('a11y') === 'true' || 
                urlParams.get('accessibility') === 'true') {
                return true;
            }
            
            return false;
        },
        
        // 初始化基礎功能（效能優化：最小化程式碼）
        initBaseFeatures: function() {
            // 基本的 ESC 鍵支援
            if (this.baseFeatures.escKeySupport) {
                this.initEscKeySupport();
            }
            
            // 基本的聚焦管理
            if (this.baseFeatures.basicFocusManagement) {
                this.initBasicFocusManagement();
            }
            
            // 基本的 ARIA 屬性
            if (this.baseFeatures.basicAriaAttributes) {
                this.initBasicAriaAttributes();
            }
        },
        
        // 初始化進階功能
        initAdvancedFeatures: function() {
            // 檢查 AccessibilityManager 是否可用
            if (typeof AccessibilityManager !== 'undefined') {
                this.initWithAccessibilityManager();
            } else {
                // console.warn('AccessibilityManager 未載入，使用基礎功能');
                this.initBaseFeatures();
            }
        },
        
        // 使用 AccessibilityManager 初始化
        initWithAccessibilityManager: function() {
            // 創建統一的無障礙管理實例
            window.progressiveA11y = new AccessibilityManager({
                selectors: {
                    // 通用選擇器
                    button: 'button, .btn, [role="button"]',
                    link: 'a[href], [role="link"]',
                    menu: '.menu, [role="menu"], .dropdown-menu, .popUpWrap',
                    menuItem: '.menu-item, [role="menuitem"], .dropdown-item',
                    modal: '.modal, .popup, .popUpWrap',
                    form: 'form, .form',
                    input: 'input, textarea, select'
                },
                features: {
                    keyboardNavigation: true,
                    focusManagement: true,
                    ariaAttributes: true,
                    responsiveSupport: true
                },
                keys: {
                    ENTER: 13,
                    SPACE: 32,
                    ESCAPE: 27,
                    TAB: 9,
                    ARROW_UP: 38,
                    ARROW_DOWN: 40,
                    HOME: 36,
                    END: 35
                }
            });
            
            // 啟用進階功能
            this.advancedFeatures.fullKeyboardNavigation = true;
            this.advancedFeatures.fullFocusManagement = true;
            this.advancedFeatures.fullAriaManagement = true;
            this.advancedFeatures.screenReaderSupport = true;
        },
        
        // 基本的 ESC 鍵支援（效能優化：事件委託）
        initEscKeySupport: function() {
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 27) { // ESC 鍵
                    // 關閉所有開啟的選單和彈窗
                    const openElements = document.querySelectorAll('.menu.--isOpen, .dropdown-menu.--isOpen, .modal.--isOpen, .popUpWrap.--isOpen');
                    openElements.forEach(function(element) {
                        element.classList.remove('--isOpen');
                        element.style.display = 'none';
                        element.setAttribute('aria-hidden', 'true');
                    });
                }
            });
        },
        
        // 基本的聚焦管理（效能優化：只在需要時執行）
        initBasicFocusManagement: function() {
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 9) { // Tab 鍵
                    const openModal = document.querySelector('.modal.--isOpen, .popup.--isOpen, .popUpWrap.--isOpen');
                    if (openModal) {
                        const focusableElements = openModal.querySelectorAll('button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                        if (focusableElements.length > 0) {
                            const firstElement = focusableElements[0];
                            const lastElement = focusableElements[focusableElements.length - 1];
                            
                            if (e.shiftKey && document.activeElement === firstElement) {
                                e.preventDefault();
                                lastElement.focus();
                            } else if (!e.shiftKey && document.activeElement === lastElement) {
                                e.preventDefault();
                                firstElement.focus();
                            }
                        }
                    }
                }
            });
        },
        
        // 基本的 ARIA 屬性（效能優化：只設定一次）
        initBasicAriaAttributes: function() {
            // 自動設定基本的 ARIA 屬性
            document.querySelectorAll('button[data-toggle], [data-dropdown]').forEach(function(button) {
                if (!button.hasAttribute('aria-haspopup')) {
                    button.setAttribute('aria-haspopup', 'true');
                }
                if (!button.hasAttribute('aria-expanded')) {
                    button.setAttribute('aria-expanded', 'false');
                }
            });
            
            document.querySelectorAll('.menu, .dropdown-menu, .popUpWrap').forEach(function(menu) {
                if (!menu.hasAttribute('role')) {
                    menu.setAttribute('role', 'menu');
                }
                if (!menu.hasAttribute('aria-hidden')) {
                    menu.setAttribute('aria-hidden', 'true');
                }
            });
        },
        
        // 手動啟用無障礙功能
        enable: function() {
            this.enabled = true;
            this.initAdvancedFeatures();
        },
        
        // 手動停用無障礙功能
        disable: function() {
            this.enabled = false;
            this.initBaseFeatures();
        },
        
        // 檢查功能是否可用
        hasFeature: function(feature) {
            if (this.enabled) {
                return this.advancedFeatures[feature] || false;
            } else {
                return this.baseFeatures[feature] || false;
            }
        },
        
        // 取得功能狀態
        getStatus: function() {
            return {
                enabled: this.enabled,
                features: this.enabled ? this.advancedFeatures : this.baseFeatures,
                hasAccessibilityManager: typeof AccessibilityManager !== 'undefined',
                performance: this.performance
            };
        },
        
        // 效能監控
        getPerformance: function() {
            return {
                loadTime: this.performance.loadTime,
                memoryUsage: performance.memory ? performance.memory.usedJSHeapSize : 'N/A',
                timestamp: new Date().toISOString()
            };
        }
    };
    
    // 自動初始化
    document.addEventListener('DOMContentLoaded', function() {
        window.ProgressiveA11y.init();
    });
    
})();
