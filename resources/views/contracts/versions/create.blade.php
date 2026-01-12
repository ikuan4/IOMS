@extends('layouts.dashboard')

@section('title', 'Add New Version')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
@endpush

@section('content')
    @php
        $statusLabel = $contract->status;
        $statusColor = match($statusLabel) {
            'Ongoing'       => '#16a34a',
            'Pending'       => '#2563eb',
            'Expiring Soon' => '#ca8a04',
            'Expired'       => '#dc2626',
            'Inactive'      => '#6b7280',
            default         => '#4b5563',
        };
    @endphp

    <div class="header-card">
        <div class="header-left">
            <h2>ADD NEW VERSION</h2>
            <p class="muted">
                Create a new version for Contract: <strong>{{ $contract->contract_number }}</strong>
            </p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
            <div>
                <span style="font-size:13px;color:#6b7280;">Current Status:</span>
                <span style="color:{{ $statusColor }};font-weight:700;margin-left:4px;">
                    {{ $statusLabel }}
                </span>
            </div>
            <div class="muted" style="font-size:12px;">
                Latest Version: v{{ $latest?->version_number ?? 'N/A' }}
            </div>
        </div>
    </div>

    {{-- Contract Summary Card --}}
    <div class="card" style="margin-top:12px; padding:16px 20px;">
        <h3 style="margin:0 0 12px 0;">Contract Summary</h3>
        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:24px;
            font-size:14px;
        ">
            <div>
                <div class="muted" style="margin-bottom:4px;">Contract Type</div>
                <div style="font-weight:600;">
                    {{ $contract->contractType->name ?? 'N/A' }}
                </div>
            </div>
            <div>
                <div class="muted" style="margin-bottom:4px;">Contract With</div>
                <div style="font-weight:600;">
                    {{ $contract->contract_with }}
                </div>
            </div>
            <div>
                <div class="muted" style="margin-bottom:4px;">Grace Period</div>
                <div style="font-weight:600;">
                    {{ $contract->grace_period_days }} days
                </div>
            </div>
            <div>
                <div class="muted" style="margin-bottom:4px;">Total Versions</div>
                <div style="font-weight:600;">
                    {{ $contract->versions->count() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Existing Versions Reference --}}
    <div class="card" style="margin-top:12px; padding:16px 20px;">
        <h3 style="margin:0 0 12px 0;">Existing Versions</h3>
        @if($contract->versions->isEmpty())
            <span class="muted" style="font-size:13px;">No versions exist yet.</span>
        @else
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                <tr style="text-align:left;border-bottom:1px solid #e5e7eb;">
                    <th style="padding:8px;">Version</th>
                    <th style="padding:8px;">Start Date (IST)</th>
                    <th style="padding:8px;">End Date (IST)</th>
                    <th style="padding:8px;">Description</th>
                </tr>
                </thead>
                <tbody>
                @foreach($contract->versions->sortByDesc('version_number') as $version)
                    @php
                        $vStart = $version->start_date?->timezone('Asia/Kolkata');
                        $vEnd   = $version->end_date?->timezone('Asia/Kolkata');
                    @endphp
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:8px;font-weight:600;">
                            v{{ $version->version_number }}
                        </td>
                        <td style="padding:8px;">
                            @if($vStart)
                                {{ $vStart->format('d M Y') }}
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="padding:8px;">
                            @if($vEnd)
                                {{ $vEnd->format('d M Y') }}
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="padding:8px;max-width:320px;">
                            @if($version->description)
                                <span style="font-size:13px;color:#4b5563;">
                                    {{ \Illuminate\Support\Str::limit($version->description, 80) }}
                                </span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- New Version Form --}}
    <form method="POST"
          action="{{ route('contracts.versions.store', $contract) }}"
          enctype="multipart/form-data">
        @csrf

        <div class="card" style="margin-top:12px; padding:16px 20px;">
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

                {{-- Version Details --}}
                <div>
                    <h3 style="margin:0 0 8px 0;">New Version Details</h3>
                    <p class="muted" style="font-size:13px;margin:0 0 10px 0;">
                        The new version will be v{{ ($latest?->version_number ?? 0) + 1 }}. Start and end dates are mandatory and stored in UTC.
                    </p>

                    <div style="display:flex;flex-direction:column;gap:12px;">

                        {{-- Description --}}
                        <div>
                            <label for="description" style="display:block;font-weight:600;margin-bottom:8px;">
                                Version Description (optional)
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Describe what changed in this version..."
                                style="
                                    width:100%;
                                    padding:14px 16px;
                                    border-radius:10px;
                                    border:1px solid #d0d7e0;
                                    font-size:15px;
                                    resize:vertical;
                                    font-family:inherit;
                                "
                            >{{ old('description') }}</textarea>
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
                                    value="{{ old('start_date') }}"
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
                                    value="{{ old('end_date') }}"
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
                    </div>
                </div>

                {{-- Buttons --}}
                <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
                    <button
                        type="submit"
                        style="
                            background:#3b82f6;
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
                        onmouseover="this.style.background='#2563eb'"
                        onmouseout="this.style.background='#3b82f6'"
                    >
                        <i data-feather="plus-circle" style="width:18px;height:18px;"></i>
                        Add Version
                    </button>

                    <a
                        href="{{ route('contracts.show', $contract) }}"
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
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        function initializeVersionDatePickers() {
            // Wait a bit to ensure everything is loaded
            setTimeout(function() {
                // Initialize Flatpickr for date inputs
                const startDateInput = document.querySelector("#start_date");
                const endDateInput = document.querySelector("#end_date");

                if (startDateInput) {
                    // Destroy existing instance if it exists
                    if (startDateInput._flatpickr) {
                        startDateInput._flatpickr.destroy();
                    }
                    flatpickr("#start_date", {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d M Y",
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
                    // Destroy existing instance if it exists
                    if (endDateInput._flatpickr) {
                        endDateInput._flatpickr.destroy();
                    }
                    flatpickr("#end_date", {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d M Y",
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

                // Initialize feather icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }, 100);
        }

        // Initialize on DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeVersionDatePickers);
        } else {
            initializeVersionDatePickers();
        }

        // Also initialize on SPA navigation
        window.addEventListener('spa:navigated', initializeVersionDatePickers);

        function updateFileLabel(input) {
            const fileCount = input.files.length;
            const fileLabel = document.getElementById('file_label');

            if (fileCount > 0) {
                fileLabel.textContent = `${fileCount} file${fileCount > 1 ? 's' : ''} selected`;
            } else {
                fileLabel.textContent = 'Choose Files';
            }
        }
    </script>
@endpush
