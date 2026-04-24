class Carousel {
    #carousel;
    #slides;
    #dots;
    #prevBtn;
    #nextBtn;
    #currentIndex = 0;
    #autoplayInterval = null;
    #autoplayDelay = 5000;

    constructor(element) {
        this.#carousel = element;
        this.#slides = element.querySelectorAll('.carousel-slide');
        this.#dots = element.querySelectorAll('[data-carousel-dot]');
        this.#prevBtn = element.querySelector('[data-carousel-prev]');
        this.#nextBtn = element.querySelector('[data-carousel-next]');
        this.#init();
    }

    #showSlide(index) {
        this.#slides.forEach(s => s.classList.remove('active'));
        this.#dots.forEach(d => d.classList.remove('active'));
        this.#slides[index].classList.add('active');
        this.#dots[index].classList.add('active');
        this.#currentIndex = index;
    }

    #startAutoplay() {
        this.#autoplayInterval = setInterval(() => {
            const next = this.#currentIndex === this.#slides.length - 1 ? 0 : this.#currentIndex + 1;
            this.#showSlide(next);
        }, this.#autoplayDelay);
    }

    #stopAutoplay() {
        clearInterval(this.#autoplayInterval);
    }

    #init() {
        this.#startAutoplay();

        this.#prevBtn?.addEventListener('click', () => {
            this.#stopAutoplay();
            const prev = this.#currentIndex === 0 ? this.#slides.length - 1 : this.#currentIndex - 1;
            this.#showSlide(prev);
            this.#startAutoplay();
        });

        this.#nextBtn?.addEventListener('click', () => {
            this.#stopAutoplay();
            const next = this.#currentIndex === this.#slides.length - 1 ? 0 : this.#currentIndex + 1;
            this.#showSlide(next);
            this.#startAutoplay();
        });

        this.#dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                this.#stopAutoplay();
                this.#showSlide(index);
                this.#startAutoplay();
            });
        });

        let touchStartX = 0;

        this.#carousel.addEventListener('touchstart', (e) => {
            this.#stopAutoplay();
            touchStartX = e.changedTouches[0].screenX;
        });

        this.#carousel.addEventListener('touchend', (e) => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) >= 50) {
                const next = diff < 0
                    ? (this.#currentIndex === this.#slides.length - 1 ? 0 : this.#currentIndex + 1)
                    : (this.#currentIndex === 0 ? this.#slides.length - 1 : this.#currentIndex - 1);
                this.#showSlide(next);
            }
            this.#startAutoplay();
        });

        this.#carousel.addEventListener('mouseenter', () => this.#stopAutoplay());
        this.#carousel.addEventListener('mouseleave', () => this.#startAutoplay());
    }
}

document.querySelectorAll('[data-carousel]').forEach(el => new Carousel(el));
