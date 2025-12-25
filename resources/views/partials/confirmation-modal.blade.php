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

<script>
function showConfirm(title, message, onConfirm) {
    document.getElementById('confirm-title').textContent = title || 'Confirm';
    document.getElementById('confirm-message').textContent = message || 'Are you sure?';
    const modal = document.getElementById('confirmation-modal');
    const ok = document.getElementById('confirm-ok');
    ok.onclick = function(){
        closeConfirm();
        if (typeof onConfirm === 'function') onConfirm();
    };
    modal.style.display = 'block';
}

function closeConfirm(){
    const modal = document.getElementById('confirmation-modal');
    modal.style.display = 'none';
}
</script>

<style>
.modal { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; z-index:2000; }
.modal-backdrop { position:absolute; inset:0; background:rgba(0,0,0,0.4); }
.modal-card { position:relative; background:var(--card); border-radius:8px; padding:16px; width:480px; max-width:92%; box-shadow:0 8px 30px rgba(0,0,0,0.2); }
.modal-header h4 { margin:0 0 8px 0; }
.modal-body { color:var(--text); }
.btn.danger { background:#ef4444; color:#fff; padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
.btn { background:#e5e7eb; color:#111827; padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
</style>
{{-- Confirmation Modal Component (copied from mshcscontr for full functionality) --}}
<div id="confirmModal" style="
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
">
    <div style="
        position: relative;
        margin: 15% auto;
        width: 90%;
        max-width: 480px;
        animation: slideDown 0.3s ease-in-out;
    ">
        <div style="
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        ">
            {{-- Modal Header --}}
            <div id="modalHeader" style="
                padding: 24px 24px 20px 24px;
                border-bottom: 1px solid #f3f4f6;
            ">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div id="modalIcon" style="
                        width: 48px;
                        height: 48px;
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                    ">
                        <span id="modalIconFeather" data-feather="alert-triangle" style="width: 24px; height: 24px;"></span>
                    </div>
                    <div>
                        <h3 id="modalTitle" style="
                            margin: 0;
                            font-size: 20px;
                            font-weight: 700;
                            color: #111827;
                        ">Confirm Action</h3>
                        <p id="modalSubtitle" style="
                            margin: 4px 0 0 0;
                            font-size: 14px;
                            color: #6b7280;
                        ">This action requires confirmation</p>
                    </div>
                </div>
            </div>

            {{-- Modal Body --}}
            <div style="padding: 24px;">
                <p id="modalMessage" style="
                    margin: 0;
                    font-size: 15px;
                    line-height: 1.6;
                    color: #374151;
                ">Are you sure you want to proceed?</p>
            </div>

            {{-- Modal Footer --}}
            <div style="
                padding: 20px 24px;
                background: #f9fafb;
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            ">
                <button
                    id="modalCancelBtn"
                    onclick="closeConfirmModal()"
                    style="
                        padding: 12px 24px;
                        border-radius: 10px;
                        border: none;
                        background: #16a34a;
                        color: white;
                        font-size: 15px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                    "
                    onmouseover="this.style.background='#15803d'"
                    onmouseout="this.style.background='#16a34a'"
                >
                    Cancel
                </button>
                <button
                    id="modalConfirmBtn"
                    style="
                        padding: 12px 24px;
                        border-radius: 10px;
                        border: none;
                        color: white;
                        font-size: 15px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                    "
                >
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
</style>

<script>
let confirmModalForm = null;

// Reset body overflow on page load (fixes issue where overflow:hidden persists after redirect)
document.addEventListener('DOMContentLoaded', function() {
    document.body.style.overflow = 'auto';
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.style.display = 'none';
    }
});

function showConfirmModal(options) {
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
    confirmModalForm = options.form;

    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // Set up confirm button
    confirmBtn.onclick = function() {
        if (confirmModalForm) {
            confirmModalForm.submit();
        }
        closeConfirmModal();
    };
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    confirmModalForm = null;
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('confirmModal');
    if (event.target === modal) {
        closeConfirmModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
    }
});
</script>
