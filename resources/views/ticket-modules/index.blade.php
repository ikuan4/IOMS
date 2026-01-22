@extends('layouts.dashboard')

@section('title', 'Ticket Modules')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>TICKET MODULE MANAGEMENT</h2>
            <p class="muted">Manage ticket modules and activation state.</p>
        </div>
    </div>

    @php
        $currentStatus = $status ?? 'all';
        $baseParams = ['search' => $search];

        $cards = [
            'all' => [ 'label' => 'All Modules', 'count' => $statusCounts['all'] ?? 0, ],
            'active' => [ 'label' => 'Active Modules', 'count' => $statusCounts['active'] ?? 0, ],
            'inactive' => [ 'label' => 'Inactive Modules', 'count' => $statusCounts['inactive'] ?? 0, ],
            'deleted' => [ 'label' => 'Deleted Modules', 'count' => $statusCounts['deleted'] ?? 0, ],
        ];
    @endphp

    <div style="margin-top:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
            @foreach ($cards as $key => $card)
                @php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(array_merge($baseParams, ['status' => $key]), fn($v) => $v !== null && $v !== '');
                @endphp

                <a href="{{ route('ticket-modules.index', $params) }}" style="text-decoration:none;color:inherit;">
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
        <form method="GET" action="{{ route('ticket-modules.index') }}" id="ticketModuleSearchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="hidden" name="per_page" id="searchPerPage" value="{{ request()->query('per_page', 10) }}">
            <input type="text" name="search" id="ticketModuleSearchInput" value="{{ $search }}" placeholder="Search by name or description..." oninput="debouncedTicketModuleSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->can('create', \App\Models\TicketModule::class))
            <a href="{{ route('ticket-modules.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                <span data-feather="plus"></span>
                Add Ticket Module
            </a>
        @endif
    </div>

    <div class="card" style="margin-top:12px;overflow:hidden;">
        <div id="ticketModulesTableWrapper">
            @include('ticket-modules._ticket_modules_table')
        </div>
    </div>

    <script>
        let __ticketModuleSearchTimer = null;
        function debouncedTicketModuleSearch() {
            clearTimeout(__ticketModuleSearchTimer);
            __ticketModuleSearchTimer = setTimeout(() => { ajaxFetchTicketModules(1); }, 300);
        }

        function ajaxFetchTicketModules(page = 1) {
            const form = document.getElementById('ticketModuleSearchForm');
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
                    document.getElementById('ticketModulesTableWrapper').innerHTML = html;
                    bindTicketModulePaginationLinks();
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindTicketModulePaginationLinks(){
            const wrapper = document.getElementById('ticketModulesTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                try {
                    const url = new URL(href, location.origin);
                    if (url.searchParams.has('page')) {
                        a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchTicketModules(url.searchParams.get('page')); });
                    }
                } catch(e) {}
            });
        }

        window.addEventListener('load', function(){
            bindTicketModulePaginationLinks();
        });
    </script>
@endsection
