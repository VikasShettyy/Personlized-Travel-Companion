document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-budget-watch]').forEach(el => {
        const budget = document.querySelector('#budget');
        const total = document.querySelector('#estimated-total');
        const notice = document.querySelector('#budget-notice');

        function update() {
            if (!budget || !total || !notice) return;

            const b = Number(budget.value || 0);
            const t = Number(total.value || 0);

            notice.textContent = t > b && b > 0
                ? `Warning: estimated cost exceeds budget by ₹${(t - b).toFixed(2)}.`
                : `Estimated total: ₹${t.toFixed(2)}. Budget: ₹${b.toFixed(2)}.`;

            notice.className = t > b && b > 0 ? 'warning' : 'good';
        }

        budget?.addEventListener('input', update);
        update();
    });

});