@extends('layouts.dashboard')

@section('title', 'Create New Role')

@section('content')
    @php /** @var \Illuminate\Support\ViewErrorBag $errors */ @endphp
    <div class="header-card">
        <div class="header-left">
            <h2>Add Role</h2>
            <p class="muted">Create a new role for access control.</p>
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

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:540px;">

                {{-- Global Role Toggle --}}
                <div>
                    <label style="font-size:15px;font-weight:600;">Role Type <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
                    <div style="display:flex;gap:16px;margin-top:8px;">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="radio" name="is_global" value="1" {{ old('is_global', '0') == '1' ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer;">
                            <span style="font-weight:600;">Global Role</span>
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="radio" name="is_global" value="0" {{ old('is_global', '0') == '0' ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer;">
                            <span style="font-weight:600;">Branch-Specific Role</span>
                        </label>
                    </div>
                    @error('is_global')
                        <div style="color:#dc2626;font-size:13px;">{{ $message }}</div>
                    @enderror
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        Global roles can be assigned to users who manage the entire system. Branch-specific roles are assigned per branch.
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
                        value="{{ old('name') }}"
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
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color:#dc2626;font-size:13px;">{{ $message }}</div>
                    @enderror
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        Brief description of the role's purpose and responsibilities
                    </p>
                </div>

                {{-- Active Status Toggle --}}
                <div>
                    {{-- Hidden field ensures "0" is always submitted if checkbox is unchecked --}}
                    <input type="hidden" name="is_active" value="0">
                    <label class="toggle-switch">
                        <input
                            id="is_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            {{ old('is_active', true) ? 'checked' : '' }}
                        >
                        <span class="toggle-slider"></span>
                        <span class="toggle-label-text">Active Status</span>
                    </label>
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        Inactive roles cannot be assigned to users
                    </p>
                </div>

                {{-- Information Box --}}
                <div style="
                    background:#dbeafe;
                    border:1px solid #93c5fd;
                    border-radius:10px;
                    padding:16px;
                    margin-top:8px;
                ">
                    <div style="display:flex;align-items:start;gap:12px;">
                        <span data-feather="info" style="color:#2563eb;flex-shrink:0;margin-top:2px;"></span>
                        <div>
                            <h6 style="margin:0 0 8px 0;font-size:14px;font-weight:600;color:#1e40af;">Next Steps</h6>
                            <p style="margin:0;font-size:13px;color:#1e40af;line-height:1.5;">
                                After creating the role, you'll be able to:
                            </p>
                            <ul style="margin:8px 0 0 0;padding-left:20px;font-size:13px;color:#1e40af;">
                                <li>Set its position in the role hierarchy</li>
                                <li>Assign specific permissions to control access</li>
                                <li>Assign users to this role</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                    <button
                        type="submit"
                        name="action"
                        value="save_only"
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
                        <span data-feather="save"></span> Save
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

            </div>
        </div>
    </form>

    <style>
        .toggle-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-slider {
            width: 44px;
            height: 24px;
            border-radius: 999px;
            background: #e5e7eb;
            position: relative;
            transition: background 0.2s ease;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
            transition: transform 0.2s ease;
        }

        .toggle-switch input:checked + .toggle-slider {
            background: #22c55e;
        }

        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(20px);
        }

        .toggle-label-text {
            font-size: 15px;
            font-weight: 600;
        }
    </style>
@endsection
