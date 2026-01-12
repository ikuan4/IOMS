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
                <input
                    type="text"
                    name="search"
                    placeholder="Search by name, email, designation..."
                    value="{{ request('search') }}"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;"
                >
            </form>

            {{-- Action buttons --}}
            <div style="display:flex;gap:12px;">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.create'))
                    <a href="{{ route('notification-recipients.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                        <span data-feather="plus"></span>
                        New Recipient
                    </a>
                @endif
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
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.restore'))
                                                <form method="POST" action="{{ route('notification-recipients.restore', $recipient->id) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore recipient"><span data-feather="rotate-ccw"></span></button>
                                                </form>
                                            @endif
                                        @else
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.view'))
                                                <a href="{{ route('notification-recipients.show', $recipient->id) }}" style="background:#f3f4f6;color:#374151;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="View recipient"><span data-feather="eye"></span></a>
                                            @endif
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.edit'))
                                                <a href="{{ route('notification-recipients.edit', $recipient->id) }}" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit recipient"><span data-feather="edit"></span></a>
                                            @endif
                                            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.delete'))
                                                <form method="POST" action="{{ route('notification-recipients.destroy', $recipient->id) }}" style="display:inline;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Recipient', subtitle: 'Are you sure you want to delete {{ addslashes($recipient->name) }}?', message: 'This action will soft delete the recipient. You can restore it later from the deleted recipients section.', confirmText: 'Delete Recipient', form: this});">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Delete recipient"><span data-feather="trash-2"></span></button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Recipients: {{ $recipients->total() }}
            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                @php
                    $current = $recipients->currentPage();
                    $last = $recipients->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $start + 4);
                    if ($end - $start < 4) { $start = max(1, $end - 4); }
                    $baseParams = request()->except(['page']);
                @endphp

                <nav aria-label="Pagination" style="display:inline-flex;align-items:center;gap:6px;background:var(--card);padding:6px 10px;border-radius:8px;">
                    {{-- First --}}
                    @php $firstColor = $current > 1 ? '#2563eb' : '#000'; @endphp
                    @if($current > 1)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => 1])) }}" aria-label="First page" style="padding:6px 10px;border-radius:6px;background:transparent;color:{{ $firstColor }};text-decoration:none;font-weight:800;font-size:14px;">&laquo;</a>
                    @else
                        <span aria-hidden="true" style="padding:6px 10px;color:{{ $firstColor }};font-weight:800;font-size:14px;">&laquo;</span>
                    @endif

                    {{-- Prev --}}
                    @php $prevColor = $current > 1 ? '#2563eb' : '#000'; @endphp
                    @if($current > 1)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current - 1])) }}" aria-label="Previous page" style="padding:8px 12px;border-radius:6px;background:transparent;color:{{ $prevColor }};text-decoration:none;font-weight:800;font-size:15px;">&lt;</a>
                    @else
                        <span aria-hidden="true" style="padding:8px 12px;color:{{ $prevColor }};font-weight:800;font-size:15px;">&lt;</span>
                    @endif

                    {{-- Page numbers --}}
                    @for($p = $start; $p <= $end; $p++)
                        @if($p == $current)
                            <span aria-current="page" style="padding:9px 14px;border-radius:8px;background:#f3f4f6;color:#374151;font-weight:800;border:1px solid #e5e7eb;font-size:15px;">{{ $p }}</span>
                        @else
                            <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $p])) }}" style="padding:8px 12px;border-radius:6px;background:transparent;color:var(--text);text-decoration:none;font-weight:800;font-size:14px;" onmouseover="this.style.border='1px solid #22c55e';this.style.background='rgba(34,197,94,0.06)';" onmouseout="this.style.border='none';this.style.background='transparent';">{{ $p }}</a>
                        @endif
                    @endfor

                    {{-- Next --}}
                    @php $nextColor = $current < $last ? '#2563eb' : '#000'; @endphp
                    @if($current < $last)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current + 1])) }}" aria-label="Next page" style="padding:8px 12px;border-radius:6px;background:transparent;color:{{ $nextColor }};text-decoration:none;font-weight:800;font-size:15px;">&gt;</a>
                    @else
                        <span aria-hidden="true" style="padding:8px 12px;color:{{ $nextColor }};font-weight:800;font-size:15px;">&gt;</span>
                    @endif

                    {{-- Last --}}
                    @php $lastColor = $current < $last ? '#2563eb' : '#000'; @endphp
                    @if($current < $last)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $last])) }}" aria-label="Last page" style="padding:6px 10px;border-radius:6px;background:transparent;color:{{ $lastColor }};text-decoration:none;font-weight:800;font-size:14px;">&raquo;</a>
                    @else
                        <span aria-hidden="true" style="padding:6px 10px;color:{{ $lastColor }};font-weight:800;font-size:14px;">&raquo;</span>
                    @endif
                </nav>
            </div>

            <div style="flex:1; min-width:180px; display:flex; justify-content:flex-end; align-items:center; gap:8px;">
                @php
                    $currentPerPage = (int) request()->query('per_page', $recipients->perPage() ?? 10);
                @endphp
                <form method="GET" action="{{ url()->current() }}" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
                    @foreach(request()->except(['per_page','page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                    @endforeach
                    <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="per_page" onchange="document.getElementById('perPageForm').submit()" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                        @foreach([5,10,15,20,30] as $opt)
                            <option value="{{ $opt }}" {{ $currentPerPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
            </div>
        @endif
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

    @include('partials.confirmation-modal')
@endsection
