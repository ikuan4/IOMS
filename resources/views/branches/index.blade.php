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
        <form method="GET" action="{{ route('branches.index') }}" style="display:flex;gap:12px;align-items:flex-end;">
            <input type="text" name="search" id="branchSearchInput" value="{{ request('search') }}" placeholder="Search branches..." oninput="filterBranchesRealtime()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
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

        <div style="padding:12px;">
            {{ $branches->links() }}
        </div>
    </div>

    @include('partials.confirmation-modal')

    <script>
        function filterBranchesRealtime() {
            const searchInput = document.getElementById('branchSearchInput');
            if (!searchInput) return;
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.branch-table-row');
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const matches = name.includes(searchTerm);
                row.style.display = (matches || searchTerm === '') ? '' : 'none';
            });
        }
        window.addEventListener('load', function(){
            const input = document.getElementById('branchSearchInput');
            if (input && input.value) filterBranchesRealtime();
        });
    </script>

@endsection
