// Carousel 轮播图组件
const Carousel = {
    carousels: new Map(),

    init() {
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const prevBtn = e.target.closest('.ux-carousel-arrow-prev');
            if (prevBtn) {
                const carousel = prevBtn.closest('.ux-carousel');
                if (carousel) {
                    this.prev(carousel);
                }
            }

            const nextBtn = e.target.closest('.ux-carousel-arrow-next');
            if (nextBtn) {
                const carousel = nextBtn.closest('.ux-carousel');
                if (carousel) {
                    this.next(carousel);
                }
            }

            const dot = e.target.closest('.ux-carousel-dot');
            if (dot) {
                const carousel = dot.closest('.ux-carousel');
                const index = parseInt(dot.dataset.index);
                if (carousel && !isNaN(index)) {
                    this.goTo(carousel, index);
                }
            }
        });

        // 自动播放
        document.querySelectorAll('.ux-carousel[data-carousel-autoplay="true"]').forEach(carousel => {
            const interval = parseInt(carousel.dataset.carouselInterval) || 3000;
            this.startAutoplay(carousel, interval);
        });
    },

    prev(carousel) {
        const current = this.getCurrentIndex(carousel);
        const total = carousel.querySelectorAll('.ux-carousel-slide').length;
        const newIndex = current === 0 ? total - 1 : current - 1;
        this.goTo(carousel, newIndex);
    },

    next(carousel) {
        const current = this.getCurrentIndex(carousel);
        const total = carousel.querySelectorAll('.ux-carousel-slide').length;
        const newIndex = current === total - 1 ? 0 : current + 1;
        this.goTo(carousel, newIndex);
    },

    goTo(carousel, index) {
        const slides = carousel.querySelectorAll('.ux-carousel-slide');
        const dots = carousel.querySelectorAll('.ux-carousel-dot');

        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });

        // 更新轨道位置
        const track = carousel.querySelector('.ux-carousel-track');
        if (track && !carousel.classList.contains('ux-carousel-fade')) {
            track.style.transform = `translateX(-${index * 100}%)`;
        }
    },

    getCurrentIndex(carousel) {
        const slides = carousel.querySelectorAll('.ux-carousel-slide');
        for (let i = 0; i < slides.length; i++) {
            if (slides[i].classList.contains('active')) {
                return i;
            }
        }
        return 0;
    },

    startAutoplay(carousel, interval) {
        setInterval(() => {
            if (!carousel.matches(':hover')) {
                this.next(carousel);
            }
        }, interval);
    }
};

export default Carousel;
