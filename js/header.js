
// !====下拉選單顯示方式配置區====
// 統一管理下拉選單的顯示/隱藏方式，避免在 CSS 和 JS 之間來回調整
// 可選值：
//   'slide'  - 使用 jQuery slideDown/slideUp 動畫（預設）
//   'css'    - 使用 CSS class 控制 display 屬性（CSP 安全，推薦）
const DROPDOWN_DISPLAY_MODE = 'css'; // 修改此處即可切換顯示方式

// 定義全域選擇器常數
const SELECTORS = {
  menuToggle: '[data-menu-toggle]',//開合主選單的按鈕
  dropdownToggle: '[data-toggle="dropdown"]',//開合下拉選單的按鈕
  dropdownMenu: '.dropdownMenu',//下拉選單的區塊
  dropdownMenuClose: '.dropdownMenu__close',//下拉選單的關閉按鈕
  navbar: '.navbar', //<header>
  body: 'body',//body
  navbarNav: '.navbarNav',//主選單區塊
  navLink: '.navLink',//主選單的按鈕(單元)
};

// 鍵盤按鍵常數
const KEYS = {
  ESCAPE: 27,
  ENTER: 13,
  SPACE: 32,
  ARROW_UP: 38,
  ARROW_DOWN: 40,
  TAB: 9,
  HOME: 36,
  END: 35
};

// 快取常用的 jQuery 物件
const $window = $(window);
const $document = $(document);
const $body = $(SELECTORS.body);

let prevWidth = $window.width(); // 宣告全域變數記錄前一次寬度
let dropdownManager = null; // 下拉選單管理器實例

$document.ready(function () {
  onResizeFunction();
  $window.on('resize', onResizeFunction);
  $window.on('scroll', onScrollFunction);
  
  // 初始化鍵盤無障礙功能（使用漸進式無障礙系統）
  initKeyboardAccessibility();
});

function onResizeFunction(e) {
  let currentWidth = $window.width();
  if (currentWidth !== prevWidth) {
    resetClass(); //手機<->電腦切換時初始化
    // 重新初始化下拉選單管理器
    if (dropdownManager) {
      dropdownManager.reinit();
    }
    prevWidth = currentWidth;
  }
  adjustHeaderHeight();//表頭高度變動時自動調節高度
  // updateNavLinks();

}
// function updateNavLinks() {
//   $('.navbarNav__item.dropdown').each(function () {
//     const $link = $(this).find('.navLink');

//     if (!$link.data('original-href')) {
//       $link.data('original-href', $link.attr('href'));
//     }

//     if ($(window).width() < 992) {
//       $link.attr('href', '#');
//     } else {
//       $link.attr('href', $link.data('original-href'));
//     }
//   });
// }
// updateNavLinks();

function resetClass() { //手機<->電腦切換時初始化，填入要執行的動作
  // 批量重置選單狀態
  $(SELECTORS.menuToggle + ', ' + SELECTORS.dropdownToggle).removeClass('--isOpen');
  
  // 重置 ARIA 屬性
  $(SELECTORS.menuToggle + ', ' + SELECTORS.dropdownToggle).attr('aria-expanded', 'false');

  // 重置所有 data-menu-toggle 對應的目標元素（處理多個選單）
  $(SELECTORS.menuToggle).each(function () {
    const $this = $(this);
    const menuClass = $this.attr('data-menu-toggle'); //取得data-menu-toggle的值
    const menuParentClass = $this.attr('data-menu-parent'); //取得data-menu-parent的值
    if (menuClass && menuParentClass) {
      const $target = $('.' + menuParentClass).find('.' + menuClass);
      // CSP 安全：使用 classList 而非 inline style
      $target.removeClass('--isOpen');
      // console.log('重置選單:', menuClass, '在', menuParentClass, '中');
    }
  });

  // 重置下拉選單並停止所有動畫
  // CSP 安全：使用 classList 而非 inline style
  $(SELECTORS.dropdownMenu).stop(true, true).removeClass('dropdownMenu--show dropdownMenu--hide');
  // 移除所有下拉選單內元素的聚焦能力
  $(SELECTORS.dropdownMenu + ' a, ' + SELECTORS.dropdownMenu + ' button').attr('tabindex', '-1');
  // 確保所有 .navSub 不可聚焦
  $('.navSub').attr('tabindex', '-1');

}

function onScrollFunction(e) {
  adjustHeaderHeight();//表頭高度變動時自動調節高度
}

//!====依header高度變動時自動調節高度
function adjustHeaderHeight() {
  var scrollTop = $window.scrollTop();
  var headerH = $('header').innerHeight();

  if (scrollTop > 250) {
    //表頭fixed
    $(SELECTORS.navbar).addClass('navbar--fixed');
  } else {
    $(SELECTORS.navbar + '.navbar--fixed').removeClass('navbar--fixed');
  }

  $body.css({
    '--headerH': headerH + 'px',
  });
}

// 下拉選單管理類別
class DropdownManager {
  constructor() {
    this.isInitialized = false;
    this.displayMode = DROPDOWN_DISPLAY_MODE; // 儲存顯示模式
    this.init();
  }

  // !====統一的顯示/隱藏方法====
  // 根據配置的顯示模式，統一處理下拉選單的顯示/隱藏
  // CSP 安全：所有模式都使用 classList，不使用 inline style
  showMenu($menu) {
    $menu.stop(true, true); // 停止所有動畫
    
    // 移除隱藏 class
    $menu.removeClass('dropdownMenu--hide');
    
    switch(this.displayMode) {
      case 'slide':
        $menu.slideDown();
        break;
      case 'css':
        // CSP 安全：使用 CSS class 而非 inline style
        $menu.addClass('dropdownMenu--show');
        break;
      default:
        $menu.slideDown(); // 預設使用 slide
    }
  }

  hideMenu($menu) {
    $menu.stop(true, true); // 停止所有動畫
    
    // 移除顯示 class
    $menu.removeClass('dropdownMenu--show');
    
    switch(this.displayMode) {
      case 'slide':
        $menu.slideUp();
        break;
      case 'css':
        // CSP 安全：使用 CSS class 而非 inline style
        $menu.addClass('dropdownMenu--hide');
        break;
      default:
        $menu.slideUp(); // 預設使用 slide
    }
  }

  init() {
    // 進入頁面時先判斷：如果 $toggle 沒有 .--isOpen，就隱藏 $menu
    $(SELECTORS.dropdownToggle).each((_, element) => {
      const $toggle = $(element);
      const $parent = $toggle.closest('.dropdown');
      const $menu = $parent.find(SELECTORS.dropdownMenu);
      
      if (!$toggle.hasClass('--isOpen')) {
        // 初始化時統一使用 hideMenu 方法
        this.hideMenu($menu);
      }
    });
    
    this.bindEvents();
    this.isInitialized = true;
  }

  // 重新初始化方法
  reinit() {
    this.unbindEvents();
    
    // 重新初始化時也先判斷：如果 $toggle 沒有 .--isOpen，就隱藏 $menu
    $(SELECTORS.dropdownToggle).each((_, element) => {
      const $toggle = $(element);
      const $parent = $toggle.closest('.dropdown');
      const $menu = $parent.find(SELECTORS.dropdownMenu);
      
      if (!$toggle.hasClass('--isOpen')) {
        // 重新初始化時統一使用 hideMenu 方法
        this.hideMenu($menu);
      }
    });
    
    this.bindEvents();
  }

  // 解除事件綁定
  unbindEvents() {
    $(SELECTORS.dropdownToggle).off('.dropdownManager');
    $('.dropdown').off('.dropdownManager'); // 解除 parent 上的 hover 事件
    $document.off('click.dropdownManager');
    $('.' + $(SELECTORS.dropdownToggle).first().attr('data-toggle')).siblings('*:not(".' + $(SELECTORS.dropdownToggle).first().attr('data-toggle') + '")').off('mouseenter.dropdownManager');
  }

  bindEvents() {
    if ($window.width() > 991) {
      // 桌面版：檢查 data-toggle-type
      $(SELECTORS.dropdownToggle).each((_, element) => {
        const $this = $(element);
        const $parent = $this.closest('.dropdown');

        if ($this.attr('data-toggle-type') === 'hover') {
          // Hover 模式
          $parent.on('mouseenter.dropdownManager', this.handleMouseEnter.bind(this));
          $parent.on('mouseleave.dropdownManager', this.handleMouseLeave.bind(this));
          
          // 支援點擊（無障礙鍵盤操作/混合裝置）
          $this.on('click.dropdownManager', (e) => {
            if ($this.attr('href') === '#') {
              e.preventDefault();
            }
            // 讓 AccessibilityManager 或鍵盤 Enter 觸發的點擊也能開合選單
            this.handleClick(e);
          });
        } else {
          // 傳統 Click 模式
          $this.on('click.dropdownManager', this.handleClick.bind(this));
        }
      });
    } else {
      // 手機版：只有 click 開關切換
      $(SELECTORS.dropdownToggle).on('click.dropdownManager', this.handleClick.bind(this));
    }

    // 添加鍵盤事件支援（Enter 和 Space 鍵）
    $(SELECTORS.dropdownToggle).on('keydown.dropdownManager', this.handleKeydown.bind(this));

    // 點擊其他地方關閉
    $document.on('click.dropdownManager', this.handleDocumentClick.bind(this));
  }

  handleMouseEnter(e) {
    const $parent = $(e.currentTarget);
    const $toggle = $parent.find(SELECTORS.dropdownToggle);
    const $menu = $parent.find(SELECTORS.dropdownMenu);

    // 清除既有的關閉計時器
    if ($parent.data('hoverTimeout')) {
      clearTimeout($parent.data('hoverTimeout'));
      $parent.removeData('hoverTimeout');
    }

    // 如果 $toggle 沒有 .--isOpen，先隱藏 $menu
    if (!$toggle.hasClass('--isOpen')) {
      this.hideMenu($menu);
    }

    // 停止之前的動畫並開啟選單
    $toggle.stop(true, true).addClass('--isOpen');
    $toggle.attr('aria-expanded', 'true');
    this.showMenu($menu);

    this.closeSiblings($parent);
  }

  handleMouseLeave(e) {
    const $parent = $(e.currentTarget);
    const $toggle = $parent.find(SELECTORS.dropdownToggle);
    const $menu = $parent.find(SELECTORS.dropdownMenu);

    // 設定延遲關閉
    const timeoutId = setTimeout(() => {
      // 關閉選單
      $toggle.stop(true, true).removeClass('--isOpen');
      $toggle.attr('aria-expanded', 'false');
      this.hideMenu($menu);
    }, 300); // 300ms 緩衝時間

    $parent.data('hoverTimeout', timeoutId);
  }

  handleKeydown(e) {
    const $this = $(e.currentTarget);
    
    switch(e.keyCode) {
      case KEYS.ENTER:
      case KEYS.SPACE:
        e.preventDefault();
        this.handleClick(e);
        break;
      case KEYS.ARROW_DOWN:
        e.preventDefault();
        if (!$this.hasClass('--isOpen')) {
          this.handleClick(e);
        }
        break;
    }
  }

  handleClick(e) {
    const $this = $(e.currentTarget);
    const $parentClass = $this.attr('data-toggle');
    const $parent = $this.parents('.' + $parentClass);
    
    // 開關切換
    $this.stop(true, true).toggleClass('--isOpen');
    const isOpen = $this.hasClass('--isOpen');
    $this.attr('aria-expanded', isOpen.toString());
    
    // 控制下拉選單的顯示/隱藏
    const $menu = $parent.find(SELECTORS.dropdownMenu);
    
    // 根據 isOpen 狀態控制選單顯示/隱藏
    if (isOpen) {
      this.showMenu($menu);
    } else {
      this.hideMenu($menu);
    }
    
    // 使用共用無障礙管理系統處理聚焦
    if (typeof window.headerAccessibility !== 'undefined') {
      const $menu = $parent.find(SELECTORS.dropdownMenu);
      window.headerAccessibility.handleMenuStateChange($this, $menu, isOpen);
    } else {
      // 備用方案：直接控制聚焦
      const $menuItems = $parent.find(SELECTORS.dropdownMenu + ' a, ' + SELECTORS.dropdownMenu + ' button');
      const $navSub = $parent.find('.navSub');
      if (isOpen) {
        // 開啟選單時，恢復聚焦能力
        $menuItems.attr('tabindex', '0');
        // 確保 .navSub 不可聚焦
        $navSub.attr('tabindex', '-1');
      } else {
        // 關閉選單時，移除聚焦能力
        $menuItems.attr('tabindex', '-1');
        // 確保 .navSub 不可聚焦
        $navSub.attr('tabindex', '-1');
      }
    }
    
    if (isOpen) {
      // 關閉其他兄弟選單
      this.closeSiblings($parent);
    }
  }

  handleDocumentClick(e) {
    const $target = $(e.target);
    if (!$target.closest(SELECTORS.dropdownMenu).length && !$target.closest(SELECTORS.dropdownToggle).length) {
      this.closeAllDropdowns();
    }
  }

  closeSiblings($parent) {
    $parent.siblings().find(SELECTORS.dropdownMenu).each((_, element) => {
      this.hideMenu($(element));
    });
    $parent.siblings().find(SELECTORS.dropdownToggle).stop(true, true).removeClass('--isOpen');
    // 更新兄弟選單的 ARIA 屬性
    $parent.siblings().find(SELECTORS.dropdownToggle).attr('aria-expanded', 'false');
    // 移除兄弟選單內元素的聚焦能力
    $parent.siblings().find(SELECTORS.dropdownMenu + ' a, ' + SELECTORS.dropdownMenu + ' button').attr('tabindex', '-1');
    // 確保兄弟選單的 .navSub 不可聚焦
    $parent.siblings().find('.navSub').attr('tabindex', '-1');
  }

  closeAllDropdowns() {
    $(SELECTORS.dropdownMenu).each((_, element) => {
      this.hideMenu($(element));
    });
    $(SELECTORS.dropdownToggle).stop(true, true).removeClass('--isOpen');
    // 更新 ARIA 屬性
    $(SELECTORS.dropdownToggle).attr('aria-expanded', 'false');
    // 移除所有下拉選單內元素的聚焦能力
    $(SELECTORS.dropdownMenu + ' a, ' + SELECTORS.dropdownMenu + ' button').attr('tabindex', '-1');
    // 確保所有 .navSub 不可聚焦
    $('.navSub').attr('tabindex', '-1');
  }

  // bindSiblingEvents() {//在兄弟層中，滑鼠滑入其他兄弟層(非下拉的任何元素)，會關閉已開啟的下拉選單
  //   const $parentClass = $(SELECTORS.dropdownToggle).first().attr('data-toggle');
  //   if ($parentClass) {
  //     $('.' + $parentClass).siblings('*:not(".' + $parentClass + '")').on('mouseenter.dropdownManager', (e) => {
  //       const $siblings = $(e.currentTarget).siblings('.' + $parentClass);
  //       $siblings.find(SELECTORS.dropdownToggle).stop(true, true).removeClass('--isOpen');
  //       $siblings.find(SELECTORS.dropdownMenu).stop(true, true).slideUp();
  //     });
  //   }
  // }
}


// !====選單開合
$(function () {
  //[data-menu-toggle] 這個屬性是給選單開合用的
  $(SELECTORS.menuToggle).on('click', function () {
    const $this = $(this);
    const menuClass = $this.attr('data-menu-toggle'); //取得data-menu-toggle的值
    const parentClass = $this.attr('data-menu-parent'); //取得data-menu-parent的值

    if (!menuClass || !parentClass) {
      // console.warn('data-menu-toggle 或 data-menu-parent 屬性未設定');
      return;
    }

    // 找到對應的目標元素
    const $targetElement = $('.' + parentClass).find('.' + menuClass);

    if ($targetElement.length === 0) {
      // console.warn('找不到對應的目標元素:', menuClass, '在', parentClass, '中');
      return;
    }

    // 當 data-menu-toggle 被點擊時，只要是相同的 data-menu-toggle 的值，data-menu-toggle按鈕元素都會增加或移除 --isOpen
    $('[data-menu-toggle="' + menuClass + '"]').toggleClass('--isOpen');

    // 檢查目標元素的顯示狀態並停止之前的動畫
    // 檢查是否有 --isOpen class，如果有則移除，如果沒有則增加
    const isCurrentlyOpen = $targetElement.hasClass('--isOpen');
    
    if (isCurrentlyOpen) {
      $targetElement.stop(true, true).removeClass('--isOpen');
      // 更新 ARIA 屬性
      $this.attr('aria-expanded', 'false');
    } else {
      $targetElement.stop(true, true).addClass('--isOpen');
      // 更新 ARIA 屬性
      $this.attr('aria-expanded', 'true');
      
      // 設定焦點到第一個可聚焦的元素（手機版）
      if ($window.width() <= 991) {
        setTimeout(() => {
          const $firstFocusable = $targetElement.find('a[tabindex="0"], button[tabindex="0"], a:not([tabindex]), button:not([tabindex])').first();
          if ($firstFocusable.length) {
            $firstFocusable.focus();
          }
        }, 300);
      }
      // 關閉其他兄弟選單
    }
  });
});

// !====鍵盤無障礙功能
function initKeyboardAccessibility() {
  // 使用漸進式無障礙系統
  if (typeof window.ProgressiveA11y !== 'undefined') {
    // console.log('使用漸進式無障礙系統');
    
    // 如果有完整的 AccessibilityManager，使用進階功能
    if (typeof AccessibilityManager !== 'undefined' && window.ProgressiveA11y.enabled) {
      // console.log('啟用進階無障礙功能');
      // 進階功能由 ProgressiveA11y 自動處理
    } else {
      // console.log('使用基礎無障礙功能');
      // 基礎功能由 ProgressiveA11y 自動處理
    }
  } else {
    // 備用方案：如果 ProgressiveA11y 未載入，使用原本的程式碼
    // console.warn('ProgressiveA11y 未載入，使用備用無障礙功能');
    
    // ESC 鍵關閉所有開啟的選單
    $document.on('keydown', function(e) {
      if (e.keyCode === KEYS.ESCAPE) {
        closeAllMenus();
        // 將焦點回到觸發元素
        const $openToggle = $(SELECTORS.dropdownToggle + '.--isOpen, ' + SELECTORS.menuToggle + '.--isOpen').first();
        if ($openToggle.length) {
          $openToggle.focus();
        }
      }
    });

    // 下拉選單的鍵盤導航
    $(SELECTORS.dropdownToggle).on('keydown', function(e) {
      const $this = $(this);
      
      switch(e.keyCode) {
        case KEYS.ENTER:
        case KEYS.SPACE:
          e.preventDefault();
          $this.click();
          break;
        case KEYS.ARROW_DOWN:
          e.preventDefault();
          if (!$this.hasClass('--isOpen')) {
            $this.click();
          } else {
            
          }
          break;
      }
    });

    // 子選單項目的鍵盤導航
    $(SELECTORS.dropdownMenu + ' a, ' + SELECTORS.dropdownMenu + ' button').on('keydown', function(e) {
      const $this = $(this);
      const $dropdown = $this.closest('.dropdown');
      const $toggle = $dropdown.find(SELECTORS.dropdownToggle);
      
      // 檢查下拉選單是否開啟，如果沒有開啟則不處理鍵盤事件
      if (!$toggle.hasClass('--isOpen')) {
        return;
      }
      
      const $allItems = $this.closest(SELECTORS.dropdownMenu).find('a[tabindex="0"], button[tabindex="0"], a:not([tabindex]), button:not([tabindex])');
      const currentIndex = $allItems.index($this);

      switch(e.keyCode) {
        case KEYS.ARROW_DOWN:
          e.preventDefault();
          const nextIndex = (currentIndex + 1) % $allItems.length;
          $allItems.eq(nextIndex).focus();
          break;
        case KEYS.ARROW_UP:
          e.preventDefault();
          const prevIndex = currentIndex === 0 ? $allItems.length - 1 : currentIndex - 1;
          $allItems.eq(prevIndex).focus();
          break;
        case KEYS.HOME:
          e.preventDefault();
          $allItems.first().focus();
          break;
        case KEYS.END:
          e.preventDefault();
          $allItems.last().focus();
          break;
        case KEYS.ESCAPE:
          e.preventDefault();
          const $parentToggle = $this.closest('.dropdown').find(SELECTORS.dropdownToggle);
          closeAllMenus();
          $parentToggle.focus();
          break;
        case KEYS.TAB:
          // Tab 鍵行為：關閉選單並繼續正常的 Tab 導航
          if (!e.shiftKey && currentIndex === $allItems.length - 1) {
            closeAllMenus();
          }
          break;
      }
    });

    // 手機選單按鈕的鍵盤支援
    $(SELECTORS.menuToggle).on('keydown', function(e) {
      if (e.keyCode === KEYS.ENTER || e.keyCode === KEYS.SPACE) {
        e.preventDefault();
        $(this).click();
      }
    });

    // 主選單項目的鍵盤導航（手機版）
    $(SELECTORS.navLink).on('keydown', function(e) {
      if ($window.width() <= 991) {
        const $this = $(this);
        const $allNavLinks = $(SELECTORS.navbarNav).find(SELECTORS.navLink);
        const currentIndex = $allNavLinks.index($this);

        switch(e.keyCode) {
          case KEYS.ARROW_DOWN:
            e.preventDefault();
            const nextIndex = (currentIndex + 1) % $allNavLinks.length;
            $allNavLinks.eq(nextIndex).focus();
            break;
          case KEYS.ARROW_UP:
            e.preventDefault();
            const prevIndex = currentIndex === 0 ? $allNavLinks.length - 1 : currentIndex - 1;
            $allNavLinks.eq(prevIndex).focus();
            break;
          case KEYS.HOME:
            e.preventDefault();
            $allNavLinks.first().focus();
            break;
          case KEYS.END:
            e.preventDefault();
            $allNavLinks.last().focus();
            break;
        }
      }
    });
  }
}

// 統一的關閉所有選單函數
function closeAllMenus() {
  // 關閉下拉選單
  if (dropdownManager) {
    dropdownManager.closeAllDropdowns();
  }
  
  // 關閉手機選單
  $(SELECTORS.menuToggle).removeClass('--isOpen').attr('aria-expanded', 'false');
  $(SELECTORS.menuToggle).each(function () {
    const $this = $(this);
    const menuClass = $this.attr('data-menu-toggle');
    const menuParentClass = $this.attr('data-menu-parent');
    if (menuClass && menuParentClass) {
      const $target = $('.' + menuParentClass).find('.' + menuClass);
      // CSP 安全：使用 classList 而非 inline style
      $target.removeClass('--isOpen');
    }
  });
  
  // 確保所有下拉選單內元素都不可聚焦
  $(SELECTORS.dropdownMenu + ' a, ' + SELECTORS.dropdownMenu + ' button').attr('tabindex', '-1');
  // 確保所有 .navSub 不可聚焦
  $('.navSub').attr('tabindex', '-1');
}

// !====下拉選單 - 使用類別管理
$(function () {
  dropdownManager = new DropdownManager();
});


$(SELECTORS.dropdownMenuClose).on('click', function () {
  // 找到最近的父層下拉選單容器
  const $dropdown = $(this).closest('.dropdown');
  
  if ($dropdown.length && dropdownManager) {
    // 關閉該下拉選單
    const $toggle = $dropdown.find(SELECTORS.dropdownToggle);
    const $menu = $dropdown.find(SELECTORS.dropdownMenu);
    
    // 移除開啟狀態
    $toggle.removeClass('--isOpen').attr('aria-expanded', 'false');
    
    // 使用統一的方法關閉選單
    dropdownManager.hideMenu($menu);
    
    // 將焦點回到觸發按鈕
    $toggle.focus();
  }
});