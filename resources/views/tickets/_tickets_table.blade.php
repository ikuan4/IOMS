<table style="width:100%; border-collapse:collapse;">
    <thead>
    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
        <th style="padding:8px;">Subject</th>
        <th style="padding:8px;">Type</th>
        <th style="padding:8px;">Module</th>
        <th style="padding:8px;">Assignee</th>
        <th style="padding:8px;">Priority</th>
        <th style="padding:8px;">Status</th>
        <th style="padding:8px;">Due</th>
        @if(auth()->user() && auth()->user()->isSuperAdmin())
            <th style="padding:8px;">Branch</th>
        @endif
        <th style="padding:8px; text-align:right;">Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($tickets as $ticket)
        @php $isDeleted = !is_null($ticket->deleted_at); @endphp
        <tr style="border-bottom:1px solid #f3f4f6; height:62px; {{ $isDeleted ? 'opacity:1;' : '' }}">
            <td style="padding:8px;">
                <div style="font-weight:800;">{{ Str::limit($ticket->subject, 60) }}</div>
                <div style="margin-top:4px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    @if(!empty($ticket->ticket_number))
                        <span style="background:#111827;color:#fff;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:900;">{{ $ticket->ticket_number }}</span>
                    @endif
                    <span style="background:#f3f4f6;color:#374151;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:900;">ID: {{ $ticket->id }}</span>
                </div>
            </td>
            <td style="padding:8px;">{{ $ticket->ticketType->name ?? 'N/A' }}</td>
            <td style="padding:8px;">{{ $ticket->ticketModule->name ?? 'N/A' }}</td>
            <td style="padding:8px;">{{ $ticket->assignee->name ?? 'Unassigned' }}</td>
            <td style="padding:8px;">
                @php
                    $priority = strtolower((string) ($ticket->priority ?? 'medium'));
                    $priorityColors = [
                        'low' => ['bg' => '#e0f2fe', 'fg' => '#0369a1'],
                        'medium' => ['bg' => '#fef9c3', 'fg' => '#854d0e'],
                        'high' => ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
                        'urgent' => ['bg' => '#fce7f3', 'fg' => '#9d174d'],
                    ];
                    $pc = $priorityColors[$priority] ?? $priorityColors['medium'];
                @endphp
                <span style="background:{{ $pc['bg'] }};color:{{ $pc['fg'] }};padding:4px 10px;border-radius:999px;font-size:13px;font-weight:700;">
                    {{ strtoupper(str_replace('_', ' ', $priority)) }}
                </span>
            </td>
            <td style="padding:8px;">
                @if($isDeleted)
                    <span style="color:#f10101;font-weight:600;">Deleted</span>
                @else
                    @php
                        $st = strtolower((string) ($ticket->status ?? 'open'));
                        $stLabel = strtoupper(str_replace('_', ' ', $st));
                        $stColors = [
                            'open' => ['bg' => '#e0f2fe', 'fg' => '#0369a1'],
                            'in_progress' => ['bg' => '#fef9c3', 'fg' => '#854d0e'],
                            'resolved' => ['bg' => '#dcfce7', 'fg' => '#15803d'],
                            'closed' => ['bg' => '#e5e7eb', 'fg' => '#374151'],
                        ];
                        $sc = $stColors[$st] ?? $stColors['open'];
                    @endphp
                    <span style="background:{{ $sc['bg'] }};color:{{ $sc['fg'] }};padding:4px 10px;border-radius:999px;font-size:13px;font-weight:700;">
                        {{ $stLabel }}
                    </span>
                @endif
            </td>
            <td style="padding:8px;">{{ $ticket->due_at ? $ticket->due_at->format('Y-m-d') : '—' }}</td>
            @if(auth()->user() && auth()->user()->isSuperAdmin())
                <td style="padding:8px;">{{ $ticket->branch->name ?? 'N/A' }}</td>
            @endif
            <td style="padding:8px; text-align:right; white-space:nowrap;">
                @if(!$isDeleted)
                    @if(\Illuminate\Support\Facades\Route::has('tickets.show') && (auth()->user()->isSuperAdmin() || auth()->user()->can('view', $ticket) || auth()->user()->hasPermission('tickets.view')))
                        <a href="{{ route('tickets.show', $ticket) }}" style="background:#f3f4f6;color:#111827;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="View ticket"><span data-feather="eye"></span></a>
                    @endif
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('update', $ticket))
                        <a href="{{ route('tickets.edit', $ticket) }}" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit ticket"><span data-feather="edit"></span></a>
                    @endif
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('delete', $ticket))
                        <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Ticket', subtitle: 'This will soft delete the ticket', message: 'Are you sure you want to delete this ticket: {{ addslashes($ticket->subject) }}?', confirmText: 'Delete Ticket', form: this});">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Soft delete ticket"><span data-feather="trash-2"></span></button>
                        </form>
                    @endif
                @else
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('restore', $ticket))
                        <form action="{{ route('tickets.restore', $ticket->id) }}" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Ticket', subtitle: 'This will restore the ticket', message: 'Are you sure you want to restore this ticket: {{ addslashes($ticket->subject) }}?', confirmText: 'Restore Ticket', form: this});">
                            @csrf
                            @if(!empty($search))<input type="hidden" name="search" value="{{ $search }}">@endif
                            <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore ticket"><span data-feather="rotate-ccw"></span></button>
                        </form>
                    @endif
                @endif
            </td>
        </tr>
    @empty
        @php $colspan = (auth()->user() && auth()->user()->isSuperAdmin()) ? 9 : 8; @endphp
        <tr><td colspan="{{ $colspan }}" style="padding:12px;">No tickets found.</td></tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
        Total Tickets: {{ $tickets->total() }}
    </div>

    <div style="flex:1; display:flex; justify-content:center;">
        @php
            $current = $tickets->currentPage();
            $last = $tickets->lastPage();
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
            $currentPerPage = (int) request()->query('per_page', $tickets->perPage() ?? 10);
        @endphp
        <form method="GET" action="{{ url()->current() }}" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
            @foreach(request()->except(['per_page','page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
            @endforeach
            <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
            <select name="per_page" id="per_page" onchange="ajaxFetchTickets(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                @foreach([5,10,15,20,30] as $opt)
                    <option value="{{ $opt }}" {{ $currentPerPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
