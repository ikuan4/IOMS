<div id="confirmationModal" class="confirmation-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
    <div class="confirmation-card" style="background:var(--card); padding:18px; border-radius:12px; width:420px; max-width:92%; box-shadow:0 12px 40px rgba(0,0,0,0.18);">
        <h3 style="margin:0 0 8px 0;">Confirm action</h3>
        <p class="muted" style="margin:0 0 18px 0;">Are you sure you want to continue? This action cannot be undone.</p>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" id="confirmCancel" style="background:transparent;border:0;padding:10px 14px;border-radius:8px;">Cancel</button>
            <button type="button" id="confirmOk" style="background:#ef4444;color:white;border:0;padding:10px 14px;border-radius:8px;">Yes, proceed</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.showConfirmation = function (opts) {
        opts = opts || {};
        const modal = document.getElementById('confirmationModal');
        const ok = document.getElementById('confirmOk');
        const cancel = document.getElementById('confirmCancel');
        modal.style.display = 'flex';
        ok.textContent = opts.okText || 'Yes, proceed';
        cancel.textContent = opts.cancelText || 'Cancel';

        function cleanup() {
            modal.style.display = 'none';
            ok.removeEventListener('click', onOk);
            cancel.removeEventListener('click', onCancel);
        }

        function onOk() {
            cleanup();
            if (typeof opts.onOk === 'function') opts.onOk();
        }

        function onCancel() {
            cleanup();
            if (typeof opts.onCancel === 'function') opts.onCancel();
        }

        ok.addEventListener('click', onOk);
        cancel.addEventListener('click', onCancel);
    }
});
</script>
@endpush
