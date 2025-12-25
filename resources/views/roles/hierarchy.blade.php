@extends('layouts.dashboard')

@section('title', 'Role Hierarchy')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Role Hierarchy</h2>
            <p class="muted">Visualize and adjust the organizational role hierarchy.</p>
        </div>
    </div>

    <div class="card" style="margin-top:12px;padding:0;">
        <div style="padding:16px 18px; border-bottom:1px solid rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
                <h5 style="margin:0; font-size:16px; font-weight:600;">Organizational Hierarchy</h5>
                <p style="margin:4px 0 0 0; font-size:13px; opacity:0.7;">Visual representation of role relationships</p>
            </div>

            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:12px;color:#6b7280;font-weight:500;">Zoom:</span>
                <button type="button" onclick="zoomOrganogram('out')" class="btn">Out</button>
                <span id="zoom-level" style="font-size:13px;color:#6b7280;min-width:45px;text-align:center;">100%</span>
                <button type="button" onclick="zoomOrganogram('in')" class="btn">In</button>
                <button type="button" onclick="resetZoom()" class="btn">Fit</button>
            </div>
        </div>

        <div style="padding:18px;overflow-x:auto;">
            <div id="dynamic-legend" style="display:flex;justify-content:center;gap:20px;margin-bottom:18px;flex-wrap:wrap;"></div>

            <div id="organogram" style="display:flex;flex-direction:column;align-items:center;gap:20px;transform:scale(1);transform-origin:center top;transition:transform 0.3s ease;">
                {{-- Existing nested hierarchy will still render for form interactions; organogram is a visual overlay of that structure. --}}
                <div id="hierarchy-root" class="organogram-root" style="width:100%;">
                    @if($roles->count())
                        <div id="sortable-roles">
                            @foreach($roles as $role)
                                @if(!$role->parent_id)
                                    @include('roles._hierarchy-item', ['role' => $role])
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p>No roles defined.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
.organogram { display:flex; flex-direction:column; gap:12px; }
.organogram .node { border:1px solid #e5e7eb; padding:12px; border-radius:8px; background:var(--card); }
.organogram .children { margin-left:20px; margin-top:8px; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('expand-all').addEventListener('click', function(e){
        document.querySelectorAll('.children').forEach(c=> c.style.display='block');
    });
    document.getElementById('collapse-all').addEventListener('click', function(e){
        document.querySelectorAll('.children').forEach(c=> c.style.display='none');
    });
});
</script>
@endpush
