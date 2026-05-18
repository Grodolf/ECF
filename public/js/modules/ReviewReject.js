/**
 * Shows the rejection reason textarea when the status select changes to "rejected".
 * Hides it for any other value.
 */
const selects = document.querySelectorAll('[data-status]');

selects?.forEach(select => {
    const comment = select.closest('form')?.querySelector('[data-comment]');
    if (!comment) return;

    select.addEventListener('change', () => {
        comment.classList.toggle('visible', select.value === 'rejected');
    });
});
