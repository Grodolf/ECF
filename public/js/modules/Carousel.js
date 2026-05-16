/**
 * Auto-playing image carousel with prev/next buttons, dot indicators, and swipe support.
 *
 * Expected DOM structure inside the root element ([data-carousel]):
 *   - .carousel-slide          — one element per slide
 *   - [data-carousel-dot]      — one dot per slide (order must match slides)
 *   - [data-carousel-prev]     — previous button (optional)
 *   - [data-carousel-next]     — next button (optional)
 *
 * Autoplay pauses on mouseenter and resumes on mouseleave.
 * Any manual interaction (button click, dot click, swipe) resets the autoplay timer.
 *
 * Instantiated automatically for every [data-carousel] element found in the document.
 */
class Carousel {
    #carousel;
    #slides;
    #dots;
    #prevBtn;
    #nextBtn;
    #currentIndex = 0;
    #autoplayInterval = null;
    /** Milliseconds between automatic slide advances. */
    #autoplayDelay = 5000;

    /**
     * @param {HTMLElement} element Root carousel element ([data-carousel]).
     */
    constructor(element) {
        this.#carousel = element;
        this.#slides = element.querySelectorAll('.carousel-slide');
        this.#dots = element.querySelectorAll('[data-carousel-dot]');
        this.#prevBtn = element.querySelector('[data-carousel-prev]');
        this.#nextBtn = element.querySelector('[data-carousel-next]');
        this.#init();
    }

    /**
     * Activates the slide at the given index and updates the corresponding dot.
     *
     * @param {number} index Zero-based slide index.
     */
    #showSlide(index) {
        this.#slides.forEach(s => s.classList.remove('active'));
        this.#dots.forEach(d => d.classList.remove('active'));
        this.#slides[index].classList.add('active');
        this.#dots[index].classList.add('active');
        this.#currentIndex = index;
    }

    /**
     * Starts the autoplay timer, advancing to the next slide every #autoplayDelay ms.
     */
    #startAutoplay() {
        this.#stopAutoplay();
        this.#autoplayInterval = setInterval(() => {
            const next = this.#currentIndex === this.#slides.length - 1 ? 0 : this.#currentIndex + 1;
            this.#showSlide(next);
        }, this.#autoplayDelay);
    }

    /**
     * Clears the autoplay timer without changing the current slide.
     */
    #stopAutoplay() {
        clearInterval(this.#autoplayInterval);
    }

    /**
     * Binds all event listeners and starts autoplay.
     *
     * Swipe detection uses a 50 px horizontal threshold: swipe left advances,
     * swipe right goes back.
     */
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
