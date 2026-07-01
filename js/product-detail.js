document.addEventListener('DOMContentLoaded', function () {
    var gallery = document.querySelector('[data-product-gallery]');
    if (gallery) {
        var mainImg = gallery.querySelector('.productGallery__mainImg');
        var thumbs = gallery.querySelectorAll('.productGallery__thumb');

        thumbs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var src = btn.getAttribute('data-gallery-src');
                if (!src || !mainImg) {
                    return;
                }

                mainImg.src = src;
                var altImg = btn.querySelector('img');
                if (altImg && altImg.alt) {
                    mainImg.alt = altImg.alt;
                }

                thumbs.forEach(function (item) {
                    item.classList.remove('is-active');
                    item.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-pressed', 'true');
            });
        });
    }

    var bookmark = document.querySelector('[data-product-bookmark]');
    if (bookmark) {
        var tabs = bookmark.querySelectorAll('.productBookmark__tab');
        var panels = bookmark.querySelectorAll('.productBookmark__panel');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var slot = tab.getAttribute('data-bookmark-tab');
                if (!slot) {
                    return;
                }

                tabs.forEach(function (item) {
                    item.classList.remove('is-active');
                    item.setAttribute('aria-selected', 'false');
                });
                panels.forEach(function (panel) {
                    var isTarget = panel.getAttribute('data-bookmark-panel') === slot;
                    panel.classList.toggle('is-active', isTarget);
                    if (isTarget) {
                        panel.removeAttribute('hidden');
                    } else {
                        panel.setAttribute('hidden', '');
                    }
                });

                tab.classList.add('is-active');
                tab.setAttribute('aria-selected', 'true');
            });
        });
    }
});
