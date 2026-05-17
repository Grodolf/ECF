/**
 * Clears and disables opening/closing time inputs when the "closed" checkbox
 * is checked, and re-enables them when unchecked.
 *
 * Expected DOM structure inside each fieldset:
 *   - input[type="time"][name="opening_time[]"]
 *   - input[type="time"][name="closing_time[]"]
 *   - input[type="checkbox"][name="closed[]"]
 */

/**
 * @param {HTMLInputElement} checkbox
 */
function applyClosedState(checkbox) {
    const fieldset = checkbox.closest('fieldset');
    if (!fieldset) return;

    const opening = fieldset.querySelector('[data-opening]');
    const closing = fieldset.querySelector('[data-closing]');

    const isClosed = checkbox.checked;

    if (opening) {
        opening.value = '';
        opening.readonly = isClosed;
    }
    if (closing) {
        closing.value = '';
        closing.readonly = isClosed;
    }
}

document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    if (checkbox.checked) {
        applyClosedState(checkbox);
    }

    checkbox.addEventListener('change', () => applyClosedState(checkbox));
});
