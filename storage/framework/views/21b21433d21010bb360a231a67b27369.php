
<div id="confirmModal" class="confirm-modal">
    <div class="confirm-modal-container">
        <div class="confirm-modal-content">
            
            <div id="modalHeader" class="confirm-modal-header">
                <div class="confirm-modal-header-content">
                    <div id="modalIcon" class="confirm-modal-icon">
                        <span id="modalIconFeather" data-feather="alert-triangle"></span>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="confirm-modal-title">Confirm Action</h3>
                        <p id="modalSubtitle" class="confirm-modal-subtitle">This action requires confirmation</p>
                    </div>
                </div>
            </div>

            
            <div class="confirm-modal-body">
                <p id="modalMessage">Are you sure you want to proceed?</p>
                <div id="modalDependencies" style="display:none; margin-top: 16px; padding: 12px; background: #fef2f2; border-left: 4px solid #dc2626; border-radius: 6px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #991b1b;">Dependencies Found</h4>
                    <ul id="modalDependenciesList" style="margin: 0; padding-left: 20px; font-size: 13px; color: #7f1d1d;">
                    </ul>
                </div>
            </div>

            
            <div class="confirm-modal-footer">
                <button type="button" id="modalCancelBtn" class="confirm-btn confirm-btn-cancel" onclick="closeConfirmModal()">
                    Cancel
                </button>
                <button type="button" id="modalConfirmBtn" class="confirm-btn confirm-btn-action">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.confirm-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.2s ease-in-out;
}

.confirm-modal-container {
    position: relative;
    margin: 15% auto;
    width: 90%;
    max-width: 480px;
    animation: slideDown 0.3s ease-in-out;
}

.confirm-modal-content {
    background: var(--card, #ffffff);
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.confirm-modal-header {
    padding: 24px 24px 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

[data-theme="dark"] .confirm-modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.confirm-modal-header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.confirm-modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.confirm-modal-icon span {
    width: 24px;
    height: 24px;
}

.confirm-modal-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: var(--text, #111827);
}

.confirm-modal-subtitle {
    margin: 4px 0 0 0;
    font-size: 14px;
    color: var(--muted, #6b7280);
}

.confirm-modal-body {
    padding: 24px;
}

.confirm-modal-body p {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
    color: var(--text, #374151);
}

.confirm-modal-footer {
    padding: 20px 24px;
    background: rgba(0, 0, 0, 0.02);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

[data-theme="dark"] .confirm-modal-footer {
    background: rgba(255, 255, 255, 0.02);
}

.confirm-btn {
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.confirm-btn-cancel {
    background: #16a34a;
    color: white;
}

.confirm-btn-cancel:hover {
    background: #15803d;
}

.confirm-btn-action {
    color: white;
}
</style>

<script>
if (typeof window.confirmModalForm === 'undefined') window.confirmModalForm = null;

// Reset body overflow on page load (fixes issue where overflow:hidden persists after redirect)
if (!window.__confirmModalInit) {
    document.addEventListener('DOMContentLoaded', function() {
        document.body.style.overflow = 'auto';
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.style.display = 'none';
        }
    });
    window.__confirmModalInit = true;
}

if (typeof window.showConfirmModal === 'undefined') {
    window.showConfirmModal = function(options) {
        const modal = document.getElementById('confirmModal');
        const modalIcon = document.getElementById('modalIcon');
        const modalIconFeather = document.getElementById('modalIconFeather');
        const modalTitle = document.getElementById('modalTitle');
        const modalSubtitle = document.getElementById('modalSubtitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalDependencies = document.getElementById('modalDependencies');
        const modalDependenciesList = document.getElementById('modalDependenciesList');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        const cancelBtn = document.getElementById('modalCancelBtn');

        // Reset dependencies display
        if (modalDependencies) modalDependencies.style.display = 'none';
        if (modalDependenciesList) modalDependenciesList.innerHTML = '';

        // Set content
        modalTitle.textContent = options.title || 'Confirm Action';
        modalSubtitle.textContent = options.subtitle || 'This action requires confirmation';
        modalMessage.innerHTML = (options.message || 'Are you sure you want to proceed?').replace(/\n/g, '<br>');

        // Store form reference FIRST
        window.confirmModalForm = options.form;

        // Set up confirm button click handler FIRST - before any async operations
        const setupConfirmButton = function() {
            if (!options.cancelOnly) {
                confirmBtn.onclick = function() {
                    // Check if there's a custom onConfirm callback
                    if (typeof options.onConfirm === 'function') {
                        options.onConfirm();
                        return;
                    }

                    // Otherwise, use form submission
                    if (window.confirmModalForm) {
                        try {
                            // IMPORTANT: forms in this app often have onsubmit="event.preventDefault(); showConfirmModal(...)".
                            // requestSubmit() triggers submit handlers and would re-open the modal + prevent the request.
                            // submit() bypasses submit handlers and performs the actual POST/DELETE.
                            confirmBtn.disabled = true;
                            confirmBtn.style.opacity = '0.7';
                            window.confirmModalForm.submit();
                            // Don't close immediately - let the page navigate
                        } catch (e) {
                            console.error('Form submission error:', e);
                            if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
                        }
                    } else {
                        console.warn('No form reference found');
                        if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
                    }
                };
            }
        };

        // Setup confirm button handler immediately
        setupConfirmButton();

        // If there's a dependency check URL, fetch dependencies first
        if (options.checkDependenciesUrl) {
            // Show loading state
            modalMessage.innerHTML = '<div style="text-align:center;padding:20px;"><span>Checking dependencies...</span></div>';
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = '0.5';

            fetch(options.checkDependenciesUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Restore original message
                modalMessage.innerHTML = (options.message || 'Are you sure you want to proceed?').replace(/\n/g, '<br>');

                if (data.can_delete === false || data.can_proceed === false) {
                    // Has dependencies - show them and hide delete button
                    if (data.dependencies && data.dependencies.length > 0) {
                        modalDependencies.style.display = 'block';
                        modalDependenciesList.innerHTML = '';

                        data.dependencies.forEach(dep => {
                            const li = document.createElement('li');
                            li.style.marginBottom = '4px';
                            let text = `<strong>${dep.message}</strong>: ${dep.count} item(s)`;
                            if (dep.details) {
                                text = `<strong>${dep.message}</strong>: ${dep.details}`;
                            }
                            if (dep.items && dep.items.length > 0) {
                                text += '<ul style="margin-top:4px;padding-left:20px;">';
                                dep.items.forEach(item => {
                                    text += `<li>${item.name}</li>`;
                                });
                                if (dep.count > dep.items.length) {
                                    text += `<li><em>...and ${dep.count - dep.items.length} more</em></li>`;
                                }
                                text += '</ul>';
                            }
                            li.innerHTML = text;
                            modalDependenciesList.appendChild(li);
                        });
                    }

                    // Hide confirm button, show only cancel
                    confirmBtn.style.display = 'none';
                    cancelBtn.textContent = 'Close';
                } else {
                    // Can delete - show confirm button
                    confirmBtn.style.display = 'block';
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = '1';
                    modalDependencies.style.display = 'none';
                    // Re-setup handler after async operation
                    setupConfirmButton();
                }
            })
            .catch(error => {
                console.error('Error checking dependencies:', error);
                modalMessage.innerHTML = (options.message || 'Are you sure you want to proceed?').replace(/\n/g, '<br>');
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                // Re-setup handler after async operation
                setupConfirmButton();
            });
        } else {
            // No dependency check - proceed normally
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
        }

        // Handle cancelOnly mode (informational modal with no action)
        if (options.cancelOnly) {
            confirmBtn.style.display = 'none';
            cancelBtn.textContent = options.confirmText || 'OK';
            cancelBtn.onclick = function() {
                if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
            };
        } else {
            confirmBtn.style.display = 'block';
            cancelBtn.textContent = 'Cancel';
            cancelBtn.onclick = function() {
                if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
            };
        }

        // Set colors based on type
        if (options.type === 'delete') {
            modalIcon.style.background = '#fee2e2';
            modalIconFeather.style.color = '#dc2626';
            modalIconFeather.setAttribute('data-feather', 'trash-2');
            confirmBtn.style.background = '#dc2626';
            confirmBtn.textContent = options.confirmText || 'Delete';
            confirmBtn.onmouseover = function() { this.style.background = '#b91c1c'; };
            confirmBtn.onmouseout = function() { this.style.background = '#dc2626'; };
        } else if (options.type === 'restore') {
            modalIcon.style.background = '#fee2e2';
            modalIconFeather.style.color = '#dc2626';
            modalIconFeather.setAttribute('data-feather', 'refresh-cw');
            confirmBtn.style.background = '#dc2626';
            confirmBtn.textContent = options.confirmText || 'Restore';
            confirmBtn.onmouseover = function() { this.style.background = '#b91c1c'; };
            confirmBtn.onmouseout = function() { this.style.background = '#dc2626'; };
        } else if (options.type === 'warning') {
            modalIcon.style.background = '#fef3c7';
            modalIconFeather.style.color = '#d97706';
            modalIconFeather.setAttribute('data-feather', 'alert-circle');
            confirmBtn.style.background = '#d97706';
            confirmBtn.textContent = options.confirmText || 'OK';
            confirmBtn.onmouseover = function() { this.style.background = '#b45309'; };
            confirmBtn.onmouseout = function() { this.style.background = '#d97706'; };
        } else {
            modalIcon.style.background = '#fef3c7';
            modalIconFeather.style.color = '#d97706';
            modalIconFeather.setAttribute('data-feather', 'alert-triangle');
            confirmBtn.style.background = '#2563eb';
            confirmBtn.textContent = options.confirmText || 'Confirm';
            confirmBtn.onmouseover = function() { this.style.background = '#1d4ed8'; };
            confirmBtn.onmouseout = function() { this.style.background = '#2563eb'; };
        }

        // Re-render feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
}

if (typeof window.closeConfirmModal === 'undefined') {
    window.closeConfirmModal = function() {
        const modal = document.getElementById('confirmModal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        window.confirmModalForm = null;
    };
}

if (!window.__confirmModalListenersAdded) {
    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmModal');
        if (event.target === modal) {
            if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
        }
    });
    window.__confirmModalListenersAdded = true;
}
</script>
<?php /**PATH E:\xampp\htdocs\IOMS\resources\views/partials/confirmation-modal.blade.php ENDPATH**/ ?>