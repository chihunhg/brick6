// 首頁專用 JavaScript（CSP 安全版本）
document.addEventListener('DOMContentLoaded', function() {
    function maxSlidesPerView(config) {
        let max = Number(config.slidesPerView) || 1;
        if (config.breakpoints) {
            Object.values(config.breakpoints).forEach((bp) => {
                const value = Number(bp && bp.slidesPerView);
                if (Number.isFinite(value) && value > max) {
                    max = value;
                }
            });
        }
        return max;
    }

    function withSafeLoop(swiperEl, baseConfig) {
        const slideCount = swiperEl.querySelectorAll('.swiper-slide').length;
        const slidesPerView = maxSlidesPerView(baseConfig);
        const slidesPerGroup = Number(baseConfig.slidesPerGroup) || 1;
        const required = Math.max(2, slidesPerView + slidesPerGroup);

        if (baseConfig.loop === true && slideCount < required) {
            return {
                ...baseConfig,
                loop: false,
                autoplay: false,
            };
        }

        return baseConfig;
    }

    // !====輪撥設定
        const banner = {// Swiper-banner
            // cssMode: true,
            loop: true,
            autoplay: {
                delay: 6000,
                disableOnInteraction: false
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            pagination: {
                el: ".swiper-pagination",
            },
            mousewheel: false,
            keyboard: true,
        };
    
        const swpMultiple = { //複數Swiper設定
            loop: true,
            // 手機預設一張，較直覺
            slidesPerView: 1,
            autoplay: {
                delay: 7000,
                disableOnInteraction: false
            },
            breakpoints: {
                480: {
                    slidesPerView: 1, 
                },
                640: {
                    slidesPerView: 2, 
                },
                991: {
                    slidesPerView: 3,
                },
            },
            pagination: {
                el: ".swiper-pagination",
                // type: "progressbar",
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev"
            },
        };
    
        // 初始化所有 Swiper
        const swipers = [];
    
        document.querySelectorAll('.ixNews').forEach((swiperEl, index) => {
            const config = {
                ...swpMultiple,
                pagination: {
                    ...swpMultiple.pagination,
                    el: swiperEl.querySelector('.swiper-pagination')
                },
                navigation: {
                    nextEl: swiperEl.querySelector('.swiper-button-next'),
                    prevEl: swiperEl.querySelector('.swiper-button-prev')
                }
            };
            // 正式指定容器，避免不同環境下行為不一
            swipers.push(new Swiper(swiperEl, withSafeLoop(swiperEl, config)));
        });
    
        document.querySelectorAll('.ixSer').forEach((swiperEl, index) => {
            const config = {
                ...swpMultiple,
                pagination: {
                    ...swpMultiple.pagination,
                    el: swiperEl.querySelector('.swiper-pagination')
                },
                navigation: {
                    nextEl: swiperEl.querySelector('.swiper-button-next'),
                    prevEl: swiperEl.querySelector('.swiper-button-prev')
                }
            };
            swipers.push(new Swiper(swiperEl, withSafeLoop(swiperEl, config)));
        });
    
        document.querySelectorAll('.banner').forEach((swiperEl, index) => {
            const config = {
                ...banner,
                effect: 'fade',        
                fadeEffect: { crossFade: true }, 
                spaceBetween: 30,
                pagination: {
                    ...banner.pagination,
                    el: swiperEl.querySelector('.swiper-pagination'),
                    clickable: true,
                },
                navigation: {
                    nextEl: swiperEl.querySelector('.swiper-button-next'),
                    prevEl: swiperEl.querySelector('.swiper-button-prev')
                }
            };
            swipers.push(new Swiper(swiperEl, withSafeLoop(swiperEl, config)));
        });
    });
    
    
            
    
    