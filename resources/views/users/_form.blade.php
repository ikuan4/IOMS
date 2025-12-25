{{-- Local form styles for this partial --}}
<style>
    .toggle-switch { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .toggle-switch input { display: none; }
    .toggle-slider { width: 44px; height: 24px; border-radius: 999px; background: #e5e7eb; position: relative; transition: background 0.2s ease; }
    .toggle-slider::before { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 999px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.25); transition: transform 0.2s ease; }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
    .toggle-label-text { font-size: 15px; font-weight: 600; }
    @media (max-width: 768px) { .card > div { max-width: 100% !important; } input[type="text"], input[type="email"], input[type="password"], select { font-size: 16px !important; } button, a[href] { width: 100%; justify-content: center; } }
</style>

@csrf

<div class="card" style="margin-top:16px;">
    <div style="display:flex;flex-direction:column;gap:18px;max-width:540px;">

        <div>
            <label for="name" style="font-size:15px;font-weight:600;">Name <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" >
            @error('name')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="mobile" style="font-size:15px;font-weight:600;">Mobile <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="mobile" name="mobile" type="text" inputmode="numeric" pattern="[0-9]*" required value="{{ old('mobile', $user->mobile) }}" maxlength="10" placeholder="Enter 10 digit mobile number" oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" >
            @error('mobile')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="email" style="font-size:15px;font-weight:600;">Email <span class="muted" style="font-weight:400;">(optional for normal users)</span></label><br>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="off" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
            @error('email')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password" style="font-size:15px;font-weight:600;">Password <span style="color:#dc2626;margin-left:2px;">*</span> @if($user->exists)<span class="muted" style="font-weight:400;">(leave blank to keep)</span>@endif</label><br>
            <input id="password" name="password" type="password" autocomplete="new-password" {{ !$user->exists ? 'required' : '' }} style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" >
            @error('password')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password_confirmation" style="font-size:15px;font-weight:600;">Confirm Password <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="password_confirmation" name="password_confirmation" type="text" autocomplete="off" {{ !$user->exists ? 'required' : '' }} style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
        </div>

        <div>
            <label for="role_id" style="font-size:15px;font-weight:600;">Role <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <select id="role_id" name="role_id" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;background:white;">
                <option value="">-- Select Role --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
            @error('role_id')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <input type="hidden" name="active" value="0">
            <label class="toggle-switch">
                <input id="active" name="active" type="checkbox" value="1" {{ old('active', $user->active ?? true) ? 'checked' : '' }}>
                <span class="toggle-slider"></span>
                <span class="toggle-label-text">Active (enabled)</span>
            </label>
        </div>

        @if($user->exists)
            <hr>
            <div>
                <div class="muted" style="margin-bottom:10px;font-size:14px;">Email bounce count: <strong>{{ $user->email_bounce_count }}</strong><br>Last bounced (IST): <strong>@if($user->email_bounced_at){{ $user->email_bounced_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}@else Never @endif</strong></div>
                <div>
                    <input type="hidden" name="reset_bounce" value="0">
                    <label class="toggle-switch">
                        <input id="reset_bounce" name="reset_bounce" type="checkbox" value="1" {{ old('reset_bounce') ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label-text">Reset bounce counter</span>
                    </label>
                </div>
            </div>
        @endif

        <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" style="background:#22c55e;color:white;padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;border:none;display:flex;align-items:center;gap:8px;cursor:pointer;"><span data-feather="save"></span> Save</button>
            <a href="{{ route('users.index') }}" style="background:#f51607;color:rgb(253,253,253);padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;text-decoration:none;display:flex;align-items:center;gap:8px;"><span data-feather="x-circle"></span> Cancel</a>
        </div>

    </div>
</div>
