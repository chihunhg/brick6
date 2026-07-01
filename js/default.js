document.addEventListener("DOMContentLoaded", () => {
  // 移除淡入效果
  document.body.classList.remove("page-fade");

  // 處理動態背景圖片（CSP 安全）
  document.querySelectorAll('.pgBanner--dynamic[data-bg-image]').forEach(function(el) {
    const bgImage = el.getAttribute('data-bg-image');
    if (bgImage) {
      // 使用 CSS 變數而非 inline style
      el.style.setProperty('background-image', 'url(' + bgImage + ')');
      // 注意：雖然這裡仍使用 style.setProperty，但這是必要的動態行為
      // 如果 CSP 嚴格限制，可以改用 CSS class 配合 data 屬性，但需要預先定義所有可能的背景圖
    }
  });

  // !====Lozad.js
  // 防重複初始化
  if (window.lozadInitialized) return;

  // 基本相容性檢查（避免舊瀏覽器報錯）
  const canUseIO = "IntersectionObserver" in window;

  if (typeof lozad === "function" && canUseIO) {
    const observer = lozad(".lozad", {
      // 上下預載 200px，左右 0px，可依需求調整
      rootMargin: "200px 0px",
      threshold: 0.1,
      // 如需客製載入流程（背景圖、srcset/sizes），打開以下範例：
      // load: (el) => {
      //   if (el.dataset.backgroundImage) {
      //     el.style.backgroundImage = `url("${el.dataset.backgroundImage}")`;
      //   } else {
      //     if (el.dataset.src) el.src = el.dataset.src;
      //     if (el.dataset.srcset) el.srcset = el.dataset.srcset;
      //     if (el.dataset.sizes) el.sizes = el.dataset.sizes;
      //   }
      // },
      loaded: (el) => {
        el.classList.add("js--isLoaded");
      },
    });

    observer.observe();
    window.lozadInitialized = true;

    // 若未來有動態插入新內容，可在插入後再次呼叫：
    // observer.observe();
  } else {
    // Fallback 策略（可選）
    // 1) 什麼都不做（讓圖片以非 lazy 狀態載入）
    // 2) 或者嘗試動態載入 polyfill 後再初始化
  }
});

// !====WOW.js
$(window).on("load", function () {
  if (!/msie [6|7|8|9]/i.test(navigator.userAgent)) {
    setTimeout(function () {
      $("body").addClass("js--animateReady");
      new WOW({
        mobile: false, // 手機不啟用動畫
      }).init();
    }, 50);
  }
});

//!====gotop
$(document).ready(function () {
  // 確保 goTop 元素存在
  if ($("#goTop").length === 0) {
    // console.warn('goTop 元素不存在，請檢查 HTML 結構');
    return;
  }

  // console.log('goTop 功能已初始化');

  //滾動事件
  $(window).scroll(function () {
    var scrollTop = $(this).scrollTop();
    // console.log('滾動位置:', scrollTop);

    if (scrollTop > 500) {
      $("#goTop").fadeIn();
    } else {
      $("#goTop").fadeOut();
    }
    
    if (scrollTop > 500) {
      $(".sideBtn").fadeIn();
    } else {
      $(".sideBtn").fadeOut();
    }

  });

  //點擊事件
  $("#goTop").click(function () {
    // console.log('goTop 被點擊');
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      500
    );
    return false;
  });
});

document.addEventListener("DOMContentLoaded", function () {
  // !====防止跳於頂部
  document.querySelectorAll('a[href="#"]').forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
    });
  });
});
