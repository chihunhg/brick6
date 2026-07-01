document.addEventListener('DOMContentLoaded', function () {
  // ------------------------------
  // 這裡的程式碼會在 DOM 結構都準備好後才執行，不用管 script 標籤放哪裡，也不用手動等載入，直接包一層就好。是現代前端開發的好習慣。
  // 除非是「全域變數」、「馬上執行」的程式，否則都建議包在這裡。
  // JS、jQuery 都可以包在這裡。多個 DOMContentLoaded 監聽可以共存，但同一份程式碼不要重複包多層。
  // 'DOMContentLoaded' 相當於 jQuery 的 $(function() {...})，注意不要重複宣告。
  // ------------------------------

  // ....自行添加程式碼....

  //!=======錨點滾動功能
  function smoothScrollToAnchor() {
    // 監聽所有錨點連結的點擊事件
    $('a[href^="#"]').on('click', function (e) {
      const targetId = $(this).attr('href');

      // 如果是空錨點或只有 #，則不處理
      if (targetId === '#' || targetId === '') {
        return;
      }

      const $target = $(targetId);

      // 如果目標元素存在
      if ($target.length) {
        e.preventDefault();

        // 暫時禁用 CSS 的 smooth scroll behavior
        $('html').css('scroll-behavior', 'auto');

        // 獲取 header 高度（從 CSS 變數或直接計算）
        const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--headerH')) || $('header').outerHeight() || 0;

        // 計算目標位置（考慮 header 高度）
        const targetOffset = $target.offset().top - headerHeight;

        // 平滑滾動到目標位置
        $('html, body').animate({
          scrollTop: targetOffset
        }, 800, 'swing', function () {
          // 動畫完成後更新 URL 的 hash
          window.history.pushState(null, null, targetId);
          // 動畫完成後恢復 CSS 的 smooth scroll behavior
          $('html').css('scroll-behavior', 'smooth');
        });
      }
    });
  }

  // 初始化錨點滾動功能
  smoothScrollToAnchor();

  // 處理頁面載入時的錨點滾動（URL 中已包含錨點）
  function handleInitialAnchor() {
    const hash = window.location.hash;
    if (hash && hash !== '#') {
      const $target = $(hash);
      if ($target.length) {
        // 延遲執行，確保頁面完全載入
        setTimeout(() => {
          // 暫時禁用 CSS 的 smooth scroll behavior
          $('html').css('scroll-behavior', 'auto');

          const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--headerH')) || $('header').outerHeight() || 0;
          const targetOffset = $target.offset().top - headerHeight;

          $('html, body').animate({
            scrollTop: targetOffset
          }, 800, 'swing', function () {
            // 動畫完成後恢復 CSS 的 smooth scroll behavior
            $('html').css('scroll-behavior', 'smooth');
          });
        }, 100);
      }
    }
  }

  // 執行初始錨點滾動
  handleInitialAnchor();


});
