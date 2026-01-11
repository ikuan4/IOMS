@extends('layouts.dashboard')

@section('title', 'Manage Contracts')

@section('content')
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
                    <select name="branch_id" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        <option value="">All Branches</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Type Filter --}}
            <div style="flex:1;min-width:180px;">
                <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">CONTRACT TYPE</label>
                <select name="contract_type_id" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                    <option value="">All Types</option>
                    @foreach($contractTypes ?? [] as $type)
                        <option value="{{ $type->id }}" {{ request('contract_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div style="flex:1;min-width:250px;">
                <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">SEARCH</label>
                <input
                    type="text"
                    name="search"
                    placeholder="Search contracts..."
                    value="{{ request('search') }}"
                    style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
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
                @can('contracts.create')
                    <a href="{{ route('contracts.create') }}" class="btn" style="background:#10b981;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:500;">
                        + New Contract
                    </a>
                @endcan
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
                                <td style="padding:14px 20px;font-size:14px;">{{ $contract->contractType->name ?? 'N/A' }}</td>
                                <td style="padding:14px 20px;font-size:14px;font-weight:500;">{{ $contract->contract_with }}</td>
                                @if($isSuperAdmin)
                                    <td style="padding:14px 20px;font-size:14px;color:#6b7280;">{{ $contract->branch->name ?? 'N/A' }}</td>
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
                                        @can('contracts.view')
                                            <a href="{{ route('contracts.show', $contract->id) }}" style="padding:8px 14px;background:#6b7280;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;">
                                                View
                                            </a>
                                        @endcan
                                        @can('contracts.edit')
                                            <a href="{{ route('contracts.edit', $contract->id) }}" style="padding:8px 14px;background:#0B6BBD;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;">
                                                Edit
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($contracts->isNotEmpty())
        <div style="margin-top:20px;">
            {{ $contracts->appends(request()->all())->links() }}
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
