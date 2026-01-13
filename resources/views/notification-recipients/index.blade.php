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
        <div style="display:flex;justify-content:flex-start;align-items:center;gap:12px;flex-wrap:wrap;">
            {{-- Search form --}}
            <form method="GET" action="{{ route('notification-recipients.index') }}" id="recipientSearchForm" style="flex:0 0 auto;min-width:250px;max-width:400px;">
                <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                <input
                    type="text"
                    name="search"
                    id="recipientSearchInput"
                    placeholder="Search by name, email, designation..."
                    value="{{ request('search') }}"
                    oninput="debouncedRecipientSearch()"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;"
                >
            </form>

            {{-- Action buttons --}}
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.create'))
                <a href="{{ route('notification-recipients.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                    <span data-feather="plus"></span>
                    New Recipient
                </a>
            @endif
        </div>
    </div>

    {{-- Recipients Table --}}
    <div class="card" style="margin-top:20px;padding:0;overflow:hidden;">
        <div id="recipientsTableWrapper">
            @include('notification-recipients._recipients_table')
        </div>
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

    <script>
        let __recipientSearchTimer = null;
        function debouncedRecipientSearch() {
            clearTimeout(__recipientSearchTimer);
            __recipientSearchTimer = setTimeout(() => { ajaxFetchRecipients(1); }, 300);
        }

        function ajaxFetchRecipients(page = 1) {
            const form = document.getElementById('recipientSearchForm');
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
                    document.getElementById('recipientsTableWrapper').innerHTML = html;
                    bindPaginationLinks();
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindPaginationLinks(){
            const wrapper = document.getElementById('recipientsTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                const url = new URL(href, location.origin);
                if (url.searchParams.has('page')) {
                    a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchRecipients(url.searchParams.get('page')); });
                }
            });
        }

        window.addEventListener('load', function(){
            bindPaginationLinks();
        });
    </script>
@endsection
