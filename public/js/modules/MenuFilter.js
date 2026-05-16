/** Absolute minimum value of the price range slider. */
const PRICE_MIN = 5;
/** Absolute maximum value of the price range slider. */
const PRICE_MAX = 25;

/**
 * Live AJAX filter for the menu list page.
 *
 * Listens for changes on all filter inputs (text, select, range sliders) and
 * POSTs the form to /menus/filter on every change, debounced at 500 ms for
 * free-text inputs. The response replaces the menu cards in the container and
 * updates the available theme/regime options to reflect the current result set.
 *
 * Expected DOM:
 *   - #menu-filter              — the filter <form>
 *   - #menus-container          — the card grid to replace on each response
 *   - #price-min-slider         — range input for minimum price (optional)
 *   - #price-max-slider         — range input for maximum price (optional)
 *   - #price-min-display        — element showing the current min price label
 *   - #price-max-display        — element showing the current max price label
 *   - .range-slider             — container whose --left/--right CSS vars drive the progress bar
 *   - #results-count            — element showing the result count string (optional)
 *   - #reset-filters            — button that resets all inputs and re-fetches (optional)
 *   - meta[name="csrf-token"]   — CSRF token passed in the X-CSRF-Token request header
 *
 * Instantiated automatically when both #menu-filter and #menus-container exist.
 */
class MenuFilter {
    #form;
    #container;
    #priceMinSlider;
    #priceMaxSlider;
    #priceMinDisplay;
    #priceMaxDisplay;
    #rangeSliderContainer;

    /**
     * @param {HTMLFormElement} formEl      The filter form (#menu-filter).
     * @param {HTMLElement}     containerEl The card grid to update (#menus-container).
     */
    constructor(formEl, containerEl) {
        this.#form = formEl;
        this.#container = containerEl;
        this.#priceMinSlider = document.querySelector('#price-min-slider');
        this.#priceMaxSlider = document.querySelector('#price-max-slider');
        this.#priceMinDisplay = document.querySelector('#price-min-display');
        this.#priceMaxDisplay = document.querySelector('#price-max-display');
        this.#rangeSliderContainer = document.querySelector('.range-slider');
        this.#init();
    }

    /**
     * Binds all event listeners: filter inputs, reset button, and price sliders.
     */
    #init() {
        const filterInputs = this.#form.querySelectorAll('input:not([type="range"]), select');
        filterInputs.forEach(input => {
            input.addEventListener('change', () => this.#filter());
            input.addEventListener('input', debounce(() => this.#filter(), 500));
        });

        document.querySelector('#reset-filters')?.addEventListener('click', () => {
            this.#form.reset();
            if (this.#priceMinSlider && this.#priceMaxSlider) {
                this.#priceMinSlider.value = PRICE_MIN;
                this.#priceMaxSlider.value = PRICE_MAX;
                this.#updatePriceDisplay();
            }
            this.#filter();
        });

        if (this.#priceMinSlider && this.#priceMaxSlider) {
            this.#priceMinSlider.addEventListener('input', () => this.#updatePriceDisplay());
            this.#priceMaxSlider.addEventListener('input', () => this.#updatePriceDisplay());
            this.#priceMinSlider.addEventListener('change', () => this.#filter());
            this.#priceMaxSlider.addEventListener('change', () => this.#filter());
            this.#updatePriceDisplay();
        }
    }

    /**
     * Syncs the price label elements and the progress bar with the current slider values.
     *
     * Enforces a minimum gap of 1 between min and max: if the user drags min above max,
     * min is clamped to max - 1.
     */
    #updatePriceDisplay() {
        let minVal = Number.parseInt(this.#priceMinSlider.value);
        let maxVal = Number.parseInt(this.#priceMaxSlider.value);

        if (minVal > maxVal) {
            minVal = maxVal - 1;
            this.#priceMinSlider.value = minVal;
        }

        this.#priceMinDisplay.textContent = `${minVal}€`;
        this.#priceMaxDisplay.textContent = `${maxVal}€`;
        this.#updateProgressBar(minVal, maxVal);
    }

    /**
     * Updates the --left and --right CSS custom properties on .range-slider to
     * visually fill the track between the two slider thumbs.
     *
     * @param {number} minVal Current minimum price value.
     * @param {number} maxVal Current maximum price value.
     */
    #updateProgressBar(minVal, maxVal) {
        const range = PRICE_MAX - PRICE_MIN;
        const leftPercent = ((minVal - PRICE_MIN) / range) * 100;
        const rightPercent = ((PRICE_MAX - maxVal) / range) * 100;
        this.#rangeSliderContainer?.style.setProperty('--left', `${leftPercent}%`);
        this.#rangeSliderContainer?.style.setProperty('--right', `${rightPercent}%`);
    }

    /**
     * POSTs the current form state to /menus/filter and updates the UI.
     *
     * Fades the container to 50% opacity during the request and restores it in
     * the finally block. On success, re-renders the card grid and refreshes the
     * available filter options. On error, replaces the container with an error message.
     */
    async #filter() {
        this.#container.style.opacity = '0.5';
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const response = await fetch('/menus/filter', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: new FormData(this.#form)
            });
            if (!response.ok) throw new Error('Erreur serveur');
            const data = await response.json();
            this.#renderMenus(data.menus);
            this.#updateFiltersFromStats(data.stats, data.available_options);
        } catch (error) {
            console.error('Erreur:', error);
            this.#container.innerHTML = '<p class="error">Une erreur est survenue lors du filtrage.</p>';
        } finally {
            this.#container.style.opacity = '1';
        }
    }

    /**
     * Replaces the card container's innerHTML with the rendered menu cards.
     *
     * All user-facing strings are passed through escapeHtml() before injection.
     * Shows an empty-state message when the array is empty.
     *
     * @param {Array<Object>} menus Menu objects returned by the filter endpoint.
     */
    #renderMenus(menus) {
        const resultsCount = document.querySelector('#results-count');

        if (menus.length === 0) {
            if (resultsCount) resultsCount.textContent = 'Aucun menu trouvé';
            this.#container.innerHTML = '<p class="text-muted">Aucun menu ne correspond à vos critères.</p>';
            return;
        }

        if (resultsCount) {
            const s = menus.length > 1 ? 's' : '';
            resultsCount.textContent = `${menus.length} menu${s} trouvé${s}`;
        }

        this.#container.innerHTML = menus.map(menu => `
            <div class="card inline">
                <div class="card-header">
                    <img src="${escapeHtml(menu.src)}" alt="${escapeHtml(menu.alt)}">
                    <div class="badge"><p>${escapeHtml(menu.theme)}</p></div>
                    <div class="badge"><p>${escapeHtml(menu.regime)}</p></div>
                </div>
                <div class="card-body">
                    <h3 class="card-title">${escapeHtml(menu.title)}</h3>
                    <p class="card-description">${escapeHtml(menu.description)}</p>
                    <p>Menu pour ${menu.min_people} personnes minimum, au prix de ${formatPrice(menu.base_price)}&nbsp;€.</p>
                    <a class="btn" href="/menu/${menu.id}">Détails</a>
                </div>
            </div>
        `).join('');
    }

    /**
     * Refreshes the min_people input bounds and the theme/regime select options
     * based on the stats and available options returned by the filter endpoint.
     *
     * @param {{count: number, min_people: number}} stats           Aggregate stats for the current result set.
     * @param {{themes: number[], regimes: number[]}} availableOptions IDs of themes and regimes still present in results.
     */
    #updateFiltersFromStats(stats, availableOptions) {
        const minPeopleInput = this.#form.querySelector('#min_people');
        if (minPeopleInput && stats.count > 0) {
            minPeopleInput.min = stats.min_people;
            minPeopleInput.placeholder = `Min ${stats.min_people} pers.`;
        }
        this.#updateSelectOptions('theme', availableOptions.themes);
        this.#updateSelectOptions('regime', availableOptions.regimes);
    }

    /**
     * Disables options in a <select> whose IDs are not in the available set.
     *
     * The empty/placeholder option (value="") is always kept enabled.
     * Unavailable options are greyed out rather than removed so the user can
     * see what exists but is filtered out.
     *
     * @param {string}   selectId     The id attribute of the target <select>.
     * @param {number[]} availableIds IDs of the options that should remain enabled.
     */
    #updateSelectOptions(selectId, availableIds) {
        const select = this.#form.querySelector(`#${selectId}`);
        if (!select) return;
        const ids = new Set(availableIds.map(Number));
        select.querySelectorAll('option').forEach(option => {
            if (option.value === '') {
                option.disabled = false;
                return;
            }
            const isAvailable = ids.has(Number(option.value));
            option.disabled = !isAvailable;
            option.style.color = isAvailable ? '' : '#999';
        });
    }
}

/**
 * Escapes a string for safe insertion into HTML by delegating to the browser's
 * text-node serialiser — avoids XSS when rendering server data into innerHTML.
 *
 * @param {string} text Raw string to escape.
 * @returns {string} HTML-safe string.
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Formats a price as a French decimal string (comma separator, 2 decimal places).
 *
 * @param {number|string} price Numeric price value.
 * @returns {string} e.g. "12,50"
 */
function formatPrice(price) {
    return Number.parseFloat(price).toFixed(2).replace('.', ',');
}

/**
 * Returns a debounced version of func that delays execution until wait ms have
 * elapsed since the last call.
 *
 * @param {Function} func Callback to debounce.
 * @param {number}   wait Delay in milliseconds.
 * @returns {Function} Debounced function.
 */
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

const form = document.querySelector('#menu-filter');
const container = document.querySelector('#menus-container');
if (form && container) new MenuFilter(form, container);
