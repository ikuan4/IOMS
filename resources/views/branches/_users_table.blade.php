<table style="width:100%;border-collapse:collapse;margin-top:8px;">
    <thead>
        <tr style="text-align:left;border-bottom:1px solid #e5e7eb;">
            <th style="padding:8px;">Name</th>
            <th style="padding:8px;">Email</th>
            <th style="padding:8px;">Roles</th>
            <th style="padding:8px; text-align:right;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td style="padding:8px;">{{ $user->name }}</td>
                <td style="padding:8px;">{{ $user->email }}</td>
                <td style="padding:8px;">@if($user->role) <span style="background:#e0f2fe;color:#0369a1;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:600;">{{ $user->role->name }}</span> @else <span class="muted">No Role</span> @endif</td>
                <td style="padding:8px; text-align:right; white-space:nowrap;">
                    @if(auth()->user()->can('view', $user))
                        <a href="{{ route('users.show', $user->id) }}" title="View" style="background:#eef2ff;color:#3730a3;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;text-decoration:none;margin-right:6px;font-size:15px;">
                            <span data-feather="eye"></span>
                        </a>
                    @endif
                    @if(auth()->user()->can('update', $user))
                        <a href="{{ route('users.edit', $user->id) }}" title="Edit" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;text-decoration:none;margin-right:6px;font-size:15px;">
                            <span data-feather="edit"></span>
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" style="padding:12px;">No users assigned to this branch.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:12px;">{{ $users->links() }}</div>
