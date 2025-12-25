@extends('layouts.dashboard')

@section('title', 'Manage Branches')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>BRANCHES</h2>
            <p class="muted">Manage organizational branches.</p>
        </div>
    </div>

    <div style="display:flex;gap:12px;align-items:flex-end; margin-top:12px;">
        <form method="GET" action="{{ route('branches.index') }}" id="branchSearchForm" style="display:flex;gap:12px;align-items:flex-end;">
            <input type="hidden" name="per_page" id="branchSearchPerPage" value="{{ request()->query('per_page', 10) }}">
            <input type="text" name="search" id="branchSearchInput" value="{{ request('search') }}" placeholder="Search branches..." oninput="debouncedBranchSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        @if(env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('create', \App\Models\Branch::class))
        <a href="{{ route('branches.create') }}" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
            <span data-feather="plus"></span>
            Add Branch
        </a>
        @endif
    </div>

    <div class="card" style="margin-top:12px;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="text-align:left;border-bottom:1px solid #e5e7eb;">
                    <th style="padding:8px;">Name</th>
                    <th style="padding:8px;">Users</th>
                    <th style="padding:8px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    @php $isDeleted = !is_null($branch->deleted_at); @endphp
                    <tr class="branch-table-row" data-name="{{ strtolower($branch->name) }}" style="border-bottom:1px solid #f3f4f6; {{ $isDeleted ? 'opacity:0.8;' : '' }}">
                        <td style="padding:8px;">{{ $branch->name }} @if($isDeleted) <span style="color:#dc2626;font-weight:600;margin-left:6px;">(Deleted)</span>@endif</td>
                        <td style="padding:8px;"><span style="background:#dbeafe;color:#1e40af;padding:4px 8px;border-radius:6px;">{{ $branch->users()->count() }}</span></td>
                        <td style="padding:8px; text-align:right; white-space:nowrap;" onclick="event.stopPropagation();">
                            @if(!$isDeleted)
                                <a href="{{ route('branches.edit', $branch->id) }}" title="Edit" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;font-size:15px;">
                                    <span data-feather="edit"></span>
                                </a>
                                <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" style="display:inline-block; margin-left:0;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Branch', subtitle: 'This will soft delete the branch', message: 'Are you sure you want to delete {{ $branch->name }}? This action can be reversed later.', confirmText: 'Delete Branch', form: this});">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-left:6px;font-size:15px;">
                                        <span data-feather="trash-2"></span>
                                    </button>
                                </form>
                                <a href="{{ route('branches.show', $branch->id) }}" title="View" style="margin-left:6px;background:#eef2ff;color:#3730a3;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;text-decoration:none;font-size:15px;">
                                    <span data-feather="eye"></span>
                                </a>
                            @else
                                <form action="{{ route('branches.restore', $branch->id) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Branch', subtitle: 'This will restore the branch', message: 'Are you sure you want to restore {{ $branch->name }}?', confirmText: 'Restore Branch', form: this});">
                                    @csrf
                                    <button type="submit" title="Restore" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-left:6px;font-size:15px;">
                                        <span data-feather="rotate-ccw"></span>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="padding:12px;">No branches found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Branches: {{ $branches->total() }}
            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                @php
                    $current = $branches->currentPage();
                    $last = $branches->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $start + 4);
                    if ($end - $start < 4) { $start = max(1, $end - 4); }
                    $baseParams = request()->except(['page']);
                @endphp

                <nav aria-label="Pagination" style="display:inline-flex;align-items:center;gap:6px;background:var(--card);padding:6px 10px;border-radius:8px;">
                    @php $firstColor = $current > 1 ? '#2563eb' : '#000'; @endphp
                    @if($current > 1)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => 1])) }}" aria-label="First page" style="padding:6px 10px;border-radius:6px;background:transparent;color:{{ $firstColor }};text-decoration:none;font-weight:800;font-size:14px;">&laquo;</a>
                    @else
                        <span aria-hidden="true" style="padding:6px 10px;color:{{ $firstColor }};font-weight:800;font-size:14px;">&laquo;</span>
                    @endif

                    @php $prevColor = $current > 1 ? '#2563eb' : '#000'; @endphp
                    @if($current > 1)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current - 1])) }}" aria-label="Previous page" style="padding:8px 12px;border-radius:6px;background:transparent;color:{{ $prevColor }};text-decoration:none;font-weight:800;font-size:15px;">&lt;</a>
                    @else
                        <span aria-hidden="true" style="padding:8px 12px;color:{{ $prevColor }};font-weight:800;font-size:15px;">&lt;</span>
                    @endif

                    @for($p = $start; $p <= $end; $p++)
                        @if($p == $current)
                            <span aria-current="page" style="padding:9px 14px;border-radius:8px;background:#f3f4f6;color:#374151;font-weight:800;border:1px solid #e5e7eb;font-size:15px;">{{ $p }}</span>
                        @else
                            <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $p])) }}" style="padding:8px 12px;border-radius:6px;background:transparent;color:var(--text);text-decoration:none;font-weight:800;font-size:14px;" onmouseover="this.style.border='1px solid #22c55e';this.style.background='rgba(34,197,94,0.06)';" onmouseout="this.style.border='none';this.style.background='transparent';">{{ $p }}</a>
                        @endif
                    @endfor

                    @php $nextColor = $current < $last ? '#2563eb' : '#000'; @endphp
                    @if($current < $last)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current + 1])) }}" aria-label="Next page" style="padding:8px 12px;border-radius:6px;background:transparent;color:{{ $nextColor }};text-decoration:none;font-weight:800;font-size:15px;">&gt;</a>
                    @else
                        <span aria-hidden="true" style="padding:8px 12px;color:{{ $nextColor }};font-weight:800;font-size:15px;">&gt;</span>
                    @endif

                    @php $lastColor = $current < $last ? '#2563eb' : '#000'; @endphp
                    @if($current < $last)
                        <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $last])) }}" aria-label="Last page" style="padding:6px 10px;border-radius:6px;background:transparent;color:{{ $lastColor }};text-decoration:none;font-weight:800;font-size:14px;">&raquo;</a>
                    @else
                        <span aria-hidden="true" style="padding:6px 10px;color:{{ $lastColor }};font-weight:800;font-size:14px;">&raquo;</span>
                    @endif
                </nav>
            </div>

            <div style="flex:1; min-width:180px; display:flex; justify-content:flex-end; align-items:center; gap:8px;">
                @php $currentPerPage = (int) request()->query('per_page', $branches->perPage() ?? 10); @endphp
                <form method="GET" action="{{ url()->current() }}" id="branchPerPageForm" style="display:flex;align-items:center;gap:8px;">
                    @foreach(request()->except(['per_page','page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                    @endforeach
                    <label for="branch_per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="branch_per_page" onchange="document.getElementById('branchPerPageForm').submit()" style="padding:8px;border-radius:8px;border:1px solid #e5e7eb;background:var(--card);">
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
        // Debounced server-side search for branches
        let __branchSearchTimer = null;
        function debouncedBranchSearch() {
            clearTimeout(__branchSearchTimer);
            __branchSearchTimer = setTimeout(() => {
                const perPageSelect = document.getElementById('branch_per_page') || document.getElementById('per_page');
                const searchPerPage = document.getElementById('branchSearchPerPage');
                if (perPageSelect && searchPerPage) searchPerPage.value = perPageSelect.value;
                const form = document.getElementById('branchSearchForm');
                if (form) form.submit();
            }, 350);
        }
        window.addEventListener('load', function(){
            const input = document.getElementById('branchSearchInput');
            if (input && input.value) {
                const form = document.getElementById('branchSearchForm');
                if (form) form.submit();
            }
        });
    </script>

@endsection
