@extends('layouts.dashboard')

@section('title', 'Edit Contract Type')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Edit Contract Type</h2>
            <p class="muted">Update contract type information.</p>
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

    <form method="POST" action="{{ route('contract-types.update', $contractType->id) }}">
        @csrf
        @method('PUT')

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:600px;">

                <div>
                    <label for="name" style="font-size:15px;font-weight:600;">Name <span style="color:#dc2626;">*</span></label><br>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $contractType->name) }}"
                        required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., Service Agreement"
                    >
                    @error('name')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                    <p class="muted" style="font-size:13px;margin-top:6px;">Current code: <strong>{{ $contractType->code }}</strong>. Code will be regenerated if name changes.</p>
                </div>

                <div>
                    <label for="description" style="font-size:15px;font-weight:600;">Description</label><br>
                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="Optional description of this contract type"
                    >{{ old('description', $contractType->description) }}</textarea>
                    @error('description')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label style="font-size:15px;font-weight:500;margin-bottom:8px;display:block;">Status</label>
                    <label class="toggle-switch" style="display:inline-flex;align-items:center;cursor:pointer;gap:12px;">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $contractType->is_active) == '1' ? 'checked' : '' }}
                            class="toggle-input"
                        >
                        <span class="toggle-slider"></span>
                        <span class="toggle-label" style="font-size:15px;font-weight:500;">Active</span>
                    </label>
                    <p class="muted" style="font-size:13px;margin-top:6px;">Only active contract types can be used for new contracts.</p>
                </div>

                <style>
                    .toggle-switch { position: relative; }
                    .toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
                    .toggle-slider {
                        position: relative;
                        display: inline-block;
                        width: 48px;
                        height: 24px;
                        background-color: #cbd5e1;
                        border-radius: 24px;
                        transition: background-color 0.3s;
                    }
                    .toggle-slider::before {
                        content: '';
                        position: absolute;
                        width: 18px;
                        height: 18px;
                        left: 3px;
                        top: 3px;
                        background-color: white;
                        border-radius: 50%;
                        transition: transform 0.3s;
                    }
                    .toggle-input:checked + .toggle-slider {
                        background-color: #22c55e;
                    }
                    .toggle-input:checked + .toggle-slider::before {
                        transform: translateX(24px);
                    }
                    .toggle-input:focus + .toggle-slider {
                        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
                    }
                </style>

            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:12px;">
            <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border:none;border-radius:10px;font-size:15px;font-weight:500;cursor:pointer;">
                Update Contract Type
            </button>
            <a href="{{ route('contract-types.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:15px;font-weight:500;">
                Cancel
            </a>
        </div>
    </form>

    @if (session('success'))
        <div style="position:fixed;bottom:20px;right:20px;background:#10b981;color:#fff;padding:16px 24px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;">
            {{ session('success') }}
        </div>
        <script>
            setTimeout(() => {
                const alert = document.querySelector('[style*="position:fixed"]');
                if(alert) alert.remove();
            }, 3000);
        </script>
    @endif
@endsection
