const forms = document.querySelectorAll('[data-form="dish-toggle"]');

forms?.forEach(form => {
    const button = form.querySelector('[data-dish-id]');
    if (!button) return;
    const id = button.dataset.dishId;
    const csrfToken = form.querySelector('[name="csrf_token"]')?.value ?? '';
    const formData = new FormData(form);
    button.addEventListener('click', async (e) => {
        e.preventDefault();
        const response = await fetch(`/employe/dish/toggle/${id}`, {
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
