@extends('layouts.dashboard')

@section('title', 'Edit Role')

@section('content')
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

    <form method="POST" action="{{ route('roles.update', $role->id) }}">
        @csrf
        @method('PUT')

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:540px;">

                {{-- Branch selection for Developers; hidden input for others --}}
                @php
                    $currentUser = Auth::user();
                    $isDeveloper = $currentUser && $currentUser->isSuperAdmin();
                @endphp
                @if($isDeveloper)
                    <div>
                        <label for="branch_id" style="font-size:15px;font-weight:600;">Branch</label><br>
                        <select name="branch_id" id="branch_id" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                            <option value="">-- Select Branch (optional) --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (old('branch_id', $role->branch_id) == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div style="color:#dc2626;font-size:13px;">{{ $message }}</div>
                        @enderror
                        <p class="muted" style="font-size:13px;margin-top:6px;">Assign this role to a specific branch. If left empty, role applies globally.</p>
                    </div>
                @else
                    <input type="hidden" name="branch_id" value="{{ Auth::user()->branch_id }}">
                @endif

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
                        <input type="hidden" name="is_active" value="0">
                        <input
                            type="checkbox"
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
@endsection

@push('scripts')
<script>
    function toggleCheckbox(event, toggleSwitch) {
        // Prevent event bubbling
        event.stopPropagation();
        event.preventDefault();

        // Skip the hidden input and get the checkbox
        const checkbox = toggleSwitch.nextElementSibling.nextElementSibling;
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
@endpush
