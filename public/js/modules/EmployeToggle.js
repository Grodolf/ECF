/**
 * AJAX employee activation toggle.
 *
 * For each form matching [data-form="employe-toggle"], intercepts the button click,
 * POSTs to /admin/employe/toggle/{id} with the CSRF token in the X-CSRF-Token header,
 * and toggles the button between "Active" (primary) and "Inactive" (outline) on success.
 */
const forms = document.querySelectorAll('[data-form="employe-toggle"]');

forms?.forEach(form => {
    const button = form.querySelector('[data-employe-id]');
    if (!button) return;
    const id = button.dataset.employeId;
    const csrfToken = form.querySelector('[name="csrf_token"]')?.value ?? '';
    button.addEventListener('click', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        
        const response = await fetch(`/admin/employe/toggle/${id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: formData
        });

        if (!response.ok) {
            throw new Error('Server error');
        }
    
        const data = await response.json();

        if (data.success) {
            if (button.classList.contains('primary')) {
                button.classList.replace('primary', 'outline');
                button.textContent = 'Inactif';
            } else {
                button.classList.replace('outline', 'primary');
                button.textContent = 'Actif';
            }
        }
    });    
});
