@extends('layouts.dashboard')

@section('title', 'Users')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>USER MANAGEMENT MODULE</h2>
            <p class="muted">Manage system users, status, and email bounce state.</p>
        </div>
    </div>

    @php
        $currentStatus = $status ?? 'all';
        $baseParams = ['search' => $search];

        $cards = [
            'all' => [ 'label' => 'All Users', 'count' => $statusCounts['all'] ?? 0, ],
            'active' => [ 'label' => 'Active Users', 'count' => $statusCounts['active'] ?? 0, ],
            'deactivated' => [ 'label' => 'Deactivated Users', 'count' => $statusCounts['deactivated'] ?? 0, ],
            'deleted' => [ 'label' => 'Deleted Users', 'count' => $statusCounts['deleted'] ?? 0, ],
        ];
    @endphp

    <div style="margin-top:12px;">
        <div id="userCardsContainer" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
            @foreach ($cards as $key => $card)
                @php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(array_merge($baseParams, ['status' => $key]), fn($v) => $v !== null && $v !== '');
                @endphp

                <a href="{{ route('users.index', $params) }}" class="user-filter-card" style="text-decoration:none;color:inherit;">
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

        <div id="toggleUserCardsContainer" style="display:none; justify-content:flex-end; margin-top:12px;">
            <button id="toggleUserCardsBtn" onclick="toggleUserCards()" style="display:flex;align-items:center;justify-content:center;border:none;background:transparent;color:#22c55e;cursor:pointer;transition:all 0.3s;padding:8px;" onmouseover="this.style.color='#16a34a'; this.style.transform='scale(1.2)'" onmouseout="this.style.color='#22c55e'; this.style.transform='scale(1)'">
                <svg id="toggleUserIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline></svg>
            </button>
        </div>
    </div>

    <script>
        let userCardsVisible = false;
        function checkUserCardWrapping() {
            const container = document.getElementById('userCardsContainer');
            const toggleContainer = document.getElementById('toggleUserCardsContainer');
            if (!container || !toggleContainer) return;
            const cards = Array.from(container.querySelectorAll('.user-filter-card'));
            if (cards.length === 0) return;
            cards.forEach(card => card.style.display = 'block');
            const firstCardTop = cards[0].getBoundingClientRect().top;
            const wrappedCards = [];
            cards.forEach((card) => { if (Math.abs(card.getBoundingClientRect().top - firstCardTop) > 10) wrappedCards.push(card); });
            if (wrappedCards.length > 0) {
                toggleContainer.style.display = 'flex';
                if (!userCardsVisible) wrappedCards.forEach(card => card.style.display = 'none');
            } else {
                toggleContainer.style.display = 'none';
                userCardsVisible = false;
            }
        }
        function toggleUserCards() {
            const container = document.getElementById('userCardsContainer');
            const toggleIcon = document.getElementById('toggleUserIcon');
            if (!container) return;
            const cards = Array.from(container.querySelectorAll('.user-filter-card'));
            const firstCardTop = cards[0].getBoundingClientRect().top;
            userCardsVisible = !userCardsVisible;
            cards.forEach(card => {
                if (Math.abs(card.getBoundingClientRect().top - firstCardTop) > 10) {
                    card.style.display = userCardsVisible ? 'block' : 'none';
                }
            });
            if (userCardsVisible) toggleIcon.innerHTML = '<polyline points="7 11 12 6 17 11"></polyline><polyline points="7 18 12 13 17 18"></polyline>'; else toggleIcon.innerHTML = '<polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline>';
        }
        window.addEventListener('load', checkUserCardWrapping);
        window.addEventListener('resize', () => { userCardsVisible = false; checkUserCardWrapping(); });
        setTimeout(checkUserCardWrapping, 100);
    </script>

    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px;">
        <form method="GET" action="{{ route('users.index') }}" id="searchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="hidden" name="per_page" id="searchPerPage" value="{{ request()->query('per_page', 10) }}">
            <input type="text" name="search" id="userSearchInput" value="{{ $search }}" placeholder="Search by name, mobile, or email..." oninput="debouncedUserSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        @if(env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('create', \App\Models\User::class))
        <a href="{{ route('users.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
            <span data-feather="user-plus"></span>
            Add User
        </a>
        @endif
    </div>

    <div class="card" style="margin-top:12px; overflow-x:auto;">
        <div id="usersTableWrapper">
            @include('users._users_table')
        </div>
    </div>

    <script>
        let __userSearchTimer = null;
        function debouncedUserSearch() {
            clearTimeout(__userSearchTimer);
            __userSearchTimer = setTimeout(() => { ajaxFetchUsers(1); }, 300);
        }

        function ajaxFetchUsers(page = 1) {
            const form = document.getElementById('searchForm');
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
                    document.getElementById('usersTableWrapper').innerHTML = html;
                    bindPaginationLinks();
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindPaginationLinks(){
            const wrapper = document.getElementById('usersTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                const url = new URL(href, location.origin);
                if (url.searchParams.has('page')) {
                    a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchUsers(url.searchParams.get('page')); });
                }
            });
        }

        window.addEventListener('load', function(){
            bindPaginationLinks();
        });
    </script>
@endsection
