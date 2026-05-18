/**
 * AJAX menu stock replenishment.
 *
 * For each form matching [data-form="menu-addstock"], intercepts the button click,
 * POSTs to /employe/menu/addstock/{id} with the CSRF token in the X-CSRF-Token header,
 * and updates the [data-stock] cell in the same table row with the new stock value.
 */
const forms = document.querySelectorAll('[data-form="menu-addstock"]');

forms?.forEach(form => {
    const button = form.querySelector('[data-menu-id]');
    if (!button) return;

    const id = button.dataset.menuId;
    const csrfToken = form.querySelector('[name="csrf_token"]')?.value ?? '';
    const stockCell = form.closest('tr').querySelector('[data-stock]');
    const quantityInput = form.querySelector('[name="quantity"]');

    button.addEventListener('click', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        const response = await fetch(`/employe/menu/addstock/${id}`, {
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

        if (data.success) {
            stockCell.textContent = data.stock;
            quantityInput.value = '';
        }
    });
});
