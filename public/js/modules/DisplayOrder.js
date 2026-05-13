const inputs = document.querySelectorAll('input[name="display_order[]"]');

inputs.forEach(input => {
    input.addEventListener('focus', () => {
        input.dataset.prev = input.value;
    });

    input.addEventListener('change', () => {
        const newVal = input.value;
        const oldVal = input.dataset.prev;

        const conflict = [...inputs].find(
            other => other !== input && other.value === newVal
        );

        if (conflict) {
            conflict.value = oldVal;
        }

        input.dataset.prev = newVal;
    });
});
