@extends('layouts.dashboard')

@section('title', 'Edit Contract')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Edit Contract</h2>
            <p class="muted">Update contract details and latest version information.</p>
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

    <form method="POST" action="{{ route('contracts.update', $contract->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Contract Details</h3>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:18px;max-width:800px;">

                <div>
                    <label for="contract_type_id" style="font-size:15px;font-weight:600;">Contract Type <span style="color:#dc2626;">*</span></label>
                    <select name="contract_type_id" id="contract_type_id" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                        <option value="">-- Select Type --</option>
                        @foreach($contractTypes as $type)
                            <option value="{{ $type->id }}" {{ old('contract_type_id', $contract->contract_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="contract_with" style="font-size:15px;font-weight:600;">Contract With <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="contract_with" id="contract_with" value="{{ old('contract_with', $contract->contract_with) }}" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;"
                        placeholder="e.g., ABC Corporation">
                </div>

                <div>
                    <label for="grace_period_days" style="font-size:15px;font-weight:600;">Grace Period (days) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="grace_period_days" id="grace_period_days" value="{{ old('grace_period_days', $contract->grace_period_days) }}" required min="0"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                    <p class="muted" style="font-size:13px;margin-top:6px;">Days before end date for "Expiring Soon" status</p>
                </div>
            </div>
        </div>

        @php
            $latestVersion = $contract->latestVersion;
        @endphp

        @if($latestVersion)
        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Latest Version Details (v{{ $latestVersion->version_number }})</h3>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:18px;max-width:800px;">
                <div>
                    <label for="start_date" style="font-size:15px;font-weight:600;">Start Date (IST) <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="start_date" id="start_date" value="{{ old('start_date', $latestVersion->start_date->format('Y-m-d H:i')) }}" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                </div>

                <div>
                    <label for="end_date" style="font-size:15px;font-weight:600;">End Date (IST) <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="end_date" id="end_date" value="{{ old('end_date', $latestVersion->end_date->format('Y-m-d H:i')) }}" required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;">
                </div>
            </div>

            <div style="margin-top:18px;">
                <label for="description" style="font-size:15px;font-weight:600;">Description</label>
                <textarea name="description" id="description" rows="4"
                    style="width:100%;max-width:800px;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;"
                    placeholder="Optional description">{{ old('description', $latestVersion->description) }}</textarea>
            </div>

            @if($latestVersion->files->count() > 0)
            <div style="margin-top:18px;">
                <label style="font-size:15px;font-weight:600;">Current Files</label>
                <div style="margin-top:8px;">
                    @foreach($latestVersion->files as $file)
                        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:#f9fafb;border-radius:6px;margin-bottom:6px;">
                            <i data-feather="file" style="width:16px;height:16px;color:#6b7280;"></i>
                            <span style="flex:1;font-size:14px;">{{ $file->storedFile->original_name }}</span>
                            <span style="font-size:12px;color:#6b7280;">{{ number_format($file->storedFile->file_size / 1024, 1) }} KB</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Add New Files</h3>
            <div>
                <label for="files" style="font-size:15px;font-weight:600;">Upload Files</label>
                <input type="file" name="files[]" id="files" multiple accept=".pdf,.doc,.docx,.xlsx,.xls,.txt,.jpg,.jpeg,.png"
                    style="width:100%;max-width:600px;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;margin-top:8px;"
                    onchange="updateFileLabel(this)">
                <p class="muted" style="font-size:13px;margin-top:6px;"><span id="file_count"></span> Maximum 10MB per file</p>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Reminders</h3>
            <p class="muted" style="font-size:13px;margin-bottom:12px;">Set days before end date to send notifications</p>
            <div id="reminder_container" style="display:flex;flex-direction:column;gap:10px;max-width:400px;">
                @if($reminders->count() > 0)
                    @foreach($reminders as $index => $reminder)
                        <div class="reminder-row" style="display:flex;align-items:center;gap:8px;">
                            <span style="width:24px;font-size:13px;color:#6b7280;">{{ $index + 1 }}.</span>
                            <input type="number" name="reminder_days[]" min="0" placeholder="Days before"
                                value="{{ old('reminder_days.' . $index, $reminder->days_before_end) }}"
                                style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;font-size:14px;">
                            @if($index > 0)
                            <button type="button" onclick="this.parentElement.remove();updateReminderNumbers();"
                                style="padding:8px 12px;background:#dc2626;color:#fff;border:none;border-radius:6px;cursor:pointer;">Remove</button>
                            @endif
                        </div>
                    @endforeach
                @else
                    @for($i = 0; $i < 3; $i++)
                        <div class="reminder-row" style="display:flex;align-items:center;gap:8px;">
                            <span style="width:24px;font-size:13px;color:#6b7280;">{{ $i + 1 }}.</span>
                            <input type="number" name="reminder_days[]" min="0" placeholder="Days before"
                                value="{{ old('reminder_days.' . $i) }}"
                                style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;font-size:14px;">
                        </div>
                    @endfor
                @endif
            </div>
            <button type="button" onclick="addReminder()" style="margin-top:12px;padding:8px 16px;background:#10b981;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                + Add Reminder
            </button>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;">Notification Recipients</h3>
            <p class="muted" style="font-size:13px;margin-bottom:12px;">Select recipients to notify about contract expiry</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));gap:12px;">
                @php
                    $selectedRecipientIds = $contract->notificationRecipients->pluck('id')->toArray();
                @endphp
                @forelse($recipients as $recipient)
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;background:#f9fafb;border-radius:8px;cursor:pointer;">
                        <input type="checkbox" name="recipient_ids[]" value="{{ $recipient->id }}"
                            {{ in_array($recipient->id, old('recipient_ids', $selectedRecipientIds)) ? 'checked' : '' }}
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
                Update Contract
            </button>
            <a href="{{ route('contracts.show', $contract->id) }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:15px;font-weight:500;">
                Cancel
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#start_date", {
        dateFormat: "Y-m-d H:i",
        enableTime: true,
        time_24hr: true,
        altInput: true,
        altFormat: "d M Y, h:i K"
    });

    flatpickr("#end_date", {
        dateFormat: "Y-m-d H:i",
        enableTime: true,
        time_24hr: true,
        altInput: true,
        altFormat: "d M Y, h:i K"
    });

    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});

function updateFileLabel(input) {
    const fileCount = input.files.length;
    const span = document.getElementById('file_count');
    span.textContent = fileCount > 0 ? `${fileCount} file(s) selected. ` : '';
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
