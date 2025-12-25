<div class="node" data-role-id="{{ $role->id }}">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <strong>{{ $role->name }}</strong>
            @if($role->description)
                <span class="muted">- {{ $role->description }}</span>
            @endif
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('roles.edit', $role->id) }}" class="btn small">Edit</a>
            @can('roles.delete')
            <form method="POST" action="{{ route('roles.destroy', $role->id) }}" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn small danger">Delete</button>
            </form>
            @endcan
        </div>
    </div>

    @if($role->children && $role->children->count())
        <div class="children">
            @foreach($role->children as $child)
                @include('roles._hierarchy-item', ['role' => $child])
            @endforeach
        </div>
    @endif
</div>
