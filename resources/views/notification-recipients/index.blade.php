@extends('layouts.dashboard')

@section('title', 'Manage Notification Recipients')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>NOTIFICATION RECIPIENTS</h2>
            <p class="muted">Manage email and SMS recipients for contract notifications.</p>
        </div>
    </div>

    {{-- Status filter cards --}}
    @php
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();
        $currentStatus = request('status') ?? 'all';
        $baseParams = ['search' => request('search')];

        $cards = [
            'all' => [
                'label' => 'All Recipients',
                'count' => $recipients->count(),
            ],
            'active' => [
                'label' => 'Active Recipients',
                'count' => $recipients->where('deleted_at', null)->where('is_active', true)->count(),
            ],
            'inactive' => [
                'label' => 'Inactive Recipients',
                'count' => $recipients->where('deleted_at', null)->where('is_active', false)->count(),
            ],
            'deleted' => [
                'label' => 'Deleted Recipients',
                'count' => $recipients->where('deleted_at', '!=', null)->count(),
            ],
        ];
    @endphp

    <div style="margin-top:12px;">
        <div id="recipientCardsContainer" style="
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:16px;
        ">
            @foreach ($cards as $key => $card)
                @php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_merge($baseParams, ['status' => $key]);
                    $url = route('notification-recipients.index', $params);
                @endphp
                <a href="{{ $url }}"
                   style="display:block;padding:20px;background:{{ $isActiveCard ? '#0B6BBD' : '#ffffff' }};
                   border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.08);
                   text-decoration:none;color:{{ $isActiveCard ? '#ffffff' : '#1f2937' }};
                   transition:all 0.15s ease;"
                   onmouseover="if(!{{ $isActiveCard ? 'true' : 'false' }}) { this.style.boxShadow='0 4px 10px rgba(0,0,0,0.12)';this.style.transform='translateY(-2px)'; }"
                   onmouseout="if(!{{ $isActiveCard ? 'true' : 'false' }}) { this.style.boxShadow='0 2px 6px rgba(0,0,0,0.08)';this.style.transform='none'; }">
                    <div style="font-size:15px;font-weight:500;margin-bottom:8px;">{{ $card['label'] }}</div>
                    <div style="font-size:32px;font-weight:700;">{{ $card['count'] }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Search and Action Bar --}}
    <div class="card" style="margin-top:20px;padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;">
            {{-- Search form --}}
            <form method="GET" action="{{ route('notification-recipients.index') }}" style="flex:1;min-width:250px;max-width:400px;">
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                <div style="position:relative;">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search recipients..."
                        value="{{ request('search') }}"
                        style="width:100%;padding:12px 40px 12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                    >
                    <button type="submit" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);
                        background:none;border:none;cursor:pointer;padding:6px;">
                        <svg width="20" height="20" fill="none" stroke="#6b7280" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                    </button>
                </div>
            </form>

            {{-- Action buttons --}}
            <div style="display:flex;gap:12px;">
                @can('notification-recipients.create')
                    <a href="{{ route('notification-recipients.create') }}" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:500;">
                        + New Recipient
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Recipients Table --}}
    <div class="card" style="margin-top:20px;padding:0;overflow:hidden;">
        @if ($recipients->isEmpty())
            <div style="padding:40px;text-align:center;color:#6b7280;">
                <svg style="margin:0 auto 16px;opacity:0.5;" width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <p style="font-size:16px;margin:0;">No notification recipients found.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                        <tr>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">NAME</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">DESIGNATION</th>
                            @if($currentUser && $currentUser->isSuperAdmin())
                                <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">BRANCH</th>
                            @endif
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">EMAIL</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">MOBILE</th>
                            <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">STATUS</th>
                            <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recipients as $recipient)
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:14px 20px;font-size:15px;font-weight:500;">{{ $recipient->name }}</td>
                                <td style="padding:14px 20px;font-size:14px;color:#6b7280;">{{ $recipient->designation ?? 'N/A' }}</td>
                                @if($currentUser && $currentUser->isSuperAdmin())
                                    <td style="padding:14px 20px;font-size:14px;color:#6b7280;">{{ $recipient->branch->name ?? 'N/A' }}</td>
                                @endif
                                <td style="padding:14px 20px;font-size:14px;">
                                    <a href="mailto:{{ $recipient->email }}" style="color:#0B6BBD;text-decoration:none;">{{ $recipient->email }}</a>
                                </td>
                                <td style="padding:14px 20px;font-size:14px;color:#6b7280;">{{ $recipient->mobile ?? 'N/A' }}</td>
                                <td style="padding:14px 20px;text-align:center;">
                                    @if($recipient->deleted_at)
                                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#fee2e2;color:#991b1b;">DELETED</span>
                                    @elseif($recipient->is_active)
                                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#d1fae5;color:#065f46;">ACTIVE</span>
                                    @else
                                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#fef3c7;color:#92400e;">INACTIVE</span>
                                    @endif
                                </td>
                                <td style="padding:14px 20px;text-align:center;">
                                    <div style="display:flex;gap:8px;justify-content:center;">
                                        @if($recipient->deleted_at)
                                            @can('notification-recipients.restore')
                                                <form method="POST" action="{{ route('notification-recipients.restore', $recipient->id) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" style="padding:8px 14px;background:#10b981;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;">
                                                        Restore
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('notification-recipients.view')
                                                <a href="{{ route('notification-recipients.show', $recipient->id) }}" style="padding:8px 14px;background:#6b7280;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;">
                                                    View
                                                </a>
                                            @endcan
                                            @can('notification-recipients.edit')
                                                <a href="{{ route('notification-recipients.edit', $recipient->id) }}" style="padding:8px 14px;background:#0B6BBD;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;">
                                                    Edit
                                                </a>
                                            @endcan
                                            @can('notification-recipients.delete')
                                                <form method="POST" action="{{ route('notification-recipients.destroy', $recipient->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this recipient?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="padding:8px 14px;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($recipients->isNotEmpty())
        <div style="margin-top:20px;">
            {{ $recipients->links() }}
        </div>
    @endif

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
