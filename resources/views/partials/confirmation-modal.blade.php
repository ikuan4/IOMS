<div id="confirmation-modal" class="modal" style="display:none;">
    <div class="modal-backdrop" onclick="closeConfirm()"></div>
    <div class="modal-card">
        <div class="modal-header">
            <h4 id="confirm-title">Confirm</h4>
        </div>
        <div class="modal-body">
            <p id="confirm-message">Are you sure?</p>
        </div>
        <div class="modal-footer" style="display:flex;gap:8px;justify-content:flex-end;">
            <button class="btn" onclick="closeConfirm();">Cancel</button>
            <button id="confirm-ok" class="btn danger">Confirm</button>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}
.modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
}
.modal-card {
    position: relative;
    background: var(--card, #ffffff);
    color: var(--text, #0f1724);
    border-radius: 8px;
    padding: 16px;
    width: 480px;
    max-width: 92%;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
}
.modal-header h4 {
    margin: 0 0 8px 0;
    color: var(--text, #0f1724);
}
.modal-body {
    color: var(--text, #0f1724);
}
.btn.danger {
    background: #ef4444;
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}
.btn {
    background: #e5e7eb;
    color: #111827;
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}
</style>

<script>
if (typeof window.showConfirm === 'undefined') {
    window.showConfirm = function(title, message, onConfirm) {
        const titleEl = document.getElementById('confirm-title');
        const msgEl = document.getElementById('confirm-message');
        const modal = document.getElementById('confirmation-modal');
        const ok = document.getElementById('confirm-ok');
        if (titleEl) titleEl.textContent = title || 'Confirm';
        if (msgEl) msgEl.textContent = message || 'Are you sure?';
        if (ok) {
            ok.onclick = function(){
                if (typeof window.closeConfirm === 'function') window.closeConfirm();
                if (typeof onConfirm === 'function') onConfirm();
            };
        }
        if (modal) modal.style.display = 'block';
    };
}

if (typeof window.closeConfirm === 'undefined') {
    window.closeConfirm = function(){
        const modal = document.getElementById('confirmation-modal');
        if (modal) modal.style.display = 'none';
    };
}
</script>

{{-- Advanced Confirmation Modal with Theme Support --}}
<div id="confirmModal" class="confirm-modal">
    <div class="confirm-modal-container">
        <div class="confirm-modal-content">
            {{-- Modal Header --}}
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

            {{-- Modal Body --}}
            <div class="confirm-modal-body">
                <p id="modalMessage">Are you sure you want to proceed?</p>
            </div>

            {{-- Modal Footer --}}
            <div class="confirm-modal-footer">
                <button id="modalCancelBtn" class="confirm-btn confirm-btn-cancel" onclick="closeConfirmModal()">
                    Cancel
                </button>
                <button id="modalConfirmBtn" class="confirm-btn confirm-btn-action">
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
        const confirmBtn = document.getElementById('modalConfirmBtn');

        // Set content
        modalTitle.textContent = options.title || 'Confirm Action';
        modalSubtitle.textContent = options.subtitle || 'This action requires confirmation';
        modalMessage.textContent = options.message || 'Are you sure you want to proceed?';

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

        // Store form reference
        window.confirmModalForm = options.form;

        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        // Set up confirm button
        confirmBtn.onclick = function() {
            if (window.confirmModalForm) {
                try { window.confirmModalForm.submit(); } catch (e) { /* ignore */ }
            }
            if (typeof window.closeConfirmModal === 'function') window.closeConfirmModal();
        };
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
