@extends('layouts.dashboard')

@section('title', 'Roles by Branch')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Roles by Branch</h2>
            <p class="muted">Select a branch to view roles and their priority.</p>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px;">
        <div class="card" style="padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div>
                    <h5 style="margin:0;font-size:16px;font-weight:600;">Branch</h5>
                    <p class="muted" style="margin:4px 0 0 0;">Choose branch to filter roles.</p>
                </div>

                <div style="min-width:240px;">
                    @if($isDeveloper ?? false)
                        <form method="GET" action="{{ route('roles.hierarchy', $role->id ?? 0) }}">
                            <select name="branch_id" onchange="this.form.submit()" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d0d7e0;font-size:14px;">
                                <option value="">-- Select Branch --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ (int)($selectedBranchId ?? 0) === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <div style="padding:10px 12px;border-radius:8px;border:1px solid #e6edf3;background:#f8fafc;font-size:14px;">{{ optional(\App\Models\Branch::find($selectedBranchId))->name ?? 'My Branch' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card" style="padding:16px;">
            <h5 style="margin:0 0 10px 0;font-size:16px;font-weight:600;">Roles</h5>
            @if($rolesForView && $rolesForView->count())
                <table class="roles-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Description</th>
                            <th style="width:120px;">Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rolesForView as $r)
                            <tr>
                                <td style="font-weight:700;">{{ $r->name }}</td>
                                <td class="muted" style="font-size:13px;">{{ $r->description ?? '—' }}</td>
                                <td>
                                    <span class="pill">{{ $r->priority ?? 100 }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted">No roles available for the selected branch or you are not part of any roles.</p>
            @endif
        </div>
    </div>

@endsection
