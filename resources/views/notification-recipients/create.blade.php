@extends('layouts.dashboard')

@section('title', 'Create Notification Recipient')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Add Notification Recipient</h2>
            <p class="muted">Create a new recipient for contract notifications.</p>
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

    <form method="POST" action="{{ route('notification-recipients.store') }}">
        @csrf

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:600px;">

                <div>
                    <label for="name" style="font-size:15px;font-weight:600;">Name <span style="color:#dc2626;">*</span></label><br>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., John Doe"
                    >
                    @error('name')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="designation" style="font-size:15px;font-weight:600;">Designation</label><br>
                    <input
                        type="text"
                        name="designation"
                        id="designation"
                        value="{{ old('designation') }}"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., Contract Manager"
                    >
                    @error('designation')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="email" style="font-size:15px;font-weight:600;">Email <span style="color:#dc2626;">*</span></label><br>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., john@example.com"
                    >
                    @error('email')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="mobile" style="font-size:15px;font-weight:600;">Mobile</label><br>
                    <input
                        type="text"
                        name="mobile"
                        id="mobile"
                        value="{{ old('mobile') }}"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., +91 9876543210"
                    >
                    @error('mobile')
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label style="display:flex;align-items:center;cursor:pointer;gap:10px;">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                            style="width:20px;height:20px;cursor:pointer;"
                        >
                        <span style="font-size:15px;font-weight:500;">Active</span>
                    </label>
                    <p class="muted" style="font-size:13px;margin-top:6px;">Only active recipients will receive notifications.</p>
                </div>

            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:12px;">
            <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border:none;border-radius:10px;font-size:15px;font-weight:500;cursor:pointer;">
                Create Recipient
            </button>
            <a href="{{ route('notification-recipients.index') }}" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:15px;font-weight:500;">
                Cancel
            </a>
        </div>
    </form>
@endsection
