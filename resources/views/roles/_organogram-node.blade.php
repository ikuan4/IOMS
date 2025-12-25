<div class="organogram-node" data-id="{{ $role->id }}">
    <div class="organogram-node-inner">
        <div class="organogram-node-title">{{ $role->name }}</div>
        @if($role->description)
            <div class="organogram-node-desc">{{ $role->description }}</div>
        @endif
    </div>
    @if($role->children && $role->children->count())
        <div class="organogram-node-children">
            @foreach($role->children as $child)
                @include('roles._organogram-node', ['role' => $child])
            @endforeach
        </div>
    @endif
</div>

<style>
.organogram-node { border:1px solid #e5e7eb; padding:8px; border-radius:6px; background:var(--card); }
.organogram-node-children { margin-top:8px; margin-left:12px; }
.organogram-node-title { font-weight:700; }
.organogram-node-desc { color:#6b7280; font-size:13px; }
</style>
