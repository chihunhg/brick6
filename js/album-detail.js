document.addEventListener('DOMContentLoaded', function () {
    var viewer = document.getElementById('albumViewer');
    if (!viewer || typeof Swiper === 'undefined') {
        return;
    }

    var swiperEl = viewer.querySelector('.albumViewer__swiper');
    var swiper = null;
    var lastFocus = null;

    function openViewer(index) {
        lastFocus = document.activeElement;
        viewer.classList.add('is-open');
        viewer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('albumViewer-open');

        if (!swiper) {
            swiper = new Swiper(swiperEl, {
                slidesPerView: 1,
                spaceBetween: 16,
                keyboard: {
                    enabled: true,
                    onlyInViewport: true,
                },
                navigation: {
                    nextEl: viewer.querySelector('.swiper-button-next'),
                    prevEl: viewer.querySelector('.swiper-button-prev'),
                },
                pagination: {
                    el: viewer.querySelector('.swiper-pagination'),
                    clickable: true,
                },
            });
        }

        swiper.slideTo(index, 0);
        var closeBtn = viewer.querySelector('.albumViewer__close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeViewer() {
        if (!viewer.classList.contains('is-open')) {
            return;
        }
        viewer.classList.remove('is-open');
        viewer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('albumViewer-open');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    document.querySelectorAll('[data-album-index]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openViewer(parseInt(btn.getAttribute('data-album-index'), 10) || 0);
        });
    });

    viewer.addEventListener('click', function (e) {
        var target = e.target;
        if (target instanceof Element && target.closest('[data-album-close]')) {
            e.preventDefault();
            closeViewer();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && viewer.classList.contains('is-open')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            closeViewer();
        }
    }, true);
});
