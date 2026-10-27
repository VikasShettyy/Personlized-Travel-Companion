/* ==============================
   ADMIN PANEL BEHAVIOUR
============================== */

document.addEventListener('DOMContentLoaded', () => {

    const TABS = ['overview', 'destinations', 'categories', 'activities', 'enquiries', 'operations', 'security'];

    /* ---------- Toast Notifications System ---------- */

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    window.showToast = function(title, message, type = 'success') {
        const container = document.getElementById('adminToastContainer');
        if (!container) return;

        const id = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        const iconClass = type === 'success'
            ? 'bi-check-circle-fill text-success'
            : type === 'danger'
            ? 'bi-exclamation-triangle-fill text-danger'
            : type === 'warning'
            ? 'bi-exclamation-circle-fill text-warning'
            : 'bi-info-circle-fill text-primary';

        const borderClass = type === 'success'
            ? 'border-success'
            : type === 'danger'
            ? 'border-danger'
            : type === 'warning'
            ? 'border-warning'
            : 'border-primary';

        const toastEl = document.createElement('div');
        toastEl.id = id;
        toastEl.className = `toast align-items-center shadow-lg border-start border-4 ${borderClass}`;
        toastEl.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        toastEl.setAttribute('aria-live', type === 'danger' ? 'assertive' : 'polite');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.style.minWidth = '320px';

        toastEl.innerHTML = `
            <div class="toast-header bg-white">
                <i class="bi ${iconClass} me-2" aria-hidden="true" style="font-size: 1.15rem;"></i>
                <strong class="me-auto">${escapeHtml(title)}</strong>
                <small class="text-muted">Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body bg-white py-2">
                ${escapeHtml(message)}
            </div>
        `;

        container.appendChild(toastEl);

        if (window.bootstrap && bootstrap.Toast) {
            const bsToast = new bootstrap.Toast(toastEl, { delay: 4500 });
            bsToast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        } else {
            toastEl.style.display = 'block';
            setTimeout(() => toastEl.remove(), 4500);
        }
    };

    // Check for initial toast notifications from server redirect
    const toastData = document.getElementById('admin-toast-data');
    if (toastData) {
        const flashMsg = toastData.dataset.flash;
        const errorMsg = toastData.dataset.error;
        if (flashMsg) {
            showToast('Success', flashMsg, 'success');
        }
        if (errorMsg) {
            showToast('Notice', errorMsg, 'danger');
        }
    }


    /* ---------- Tabs: restore from URL hash, keep hash in sync ---------- */

    const tabButton = name => document.getElementById('tab-' + name);

    const initial = location.hash.replace('#', '');

    if (TABS.includes(initial) && window.bootstrap) {
        const targetBtn = tabButton(initial);
        if (targetBtn) {
            bootstrap.Tab.getOrCreateInstance(targetBtn).show();
        }
    }

    document.querySelectorAll('.admin-tabs [role="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => {
            const name = btn.id.replace('tab-', '');
            history.replaceState(null, '', '#' + name);
        });
    });

    /* Move focus to the error message so screen readers announce it. */
    document.getElementById('admin-error')?.focus();


    /* ---------- Image Upload from Drive in Destination Modal ---------- */

    const destImageFile = document.getElementById('dest-image-file');
    const destImagePreviewWrapper = document.getElementById('dest-image-preview-wrapper');
    const destImagePreview = document.getElementById('dest-image-preview');
    const destImageFilename = document.getElementById('dest-image-filename');
    const destFileChosenText = document.getElementById('file-chosen-text');
    const btnRemoveImage = document.getElementById('btn-remove-image');
    const destImageUrlInput = document.getElementById('dest-image');
    const destExistingImage = document.getElementById('dest-existing-image');

    function resetImageUpload() {
        if (destImageFile) destImageFile.value = '';
        if (destImagePreviewWrapper) destImagePreviewWrapper.style.display = 'none';
        if (destImagePreview) destImagePreview.src = '';
        if (destImageFilename) destImageFilename.textContent = '';
        if (destFileChosenText) destFileChosenText.textContent = 'Max 5MB (JPG, PNG, WEBP, GIF, AVIF)';
    }

    if (destImageFile) {
        destImageFile.addEventListener('change', () => {
            const file = destImageFile.files[0];
            if (!file) return;

            // Validate size
            if (file.size > 5 * 1024 * 1024) {
                showToast('Image Too Large', 'Selected image exceeds the 5MB limit.', 'danger');
                destImageFile.value = '';
                return;
            }

            // Validate type
            if (!file.type.startsWith('image/')) {
                showToast('Invalid File', 'Please select a valid image file from your drive.', 'danger');
                destImageFile.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                if (destImagePreview) destImagePreview.src = e.target.result;
                const sizeKb = (file.size / 1024).toFixed(1);
                const sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB';
                if (destImageFilename) destImageFilename.textContent = `${file.name} (${sizeStr})`;
                if (destImagePreviewWrapper) destImagePreviewWrapper.style.display = 'flex';
                if (destFileChosenText) destFileChosenText.textContent = `Selected: ${file.name}`;
                if (destImageUrlInput) destImageUrlInput.value = ''; // clear text if uploading file
                showToast('Image Selected', `Ready to upload ${file.name} from your drive.`, 'info');
            };
            reader.readAsDataURL(file);
        });
    }

    if (btnRemoveImage) {
        btnRemoveImage.addEventListener('click', () => {
            resetImageUpload();
            if (destImageUrlInput) destImageUrlInput.value = '';
            if (destExistingImage) destExistingImage.value = '';
            showToast('Image Cleared', 'Image selection removed.', 'info');
        });
    }


    /* ---------- Add / edit modals ---------- */

    function setValue(form, name, value) {
        const field = form.elements[name];
        if (field) field.value = value ?? '';
    }

    function prepareModal(modalId, config) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.addEventListener('show.bs.modal', event => {
            const trigger = event.relatedTarget;
            const form = modal.querySelector('form');
            const title = modal.querySelector('.modal-title');
            const submit = modal.querySelector('[data-submit-label]');
            const editing = Boolean(trigger && trigger.dataset.id);

            form.reset();
            setValue(form, 'id', '');
            setValue(form, 'action', config.prefix + (editing ? '_update' : '_add'));

            title.textContent = (editing ? 'Edit ' : 'Add ') + config.noun;
            submit.textContent = editing
                ? 'Update ' + config.noun
                : 'Save ' + config.noun;

            if (modalId === 'destinationModal') {
                resetImageUpload();
            }

            if (editing) {
                setValue(form, 'id', trigger.dataset.id);
                Object.entries(config.fields).forEach(([field, dataKey]) => {
                    setValue(form, field, trigger.dataset[dataKey]);
                });

                if (modalId === 'destinationModal') {
                    const existingImg = trigger.dataset.image;
                    if (existingImg) {
                        if (destExistingImage) destExistingImage.value = existingImg;
                        const previewSrc = existingImg.startsWith('http') || existingImg.startsWith('/') || existingImg.startsWith('../')
                            ? existingImg
                            : ('../' + existingImg);
                        if (destImagePreview) destImagePreview.src = previewSrc;
                        if (destImageFilename) destImageFilename.textContent = `Current: ${existingImg}`;
                        if (destImagePreviewWrapper) destImagePreviewWrapper.style.display = 'flex';
                    }
                }
            }
        });

        /* Focus the first field once the dialog is visible. */
        modal.addEventListener('shown.bs.modal', () => {
            modal.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
        });
    }

    prepareModal('destinationModal', {
        prefix: 'destination',
        noun: 'destination',
        fields: {
            name: 'name',
            district: 'district',
            category_id: 'category',
            estimated_cost: 'cost',
            image_url: 'image',
            description: 'description'
        }
    });

    prepareModal('categoryModal', {
        prefix: 'category',
        noun: 'category',
        fields: { name: 'name' }
    });

    prepareModal('activityModal', {
        prefix: 'activity',
        noun: 'activity',
        fields: {
            destination_id: 'destination',
            name: 'name',
            estimated_cost: 'cost',
            description: 'description'
        }
    });


    /* ---------- Delete confirmation modal ---------- */

    const deleteModal = document.getElementById('deleteModal');

    deleteModal?.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const form = deleteModal.querySelector('form');
        const note = deleteModal.querySelector('[data-delete-note]');

        setValue(form, 'action', trigger.dataset.action);
        setValue(form, 'tab', trigger.dataset.tab);
        setValue(form, 'id', trigger.dataset.id);

        deleteModal.querySelector('[data-delete-name]').textContent =
            trigger.dataset.name;

        note.textContent = trigger.dataset.note || '';
        note.hidden = !trigger.dataset.note;
    });

    deleteModal?.addEventListener('shown.bs.modal', () => {
        deleteModal.querySelector('.btn.secondary')?.focus();
    });


    /* ---------- Enquiry Reply Modal ---------- */

    const enquiryReplyModal = document.getElementById('enquiryReplyModal');
    enquiryReplyModal?.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const idField = document.getElementById('reply-enquiry-id');
        const destTag = document.getElementById('reply-destination-tag');
        const nameText = document.getElementById('reply-user-name-text');
        const emailText = document.getElementById('reply-user-email-text');
        const msgText = document.getElementById('reply-message-text');
        const replyArea = document.getElementById('reply-textarea');

        if (idField) idField.value = trigger.dataset.id || '';
        if (destTag) destTag.textContent = trigger.dataset.destination || 'Destination';
        if (nameText) nameText.textContent = trigger.dataset.name || 'Traveler';
        if (emailText) emailText.textContent = trigger.dataset.email || '';
        if (msgText) msgText.textContent = trigger.dataset.message || '';
        if (replyArea) replyArea.value = trigger.dataset.reply || '';
    });

    enquiryReplyModal?.addEventListener('shown.bs.modal', () => {
        document.getElementById('reply-textarea')?.focus();
    });


    /* ---------- Password Validation UI ---------- */

    const currentPassInput = document.getElementById('current_password');
    const newPassInput = document.getElementById('new_password');
    const confirmPassInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthLabel = document.getElementById('strength-label');
    const btnSavePassword = document.getElementById('btn-save-password');
    const matchFeedback = document.getElementById('match-feedback');

    const ruleLength = document.getElementById('rule-length');
    const ruleUpper = document.getElementById('rule-upper');
    const ruleLower = document.getElementById('rule-lower');
    const ruleNumber = document.getElementById('rule-number');
    const ruleSpecial = document.getElementById('rule-special');

    // Toggle password eye visibility
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.toggleFor;
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
            btn.setAttribute('aria-label', (isPassword ? 'Hide ' : 'Show ') + targetId.replace('_', ' '));
        });
    });

    function setRuleState(el, valid) {
        if (!el) return;
        el.className = valid ? 'rule-valid' : 'rule-invalid';
        const icon = el.querySelector('i');
        if (icon) {
            icon.className = valid ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        }
    }

    function validatePasswordForm() {
        if (!newPassInput) return;

        const val = newPassInput.value;
        const confirmVal = confirmPassInput ? confirmPassInput.value : '';
        const currentVal = currentPassInput ? currentPassInput.value.trim() : '';

        const hasLength = val.length >= 8 && val.length <= 72;
        const hasUpper = /[A-Z]/.test(val);
        const hasLower = /[a-z]/.test(val);
        const hasNumber = /[0-9]/.test(val);
        const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);

        setRuleState(ruleLength, hasLength);
        setRuleState(ruleUpper, hasUpper);
        setRuleState(ruleLower, hasLower);
        setRuleState(ruleNumber, hasNumber);
        setRuleState(ruleSpecial, hasSpecial);

        const score = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;

        if (strengthBar && strengthLabel) {
            if (val.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = '#e03131';
                strengthLabel.textContent = 'Too short';
                strengthLabel.style.color = '#68716c';
            } else if (score <= 2) {
                strengthBar.style.width = '30%';
                strengthBar.style.backgroundColor = '#e03131';
                strengthLabel.textContent = 'Weak';
                strengthLabel.style.color = '#e03131';
            } else if (score === 3 || score === 4) {
                strengthBar.style.width = '65%';
                strengthBar.style.backgroundColor = '#f59f00';
                strengthLabel.textContent = score === 3 ? 'Fair' : 'Good';
                strengthLabel.style.color = '#f59f00';
            } else if (score === 5) {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#0ca678';
                strengthLabel.textContent = 'Strong';
                strengthLabel.style.color = '#0ca678';
            }
        }

        // Match check
        let isMatch = false;
        if (matchFeedback && confirmPassInput) {
            if (confirmVal.length === 0) {
                matchFeedback.textContent = '';
                matchFeedback.className = 'smalltext match-feedback';
            } else if (val === confirmVal) {
                isMatch = true;
                matchFeedback.textContent = '✓ Passwords match';
                matchFeedback.className = 'smalltext match-feedback valid';
            } else {
                isMatch = false;
                matchFeedback.textContent = '✗ Passwords do not match';
                matchFeedback.className = 'smalltext match-feedback invalid';
            }
        }

        const allValid = score === 5 && isMatch && currentVal.length > 0;
        if (btnSavePassword) {
            btnSavePassword.disabled = !allValid;
        }
    }

    newPassInput?.addEventListener('input', validatePasswordForm);
    confirmPassInput?.addEventListener('input', validatePasswordForm);
    currentPassInput?.addEventListener('input', validatePasswordForm);


    /* ---------- Screen Reader (Web Speech API) ---------- */

    function initScreenReader() {
        const btn = document.getElementById('btn-screen-reader');
        const stopBtn = document.getElementById('btn-stop-reader');
        const icon = document.getElementById('screen-reader-icon');
        const label = document.getElementById('screen-reader-label');

        if (!btn) return;

        if (!('speechSynthesis' in window)) {
            btn.addEventListener('click', () => {
                showToast('Screen Reader', 'Web Speech API is not supported in this browser.', 'warning');
            });
            return;
        }

        let speaking = false;

        function stopSpeaking() {
            if (window.speechSynthesis.speaking || window.speechSynthesis.pending) {
                window.speechSynthesis.cancel();
            }
            speaking = false;
            btn.classList.remove('speaking');
            btn.setAttribute('aria-pressed', 'false');
            if (icon) icon.className = 'bi bi-volume-up-fill';
            if (label) label.textContent = 'Screen Reader';
            if (stopBtn) stopBtn.style.display = 'none';
        }

        function startSpeaking(text, contextName) {
            stopSpeaking();

            if (!text || !text.trim()) {
                showToast('Screen Reader', 'No text content available to read.', 'info');
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            utterance.lang = 'en-US';

            utterance.onstart = () => {
                speaking = true;
                btn.classList.add('speaking');
                btn.setAttribute('aria-pressed', 'true');
                if (icon) icon.className = 'bi bi-volume-up-fill text-success';
                if (label) label.textContent = 'Reading...';
                if (stopBtn) stopBtn.style.display = 'inline-flex';
                showToast('Screen Reader', `Reading ${contextName}...`, 'info');
            };

            utterance.onend = () => {
                stopSpeaking();
                showToast('Screen Reader', 'Finished reading.', 'success');
            };

            utterance.onerror = (e) => {
                if (e.error !== 'canceled') {
                    console.warn('Speech error:', e);
                    showToast('Screen Reader', 'Speech interrupted or not allowed.', 'warning');
                }
                stopSpeaking();
            };

            window.speechSynthesis.speak(utterance);
        }

        function getScreenContentToRead() {
            // 1. If text is selected by user
            const selected = window.getSelection()?.toString().trim();
            if (selected) {
                return { text: selected, context: 'Selected Text' };
            }

            // 2. Read active tab
            const activeTabPane = document.querySelector('.tab-pane.active');
            if (!activeTabPane) {
                return { text: 'Admin Panel. Manage destinations, categories and activities.', context: 'Admin Panel' };
            }

            const tabId = activeTabPane.id;
            if (tabId === 'panel-overview') {
                const stats = [...activeTabPane.querySelectorAll('.stat-card')].map(card => {
                    const labelText = card.querySelector('dt')?.textContent?.trim() || '';
                    const val = card.querySelector('dd')?.textContent?.trim() || '';
                    return `${labelText}: ${val}`;
                }).join('. ');

                const categories = [...activeTabPane.querySelectorAll('.bar-list li')].map(li => {
                    return li.querySelector('span')?.textContent?.trim() + ', ' +
                           li.querySelector('strong')?.textContent?.trim();
                }).slice(0, 5).join('; ');

                const trips = [...activeTabPane.querySelectorAll('.recent-list li')].map(li => {
                    return li.querySelector('strong')?.textContent?.trim() + ', ' +
                           li.querySelector('.smalltext')?.textContent?.trim();
                }).slice(0, 3).join('; ');

                const text = `Admin Panel Overview. Summary Statistics: ${stats}. Top Categories: ${categories}. Recent Trips: ${trips}.`;
                return { text, context: 'Overview Section' };
            }

            if (tabId === 'panel-destinations') {
                const rows = [...activeTabPane.querySelectorAll('#destination-table tbody tr[data-search]')];
                const visibleRows = rows.filter(r => !r.hidden);
                const countText = `Destinations Section. There are ${visibleRows.length} destinations listed. `;
                const summary = visibleRows.slice(0, 6).map((r, i) => {
                    const name = r.querySelector('[data-label="Destination"] strong')?.textContent?.trim() || '';
                    const district = r.querySelector('[data-label="Destination"] .smalltext')?.textContent?.trim() || '';
                    const category = r.querySelector('[data-label="Category"]')?.textContent?.trim() || '';
                    const cost = r.querySelector('[data-label="Base cost"]')?.textContent?.trim() || '';
                    return `Destination ${i + 1}: ${name}, District: ${district}, Category: ${category}, Base cost: ${cost}`;
                }).join('. ');
                return { text: countText + summary, context: 'Destinations Section' };
            }

            if (tabId === 'panel-categories') {
                const rows = [...activeTabPane.querySelectorAll('tbody tr')].filter(r => !r.querySelector('.empty'));
                const countText = `Categories Section. Showing ${rows.length} categories. `;
                const summary = rows.map((r, i) => {
                    const name = r.querySelector('[data-label="Category"]')?.textContent?.trim() || '';
                    const count = r.querySelector('[data-label="Destinations"]')?.textContent?.trim() || '0';
                    return `${name} with ${count} destinations`;
                }).join(', ');
                return { text: countText + summary, context: 'Categories Section' };
            }

            if (tabId === 'panel-activities') {
                const rows = [...activeTabPane.querySelectorAll('#activity-table tbody tr[data-dest]')].filter(r => !r.hidden);
                const countText = `Activities Section. Showing ${rows.length} activities. `;
                const summary = rows.slice(0, 6).map((r, i) => {
                    const name = r.querySelector('[data-label="Activity"] strong')?.textContent?.trim() || '';
                    const dest = r.querySelector('[data-label="Destination"]')?.textContent?.trim() || '';
                    const cost = r.querySelector('[data-label="Cost"]')?.textContent?.trim() || '';
                    return `Activity ${i + 1}: ${name} in ${dest}, cost: ${cost}`;
                }).join('. ');
                return { text: countText + summary, context: 'Activities Section' };
            }

            if (tabId === 'panel-operations') {
                const rows = [...activeTabPane.querySelectorAll('#operations-table tbody tr[data-search]')].filter(r => !r.hidden);
                const countText = `Admin Operations Section. Showing ${rows.length} operations. `;
                const summary = rows.slice(0, 5).map((r, i) => {
                    const op = r.querySelector('[data-label="Operation"]')?.textContent?.trim() || '';
                    const desc = r.querySelector('[data-label="Description"]')?.textContent?.trim() || '';
                    const admin = r.querySelector('[data-label="Administrator"]')?.textContent?.trim() || '';
                    return `Log ${i + 1}: ${op} by ${admin}, ${desc}`;
                }).join('. ');
                return { text: countText + summary, context: 'Admin Operations Section' };
            }

            if (tabId === 'panel-security') {
                return {
                    text: 'Security and Password Section. Change administrator password. Password must have at least 8 characters, with uppercase, lowercase, number, and special character.',
                    context: 'Security Section'
                };
            }

            return { text: activeTabPane.textContent.replace(/\s+/g, ' ').trim().slice(0, 500), context: 'Active Panel' };
        }

        btn.addEventListener('click', () => {
            if (speaking) {
                stopSpeaking();
                showToast('Screen Reader', 'Reading stopped.', 'info');
            } else {
                const { text, context } = getScreenContentToRead();
                startSpeaking(text, context);
            }
        });

        stopBtn?.addEventListener('click', () => {
            stopSpeaking();
            showToast('Screen Reader', 'Reading stopped.', 'info');
        });

        window.addEventListener('beforeunload', () => {
            if (window.speechSynthesis) window.speechSynthesis.cancel();
        });
    }

    initScreenReader();


    /* ---------- Table search and filtering ---------- */

    document.querySelectorAll('[data-filter-table]').forEach(input => {
        const table = document.getElementById(input.dataset.filterTable);
        const counter = document.getElementById(input.dataset.filterCount);
        const select = input.dataset.filterSelect
            ? document.getElementById(input.dataset.filterSelect)
            : null;

        if (!table) return;

        const rows = [...table.querySelectorAll('tbody tr[data-search]')];
        const empty = table.querySelector('tr.no-results');

        function apply() {
            const term = input.value.trim().toLowerCase();
            const dest = select ? select.value : '';
            let visible = 0;

            rows.forEach(row => {
                const matchText = !term || row.dataset.search.includes(term);
                const matchDest = !dest || row.dataset.dest === dest;
                const show = matchText && matchDest;

                row.hidden = !show;
                if (show) visible++;
            });

            if (empty) empty.hidden = visible !== 0;

            if (counter) {
                counter.textContent =
                    `Showing ${visible} of ${rows.length} ${input.dataset.filterNoun}.`;
            }
        }

        input.addEventListener('input', apply);
        select?.addEventListener('change', apply);
    });

});
