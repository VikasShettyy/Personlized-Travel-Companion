// Ensure window resets to top on refresh
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}

document.addEventListener('DOMContentLoaded', () => {

    /* ==========================================================
       1. Mobile Navigation Sidebar Drawer
    ========================================================== */
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    function openMobileSidebar() {
        if (!mobileSidebar || !sidebarBackdrop) return;
        mobileSidebar.classList.add('open');
        sidebarBackdrop.classList.add('open');
        mobileSidebar.setAttribute('aria-hidden', 'false');
        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-expanded', 'true');
        }
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (!mobileSidebar || !sidebarBackdrop) return;
        mobileSidebar.classList.remove('open');
        sidebarBackdrop.classList.remove('open');
        mobileSidebar.setAttribute('aria-hidden', 'true');
        if (mobileMenuBtn) {
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
        }
        document.body.style.overflow = '';
    }

    mobileMenuBtn?.addEventListener('click', openMobileSidebar);
    sidebarCloseBtn?.addEventListener('click', closeMobileSidebar);
    sidebarBackdrop?.addEventListener('click', closeMobileSidebar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileSidebar?.classList.contains('open')) {
            closeMobileSidebar();
        }
    });

    /* ==========================================================
       2. Custom Confirmation Modal (Replaces browser confirm())
    ========================================================== */
    let pendingForm = null;

    // Create custom confirmation modal DOM if not already present
    function ensureConfirmModal() {
        let modal = document.getElementById('customConfirmModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'customConfirmModal';
            modal.className = 'custom-confirm-modal';
            modal.innerHTML = `
                <div class="custom-confirm-backdrop"></div>
                <div class="custom-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="customConfirmTitle">
                    <div class="custom-confirm-icon">⚠️</div>
                    <h3 id="customConfirmTitle" class="custom-confirm-title">Confirm Action</h3>
                    <p id="customConfirmMessage" class="custom-confirm-message"></p>
                    <div class="custom-confirm-actions">
                        <button type="button" class="btn secondary" id="customConfirmCancel">Cancel</button>
                        <button type="button" class="btn danger" id="customConfirmOk">Yes, Proceed</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const cancelBtn = modal.querySelector('#customConfirmCancel');
            const okBtn = modal.querySelector('#customConfirmOk');
            const backdrop = modal.querySelector('.custom-confirm-backdrop');

            const closeModal = () => {
                modal.classList.remove('open');
                pendingForm = null;
            };

            cancelBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', closeModal);

            okBtn.addEventListener('click', () => {
                modal.classList.remove('open');
                if (pendingForm) {
                    const formToSubmit = pendingForm;
                    pendingForm = null;
                    formToSubmit.dataset.confirmed = 'true';
                    formToSubmit.submit();
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('open')) {
                    closeModal();
                }
            });
        }
        return modal;
    }

    window.showCustomConfirm = function (message, onConfirm) {
        const modal = ensureConfirmModal();
        const msgEl = modal.querySelector('#customConfirmMessage');
        msgEl.textContent = message || 'Are you sure you want to proceed?';
        modal.classList.add('open');

        const okBtn = modal.querySelector('#customConfirmOk');
        const handleOk = () => {
            modal.classList.remove('open');
            okBtn.removeEventListener('click', handleOk);
            if (typeof onConfirm === 'function') onConfirm();
        };
        okBtn.addEventListener('click', handleOk, { once: true });
    };

    // Attach to all forms with data-confirm
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (this.dataset.confirmed === 'true') {
                delete this.dataset.confirmed;
                return; // Allow submission
            }
            e.preventDefault();
            pendingForm = this;
            const message = this.dataset.confirm || 'Are you sure you want to do this?';
            const modal = ensureConfirmModal();
            const msgEl = modal.querySelector('#customConfirmMessage');
            msgEl.textContent = message;
            modal.classList.add('open');
        });
    });

    /* ==========================================================
       3. Custom Toast Alert (Replaces browser alert())
    ========================================================== */
    window.showCustomAlert = function (message, type = 'warning', duration = 4000) {
        let container = document.getElementById('customToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'customToastContainer';
            container.className = 'custom-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        
        let icon = 'ℹ️';
        if (type === 'warning') icon = '⚠️';
        if (type === 'error') icon = '🛑';
        if (type === 'success') icon = '✅';

        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-msg">${message}</span>
            <button type="button" class="toast-close" aria-label="Close notification">&times;</button>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });

        const removeToast = () => {
            toast.classList.remove('visible');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        };

        toast.querySelector('.toast-close').addEventListener('click', removeToast);
        setTimeout(removeToast, duration);
    };

    /* ==========================================================
       4. Budget Watchers
    ========================================================== */
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
