@extends('layouts.dashboard')

@section('title', 'Recipient Details')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>{{ $notificationRecipient->name }}</h2>
            <p class="muted">Notification recipient details</p>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:24px;">
            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">NAME</div>
                <div style="font-size:16px;font-weight:500;">{{ $notificationRecipient->name }}</div>
            </div>

            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">DESIGNATION</div>
                <div style="font-size:16px;">{{ $notificationRecipient->designation ?? 'N/A' }}</div>
            </div>

            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">EMAIL</div>
                <div style="font-size:16px;"><a href="mailto:{{ $notificationRecipient->email }}" style="color:#0B6BBD;">{{ $notificationRecipient->email }}</a></div>
            </div>

            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">MOBILE</div>
                <div style="font-size:16px;">{{ $notificationRecipient->mobile ?? 'N/A' }}</div>
            </div>

            @if(auth()->user() && auth()->user()->isSuperAdmin())
                <div>
                    <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">BRANCH</div>
                    <div style="font-size:16px;">{{ $notificationRecipient->branch->name ?? 'N/A' }}</div>
                </div>
            @endif

            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">STATUS</div>
                <div>
                    @if($notificationRecipient->is_active)
                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#d1fae5;color:#065f46;">ACTIVE</span>
                    @else
                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#fef3c7;color:#92400e;">INACTIVE</span>
                    @endif
                </div>
            </div>

            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">CREATED BY</div>
                <div style="font-size:16px;">{{ $notificationRecipient->creator->name ?? 'N/A' }} on {{ $notificationRecipient->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
            </div>

            <div>
                <div style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">LAST UPDATED BY</div>
                <div style="font-size:16px;">{{ $notificationRecipient->updater->name ?? 'N/A' }} on {{ $notificationRecipient->updated_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;display:flex;gap:12px;">
        @can('notification-recipients.edit')
            <a href="{{ route('notification-recipients.edit', $notificationRecipient->id) }}" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:500;">
                Edit Recipient
            </a>
        @endcan
        <a href="{{ route('notification-recipients.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:500;">
            Back to List
        </a>
    </div>
@endsection
