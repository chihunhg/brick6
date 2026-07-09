(function () {
    'use strict';

    function closeItem(item) {
        var btn = item.querySelector('.faqItem__q');
        var panel = item.querySelector('.faqItem__a');
        if (!btn || !panel) {
            return;
        }
        btn.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
        item.classList.remove('is-open');
    }

    function openItem(item) {
        var btn = item.querySelector('.faqItem__q');
        var panel = item.querySelector('.faqItem__a');
        if (!btn || !panel) {
            return;
        }
        btn.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
        item.classList.add('is-open');
    }

    document.querySelectorAll('[data-faq-accordion]').forEach(function (root) {
        root.querySelectorAll('.faqItem__q').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.faqItem');
                if (!item) {
                    return;
                }
                var isOpen = btn.getAttribute('aria-expanded') === 'true';

                root.querySelectorAll('.faqItem').forEach(function (other) {
                    closeItem(other);
                });

                if (!isOpen) {
                    openItem(item);
                }
            });
        });
    });
})();
