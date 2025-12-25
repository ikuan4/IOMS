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
        <form method="GET" action="{{ route('users.index') }}" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
            <input type="text" name="search" id="userSearchInput" value="{{ $search }}" placeholder="Search by name, mobile, or email..." oninput="filterUsersRealtime()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        @can('create', \App\Models\User::class)
        <a href="{{ route('users.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
            <span data-feather="user-plus"></span>
            Add User
        </a>
        @endcan
    </div>

    <div class="card" style="margin-top:12px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
            <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
                <th style="padding:8px;">Name</th>
                <th style="padding:8px;">Role</th>
                <th style="padding:8px;">Mobile</th>
                <th style="padding:8px;">Email</th>
                <th style="padding:8px;">Status</th>
                <th style="padding:8px; text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                @php $isDeleted = !is_null($user->deleted_at); @endphp
                <tr class="user-table-row" style="border-bottom:1px solid #f3f4f6; height:62px; {{ $isDeleted ? 'opacity:1;' : '' }}" data-name="{{ strtolower($user->name) }}" data-mobile="{{ strtolower($user->mobile) }}" data-email="{{ strtolower($user->email) }}">
                    <td style="padding:8px;">{{ $user->name }}</td>
                    <td style="padding:8px;">
                        @if($user->role)
                            <span style="background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:6px;font-size:13px;font-weight:600;">{{ $user->role->name }}</span>
                        @else
                            <span class="muted">No Role</span>
                        @endif
                    </td>
                    <td style="padding:8px;">{{ $user->mobile }}</td>
                    <td style="padding:8px;">{{ $user->email }}</td>
                    <td style="padding:8px;">@if($isDeleted)<span style="color:#f10101;font-weight:600;">Deleted</span>@else @if($user->active)<span style="color:#16a34a;font-weight:600;">Active</span>@else<span style="color:#dc2626;font-weight:600;">Deactivated</span>@endif @endif</td>
                    <td style="padding:8px; text-align:right; white-space:nowrap;">
                        @if(!$isDeleted)
                            @can('update', $user)
                            <a href="{{ route('users.edit', $user) }}" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit user"><span data-feather="edit"></span></a>
                            @endcan
                            @can('delete', $user)
                            <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete User', subtitle: 'This will soft delete the user', message: 'Are you sure you want to delete {{ $user->name }}? This action can be reversed later.', confirmText: 'Delete User', form: this});">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Soft delete user"><span data-feather="trash-2"></span></button>
                            </form>
                            @endcan
                        @else
                            @can('restore', $user)
                            <form action="{{ route('users.restore', $user->id) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore User', subtitle: 'This will restore the user', message: 'Are you sure you want to restore {{ $user->name }}? The user will be active again.', confirmText: 'Restore User', form: this});">
                                @csrf
                                @if(!empty($search))<input type="hidden" name="search" value="{{ $search }}">@endif
                                <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore user"><span data-feather="rotate-ccw"></span></button>
                            </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:12px;">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:12px;">{{ $users->links() }}</div>
    </div>

    @include('partials.confirmation-modal')

    <script>
        function filterUsersRealtime() {
            const searchInput = document.getElementById('userSearchInput');
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.user-table-row');
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const mobile = row.getAttribute('data-mobile') || '';
                const email = row.getAttribute('data-email') || '';
                const matches = name.includes(searchTerm) || mobile.includes(searchTerm) || email.includes(searchTerm);
                row.style.display = (matches || searchTerm === '') ? '' : 'none';
            });
        }
    </script>
@endsection
