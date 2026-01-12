@extends('layouts.dashboard')

@section('title', 'Contract Types')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>CONTRACT TYPE MANAGEMENT MODULE</h2>
            <p class="muted">Manage contract types, activation state, and usage metadata.</p>
        </div>
    </div>

    @php
        $currentStatus = $status ?? 'all';
        $baseParams = ['search' => $search];

        $cards = [
            'all' => [ 'label' => 'All Types', 'count' => $statusCounts['all'] ?? 0, ],
            'active' => [ 'label' => 'Active Types', 'count' => $statusCounts['active'] ?? 0, ],
            'inactive' => [ 'label' => 'Inactive Types', 'count' => $statusCounts['inactive'] ?? 0, ],
            'deleted' => [ 'label' => 'Deleted Types', 'count' => $statusCounts['deleted'] ?? 0, ],
        ];
    @endphp

    <div style="margin-top:12px;">
        <div id="contractTypeCardsContainer" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
            @foreach ($cards as $key => $card)
                @php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(array_merge($baseParams, ['status' => $key]), fn($v) => $v !== null && $v !== '');
                @endphp

                <a href="{{ route('contract-types.index', $params) }}" class="contract-type-filter-card" style="text-decoration:none;color:inherit;">
                    <div class="card" style="padding:16px 18px;border-radius:12px;border:2px solid {{ $isActiveCard ? '#22c55e' : '#e5e7eb' }};box-shadow:{{ $isActiveCard ? '0 0 0 1px rgba(34,197,94,0.15)' : 'none' }};display:flex;flex-direction:column;justify-content:space-between;height:100%;">
                        <div style="font-size:14px; opacity:0.8;">{{ $card['label'] }}</div>
                        <div style="margin-top:8px; font-size:24px; font-weight:700;">{{ $card['count'] }}</div>
                        @if($isActiveCard)
                            <div style="margin-top:8px; font-size:12px; color:#16a34a;">Currently applied to table ↓</div>
                        @else
                            <div style="margin-top:8px; font-size:12px; opacity:0.6;">Click to filter table</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div id="toggleContractTypeCardsContainer" style="display:none; justify-content:flex-end; margin-top:12px;">
            <button id="toggleContractTypeCardsBtn" onclick="toggleContractTypeCards()" style="display:flex;align-items:center;justify-content:center;border:none;background:transparent;color:#22c55e;cursor:pointer;transition:all 0.3s;padding:8px;" onmouseover="this.style.color='#16a34a'; this.style.transform='scale(1.2)'" onmouseout="this.style.color='#22c55e'; this.style.transform='scale(1)'">
                <svg id="toggleContractTypeIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline></svg>
            </button>
        </div>
    </div>

    <script>
        let contractTypeCardsVisible = false;
        function checkContractTypeCardWrapping() {
            const container = document.getElementById('contractTypeCardsContainer');
            const toggleContainer = document.getElementById('toggleContractTypeCardsContainer');
            if (!container || !toggleContainer) return;
            const cards = Array.from(container.querySelectorAll('.contract-type-filter-card'));
            if (cards.length === 0) return;
            cards.forEach(card => card.style.display = 'block');
            const firstCardTop = cards[0].getBoundingClientRect().top;
            const wrappedCards = [];
            cards.forEach((card) => { if (Math.abs(card.getBoundingClientRect().top - firstCardTop) > 10) wrappedCards.push(card); });
            if (wrappedCards.length > 0) {
                toggleContainer.style.display = 'flex';
                if (!contractTypeCardsVisible) wrappedCards.forEach(card => card.style.display = 'none');
            } else {
                toggleContainer.style.display = 'none';
                contractTypeCardsVisible = false;
            }
        }
        function toggleContractTypeCards() {
            const container = document.getElementById('contractTypeCardsContainer');
            const toggleIcon = document.getElementById('toggleContractTypeIcon');
            if (!container) return;
            const cards = Array.from(container.querySelectorAll('.contract-type-filter-card'));
            const firstCardTop = cards[0].getBoundingClientRect().top;
            contractTypeCardsVisible = !contractTypeCardsVisible;
            cards.forEach(card => {
                if (Math.abs(card.getBoundingClientRect().top - firstCardTop) > 10) {
                    card.style.display = contractTypeCardsVisible ? 'block' : 'none';
                }
            });
            if (contractTypeCardsVisible) toggleIcon.innerHTML = '<polyline points="7 11 12 6 17 11"></polyline><polyline points="7 18 12 13 17 18"></polyline>'; else toggleIcon.innerHTML = '<polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline>';
        }
        window.addEventListener('load', checkContractTypeCardWrapping);
        window.addEventListener('resize', () => { contractTypeCardsVisible = false; checkContractTypeCardWrapping(); });
        setTimeout(checkContractTypeCardWrapping, 100);
    </script>

    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px;">
        <form method="GET" action="{{ route('contract-types.index') }}" id="searchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="hidden" name="per_page" id="searchPerPage" value="{{ request()->query('per_page', 10) }}">
            <input type="text" name="search" id="contractTypeSearchInput" value="{{ $search }}" placeholder="Search by name, code, or description..." oninput="debouncedSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->can('create', \App\Models\ContractType::class))
        <a href="{{ route('contract-types.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
            <span data-feather="plus"></span>
            Add Contract Type
        </a>
        @endif
    </div>

    <div class="card" style="margin-top:12px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
            <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
                <th style="padding:8px;">Name</th>
                <th style="padding:8px;">Code</th>
                @if(auth()->user() && auth()->user()->isSuperAdmin())
                    <th style="padding:8px;">Branch</th>
                @endif
                <th style="padding:8px;">Description</th>
                <th style="padding:8px;">Status</th>
                <th style="padding:8px; text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($contractTypes as $type)
                @php $isDeleted = !is_null($type->deleted_at); @endphp
                <tr style="border-bottom:1px solid #f3f4f6; height:62px; {{ $isDeleted ? 'opacity:1;' : '' }}">
                    <td style="padding:8px;">{{ $type->name }}</td>
                    <td style="padding:8px;">
                        <span style="background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:6px;font-size:13px;font-weight:600;">{{ $type->code }}</span>
                    </td>
                    @if(auth()->user() && auth()->user()->isSuperAdmin())
                        <td style="padding:8px;">{{ $type->branch->name ?? 'N/A' }}</td>
                    @endif
                    <td style="padding:8px;">{{ Str::limit($type->description ?? 'No description', 50) }}</td>
                    <td style="padding:8px;">
                        @if($isDeleted)
                            <span style="color:#f10101;font-weight:600;">Deleted</span>
                        @else
                            @if($type->is_active)
                                <span style="color:#16a34a;font-weight:600;">Active</span>
                            @else
                                <span style="color:#dc2626;font-weight:600;">Inactive</span>
                            @endif
                        @endif
                    </td>
                    <td style="padding:8px; text-align:right; white-space:nowrap;">
                        @if(!$isDeleted)
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('update', $type))
                            <a href="{{ route('contract-types.edit', $type) }}" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit contract type"><span data-feather="edit"></span></a>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('delete', $type))
                            <form action="{{ route('contract-types.destroy', $type) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Contract Type', subtitle: 'This will soft delete the contract type', message: 'Are you sure you want to delete {{ $type->name }}?', confirmText: 'Delete Contract Type', form: this});">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Soft delete contract type"><span data-feather="trash-2"></span></button>
                            </form>
                            @endif
                        @else
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->can('restore', $type))
                            <form action="{{ route('contract-types.restore', $type->id) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Contract Type', subtitle: 'This will restore the contract type', message: 'Are you sure you want to restore {{ $type->name }}?', confirmText: 'Restore Contract Type', form: this});">
                                @csrf
                                @if(!empty($search))<input type="hidden" name="search" value="{{ $search }}">@endif
                                <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore contract type"><span data-feather="rotate-ccw"></span></button>
                            </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:12px;">No contract types found.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Contract Types: {{ $contractTypes->total() }}
            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                @php
                    $current = $contractTypes->currentPage();
                    $last = $contractTypes->lastPage();
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
                    $currentPerPage = (int) request()->query('per_page', $contractTypes->perPage() ?? 10);
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

    @include('partials.confirmation-modal')

    <script>
        // Debounced server-side search so it searches all contract types (not just current page)
        let __contractTypeSearchTimer = null;
        function debouncedSearch() {
            clearTimeout(__contractTypeSearchTimer);
            __contractTypeSearchTimer = setTimeout(() => {
                // ensure per_page selection is preserved when searching
                const perPageSelect = document.getElementById('per_page');
                const searchPerPage = document.getElementById('searchPerPage');
                if (perPageSelect && searchPerPage) searchPerPage.value = perPageSelect.value;
                const form = document.getElementById('searchForm');
                if (form) form.submit();
            }, 350);
        }
    </script>
@endsection
