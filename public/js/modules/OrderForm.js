/**
 * Live price estimator for the order form.
 *
 * Listens for changes on nb_people, delivery_address, and delivery_city, then
 * POSTs to /order/calculate-price to fetch an up-to-date price breakdown.
 * The result is rendered as a summary table and the submit button is enabled
 * only when a valid price has been received.
 *
 * A 10 % discount hint is shown when nb_people >= min_people + 5, or a
 * "X more guests needed" nudge is shown when the threshold is not yet reached.
 *
 * Expected DOM (all within #order-form unless noted):
 *   - #order-form              — the order <form>
 *   - #price-details           — container for the price breakdown table
 *   - #submit-btn              — submit button, disabled until a valid price is loaded
 *   - #reduction-info          — element for discount / nudge messages
 *   - [name="menu_id"]         — hidden input carrying the menu ID
 *   - #nb_people               — number input (min attribute = menu's min_people)
 *   - #delivery_address        — text input for the street address
 *   - #delivery_city           — select/text input for the delivery city
 *   - meta[name="csrf-token"]  — CSRF token passed in the X-CSRF-Token request header
 *
 * Loaded as a ES module (type="module"), enabling top-level await for the
 * initial price fetch on page load when address fields are pre-filled.
 */
const orderForm = document.querySelector('#order-form');
const priceDetails = document.querySelector('#price-details');
const submitBtn = document.querySelector('#submit-btn');
const reductionInfo = document.querySelector('#reduction-info');

if (orderForm && priceDetails) {

    const menuIdInput = orderForm.querySelector('[name="menu_id"]');
    const nbPeopleInput = orderForm.querySelector('#nb_people');
    const deliveryAddressInput = orderForm.querySelector('#delivery_address');
    const deliveryCityInput = orderForm.querySelector('#delivery_city');
    
    const minPeople = Number.parseInt(nbPeopleInput.min);
    
    /**
     * Fetches a live price estimate from the server and updates the UI.
     *
     * Disables the submit button and shows a loading state while the request is
     * in flight. Re-enables the button only on a successful, error-free response.
     * Returns early (submit stays disabled) if any required field is empty.
     */
    async function calculatePrice() {
        const menuId = menuIdInput.value;
        const nbPeople = Number.parseInt(nbPeopleInput.value);
        const deliveryAddress = deliveryAddressInput.value.trim();
        const deliveryCity = deliveryCityInput.value.trim();
        
        if (!menuId || !nbPeople || !deliveryAddress || !deliveryCity) {
            priceDetails.innerHTML = '<p class="text-muted">Remplissez tous les champs pour voir le prix.</p>';
            submitBtn.disabled = true;
            return;
        }
        
        priceDetails.innerHTML = '<p class="calculating">Calcul en cours...</p>';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('menu_id', menuId);
            formData.append('nb_people', nbPeople);
            formData.append('delivery_address', deliveryAddress);
            formData.append('delivery_city', deliveryCity);
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const response = await fetch('/order/calculate-price', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Erreur serveur');
            }
            
            const data = await response.json();
            
            if (data.error) {
                priceDetails.innerHTML = `<p class="error">${escapeHtml(data.error)}</p>`;
                submitBtn.disabled = true;
                return;
            }
            
            displayPriceDetails(data);
            
            if (data.reduction > 0) {
                reductionInfo.textContent = `🎉 Réduction de 10% applicable (${nbPeople} personnes ≥ ${minPeople + 5})`;
                reductionInfo.classList.remove('hidden');
            } else {
                // Show how many more guests are needed to reach the discount threshold.
                const peopleNeeded = (minPeople + 5) - nbPeople;
                if (peopleNeeded > 0) {
                    reductionInfo.textContent = `💡 Ajoutez ${peopleNeeded} personne${peopleNeeded > 1 ? 's' : ''} de plus pour bénéficier de 10% de réduction`;
                    reductionInfo.classList.remove('hidden', 'success');
                    reductionInfo.classList.add('info');
                } else {
                    reductionInfo.classList.add('hidden');
                }
            }
            
            submitBtn.disabled = false;
            
        } catch (error) {
            console.error('Erreur:', error);
            priceDetails.innerHTML = '<p class="error">Erreur lors du calcul du prix.</p>';
            submitBtn.disabled = true;
        }
    }
    
    /**
     * Renders the price breakdown as an HTML table inside #price-details.
     *
     * The reduction and delivery rows are conditional: reduction row is omitted
     * when there is no discount; delivery row is omitted when distance is null
     * (Bordeaux or incomplete address).
     *
     * @param {{
     *   menu_price: number,
     *   reduction: number,
     *   menu_price_after_reduction: number,
     *   delivery_cost: number,
     *   distance: number|null,
     *   total_price: number
     * }} data Price breakdown returned by the calculate-price endpoint.
     */
    function displayPriceDetails(data) {
        let html = '<table class="price-table">';
        
        html += `
            <tr>
                <td>Prix du menu :</td>
                <td>${formatPrice(data.menu_price)}&nbsp;€</td>
            </tr>
        `;
        
        if (data.reduction > 0) {
            html += `
                <tr class="success">
                    <td>Réduction (10%) :</td>
                    <td>- ${formatPrice(data.reduction)}&nbsp;€</td>
                </tr>
                <tr>
                    <td>Sous-total menu :</td>
                    <td>${formatPrice(data.menu_price_after_reduction)}&nbsp;€</td>
                </tr>
            `;
        }
        
        if (data.distance !== null) {
            html += `
                <tr>
                    <td>Frais de livraison (${data.distance} km) :</td>
                    <td>${formatPrice(data.delivery_cost)}&nbsp;€</td>
                </tr>
            `;
        }
        
        html += `
            <tr class="total">
                <td><strong>TOTAL :</strong></td>
                <td><strong>${formatPrice(data.total_price)}&nbsp;€</strong></td>
            </tr>
        `;
        
        html += '</table>';
        
        priceDetails.innerHTML = html;
    }
    
    nbPeopleInput.addEventListener('input', debounce(calculatePrice, 500));
    deliveryAddressInput.addEventListener('input', debounce(calculatePrice, 800));
    deliveryCityInput.addEventListener('change', calculatePrice);
    
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
     * Escapes a string for safe insertion into innerHTML via the browser's
     * text-node serialiser, preventing XSS when rendering server error messages.
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
     * Returns a debounced version of func that fires only after wait ms of inactivity.
     *
     * Used to avoid sending a request on every keystroke in address fields.
     *
     * @param {Function} func Async or sync callback to debounce.
     * @param {number}   wait Delay in milliseconds.
     * @returns {Function} Debounced wrapper.
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    if (deliveryAddressInput.value && deliveryCityInput.value) {
        await calculatePrice();
    }
}
