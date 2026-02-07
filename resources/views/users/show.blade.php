@extends('layouts.app')

@section('content')
<div class="container">
    <h1>User Details</h1>

    <div class="card">
        <div class="card-body">
            <div style="display:flex;gap:18px;align-items:flex-start;">
                <div style="flex:0 0 160px;">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="avatar" style="width:150px;height:150px;object-fit:cover;border-radius:8px;" />
                    @else
                        <div style="width:150px;height:150px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                    @endif
                </div>

                <div style="flex:1;">

                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div>
                            <h2 style="margin:0;">{{ $user->name }}</h2>
                            <div style="color:var(--muted,#6b7280);margin-top:6px;">{{ optional($user->role)->name ?? 'No Role' }} — {{ optional($user->branch)->name ?? 'No Branch' }}</div>
                        </div>
                    </div>

                    <div style="margin-top:18px;display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:12px;">
                        <div><strong>Mobile:</strong> {{ $user->mobile ?? '-' }}</div>
                        <div><strong>Email:</strong> {{ $user->email ?? '-' }}</div>

                        <div><strong>Role:</strong> {{ optional($user->role)->name ?? '-' }}</div>
                        <div><strong>Branch:</strong> {{ optional($user->branch)->name ?? '-' }}</div>

                        <div><strong>Active:</strong> {{ $user->active ? 'Yes' : 'No' }}</div>
                        <div><strong>Deleted At:</strong> {{ optional($user->deleted_at)->toDateTimeString() ?? '-' }}</div>

                        <div><strong>Created At:</strong> {{ optional($user->created_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Created By:</strong> {{ optional($user->createdBy)->name ?? '-' }}</div>

                        <div><strong>Updated At:</strong> {{ optional($user->updated_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Updated By:</strong> {{ optional($user->updatedBy)->name ?? '-' }}</div>

                        <div><strong>Last Updated At:</strong> {{ optional($user->last_updated_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Last Updated By:</strong> {{ optional($user->lastUpdatedBy)->name ?? '-' }}</div>

                        <div><strong>Restored At:</strong> {{ optional($user->restored_at)->toDateTimeString() ?? '-' }}</div>
                        <div><strong>Restored By:</strong> {{ optional($user->restoredBy)->name ?? '-' }}</div>

                        <div><strong>Email Bounce Count:</strong> {{ $user->email_bounce_count ?? 0 }}</div>
                        <div><strong>Last Email Bounce:</strong> {{ optional($user->email_bounced_at)->toDateTimeString() ?? '-' }}</div>
                    </div>

                    <div style="margin-top:18px; display:flex; gap:8px;">
                        @php $backUrl = session('users_list_back_url'); @endphp
                        @if($backUrl)
                            <a href="{{ $backUrl }}" title="Back to previous page" style="background:#22c55e;color:#ffffff;padding:12px 16px;border-radius:10px;border:1px solid #16a34a;display:inline-flex;align-items:center;gap:10px;cursor:pointer;margin-right:6px;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Back to previous page</a>
                        @else
                            <a href="#" onclick="history.back(); return false;" title="Back to previous page" style="background:#22c55e;color:#ffffff;padding:12px 16px;border-radius:10px;border:1px solid #16a34a;display:inline-flex;align-items:center;gap:10px;cursor:pointer;margin-right:6px;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Back to previous page</a>
                        @endif
                        <a href="{{ route('users.edit', $user) }}" style="background:#e0f2fe;color:#0369a1;padding:12px 16px;border-radius:10px;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Edit</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
