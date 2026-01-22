@extends('layouts.dashboard')

@section('title', 'Manage Contracts')

@section('content')
    <style>
        /* Custom select arrow so we can control its position (move ~10px left) */
        #contracts_branch_id,
        #contracts_contract_type_id,
        #per_page {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%236b7280' d='M5.25 7.5 10 12.25 14.75 7.5l1.5 1.5L10 15.25 3.75 9z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 22px center;
            background-size: 16px 16px;
            padding-right: 48px !important;
            background-color: var(--card, #fff);
            color: var(--text, #111827);
        }
    </style>

    <div class="header-card">
        <div class="header-left">
            <h2>CONTRACT MANAGEMENT</h2>
            <p class="muted">Manage contracts, versions, and notifications.</p>
        </div>
    </div>

    {{-- Status cards --}}
    @php
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        $currentStatus = request('status') ?? 'all';

        $cards = [
            'all' => ['label' => 'All Contracts', 'count' => $statusCounts['all'] ?? 0, 'color' => '#0B6BBD'],
            'ongoing' => ['label' => 'Ongoing', 'count' => $statusCounts['ongoing'] ?? 0, 'color' => '#10b981'],
            'pending' => ['label' => 'Pending', 'count' => $statusCounts['pending'] ?? 0, 'color' => '#f59e0b'],
            'expiring' => ['label' => 'Expiring Soon', 'count' => $statusCounts['expiring'] ?? 0, 'color' => '#ef4444'],
            'expired' => ['label' => 'Expired', 'count' => $statusCounts['expired'] ?? 0, 'color' => '#dc2626'],
            'inactive' => ['label' => 'Inactive', 'count' => $statusCounts['inactive'] ?? 0, 'color' => '#6b7280'],
        ];
    @endphp

    <div style="margin-top:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px;">
            @foreach ($cards as $key => $card)
                @php
                    $isActive = $currentStatus === $key;
                    $params = array_merge(request()->all(), ['status' => $key]);
                    unset($params['page']);
                    $url = route('contracts.index', $params);
                @endphp
                <a href="{{ $url }}"
                   style="display:block;padding:20px;background:{{ $isActive ? $card['color'] : '#ffffff' }};
                   border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.08);
                   text-decoration:none;color:{{ $isActive ? '#ffffff' : '#1f2937' }};transition:all 0.15s ease;"
                   onmouseover="if(!{{ $isActive ? 'true' : 'false' }}) { this.style.boxShadow='0 4px 10px rgba(0,0,0,0.12)';this.style.transform='translateY(-2px)'; }"
                   onmouseout="if(!{{ $isActive ? 'true' : 'false' }}) { this.style.boxShadow='0 2px 6px rgba(0,0,0,0.08)';this.style.transform='none'; }">
                    <div style="font-size:15px;font-weight:500;margin-bottom:8px;">{{ $card['label'] }}</div>
                    <div style="font-size:32px;font-weight:700;">{{ $card['count'] }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="card" style="margin-top:20px;padding:20px;">
        <form method="GET" action="{{ route('contracts.index') }}" style="display:flex;gap:16px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ request('status', 'all') }}">

            {{-- Branch Filter for Super Admin --}}
            @if($isSuperAdmin)
                <div style="flex:1;min-width:180px;">
                    <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">BRANCH</label>
                    <select name="branch_id" id="contracts_branch_id" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;background-color:var(--card,#fff);color:var(--text,#111827);">
                        <option value="">All Branches</option>
                        @foreach($branches ?? [] as $branch)
                            @if($branch)
                            <option value="{{ optional($branch)->id }}" {{ optional($branch)->id && request('branch_id') == optional($branch)->id ? 'selected' : '' }}>
                                {{ optional($branch)->name }}
                            </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Type Filter --}}
            <div style="flex:1;min-width:180px;">
                <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">CONTRACT TYPE</label>
                <select name="contract_type_id" id="contracts_contract_type_id" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;background-color:var(--card,#fff);color:var(--text,#111827);">
                    <option value="">All Types</option>
                    @foreach($contractTypes ?? [] as $type)
                        @if($type)
                        <option value="{{ optional($type)->id }}" {{ optional($type)->id && request('contract_type_id') == optional($type)->id ? 'selected' : '' }}>
                            {{ optional($type)->name }}
                        </option>
                        @endif
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div style="flex:1;min-width:250px;">
                <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">SEARCH</label>
                <input
                    type="text"
                    name="search"
                    placeholder="Search by contract number, company name..."
                    value="{{ request('search') }}"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;"
                >
            </div>

            {{-- Buttons --}}
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 20px;border:none;border-radius:10px;font-weight:500;cursor:pointer;">
                    Apply
                </button>
                <a href="{{ route('contracts.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:500;">
                    Reset
                </a>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.create'))
                    <a href="{{ route('contracts.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                        <span data-feather="plus"></span>
                        New Contract
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Contracts Table --}}
    <div class="card" style="margin-top:20px;padding:0;overflow:hidden;">
        @if ($contracts->isEmpty())
            <div style="padding:40px;text-align:center;color:#6b7280;">
                <svg style="margin:0 auto 16px;opacity:0.5;" width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p style="font-size:16px;margin:0;">No contracts found.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                        <tr>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">CONTRACT NO.</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">TYPE</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">CONTRACT WITH</th>
                            @if($isSuperAdmin)
                                <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">BRANCH</th>
                            @endif
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">START DATE</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">END DATE</th>
                            <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">STATUS</th>
                            <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $contract)
                            @php
                                $latestVersion = $contract->latestVersion;
                                $statusColors = [
                                    'Ongoing' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                                    'Pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                    'Expiring Soon' => ['bg' => '#fecaca', 'text' => '#991b1b'],
                                    'Expired' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                    'Inactive' => ['bg' => '#e5e7eb', 'text' => '#6b7280'],
                                ];
                                $statusColor = $statusColors[$contract->status] ?? ['bg' => '#e5e7eb', 'text' => '#6b7280'];
                            @endphp
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:14px 20px;">
                                    <span style="font-size:15px;font-weight:600;color:#0B6BBD;">{{ $contract->contract_number }}</span>
                                </td>
                                <td style="padding:14px 20px;font-size:14px;">{{ optional($contract->contractType)->name ?? 'N/A' }}</td>
                                <td style="padding:14px 20px;font-size:14px;font-weight:500;">{{ $contract->contract_with }}</td>
                                @if($isSuperAdmin)
                                    <td style="padding:14px 20px;font-size:14px;color:#6b7280;">{{ optional($contract->branch)->name ?? 'N/A' }}</td>
                                @endif
                                <td style="padding:14px 20px;font-size:14px;">
                                    {{ $latestVersion ? $latestVersion->start_date->timezone('Asia/Kolkata')->format('d M Y') : 'N/A' }}
                                </td>
                                <td style="padding:14px 20px;font-size:14px;">
                                    {{ $latestVersion ? $latestVersion->end_date->timezone('Asia/Kolkata')->format('d M Y') : 'N/A' }}
                                </td>
                                <td style="padding:14px 20px;text-align:center;">
                                    <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;
                                        background:{{ $statusColor['bg'] }};color:{{ $statusColor['text'] }};">
                                        {{ strtoupper($contract->status) }}
                                    </span>
                                </td>
                                <td style="padding:14px 20px;text-align:center;">
                                    <div style="display:flex;gap:8px;justify-content:center;">
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.view'))
                                            <a href="{{ route('contracts.show', $contract->id) }}" style="background:#f3f4f6;color:#374151;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="View contract"><span data-feather="eye"></span></a>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.edit'))
                                            <a href="{{ route('contracts.edit', $contract->id) }}" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;text-decoration:none;" title="Edit contract"><span data-feather="edit"></span></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Contracts: {{ $contracts->total() }}
            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                @php
                    $current = $contracts->currentPage();
                    $last = $contracts->lastPage();
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
                    $currentPerPage = (int) request()->query('per_page', $contracts->perPage() ?? 10);
                @endphp
                <form method="GET" action="{{ url()->current() }}" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
                    @foreach(request()->except(['per_page','page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                    @endforeach
                    <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="per_page" onchange="document.getElementById('perPageForm').submit()" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background-color:var(--card);color:var(--text,inherit);">
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
@endsection
