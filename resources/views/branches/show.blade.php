@extends('layouts.dashboard')

@section('title', 'Branch Details')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>BRANCH: {{ $branch->name }}</h2>
            <p class="muted">Details and users for this branch.</p>
        </div>
        <div class="header-right">
            <a href="{{ route('branches.edit', $branch->id) }}" style="background:#e0f2fe;color:#0369a1;padding:8px 16px;border-radius:8px;">Edit Branch</a>
        </div>
    </div>

    <div class="card" style="margin-top:12px;padding:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;">
            <h3 style="margin:0;">Assigned Users ({{ $branch->users()->count() }})</h3>

            <div style="display:flex;gap:8px;align-items:center;">
                <form method="GET" id="branchUserSearchForm" action="" style="margin:0;">
                    <input type="text" name="search" id="branchUserSearch" placeholder="Search users in branch..." value="{{ request('search') }}" style="padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;min-width:260px;" oninput="debouncedAjaxSearch()" />
                </form>

                @can('export', $branch)
                    <a id="branchExportBtn" href="{{ route('branches.export', array_merge(['branch' => $branch->id], request()->only('search'))) }}" style="background:#f8fafc;color:#0f172a;padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #e2e8f0;">Export CSV</a>
                @endcan
            </div>
        </div>

        <div id="branchUsersTableWrapper">
            @include('branches._users_table')
        </div>

        <div id="branchUsersLoading" style="display:none;margin-top:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <svg width="20" height="20" viewBox="0 0 50 50" style="animation:spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg"><circle cx="25" cy="25" r="20" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4"/></svg>
                <div style="font-size:14px;color:#374151;">Loading users…</div>
            </div>
        </div>

        <div id="branchUsersError" style="display:none;margin-top:12px;color:#b91c1c;font-weight:600;"></div>


    <script>
        let searchTimer = null;
        function debouncedSubmitSearch() {
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { document.querySelector('form[method="GET"]').submit(); }, 500);
        }

        // AJAX search & pagination
        function debouncedAjaxSearch() {
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { ajaxFetchUsers(1); }, 300);
        }

        function ajaxFetchUsers(page = 1) {
            const form = document.getElementById('branchUserSearchForm');
            const params = new URLSearchParams(new FormData(form));
            params.set('page', page);
            const url = `${location.pathname}?${params.toString()}`;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network error');
                    return r.text();
                })
                .then(html => {
                        // insert HTML
                        document.getElementById('branchUsersTableWrapper').innerHTML = html;
                    // update export link if present
                    const exportBtn = document.getElementById('branchExportBtn');
                    if (exportBtn) {
                        exportBtn.href = `{{ route('branches.export', ['branch' => $branch->id]) }}?` + params.toString();
                    }
                    // rebind pagination links
                    bindPaginationLinks();
                    // re-render feather icons if available
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { /* ignore */ }
                        // hide loader
                        try { document.getElementById('branchUsersLoading').style.display = 'none'; } catch (e) {}
                    }).catch(e => {
                        console.error(e);
                        try {
                            document.getElementById('branchUsersLoading').style.display = 'none';
                            const errEl = document.getElementById('branchUsersError');
                            errEl.textContent = 'Unable to load users. Try again later.' + (e && e.message ? ' ('+e.message+')' : '');
                            errEl.style.display = 'block';
                        } catch (ee) {}
                    });
        }

        function bindPaginationLinks(){
            const wrapper = document.getElementById('branchUsersTableWrapper');
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
            const input = document.getElementById('branchUserSearch');
            if (input && input.value) ajaxFetchUsers();
        });
    </script>

@endsection
