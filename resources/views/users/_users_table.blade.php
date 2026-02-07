<table style="width:100%; border-collapse:collapse;">
    <thead>
    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
        <th style="padding:8px; width:64px;">Avatar</th>
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
            <td style="padding:8px; vertical-align:middle;">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}'s avatar" style="width:40px;height:40px;border-radius:8px;object-fit:cover;display:block;" />
                @else
                    <div style="width:40px;height:40px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                @endif
            </td>
            <td style="padding:8px;">
                {{ $user->name }}
                @if($user->isSuperAdmin())
                <span style="display:inline-block;margin-left:8px;padding:4px 8px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Developer</span>
                @endif
            </td>
            <td style="padding:8px;">
                @php
                    $displayRole = null;
                    if (method_exists($user, 'effectiveRole')) {
                        $displayRole = $user->effectiveRole();
                    } else {
                        $displayRole = $user->role ?? null;
                    }
                @endphp
                @if($displayRole)
                    <span style="background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:6px;font-size:13px;font-weight:600;">{{ $displayRole->name }}</span>
                @else
                    <span class="muted">No Role</span>
                @endif
            </td>
            <td style="padding:8px;">{{ $user->mobile }}</td>
            <td style="padding:8px;">{{ $user->email }}</td>
            <td style="padding:8px;">@if($isDeleted)<span style="color:#f10101;font-weight:600;">Deleted</span>@else @if($user->active)<span style="color:#16a34a;font-weight:600;">Active</span>@else<span style="color:#dc2626;font-weight:600;">Deactivated</span>@endif @endif</td>
            <td style="padding:8px; text-align:right; white-space:nowrap;">
                @if(!$isDeleted)
                    @if(env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('update', $user))
                    <a href="{{ route('users.edit', $user) }}" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit user"><span data-feather="edit"></span></a>
                    @endif
                    @if(!$user->isSuperAdmin() && (env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('delete', $user)))
                    <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete User', subtitle: 'This will soft delete the user', message: 'Are you sure you want to delete {{ str_replace("'", "\\'", $user->name) }}?', confirmText: 'Delete User', form: this});">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Soft delete user"><span data-feather="trash-2"></span></button>
                    </form>
                    @endif
                    @if(env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('view', $user))
                    <a href="{{ route('users.show', $user) }}" title="View user" style="background:#f8fafc;color:#0f172a;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-left:6px;text-decoration:none;"><span data-feather="eye"></span></a>
                    @endif
                @else
                    @if(!$user->isSuperAdmin() && (env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('restore', $user)))
                    <form action="{{ route('users.restore', $user->id) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore User', subtitle: 'This will restore the user', message: 'Are you sure you want to restore {{ str_replace("'", "\\'", $user->name) }}?', confirmText: 'Restore User', checkDependenciesUrl: '{{ route('users.check_dependencies', $user->id) }}', form: this});">
                        @csrf
                        @if(!empty(request('search')))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                        <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore user"><span data-feather="rotate-ccw"></span></button>
                    </form>
                    @endif
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" style="padding:12px;">No users found.</td></tr>
    @endforelse
    </tbody>
</table>
<div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
        Total Users: {{ $users->total() }}
    </div>

    <div style="flex:1; display:flex; justify-content:center;">
        @php
            $current = $users->currentPage();
            $last = $users->lastPage();
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
        @php $currentPerPage = (int) request()->query('per_page', $users->perPage() ?? 10); @endphp
        <form method="GET" action="{{ url()->current() }}" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
            @foreach(request()->except(['per_page','page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
            @endforeach
            <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
            <select name="per_page" id="per_page" onchange="ajaxFetchUsers(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                @foreach([5,10,15,20,30] as $opt)
                    <option value="{{ $opt }}" {{ $currentPerPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
