@csrf

@php
    /** @var \App\Models\Contract $contract */

    $mode             = $mode ?? 'create'; // 'create' or 'edit'
    $showActiveToggle = $showActiveToggle ?? false;

    // Safe defaults
    $reminders = $reminders ?? collect();

    $latestVersion = $contract->latestVersion ?? null;

    // Start/end dates: prefer old() after validation errors
    $startDateValue = old('start_date');
    if (!$startDateValue && $latestVersion?->start_date) {
        $startDateValue = $latestVersion->start_date->timezone('Asia/Kolkata')->format('Y-m-d');
    }

    $endDateValue = old('end_date');
    if (!$endDateValue && $latestVersion?->end_date) {
        $endDateValue = $latestVersion->end_date->timezone('Asia/Kolkata')->format('Y-m-d');
    }

    // Reminder days
    $existingReminderDays = $reminders->pluck('days_before_end')->all();
    $oldReminderDays      = old('reminder_days', $existingReminderDays);
    if (!is_array($oldReminderDays)) {
        $oldReminderDays = [];
    }
    $reminderRows = max(3, count($oldReminderDays));

    // Selected recipients
    $selectedRecipientIds = collect(
        old(
            'recipient_ids',
            $contract->exists ? $contract->notificationRecipients->pluck('id')->all() : []
        )
    )
    ->map(fn($v) => (int) $v)
    ->all();
@endphp

<div class="card" style="margin-top:16px; padding:16px 20px;">
    <div style="display:flex;flex-direction:column;gap:24px;max-width:820px;">

        {{-- Validation errors --}}
        @if ($errors->any())
            <div style="margin-bottom:8px; color:#b91c1c;">
                <strong>There were some problems with your input:</strong>
                <ul style="margin-top:8px; padding-left:20px;">
                    @foreach ($errors->all() as $error)
                        <li style="font-size:14px;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Contract Basics --}}
        <div>
            <h3 style="margin:0 0 8px 0;">Contract Details</h3>
            <p class="muted" style="font-size:13px;margin:0 0 10px 0;">
                Contract type, counterparty, and grace period. Grace period is mandatory and used for "Expiring Soon".
            </p>

            <div style="display:flex;flex-direction:column;gap:12px;">

                {{-- Contract Type --}}
                <div>
                    <label for="contract_type_id" style="display:block;font-weight:600;margin-bottom:8px;">
                        Contract Type <span style="color:#dc2626;">*</span>
                    </label>
                    <select
                        id="contract_type_id"
                        name="contract_type_id"
                        required
                        style="
                            width:100%;
                            max-width:420px;
                            padding:10px 12px;
                            border-radius:8px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                        "
                    >
                        <option value="">-- Select Contract Type --</option>
                        @foreach($contractTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ (int) old('contract_type_id', $contract->contract_type_id) === $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Contract With --}}
                <div>
                    <label for="contract_with" style="display:block;font-weight:600;margin-bottom:8px;">
                        Contract With <span style="color:#dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        id="contract_with"
                        name="contract_with"
                        value="{{ old('contract_with', $contract->contract_with) }}"
                        required
                        style="
                            width:100%;
                            max-width:420px;
                            padding:14px 16px;
                            border-radius:10px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                        "
                    />
                </div>

                {{-- Grace Period --}}
                <div>
                    <label for="grace_period_days" style="display:block;font-weight:600;margin-bottom:8px;">
                        Grace Period (days) <span style="color:#dc2626;">*</span>
                    </label>
                    <input
                        type="number"
                        id="grace_period_days"
                        name="grace_period_days"
                        min="0"
                        value="{{ old('grace_period_days', $contract->grace_period_days ?? 30) }}"
                        required
                        style="
                            width:140px;
                            padding:10px 12px;
                            border-radius:8px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                        "
                    />
                    <div class="muted" style="font-size:12px;margin-top:4px;">
                        Used to determine "Expiring Soon" before end date.
                    </div>
                </div>

                {{-- Active toggle (edit only) --}}
                @if($showActiveToggle)
                    <div>
                        <label style="font-size:15px;font-weight:500;margin-bottom:8px;display:block;">Status</label>
                        <label class="toggle-switch" style="display:inline-flex;align-items:center;cursor:pointer;gap:12px;">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $contract->is_active ?? true) ? 'checked' : '' }}
                                class="toggle-input"
                            >
                            <span class="toggle-slider"></span>
                            <span class="toggle-label" style="font-size:15px;font-weight:500;">Contract is Active</span>
                        </label>
                        <div class="muted" style="font-size:12px;margin-top:4px;">
                            Turn this off to mark the contract as Inactive / Archived.
                        </div>
                    </div>

                    <style>
                        .toggle-switch { position: relative; }
                        .toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
                        .toggle-slider {
                            position: relative;
                            display: inline-block;
                            width: 48px;
                            height: 24px;
                            background-color: #cbd5e1;
                            border-radius: 24px;
                            transition: background-color 0.3s;
                        }
                        .toggle-slider::before {
                            content: '';
                            position: absolute;
                            width: 18px;
                            height: 18px;
                            left: 3px;
                            top: 3px;
                            background-color: white;
                            border-radius: 50%;
                            transition: transform 0.3s;
                        }
                        .toggle-input:checked + .toggle-slider {
                            background-color: #22c55e;
                        }
                        .toggle-input:checked + .toggle-slider::before {
                            transform: translateX(24px);
                        }
                        .toggle-input:focus + .toggle-slider {
                            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
                        }
                    </style>
                @endif
            </div>
        </div>

        {{-- Version (latest) --}}
        <div>
            <h3 style="margin:0 0 8px 0;">
                {{ $mode === 'edit' ? 'Latest Version Details' : 'Initial Version Details' }}
            </h3>
            <p class="muted" style="font-size:13px;margin:0 0 10px 0;">
                Start and end dates are mandatory and stored in UTC. Values shown here are in IST.
            </p>

            <div style="display:flex;flex-direction:column;gap:12px;">

                {{-- Description --}}
                <div>
                    <label for="description" style="display:block;font-weight:600;margin-bottom:8px;">
                        Contract Description (optional)
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        style="
                            width:100%;
                            padding:14px 16px;
                            border-radius:10px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                            resize:vertical;
                            font-family:inherit;
                        "
                    >{{ old('description', $latestVersion->description ?? '') }}</textarea>
                </div>

                {{-- Dates --}}
                <div style="display:flex;flex-wrap:wrap;gap:16px;">
                    <div style="flex:1;min-width:280px;">
                        <label for="start_date" style="display:block;font-weight:600;margin-bottom:8px;">
                            Start Date (IST) <span style="color:#dc2626;">*</span>
                        </label>
                        <input
                            type="text"
                            id="start_date"
                            name="start_date"
                            value="{{ $startDateValue }}"
                            placeholder="Select start date"
                            required
                            style="
                                width:100%;
                                padding:14px 16px;
                                border-radius:10px;
                                border:1px solid #d0d7e0;
                                font-size:15px;
                                cursor:pointer;
                                background-color:white;
                            "
                        />
                    </div>

                    <div style="flex:1;min-width:280px;">
                        <label for="end_date" style="display:block;font-weight:600;margin-bottom:8px;">
                            End Date (IST) <span style="color:#dc2626;">*</span>
                        </label>
                        <input
                            type="text"
                            id="end_date"
                            name="end_date"
                            value="{{ $endDateValue }}"
                            placeholder="Select end date"
                            required
                            style="
                                width:100%;
                                padding:14px 16px;
                                border-radius:10px;
                                border:1px solid #d0d7e0;
                                font-size:15px;
                                cursor:pointer;
                                background-color:white;
                            "
                        />
                    </div>
                </div>

                {{-- Files --}}
                <div style="margin-bottom:8px;">
                    <label style="display:block;font-weight:700;margin-bottom:8px;">
                        Attach Documents (optional, multiple)
                    </label>
                    <div style="position:relative;">
                        <input
                            type="file"
                            name="files[]"
                            id="file_input"
                            multiple
                            style="display:none;"
                            onchange="updateFileLabel(this)"
                        />
                        <label for="file_input" style="
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

                @if($mode === 'edit' && $latestVersion && $latestVersion->files->count() > 0)
                    {{-- Existing attachments preview --}}
                    <div>
                        <label style="display:block;font-weight:700;margin-bottom:8px;">
                            Current Attachments (latest version)
                        </label>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @foreach($latestVersion->files as $vf)
                                <div style="display:flex;align-items:center;justify-content:space-between;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <i data-feather="file" style="width:16px;height:16px;color:#6b7280;"></i>
                                        <span style="font-size:14px;">{{ $vf->storedFile->original_name ?? 'Attachment' }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        onclick="removeContractAttachment({{ $vf->id }}, this)"
                                        style="
                                            background:#fee2e2;
                                            color:#b91c1c;
                                            padding:6px 12px;
                                            border-radius:8px;
                                            border:none;
                                            cursor:pointer;
                                            display:flex;
                                            align-items:center;
                                            gap:4px;
                                            font-size:13px;
                                            font-weight:600;
                                        "
                                        title="Remove this attachment">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                                {{-- Hidden input to mark file for deletion --}}
                                <input type="hidden" name="remove_files[]" value="" id="remove_contract_file_{{ $vf->id }}">
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Reminders --}}
        <div>
            <h3 style="margin:0 0 8px 0;">Reminder Configuration</h3>
            <p class="muted" style="font-size:13px;margin:0 0 10px 0;">
                Set reminders X days before the current end date. Leave rows blank if not needed.
            </p>

            <div id="reminder_container" style="display:flex;flex-direction:column;gap:10px;max-width:420px;">
                @for($i = 0; $i < $reminderRows; $i++)
                    <div class="reminder-row" style="display:flex;align-items:center;gap:8px;">
                        <span class="reminder-number" style="width:24px;font-size:13px;color:#6b7280;">{{ $i + 1 }}.</span>
                        <input
                            type="number"
                            name="reminder_days[]"
                            min="0"
                            value="{{ $oldReminderDays[$i] ?? '' }}"
                            placeholder="Days before end date"
                            style="
                                flex:1;
                                padding:10px 12px;
                                border-radius:8px;
                                border:1px solid #d0d7e0;
                                font-size:14px;
                            "
                        />
                        @if($i === 2)
                        <button
                            type="button"
                            onclick="addReminder()"
                            style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                width:40px;
                                height:40px;
                                padding:0;
                                background:transparent;
                                color:#22c55e;
                                border:none;
                                cursor:pointer;
                                transition:color 0.2s;
                                flex-shrink:0;
                            "
                            onmouseover="this.style.color='#16a34a'"
                            onmouseout="this.style.color='#22c55e'"
                            title="Add reminder"
                        >
                            <i data-feather="plus-circle" style="width:24px;height:24px;"></i>
                        </button>
                        @elseif($i >= 3)
                        <button
                            type="button"
                            onclick="removeReminder(this)"
                            style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                width:40px;
                                height:40px;
                                padding:0;
                                background:transparent;
                                color:#dc2626;
                                border:none;
                                cursor:pointer;
                                transition:color 0.2s;
                                flex-shrink:0;
                            "
                            onmouseover="this.style.color='#b91c1c'"
                            onmouseout="this.style.color='#dc2626'"
                            title="Remove reminder"
                        >
                            <i data-feather="minus-circle" style="width:24px;height:24px;"></i>
                        </button>
                        @else
                        <div style="width:40px;flex-shrink:0;"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        {{-- Notification Recipients --}}
        <div>
            <h3 style="margin:0 0 8px 0;">Notification Recipients</h3>
            <p class="muted" style="font-size:13px;margin:0 0 10px 0;">
                Select recipients to notify about contract expiry reminders.
            </p>

            @if($recipients->isEmpty())
                <div style="padding:16px;background:#fef2f2;border-radius:8px;border-left:4px solid #ef4444;">
                    <p style="margin:0;font-size:13px;color:#991b1b;">
                        <strong>No recipients available.</strong> Please create notification recipients first.
                    </p>
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));gap:12px;">
                    @foreach($recipients as $recipient)
                        <label style="display:flex;align-items:center;gap:10px;padding:12px;background:#f9fafb;border-radius:8px;cursor:pointer;">
                            <input type="checkbox" name="recipient_ids[]" value="{{ $recipient->id }}"
                                {{ in_array($recipient->id, $selectedRecipientIds) ? 'checked' : '' }}
                                style="width:18px;height:18px;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">{{ $recipient->name }}</div>
                                <div style="font-size:12px;color:#6b7280;">{{ $recipient->email }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Buttons --}}
        <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
            <button
                type="submit"
                style="
                    background:#0B6BBD;
                    color:white;
                    padding:12px 24px;
                    border-radius:10px;
                    font-weight:600;
                    font-size:15px;
                    border:none;
                    display:flex;
                    align-items:center;
                    gap:8px;
                    cursor:pointer;
                    transition:all 0.2s;
                "
                onmouseover="this.style.background='#0956a5'"
                onmouseout="this.style.background='#0B6BBD'"
            >
                <i data-feather="save" style="width:18px;height:18px;"></i>
                {{ $mode === 'edit' ? 'Update Contract' : 'Create Contract' }}
            </button>

            <a
                href="{{ route('contracts.index') }}"
                style="
                    background:#6b7280;
                    color:white;
                    padding:12px 24px;
                    border-radius:10px;
                    font-weight:600;
                    font-size:15px;
                    text-decoration:none;
                    display:flex;
                    align-items:center;
                    gap:8px;
                    transition:all 0.2s;
                "
                onmouseover="this.style.background='#4b5563'"
                onmouseout="this.style.background='#6b7280'"
            >
                <i data-feather="x-circle" style="width:18px;height:18px;"></i>
                Cancel
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateFileLabel(input) {
    const fileCount = input.files.length;
    const fileCountSpan = document.getElementById('file_count');
    const fileLabel = document.getElementById('file_label');

    if (fileCount > 0) {
        fileLabel.textContent = `${fileCount} file${fileCount > 1 ? 's' : ''} selected`;
        fileCountSpan.textContent = '';
    } else {
        fileLabel.textContent = 'Choose Files';
        fileCountSpan.textContent = '';
    }
}

function addReminder() {
    const container = document.getElementById('reminder_container');
    const currentCount = container.querySelectorAll('.reminder-row').length;
    const newIndex = currentCount;

    const newRow = document.createElement('div');
    newRow.className = 'reminder-row';
    newRow.style.cssText = 'display:flex;align-items:center;gap:8px;';

    newRow.innerHTML = `
        <span class="reminder-number" style="width:24px;font-size:13px;color:#6b7280;">${newIndex + 1}.</span>
        <input
            type="number"
            name="reminder_days[]"
            min="0"
            placeholder="Days before end date"
            style="
                flex:1;
                padding:10px 12px;
                border-radius:8px;
                border:1px solid #d0d7e0;
                font-size:14px;
            "
        />
        <button
            type="button"
            onclick="removeReminder(this)"
            style="
                display:inline-flex;
                align-items:center;
                justify-content:center;
                width:40px;
                height:40px;
                padding:0;
                background:transparent;
                color:#dc2626;
                border:none;
                cursor:pointer;
                transition:color 0.2s;
                flex-shrink:0;
            "
            onmouseover="this.style.color='#b91c1c'"
            onmouseout="this.style.color='#dc2626'"
            title="Remove reminder"
        >
            <i data-feather="minus-circle" style="width:24px;height:24px;"></i>
        </button>
    `;
    
    container.appendChild(newRow);
    
    // Re-initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    updateReminderNumbers();
}

function removeReminder(button) {
    button.closest('.reminder-row').remove();
    updateReminderNumbers();
}

function updateReminderNumbers() {
    const rows = document.querySelectorAll('.reminder-row');
    rows.forEach((row, index) => {
        const numberSpan = row.querySelector('.reminder-number');
        if (numberSpan) {
            numberSpan.textContent = `${index + 1}.`;
        }
    });
}

function removeContractAttachment(fileId, button) {
    if (confirm('Are you sure you want to remove this attachment?')) {
        // Mark file for deletion
        const hiddenInput = document.getElementById('remove_contract_file_' + fileId);
        if (hiddenInput) {
            hiddenInput.value = fileId;
        }
        
        // Hide the attachment row
        button.closest('div[style*="display:flex"]').style.display = 'none';
    }
}
</script>
@endpush
