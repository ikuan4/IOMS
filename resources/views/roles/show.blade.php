@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Role Details</h1>

    <div class="card">
        <div class="card-body">
            <div style="display:flex;gap:18px;align-items:flex-start;">
                <div style="flex:0 0 160px;">
                    <div style="width:150px;height:150px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                        <span data-feather="shield" style="width:48px;height:48px;color:#6b7280;"></span>
                    </div>
                </div>

                <div style="flex:1;">

                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div>
                            <h2 style="margin:0;">{{ $role->name }}</h2>
                            <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                                <span style="color:var(--muted,#6b7280);">{{ $role->slug }}</span>
                                @if($role->is_global)
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:6px;font-weight:700;font-size:12px;">
                                        <span data-feather="globe" style="width:14px;height:14px;"></span>
                                        GLOBAL
                                    </span>
                                @else
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#166534;padding:4px 10px;border-radius:6px;font-weight:700;font-size:12px;">
                                        <span data-feather="map-pin" style="width:14px;height:14px;"></span>
                                        BRANCH-SPECIFIC
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:18px;display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:12px;">
                        <div><strong>Slug:</strong> {{ $role->slug ?? '-' }}</div>
                        <div><strong>Type:</strong> {{ $role->is_global ? 'Global Role' : 'Branch-Specific Role' }}</div>

                        <div><strong>Hierarchy:</strong> {{ $role->parent?->name ? 'Child of: '.$role->parent->name : 'Parent Role' }}</div>
                        <div><strong>Users Count:</strong> 
                            @if($role->is_global)
                                {{ $role->globalUsers()->count() }}
                            @else
                                {{ $role->branchUsers()->count() }}
                            @endif
                        </div>

                        <div><strong>Status:</strong> @if($role->deleted_at) Deleted @elseif($role->is_active) Active @else Inactive @endif</div>
                        <div><strong>Description:</strong> {{ $role->description ?? '-' }}</div>

                        <div><strong>Created At:</strong> {{ optional($role->created_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Created By:</strong> {{ optional($role->createdBy)->name ?? '-' }}</div>

                        <div><strong>Updated At:</strong> {{ optional($role->updated_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Updated By:</strong> {{ optional($role->updatedBy)->name ?? '-' }}</div>

                        <div><strong>Deleted At:</strong> {{ optional($role->deleted_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Deleted By:</strong> {{ optional($role->deletedBy)->name ?? '-' }}</div>

                        <div><strong>Restored At:</strong> {{ optional($role->restored_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Restored By:</strong> {{ optional($role->restoredBy)->name ?? '-' }}</div>

                        <div style="grid-column:1/-1"><strong>Permissions:</strong>
                            @if($role->permissions && $role->permissions->count())
                                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
                                    @foreach($role->permissions as $perm)
                                        <span style="background:#eef2ff;color:#3730a3;padding:6px 10px;border-radius:8px;font-weight:700;font-size:13px;">{{ $perm->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <div style="opacity:0.6;margin-top:6px;">No permissions assigned.</div>
                            @endif
                        </div>
                    </div>

                    <div style="margin-top:18px; display:flex; gap:8px;">
                        @php $backUrl = session('roles_list_back_url'); @endphp
                        @if($backUrl)
                            <a href="{{ $backUrl }}" title="Back to previous page" style="background:#22c55e;color:#ffffff;padding:12px 16px;border-radius:10px;border:1px solid #16a34a;display:inline-flex;align-items:center;gap:10px;cursor:pointer;margin-right:6px;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Back to previous page</a>
                        @else
                            <a href="#" onclick="history.back(); return false;" title="Back to previous page" style="background:#22c55e;color:#ffffff;padding:12px 16px;border-radius:10px;border:1px solid #16a34a;display:inline-flex;align-items:center;gap:10px;cursor:pointer;margin-right:6px;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Back to previous page</a>
                        @endif

                        @can('update', $role)
                        <a href="{{ route('roles.edit', $role->id) }}" style="background:#e0f2fe;color:#0369a1;padding:12px 16px;border-radius:10px;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Edit</a>
                        @endcan
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
