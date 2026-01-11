@extends('layouts.dashboard')

@section('title', 'Contract Details')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>{{ $contract->contract_number }}</h2>
            <p class="muted">{{ $contract->contractType->name ?? 'N/A' }} • {{ $contract->contract_with }}</p>
        </div>
    </div>

    {{-- Status Card --}}
    <div class="card" style="margin-top:16px;background:linear-gradient(135deg, #0B6BBD 0%, #0956a5 100%);color:#fff;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
            <div>
                <div style="font-size:14px;opacity:0.9;margin-bottom:8px;">Contract Status</div>
                <div style="font-size:32px;font-weight:700;">{{ $contract->status }}</div>
            </div>
            @if($latestVersion)
                <div style="text-align:right;">
                    <div style="font-size:14px;opacity:0.9;">Valid Until</div>
                    <div style="font-size:24px;font-weight:600;">{{ $latestVersion->end_date->timezone('Asia/Kolkata')->format('d M Y') }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Contract Details --}}
    <div class="card" style="margin-top:16px;">
        <h3 style="margin:0 0 16px 0;font-size:18px;">Contract Information</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;">
            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">CONTRACT NUMBER</div>
                <div style="font-size:16px;font-weight:500;">{{ $contract->contract_number }}</div>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">CONTRACT TYPE</div>
                <div style="font-size:16px;">{{ $contract->contractType->name ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">CONTRACT WITH</div>
                <div style="font-size:16px;font-weight:500;">{{ $contract->contract_with }}</div>
            </div>
            @if(auth()->user() && auth()->user()->isSuperAdmin())
                <div>
                    <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">BRANCH</div>
                    <div style="font-size:16px;">{{ $contract->branch->name ?? 'N/A' }}</div>
                </div>
            @endif
            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">GRACE PERIOD</div>
                <div style="font-size:16px;">{{ $contract->grace_period_days }} days</div>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">ACTIVE STATUS</div>
                <div>
                    @if($contract->is_active)
                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#d1fae5;color:#065f46;">ACTIVE</span>
                    @else
                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#e5e7eb;color:#6b7280;">INACTIVE</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Current Version --}}
    @if($latestVersion)
        <div class="card" style="margin-top:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;font-size:18px;">Current Version (v{{ $latestVersion->version_number }})</h3>
                @can('contracts.versions.create')
                    <a href="{{ route('contracts.versions.create', $contract->id) }}" class="btn" style="background:#10b981;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;">
                        + New Version
                    </a>
                @endcan
            </div>
            
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">START DATE (IST)</div>
                    <div style="font-size:16px;">{{ $latestVersion->start_date->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">END DATE (IST)</div>
                    <div style="font-size:16px;">{{ $latestVersion->end_date->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">TOTAL VERSIONS</div>
                    <div style="font-size:16px;">{{ $contract->versions->count() }}</div>
                </div>
            </div>

            @if($latestVersion->description)
                <div style="margin-top:16px;padding:16px;background:#f9fafb;border-radius:8px;">
                    <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:8px;">DESCRIPTION</div>
                    <div style="font-size:15px;color:#4b5563;">{{ $latestVersion->description }}</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Attached Files --}}
    <div class="card" style="margin-top:16px;">
        <h3 style="margin:0 0 16px 0;font-size:18px;">Attached Files</h3>
        @if($latestVersion && $latestVersion->files->isNotEmpty())
            <div style="display:grid;gap:12px;">
                @foreach($latestVersion->files as $file)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px;background:#f9fafb;border-radius:8px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <svg width="24" height="24" fill="none" stroke="#0B6BBD" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <div style="font-weight:600;font-size:15px;">{{ $file->original_filename }}</div>
                                <div style="font-size:13px;color:#6b7280;">{{ number_format($file->size_bytes / 1024, 2) }} KB</div>
                            </div>
                        </div>
                        <a href="{{ Storage::url($file->path) }}" target="_blank" class="btn" style="background:#0B6BBD;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;">
                            Download
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="muted">No files attached</p>
        @endif
    </div>

    {{-- Reminders --}}
    <div class="card" style="margin-top:16px;">
        <h3 style="margin:0 0 16px 0;font-size:18px;">Reminders</h3>
        @if($contract->reminders->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                @foreach($contract->reminders->sortBy('days_before_end') as $reminder)
                    <div style="padding:12px 20px;background:#dbeafe;border-radius:8px;">
                        <div style="font-size:20px;font-weight:700;color:#1e40af;text-align:center;">{{ $reminder->days_before_end }}</div>
                        <div style="font-size:12px;color:#1e3a8a;text-align:center;">days before</div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="muted">No reminders configured</p>
        @endif
    </div>

    {{-- Notification Recipients --}}
    <div class="card" style="margin-top:16px;">
        <h3 style="margin:0 0 16px 0;font-size:18px;">Notification Recipients</h3>
        @if($contract->notificationRecipients->isNotEmpty())
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:12px;">
                @foreach($contract->notificationRecipients as $recipient)
                    <div style="padding:14px;background:#f9fafb;border-radius:8px;">
                        <div style="font-weight:600;font-size:15px;">{{ $recipient->name }}</div>
                        <div style="font-size:13px;color:#6b7280;margin-top:4px;">{{ $recipient->designation ?? 'N/A' }}</div>
                        <div style="font-size:13px;color:#0B6BBD;margin-top:4px;">{{ $recipient->email }}</div>
                        @if($recipient->mobile)
                            <div style="font-size:13px;color:#6b7280;margin-top:2px;">{{ $recipient->mobile }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="muted">No recipients assigned</p>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
        @can('contracts.edit')
            <a href="{{ route('contracts.edit', $contract->id) }}" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:500;">
                Edit Contract
            </a>
        @endcan
        @can('contracts.export')
            <a href="{{ route('contracts.export', $contract->id) }}" class="btn" style="background:#10b981;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:500;">
                Export to Excel
            </a>
        @endcan
        <form method="POST" action="{{ route('contracts.test-notification', $contract->id) }}" style="display:inline;">
            @csrf
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="email" name="email" placeholder="Email for test" required
                    style="padding:12px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:14px;">
                <button type="submit" class="btn" style="background:#f59e0b;color:#fff;padding:12px 20px;border:none;border-radius:10px;font-weight:500;cursor:pointer;">
                    Send Test Notification
                </button>
            </div>
        </form>
        <a href="{{ route('contracts.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:500;">
            Back to List
        </a>
    </div>

    @if (session('success'))
        <div style="position:fixed;bottom:20px;right:20px;background:#10b981;color:#fff;padding:16px 24px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;">
            {{ session('success') }}
        </div>
        <script>
            setTimeout(() => {
                const alert = document.querySelector('[style*="position:fixed"]');
                if(alert) alert.remove();
            }, 3000);
        </script>
    @endif
@endsection
