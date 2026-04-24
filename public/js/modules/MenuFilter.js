const PRICE_MIN = 5;
const PRICE_MAX = 25;

class MenuFilter {
    #form;
    #container;
    #priceMinSlider;
    #priceMaxSlider;
    #priceMinDisplay;
    #priceMaxDisplay;
    #rangeSliderContainer;

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

    #updateProgressBar(minVal, maxVal) {
        const range = PRICE_MAX - PRICE_MIN;
        const leftPercent = ((minVal - PRICE_MIN) / range) * 100;
        const rightPercent = ((PRICE_MAX - maxVal) / range) * 100;
        this.#rangeSliderContainer?.style.setProperty('--left', `${leftPercent}%`);
        this.#rangeSliderContainer?.style.setProperty('--right', `${rightPercent}%`);
    }

    async #filter() {
        this.#container.style.opacity = '0.5';
        try {
            const response = await fetch('/menus/filter', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
            <div class="card">
                <div class="card-header">
                    <img src="${escapeHtml(menu.src)}" alt="${escapeHtml(menu.alt)}">
                    <div class="badge"><p>${escapeHtml(menu.theme)}</p></div>
                    <div class="badge"><p>${escapeHtml(menu.regime)}</p></div>
                </div>
                <div class="card-body">
                    <h3 class="card-title">${escapeHtml(menu.title)}</h3>
                    <p class="card-description">${escapeHtml(menu.description)}</p>
                    <p>Menu pour ${menu.min_people} personnes minimum, au prix de ${formatPrice(menu.base_price)}&nbsp;€.</p>
                    <div class="btn"><a href="/menu/${menu.id}">Détails</a></div>
                </div>
            </div>
        `).join('');
    }

    #updateFiltersFromStats(stats, availableOptions) {
        const minPeopleInput = this.#form.querySelector('#min_people');
        if (minPeopleInput && stats.count > 0) {
            minPeopleInput.min = stats.min_people;
            minPeopleInput.placeholder = `Min ${stats.min_people} pers.`;
        }
        this.#updateSelectOptions('theme', availableOptions.themes);
        this.#updateSelectOptions('regime', availableOptions.regimes);
    }

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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatPrice(price) {
    return Number.parseFloat(price).toFixed(2).replace('.', ',');
}

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
