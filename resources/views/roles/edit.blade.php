@extends('layouts.dashboard')

@section('title', 'Edit Role')

@section('content')
    @php /** @var \Illuminate\Support\ViewErrorBag $errors */ @endphp
    <div class="header-card">
        <div class="header-left">
            <h2>Edit Role: {{ $role->name }}</h2>
            <p class="muted">Update role information and settings.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-top:12px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('roles.update', $role->id) }}" onsubmit="event.preventDefault(); checkRoleDeactivate(this, '{{ $role->id }}', '{{ addslashes($role->name) }}');">
        @csrf
        @method('PUT')

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:540px;">

                {{-- Global Role Toggle (Read-only display) --}}
                <div>
                    <label style="font-size:15px;font-weight:600;">Role Type</label><br>
                    <div style="margin-top:8px;padding:12px 16px;background:var(--muted-bg,#f3f4f6);border-radius:8px;display:inline-flex;align-items:center;gap:8px;">
                        @if($role->is_global)
                            <span data-feather="globe" style="width:18px;height:18px;color:#3b82f6;"></span>
                            <span style="font-weight:600;">Global Role</span>
                        @else
                            <span data-feather="map-pin" style="width:18px;height:18px;color:#22c55e;"></span>
                            <span style="font-weight:600;">Branch-Specific Role</span>
                        @endif
                    </div>
                    <input type="hidden" name="is_global" value="{{ $role->is_global ? '1' : '0' }}">
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        Role type cannot be changed after creation. To change the type, create a new role.
                    </p>
                </div>

                {{-- Role Name (required) --}}
                <div>
                    <label for="name" style="font-size:15px;font-weight:600;">
                        Role Name
                        <span style="color:#dc2626;margin-left:2px;">*</span>
                    </label><br>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $role->name) }}"
                        required
                        autofocus
                        style="
                            width:100%;
                            padding:14px 16px;
                            border-radius:10px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                        "
                    >
                    @error('name')
                        <div style="color:#dc2626;font-size:13px;">{{ $message }}</div>
                    @enderror
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        A unique name for this role (e.g., "Regional Manager", "Content Editor")
                    </p>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" style="font-size:15px;font-weight:600;">
                        Description
                    </label><br>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        style="
                            width:100%;
                            padding:14px 16px;
                            border-radius:10px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                            resize:vertical;
                        "
                    >{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div style="color:#dc2626;font-size:13px;">{{ $message }}</div>
                    @enderror
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        Brief description of the role's purpose and responsibilities
                    </p>
                </div>

                {{-- Active Status --}}
                <div>
                    <label style="font-size:15px;font-weight:600;">Status</label><br>
                    <div style="
                        display:inline-flex;
                        align-items:center;
                        gap:10px;
                        user-select:none;
                        margin-top:8px;
                    ">
                        <div class="toggle-switch" onclick="toggleCheckbox(event, this)" style="
                            width:48px;
                            height:26px;
                            background:{{ old('is_active', $role->is_active) ? '#22c55e' : '#cbd5e1' }};
                            border-radius:13px;
                            position:relative;
                            transition:all 0.3s;
                            cursor:pointer;
                        ">
                            <div class="toggle-knob" style="
                                width:20px;
                                height:20px;
                                background:white;
                                border-radius:50%;
                                position:absolute;
                                top:3px;
                                left:{{ old('is_active', $role->is_active) ? '25px' : '3px' }};
                                transition:all 0.3s;
                                box-shadow:0 2px 4px rgba(0,0,0,0.2);
                            "></div>
                        </div>
                        <input
                            type="checkbox"
                            id="is_active_checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $role->is_active) ? 'checked' : '' }}
                            style="display:none;"
                        >
                        <span>Active (role can be assigned to users)</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- Action Buttons --}}
        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <button
                type="submit"
                style="
                    background:#22c55e;
                    color:white;
                    padding:14px 24px;
                    border-radius:10px;
                    font-weight:1000;
                    font-size:15px;
                    border:none;
                    display:flex;
                    align-items:center;
                    gap:8px;
                    cursor:pointer;
                "
            >
                <span data-feather="save"></span> Update Role
            </button>

            <a
                href="{{ route('roles.index') }}"
                style="
                    background:#f51607;
                    color:rgb(253, 253, 253);
                    padding:14px 24px;
                    border-radius:10px;
                    font-weight:1000;
                    font-size:15px;
                    text-decoration:none;
                    display:flex;
                    align-items:center;
                    gap:8px;
                "
            >
                <span data-feather="x-circle"></span> Cancel
            </a>
        </div>
    </form>
    @include('partials.confirmation-modal')

@endsection

@push('scripts')
<script>
    function toggleCheckbox(event, toggleSwitch) {
        // Prevent event bubbling
        event.stopPropagation();
        event.preventDefault();

        // Get the checkbox (now it's the next sibling)
        const checkbox = toggleSwitch.nextElementSibling;
        checkbox.checked = !checkbox.checked;

        const knob = toggleSwitch.querySelector('.toggle-knob');

        if (checkbox.checked) {
            toggleSwitch.style.background = '#22c55e';
            knob.style.left = '25px';
        } else {
            toggleSwitch.style.background = '#cbd5e1';
            knob.style.left = '3px';
        }
    }
</script>
<script>
    async function checkRoleDeactivate(form, roleId, roleName) {
        try {
            // Before checking anything, ensure unchecked checkbox is handled
            const checkbox = form.querySelector('#is_active_checkbox');
            if (checkbox && !checkbox.checked) {
                // Add hidden field to ensure 0 is sent for unchecked
                let hiddenField = form.querySelector('input[name="is_active"][type="hidden"]');
                if (!hiddenField) {
                    hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'is_active';
                    hiddenField.value = '0';
                    form.appendChild(hiddenField);
                }
                // Remove the checkbox so it doesn't send value="1"
                checkbox.remove();
            }

            // Determine if the form is attempting to deactivate the role
            const wantsActive = checkbox && checkbox.checked;

            // If still active or no change, just submit
            if (wantsActive) {
                form.submit();
                return;
            }

            const url = `{{ url('/') }}/roles/${roleId}/mapped-active-users`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            const count = parseInt(data.count || 0, 10);

            if (count <= 0) {
                form.submit();
                return;
            }

            let input = form.querySelector('input[name="deactivate_mapped_users"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'deactivate_mapped_users';
                form.appendChild(input);
            }
            input.value = '1';

            showConfirmModal({
                type: 'delete',
                title: 'Confirm Deactivation',
                subtitle: '',
                message: `Role "${roleName}" is assigned to ${count} active user${count===1? '' : 's'}. Confirm deactivation? This will set those users to inactive.`,
                confirmText: 'Deactivate Role',
                form: form
            });
        } catch (e) {
            console.error(e);
            form.submit();
        }
    }
</script>
@endpush
