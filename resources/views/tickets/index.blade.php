@extends('layouts.dashboard')

@section('title', 'Tickets')

@section('content')
    <div class="header-card">
        <div class="header-left">
            @php $viewMode = $viewMode ?? 'all'; @endphp
            @if($viewMode === 'my')
                <h2>PENDING TICKETS</h2>
                <p class="muted">Tickets assigned to you. Filter by pending, forwarded, resolved, or closed.</p>
            @else
                <h2>TICKET MANAGEMENT MODULE</h2>
                <p class="muted">Manage tickets, assignments, and status.</p>
            @endif
        </div>
    </div>

    @php
        $currentStatus = $status ?? 'all';
        $baseParams = ['search' => $search];

        $indexUrl = $viewMode === 'my' ? route('tickets.pending') : route('tickets.index');

        $cards = $viewMode === 'my'
            ? [
                'pending' => [ 'label' => 'Pending Tickets', 'count' => $statusCounts['pending'] ?? 0, ],
                'forwarded' => [ 'label' => 'Forwarded Tickets', 'count' => $statusCounts['forwarded'] ?? 0, ],
                'resolved' => [ 'label' => 'Resolved Tickets', 'count' => $statusCounts['resolved'] ?? 0, ],
                'closed' => [ 'label' => 'Closed Tickets', 'count' => $statusCounts['closed'] ?? 0, ],
            ]
            : [
                'all' => [ 'label' => 'All Tickets', 'count' => $statusCounts['all'] ?? 0, ],
                'pending' => [ 'label' => 'Pending Tickets', 'count' => $statusCounts['pending'] ?? 0, ],
                'open' => [ 'label' => 'Open', 'count' => $statusCounts['open'] ?? 0, ],
                'in_progress' => [ 'label' => 'In Progress', 'count' => $statusCounts['in_progress'] ?? 0, ],
                'resolved' => [ 'label' => 'Resolved', 'count' => $statusCounts['resolved'] ?? 0, ],
                'closed' => [ 'label' => 'Closed', 'count' => $statusCounts['closed'] ?? 0, ],
                'deleted' => [ 'label' => 'Deleted', 'count' => $statusCounts['deleted'] ?? 0, ],
            ];
    @endphp

    <div style="margin-top:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
            @foreach ($cards as $key => $card)
                @php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(array_merge($baseParams, ['status' => $key]), fn($v) => $v !== null && $v !== '');
                @endphp

                @php
                    $qs = http_build_query($params);
                    $href = $indexUrl . ($qs !== '' ? ('?' . $qs) : '');
                @endphp
                <a href="{{ $href }}" style="text-decoration:none;color:inherit;">
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
    </div>

    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px; flex-wrap:wrap;">
        <form method="GET" action="{{ $indexUrl }}" id="ticketSearchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="hidden" name="per_page" id="searchPerPage" value="{{ request()->query('per_page', 10) }}">
            <input type="text" name="search" id="ticketSearchInput" value="{{ $search }}" placeholder="Search by subject, type, or assignee..." oninput="debouncedTicketSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        @if($viewMode !== 'my' && (auth()->user()->isSuperAdmin() || auth()->user()->can('create', \App\Models\Ticket::class)))
            <a href="{{ route('tickets.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                <span data-feather="plus"></span>
                New Ticket
            </a>
        @endif
    </div>

    <div class="card" style="margin-top:12px;overflow:hidden;">
        <div id="ticketsTableWrapper">
            @include('tickets._tickets_table')
        </div>
    </div>

    <script>
        let __ticketSearchTimer = null;
        function debouncedTicketSearch() {
            clearTimeout(__ticketSearchTimer);
            __ticketSearchTimer = setTimeout(() => { ajaxFetchTickets(1); }, 300);
        }

        function ajaxFetchTickets(page = 1) {
            const form = document.getElementById('ticketSearchForm');
            const params = new URLSearchParams(new FormData(form));
            const perPageSelect = document.getElementById('per_page');
            if (perPageSelect) params.set('per_page', perPageSelect.value);
            params.set('page', page);
            const url = `${location.pathname}?${params.toString()}`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network error');
                    return r.text();
                })
                .then(html => {
                    document.getElementById('ticketsTableWrapper').innerHTML = html;
                    bindTicketPaginationLinks();
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindTicketPaginationLinks(){
            const wrapper = document.getElementById('ticketsTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                try {
                    const url = new URL(href, location.origin);
                    if (url.searchParams.has('page')) {
                        a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchTickets(url.searchParams.get('page')); });
                    }
                } catch(e) {}
            });
        }

        window.addEventListener('load', function(){
            bindTicketPaginationLinks();
        });
    </script>
@endsection
