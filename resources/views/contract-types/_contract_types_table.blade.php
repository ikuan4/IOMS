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
                    <form action="{{ route('contract-types.destroy', $type) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Contract Type', subtitle: 'This will soft delete the contract type', message: 'Are you sure you want to delete {{ addslashes($type->name) }}?', confirmText: 'Delete Contract Type', checkDependenciesUrl: '{{ route('contract-types.check_delete_dependencies', $type) }}', form: this});">
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
        @php
            $currentPerPage = (int) request()->query('per_page', $contractTypes->perPage() ?? 10);
        @endphp
        <form method="GET" action="{{ url()->current() }}" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
            @foreach(request()->except(['per_page','page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
            @endforeach
            <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
            <select name="per_page" id="per_page" onchange="ajaxFetchContractTypes(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                @foreach([5,10,15,20,30] as $opt)
                    <option value="{{ $opt }}" {{ $currentPerPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
