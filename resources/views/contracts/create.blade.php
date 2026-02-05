@extends('layouts.dashboard')

@section('title', 'Create Contract')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Add Contract</h2>
            <p class="muted">Create a new contract with initial version, files, and reminders.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-top:12px;background:#fee2e2;border-left:4px solid #dc2626;">
            <ul style="margin:0;padding-left:20px;color:#991b1b;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('contracts.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Contract Details</h3>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:18px;max-width:800px;">

                <div>
                    <label for="contract_type_id" style="font-size:15px;font-weight:600;">Contract Type <span style="color:#dc2626;">*</span></label>
                    <select name="contract_type_id" id="contract_type_id" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                        <option value="">-- Select Type --</option>
                        @foreach($contractTypes as $type)
                            <option value="{{ $type->id }}" {{ old('contract_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="contract_with" style="font-size:15px;font-weight:600;">Contract With <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="contract_with" id="contract_with" value="{{ old('contract_with') }}" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;"
                        placeholder="e.g., ABC Corporation">
                </div>

                <div>
                    <label for="grace_period_days" style="font-size:15px;font-weight:600;">Grace Period (days) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="grace_period_days" id="grace_period_days" value="{{ old('grace_period_days', 30) }}" required min="0"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                    <p class="muted" style="font-size:13px;margin-top:6px;">Days before end date for "Expiring Soon" status</p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Initial Version Details</h3>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:18px;max-width:800px;">
                <div>
                    <label for="start_date" style="font-size:15px;font-weight:600;">Start Date (IST) <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                </div>

                <div>
                    <label for="end_date" style="font-size:15px;font-weight:600;">End Date (IST) <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                </div>
            </div>

            <div style="margin-top:18px;">
                <label for="description" style="display:block;font-size:15px;font-weight:600;margin-bottom:8px;">Description</label>
                <textarea name="description" id="description" rows="4"
                    style="width:100%;max-width:800px;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                    placeholder="Optional description">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">File Attachments</h3>
            <div>
                <label style="display:block;font-weight:700;margin-bottom:8px;">
                    Attach Documents (optional, multiple)
                </label>
                <div style="position:relative;">
                    <input
                        type="file"
                        name="files[]"
                        id="files"
                        multiple
                        accept=".pdf,.doc,.docx,.xlsx,.xls,.txt,.jpg,.jpeg,.png"
                        style="display:none;"
                        onchange="updateFileLabel(this)"
                    />
                    <label for="files" style="
                        display:inline-flex;
                        align-items:center;
                        gap:8px;
                        padding:14px 24px;
                        background:#22c55e;
                        color:white;
                        border-radius:10px;
                        font-weight:600;
                        font-size:15px;
                        cursor:pointer;
                        border:none;
                        transition:all 0.2s;
                    "
                    onmouseover="this.style.background='#16a34a'"
                    onmouseout="this.style.background='#22c55e'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                        <span id="file_label">Choose Files</span>
                    </label>
                    <span id="file_count" style="margin-left:20px;font-size:14px;color:#6b7280;"></span>
                </div>
                <div class="muted" style="font-size:12px;margin-top:4px;">
                    Max 20 MB per file. Allowed: PDF, DOC/DOCX, XLS/XLSX, TXT, PNG, JPG, JPEG.
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Reminders</h3>
            <p class="muted" style="font-size:13px;margin-bottom:12px;">Set days before end date to send notifications</p>
            <div id="reminder_container" style="display:flex;flex-direction:column;gap:10px;max-width:400px;">
                @for($i = 0; $i < 3; $i++)
                    <div class="reminder-row" style="display:flex;align-items:center;gap:8px;">
                        <span style="width:24px;font-size:13px;color:#6b7280;">{{ $i + 1 }}.</span>
                        <input type="number" name="reminder_days[]" min="0" placeholder="Days before"
                            value="{{ old('reminder_days.' . $i) }}"
                            style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;font-size:14px;">
                    </div>
                @endfor
            </div>
            <button type="button" onclick="addReminder()" style="margin-top:12px;padding:8px 16px;background:#10b981;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                + Add Reminder
            </button>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Notification Recipients</h3>
            <p class="muted" style="font-size:13px;margin-bottom:12px;">Select recipients to notify about contract expiry</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));gap:12px;">
                @forelse($recipients as $recipient)
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;background:#f9fafb;border-radius:8px;cursor:pointer;">
                        <input type="checkbox" name="recipient_ids[]" value="{{ $recipient->id }}"
                            {{ in_array($recipient->id, old('recipient_ids', [])) ? 'checked' : '' }}
                            style="width:18px;height:18px;">
                        <div>
                            <div style="font-weight:600;font-size:14px;">{{ $recipient->name }}</div>
                            <div style="font-size:12px;color:#6b7280;">{{ $recipient->email }}</div>
                        </div>
                    </label>
                @empty
                    <p class="muted">No recipients available</p>
                @endforelse
            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:12px;">
            <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border:none;border-radius:10px;font-size:15px;font-weight:500;cursor:pointer;">
                Create Contract
            </button>
            <a href="{{ route('contracts.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:15px;font-weight:500;">
                Cancel
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
function initializeContractDatePickers() {
    const startDateInput = document.querySelector('#start_date');
    const endDateInput = document.querySelector('#end_date');

    if (startDateInput) {
        if (startDateInput._flatpickr) {
            startDateInput._flatpickr.destroy();
        }
        flatpickr("#start_date", {
            dateFormat: "Y-m-d H:i",
            enableTime: true,
            time_24hr: true,
            altInput: true,
            altFormat: "d M Y, h:i K",
            onReady: function(selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.style.width = '100%';
                    instance.altInput.style.padding = '14px 16px';
                    instance.altInput.style.borderRadius = '10px';
                    instance.altInput.style.border = '1px solid #d0d7e0';
                    instance.altInput.style.fontSize = '15px';
                    instance.altInput.style.cursor = 'pointer';
                    instance.altInput.style.backgroundColor = 'white';
                }
            }
        });
    }

    if (endDateInput) {
        if (endDateInput._flatpickr) {
            endDateInput._flatpickr.destroy();
        }
        flatpickr("#end_date", {
            dateFormat: "Y-m-d H:i",
            enableTime: true,
            time_24hr: true,
            altInput: true,
            altFormat: "d M Y, h:i K",
            onReady: function(selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.style.width = '100%';
                    instance.altInput.style.padding = '14px 16px';
                    instance.altInput.style.borderRadius = '10px';
                    instance.altInput.style.border = '1px solid #d0d7e0';
                    instance.altInput.style.fontSize = '15px';
                    instance.altInput.style.cursor = 'pointer';
                    instance.altInput.style.backgroundColor = 'white';
                }
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeContractDatePickers);
} else {
    initializeContractDatePickers();
}

// Re-initialize on SPA navigation
window.addEventListener('spa:navigated', initializeContractDatePickers);

function updateFileLabel(input) {
    const fileCount = input.files.length;
    const fileLabel = document.getElementById('file_label');
    const countSpan = document.getElementById('file_count');

    if (fileCount > 0) {
        fileLabel.textContent = `${fileCount} file${fileCount > 1 ? 's' : ''} selected`;
        countSpan.textContent = '';
    } else {
        fileLabel.textContent = 'Choose Files';
        countSpan.textContent = '';
    }
}

function addReminder() {
    const container = document.getElementById('reminder_container');
    const count = container.querySelectorAll('.reminder-row').length;
    const row = document.createElement('div');
    row.className = 'reminder-row';
    row.style.cssText = 'display:flex;align-items:center;gap:8px;';
    row.innerHTML = `
        <span style="width:24px;font-size:13px;color:#6b7280;">${count + 1}.</span>
        <input type="number" name="reminder_days[]" min="0" placeholder="Days before"
            style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;font-size:14px;">
        <button type="button" onclick="this.parentElement.remove();updateReminderNumbers();"
            style="padding:8px 12px;background:#dc2626;color:#fff;border:none;border-radius:6px;cursor:pointer;">Remove</button>
    `;
    container.appendChild(row);
}

function updateReminderNumbers() {
    const rows = document.querySelectorAll('.reminder-row');
    rows.forEach((row, index) => {
        row.querySelector('span').textContent = `${index + 1}.`;
    });
}
</script>
@endpush
