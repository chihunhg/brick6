/**
 * 無障礙管理系統 - 共用範本
 * 提供統一的無障礙功能管理，包括鍵盤導航、聚焦控制、ARIA 屬性管理等
 * 
 * 使用方式：
 * const accessibilityManager = new AccessibilityManager({
 *   selectors: {
 *     menuToggle: '[data-menu-toggle]',
 *     dropdownToggle: '[data-toggle="dropdown"]',
 *     dropdownMenu: '.dropdownMenu',
 *     sideNavLink: '.sideNavLink.--hasSub',
 *     sideNavDrop: '.sideNavDrop'
 *   }
 * });
 */

class AccessibilityManager {
  constructor(options = {}) {
    // 預設配置
    this.config = {
      selectors: {
        menuToggle: '[data-menu-toggle]',
        dropdownToggle: '[data-toggle="dropdown"]',
        dropdownMenu: '.dropdownMenu',
        sideNavLink: '.sideNavLink.--hasSub',
        sideNavDrop: '.sideNavDrop',
        navLink: '.navLink',
        navbarNav: '.navbarNav'
      },
      keys: {
        ESCAPE: 27,
        ENTER: 13,
        SPACE: 32,
        ARROW_UP: 38,
        ARROW_DOWN: 40,
        TAB: 9,
        HOME: 36,
        END: 35
      },
      animationDuration: 300,
      enableKeyboardNavigation: true,
      enableFocusManagement: true,
      enableAriaAttributes: true
    };

    // 合併使用者配置
    this.config = this.mergeConfig(this.config, options);
    
    // 快取 jQuery 物件
    this.$document = $(document);
    this.$window = $(window);
    this.$body = $('body');
    
    // 初始化
    this.init();
  }

  /**
   * 深度合併配置物件
   */
  mergeConfig(defaultConfig, userConfig) {
    const merged = { ...defaultConfig };
    
    for (const key in userConfig) {
      if (userConfig[key] && typeof userConfig[key] === 'object' && !Array.isArray(userConfig[key])) {
        merged[key] = this.mergeConfig(defaultConfig[key] || {}, userConfig[key]);
      } else {
        merged[key] = userConfig[key];
      }
    }
    
    return merged;
  }

  /**
   * 初始化無障礙功能
   */
  init() {
    if (this.config.enableKeyboardNavigation) {
      this.initKeyboardNavigation();
    }
    
    if (this.config.enableFocusManagement) {
      this.initFocusManagement();
    }
    
    if (this.config.enableAriaAttributes) {
      this.initAriaAttributes();
    }
  }

  /**
   * 初始化鍵盤導航
   */
  initKeyboardNavigation() {
    // ESC 鍵關閉所有開啟的選單
    this.$document.on('keydown.accessibility', (e) => {
      if (e.keyCode === this.config.keys.ESCAPE) {
        this.closeAllMenus();
        this.focusToLastTrigger();
      }
    });

    // 使用事件委託來綁定鍵盤事件，確保動態元素也能被處理
    this.$document.on('keydown.accessibility', this.config.selectors.dropdownToggle, (e) => {
      this.handleDropdownToggleKeydown(e);
    });

    // 下拉選單項目的鍵盤導航
    this.$document.on('keydown.accessibility', this.config.selectors.dropdownMenu + ' a, ' + this.config.selectors.dropdownMenu + ' button', (e) => {
      this.handleDropdownItemKeydown(e);
    });

    // 側選單按鈕的鍵盤支援
    $(this.config.selectors.sideNavLink).on('keydown.accessibility', (e) => {
      this.handleSideNavKeydown(e);
    });

    // 手機選單按鈕的鍵盤支援
    $(this.config.selectors.menuToggle).on('keydown.accessibility', (e) => {
      this.handleMenuToggleKeydown(e);
    });

    // 主選單項目的鍵盤導航（手機版）
    $(this.config.selectors.navLink).on('keydown.accessibility', (e) => {
      this.handleNavLinkKeydown(e);
    });
  }

  /**
   * 初始化聚焦管理
   */
  initFocusManagement() {
    // 初始化時禁用所有隱藏選單的聚焦
    this.disableHiddenMenusFocus();
  }

  /**
   * 初始化 ARIA 屬性
   */
  initAriaAttributes() {
    // 為所有選單按鈕設定初始 ARIA 屬性
    $(this.config.selectors.menuToggle + ', ' + this.config.selectors.dropdownToggle).attr('aria-expanded', 'false');
  }

  /**
   * 處理下拉選單按鈕的鍵盤事件
   */
  handleDropdownToggleKeydown(e) {
    const $this = $(e.currentTarget);
    
    switch(e.keyCode) {
      case this.config.keys.ENTER:
      case this.config.keys.SPACE:
        e.preventDefault();
        $this.click();
        break;
      case this.config.keys.ARROW_DOWN:
        e.preventDefault();
        if (!$this.hasClass('--isOpen')) {
          $this.click();
        }
        break;
    }
  }

  /**
   * 處理下拉選單項目的鍵盤事件
   */
  handleDropdownItemKeydown(e) {
    const $this = $(e.currentTarget);
    const $dropdown = $this.closest('.dropdown');
    const $toggle = $dropdown.find(this.config.selectors.dropdownToggle);
    
    // 檢查下拉選單是否開啟
    if (!$toggle.hasClass('--isOpen')) {
      return;
    }
    
    const $allItems = $this.closest(this.config.selectors.dropdownMenu)
      .find('a[tabindex="0"], button[tabindex="0"], a:not([tabindex]), button:not([tabindex])');
    const currentIndex = $allItems.index($this);

    switch(e.keyCode) {
      case this.config.keys.ARROW_DOWN:
        e.preventDefault();
        const nextIndex = (currentIndex + 1) % $allItems.length;
        $allItems.eq(nextIndex).focus();
        break;
      case this.config.keys.ARROW_UP:
        e.preventDefault();
        const prevIndex = currentIndex === 0 ? $allItems.length - 1 : currentIndex - 1;
        $allItems.eq(prevIndex).focus();
        break;
      case this.config.keys.HOME:
        e.preventDefault();
        $allItems.first().focus();
        break;
      case this.config.keys.END:
        e.preventDefault();
        $allItems.last().focus();
        break;
      case this.config.keys.ESCAPE:
        e.preventDefault();
        this.closeAllMenus();
        $toggle.focus();
        break;
      case this.config.keys.TAB:
        // Tab 鍵行為：關閉選單並繼續正常的 Tab 導航
        if (!e.shiftKey && currentIndex === $allItems.length - 1) {
          this.closeAllMenus();
        }
        break;
    }
  }

  /**
   * 處理側選單的鍵盤事件
   */
  handleSideNavKeydown(e) {
    if (e.keyCode === this.config.keys.ENTER || e.keyCode === this.config.keys.SPACE) {
      e.preventDefault();
      $(e.currentTarget).click();
    }
  }

  /**
   * 處理選單切換按鈕的鍵盤事件
   */
  handleMenuToggleKeydown(e) {
    if (e.keyCode === this.config.keys.ENTER || e.keyCode === this.config.keys.SPACE) {
      e.preventDefault();
      $(e.currentTarget).click();
    }
  }

  /**
   * 處理主選單項目的鍵盤事件（手機版）
   */
  handleNavLinkKeydown(e) {
    if (this.$window.width() <= 991) {
      const $this = $(e.currentTarget);
      const $allNavLinks = $(this.config.selectors.navbarNav).find(this.config.selectors.navLink);
      const currentIndex = $allNavLinks.index($this);

      switch(e.keyCode) {
        case this.config.keys.ARROW_DOWN:
          e.preventDefault();
          const nextIndex = (currentIndex + 1) % $allNavLinks.length;
          $allNavLinks.eq(nextIndex).focus();
          break;
        case this.config.keys.ARROW_UP:
          e.preventDefault();
          const prevIndex = currentIndex === 0 ? $allNavLinks.length - 1 : currentIndex - 1;
          $allNavLinks.eq(prevIndex).focus();
          break;
        case this.config.keys.HOME:
          e.preventDefault();
          $allNavLinks.first().focus();
          break;
        case this.config.keys.END:
          e.preventDefault();
          $allNavLinks.last().focus();
          break;
      }
    }
  }

  /**
   * 禁用隱藏選單的聚焦
   */
  disableHiddenMenusFocus() {
    // 禁用未展開的下拉選單
    $(this.config.selectors.dropdownMenu + ' a, ' + this.config.selectors.dropdownMenu + ' button').attr('tabindex', '-1');
    
    // 禁用未展開的側選單
    $(this.config.selectors.sideNavDrop).each((index, element) => {
      if (!$(element).hasClass('--isOpen')) {
        this.disableElementFocus($(element));
      }
    });
    
    // 確保所有 .navSub 不可聚焦
    $('.navSub').attr('tabindex', '-1');
  }

  /**
   * 禁用指定元素的聚焦
   */
  disableElementFocus($element) {
    if ($element && $element.length) {
      $element.find('a, button, input, select, textarea, [tabindex]').attr('tabindex', '-1');
    }
  }

  /**
   * 啟用指定元素的聚焦
   */
  enableElementFocus($element) {
    if ($element && $element.length) {
      $element.find('a, button, input, select, textarea, [tabindex]').attr('tabindex', '0');
    }
  }

  /**
   * 關閉所有選單
   */
  closeAllMenus() {
    // 關閉下拉選單
    $(this.config.selectors.dropdownToggle).removeClass('--isOpen').attr('aria-expanded', 'false');
    $(this.config.selectors.dropdownMenu).stop(true, true);
    
    // 關閉側選單
    $(this.config.selectors.sideNavLink).removeClass('--isOpen');
    $(this.config.selectors.sideNavDrop).removeClass('--isOpen');
    
    // 關閉手機選單
    $(this.config.selectors.menuToggle).removeClass('--isOpen').attr('aria-expanded', 'false');
    
    // 禁用所有隱藏選單的聚焦
    this.disableHiddenMenusFocus();
  }

  /**
   * 將焦點回到最後觸發的元素
   */
  focusToLastTrigger() {
    const $openToggle = $(this.config.selectors.dropdownToggle + '.--isOpen, ' + 
                        this.config.selectors.menuToggle + '.--isOpen, ' + 
                        this.config.selectors.sideNavLink + '.--isOpen').first();
    if ($openToggle.length) {
      $openToggle.focus();
    }
  }

  /**
   * 處理選單狀態變更
   */
  handleMenuStateChange($trigger, $target, isOpen) {
    if (this.config.enableAriaAttributes) {
      $trigger.attr('aria-expanded', isOpen.toString());
    }
    
    if (this.config.enableFocusManagement) {
      if (isOpen) {
        this.enableElementFocus($target);
        // 手機版時將焦點移到第一個可聚焦元素
        if (this.$window.width() <= 991) {
          setTimeout(() => {
            const $firstFocusable = $target.find('a[tabindex="0"], button[tabindex="0"], a:not([tabindex]), button:not([tabindex])').first();
            if ($firstFocusable.length) {
              $firstFocusable.focus();
            }
          }, this.config.animationDuration);
        }
      } else {
        this.disableElementFocus($target);
      }
    }
  }

  /**
   * 重新初始化（用於響應式切換）
   */
  reinit() {
    this.disableHiddenMenusFocus();
  }

  /**
   * 銷毀實例
   */
  destroy() {
    this.$document.off('.accessibility');
    $(this.config.selectors.dropdownToggle).off('.accessibility');
    $(this.config.selectors.dropdownMenu + ' a, ' + this.config.selectors.dropdownMenu + ' button').off('.accessibility');
    $(this.config.selectors.sideNavLink).off('.accessibility');
    $(this.config.selectors.menuToggle).off('.accessibility');
    $(this.config.selectors.navLink).off('.accessibility');
  }
}

// 匯出類別供其他檔案使用
if (typeof module !== 'undefined' && module.exports) {
  module.exports = AccessibilityManager;
} else if (typeof window !== 'undefined') {
  window.AccessibilityManager = AccessibilityManager;
}
