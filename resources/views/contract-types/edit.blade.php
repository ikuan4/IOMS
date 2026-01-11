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
                    <label style="display:flex;align-items:center;cursor:pointer;gap:10px;">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $contractType->is_active) == '1' ? 'checked' : '' }}
                            style="width:20px;height:20px;cursor:pointer;"
                        >
                        <span style="font-size:15px;font-weight:500;">Active</span>
                    </label>
                    <p class="muted" style="font-size:13px;margin-top:6px;">Only active contract types can be used for new contracts.</p>
                </div>

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
