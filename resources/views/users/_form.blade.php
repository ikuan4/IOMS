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
    /* Responsive ordering: left column first on desktop, avatar on the right with fixed width to avoid wrapping/collision */
    .user-form-left { order: 0; flex: 1 1 auto; }
    /* right column should take remaining space so the upload card can be centered within it */
    .user-form-right { order: 1; flex: 1 1 420px; display: flex; align-items: stretch; justify-content: center; }
    /* Row container: avoid wrapping until breakpoint to prevent the avatar jumping around */
    .user-form-row { display:flex; gap:18px; flex-wrap:nowrap; align-items:flex-start; justify-content:flex-start; }
    @media (max-width: 1165px) {
        /* On narrower screens place avatar above the form and make it full width */
        .user-form-row { flex-wrap:wrap; }
        .user-form-right { order: -1; width: 100%; flex: 0 0 auto; }
        .user-form-right > div { width: 100% !important; }
        .user-form-left { order: 0; }
    }
    /* Drag & drop visual state for avatar preview */
    #avatarPreviewContainer { position: relative; }
    #avatarPreviewContainer.dragover { box-shadow: 0 8px 16px rgba(34,197,94,0.08), inset 0 0 0 3px rgba(34,197,94,0.12); transform: translateY(-2px); }
    /* subtle shaded overlay that appears only over the dropzone when dragging files */
    #avatarPreviewContainer.dragover::after { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,0.06); border-radius: 6px; pointer-events: none; }
    #avatarBtn { transition: transform 0.08s ease; }
    #avatarBtn:active { transform: translateY(1px); }
</style>

@csrf

<div class="card" style="margin-top:16px;">
    <div class="user-form-row" style="gap:18px;">

        {{-- Left column: main fields --}}
        <div class="user-form-left" style="flex:1 1 540px;min-width:260px;max-width:720px;display:flex;flex-direction:column;gap:18px;">

        <div>
            <label for="name" style="font-size:15px;font-weight:600;">Name <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name', $user->name) }}" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" >
            @error('name')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="mobile" style="font-size:15px;font-weight:600;">Mobile <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="mobile" name="mobile" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" required value="{{ old('mobile', $user->mobile) }}" maxlength="10" placeholder="Enter 10 digit mobile number" oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" >
            @error('mobile')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="email" style="font-size:15px;font-weight:600;">Email <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
            @error('email')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password" style="font-size:15px;font-weight:600;">Password <span style="color:#dc2626;margin-left:2px;">*</span> @if($user->exists)<span class="muted" style="font-weight:400;">(leave blank to keep)</span>@endif</label><br>
            <input id="password" name="password" type="password" autocomplete="new-password" {{ !$user->exists ? 'required' : '' }} style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" >
            @error('password')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password_confirmation" style="font-size:15px;font-weight:600;">Confirm Password <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <input id="password_confirmation" name="password_confirmation" type="text" autocomplete="new-password" {{ !$user->exists ? 'required' : '' }} style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
        </div>

        <div>
            <label for="role_id" style="font-size:15px;font-weight:600;">Role <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
            <select id="role_id" name="role_id" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;background:white;">
                <option value="">-- Select Role --</option>
                @foreach($roles as $role)
                    @php
                        $isProtectedRole = method_exists($role, 'isSuperAdmin') && $role->isSuperAdmin();
                        $currentIsSuperAdmin = auth()->user() && method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin();
                    @endphp
                    @if($isProtectedRole && !$currentIsSuperAdmin)
                        @continue
                    @endif
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

        {{-- Right column: optional avatar upload/preview --}}
        <div class="user-form-right" style="min-width:260px;display:flex;align-items:stretch;justify-content:center;flex:1 1 420px;">
            <div style="padding:8px;border-radius:10px;background:var(--card, #fff);text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;width:420px;box-sizing:border-box;margin:0 auto;">
                <div style="margin-bottom:12px;font-weight:700;font-size:15px;">User Photo (optional)</div>
                <div id="avatarPreviewContainer" style="width:320px;height:320px;margin:0 auto;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--muted-bg,#f3f4f6);">
                    <img id="avatarPreview" src="{{ old('avatar_preview', $user->avatar ? asset('storage/'.$user->avatar) : '') }}" alt="{{ $user->name }} avatar" style="max-width:100%;max-height:100%;display:block;object-fit:cover;" />
                    <div id="avatarIcon" style="position:absolute;display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
                        <!-- Colored cloud upload icon (blue cloud + solid white arrow) -->
                        <svg width="192" height="144" viewBox="0 0 96 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <g fill-rule="evenodd">
                                <path d="M72.6 32.8c-1.6-9.6-9.8-17-19.8-17-6.6 0-12.5 3.6-15.6 8.8-2.7-1.6-5.9-2.6-9.3-2.6-9.6 0-17.4 7.6-17.4 17 0 .8.1 1.6.2 2.4C4.9 42.2 0 47.8 0 54.6 0 63 7.2 69 16 69h56c11 0 20-8.6 20-19.2 0-9.8-7.2-17.1-20.4-17z" fill="#00B5F1"/>
                                <!-- Large solid white upload arrow centered in cloud (moved up by 10% of arrow height) -->
                                <polygon points="48,30.8 34,46.8 42,46.8 42,62.8 54,62.8 54,46.8 62,46.8" fill="#fff" />
                            </g>
                        </svg>
                    </div>
                </div>
                <div style="margin-top:12px;display:flex;flex-direction:column;align-items:center;gap:8px;">
                    <div style="display:flex;flex-direction:row;align-items:center;gap:8px;">
                        <label for="avatar" id="avatarBtn" style="background:#22c55e;color:#ffffff;padding:10px 18px;border-radius:10px;font-weight:700;cursor:pointer;display:inline-block;font-size:15px;box-shadow:0 6px 0 rgba(0,0,0,0.08);">Choose Photo</label>
                        <button type="button" id="removeAvatarBtn" style="background:#ef4444;color:#ffffff;padding:10px 12px;border-radius:10px;font-weight:700;cursor:pointer;display:inline-block;font-size:15px;border:none;">Remove Photo</button>
                    </div>
                    <input id="avatar" name="avatar" type="file" accept="image/*" style="position:absolute;left:-9999px;" />
                    <input type="hidden" name="remove_avatar" id="remove_avatar" value="0" />
                    <div id="avatarFilename" style="font-size:13px;color:var(--muted,#6b7280);{{ $user->avatar ? 'display:block;' : 'display:none;' }}" data-initial-filename="{{ $user->avatar ? basename($user->avatar) : '' }}">{{ $user->avatar ? basename($user->avatar) : '' }}</div>
                </div>
                <div style="margin-top:10px;font-size:13px;color:var(--muted,#6b7280);">Supported files: JPG, PNG. Max 2MB.</div>
            </div>
        </div>

    </div>
</div>

<script>
    (function(){
        const input = document.getElementById('avatar');
        const img = document.getElementById('avatarPreview');
        const container = document.getElementById('avatarPreviewContainer');

        // Holds a dropped File when the browser prevents assigning to input.files
        let pendingAvatarFile = null;

        // If preview image not present, show inline SVG icon; otherwise hide icon.
        const icon = document.getElementById('avatarIcon');
        function showIcon() { if (icon) icon.style.display = 'flex'; if (img) img.style.display = 'none'; }
        function hideIcon() { if (icon) icon.style.display = 'none'; if (img) img.style.display = 'block'; }
        if (!img.getAttribute('src')) { showIcon(); } else { hideIcon(); }

        if (!input) return;
        const filenameEl = document.getElementById('avatarFilename');
        const avatarBtn = document.getElementById('avatarBtn');

        function handleFile(file) {
            if (!file) {
                if (filenameEl) { filenameEl.textContent = ''; filenameEl.style.display = 'none'; }
                showIcon();
                pendingAvatarFile = null;
                return;
            }
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                input.value = '';
                if (filenameEl) { filenameEl.textContent = ''; filenameEl.style.display = 'none'; }
                showIcon();
                return;
            }
            if (file.size > 2 * 1024 * 1024) { // 2MB
                alert('Image is too large. Max 2MB.');
                input.value = '';
                if (filenameEl) { filenameEl.textContent = ''; filenameEl.style.display = 'none'; }
                showIcon();
                return;
            }
            if (filenameEl) { filenameEl.textContent = file.name; filenameEl.style.display = 'block'; }
            const reader = new FileReader();
            reader.onload = function(ev){ img.src = ev.target.result; hideIcon(); };
            reader.readAsDataURL(file);

            // reset remove flag when user picks a file
            try { document.getElementById('remove_avatar').value = '0'; } catch(e){}

            // try to set the file into the hidden input for form submit
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                // if assigning succeeded, clear any pending file
                pendingAvatarFile = null;
            } catch (err) {
                // ignore if not supported
                // keep the file in pendingAvatarFile so we can submit it manually
                pendingAvatarFile = file;
            }
        }

        input.addEventListener('change', function(e){ handleFile(input.files && input.files[0]); });

        // make label trigger file dialog for accessibility (label[for] already binds, but ensure keyboard support)
        if (avatarBtn) {
            avatarBtn.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
        }

        const removeAvatarBtn = document.getElementById('removeAvatarBtn');
        if (removeAvatarBtn) {
            removeAvatarBtn.addEventListener('click', function(e){
                try {
                    // mark for removal
                    const rem = document.getElementById('remove_avatar'); if (rem) rem.value = '1';
                    // clear file input and preview
                    try { input.value = ''; } catch(e){}
                    pendingAvatarFile = null;
                    if (filenameEl) { filenameEl.textContent = ''; filenameEl.style.display = 'none'; }
                    if (img) { img.src = ''; }
                    showIcon();
                } catch (err) { dbg('removeAvatar error', err); }
            });
        }

        // Prevent default document-level drag/drop to avoid browser navigating/opening files (page flicker)
        ['dragenter','dragover','dragleave','drop'].forEach(evt => {
            document.addEventListener(evt, function(e){ e.preventDefault(); }, false);
        });

        // drag & drop support limited to the preview container — show shaded overlay instead of page flicker
        if (container) {
            container.addEventListener('dragenter', function(e){ e.preventDefault(); container.classList.add('dragover'); });
            container.addEventListener('dragover', function(e){ e.preventDefault(); container.classList.add('dragover'); });
            container.addEventListener('dragleave', function(e){ e.preventDefault(); container.classList.remove('dragover'); });
            container.addEventListener('drop', function(e){
                e.preventDefault();
                container.classList.remove('dragover');
                const files = e.dataTransfer && e.dataTransfer.files;
                const file = files && files[0];
                // try to attach the FileList to the real input first (some browsers accept this)
                try {
                    if (files && input) input.files = files;
                } catch (err) {
                    // ignore if not supported
                }
                if (file) {
                    handleFile(file);
                    // also keep it available for fallback submit
                    pendingAvatarFile = file;
                }
            });
        }

        // Intercept form submit and always post via fetch so we can inspect server response
        (function attachFormInterceptor(){
            let form = document.querySelector('form.user-form') || document.querySelector('form');
            if (!form) return;
            form.addEventListener('submit', function(e){
                try {
                    const hasFile = input && input.files && input.files.length > 0;
                    dbg('submit triggered', { hasFile, pendingAvatarFile });

                    // Only intercept submit when we have a pendingAvatarFile fallback (browser blocked assigning input.files)
                    if (!pendingAvatarFile) {
                        dbg('no pending file; allowing native form submit');
                        return; // allow normal submission so Laravel can flash and redirect normally
                    }

                    e.preventDefault();
                    const fd = new FormData(form);

                    // If input has files, ensure they are included; otherwise attach pendingAvatarFile if available
                    if (!(hasFile) && pendingAvatarFile) {
                        let fileToSend = pendingAvatarFile;
                        try {
                            if (!fileToSend.type) {
                                const name = (fileToSend.name || '').toLowerCase();
                                let guessed = '';
                                if (name.endsWith('.jpg') || name.endsWith('.jpeg')) guessed = 'image/jpeg';
                                else if (name.endsWith('.png')) guessed = 'image/png';
                                else if (name.endsWith('.gif')) guessed = 'image/gif';
                                else if (name.endsWith('.webp')) guessed = 'image/webp';
                                if (guessed) {
                                    try { fileToSend = new File([fileToSend], fileToSend.name, { type: guessed }); } catch (err) { dbg('File ctor failed', err); }
                                }
                            }
                        } catch (err) { dbg('mime-guess error', err); }
                        fd.set('avatar', fileToSend, fileToSend.name);
                    } else if (hasFile) {
                        // ensure the file from the input is present in FormData (it should be already)
                        dbg('input.files present', input.files[0]);
                    }

                    dbg('posting FormData via fetch', { action: form.action, method: form.method || 'POST' });
                    fetch(form.action, {
                        method: form.method || 'POST',
                        body: fd,
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'fetch' }
                    }).then(function(resp){
                        dbg('fetch response', { status: resp.status, redirected: resp.redirected });
                        return resp.text().then(function(txt){
                            dbg('server response text', txt.slice(0, 2000));

                            // If server redirected to another URL, follow it
                            if (resp.redirected && resp.url) { window.location = resp.url; return; }

                            // On success (200) but no redirect provided, navigate to the users index
                            // to ensure flash messages are shown reliably.
                            if (resp.ok) { window.location = "{{ route('users.index') }}"; return; }

                            // Otherwise try to show any server-rendered notification HTML returned
                            try {
                                if (txt && txt.indexOf('notification-toast') !== -1) {
                                    const container = document.createElement('div');
                                    container.innerHTML = txt;
                                    const newToast = container.querySelector('.notification-toast');
                                    if (newToast) {
                                        const existing = document.querySelector('.notification-toast');
                                        if (existing) existing.remove();
                                        document.body.appendChild(newToast);
                                        setTimeout(()=> newToast.classList.add('show'), 50);
                                        setTimeout(()=> { try { newToast.remove(); } catch(e){} }, 30000);
                                        return;
                                    }
                                }
                            } catch (e) { dbg('toast-inject error', e); }

                            console.error('[avatar-upload] server error', resp.status, txt);
                            alert('Upload failed. See console for server response.');
                        });
                    }).catch(function(err){ dbg('fetch error', err); alert('Upload failed (network). See console for details.'); });

                } catch (err) { dbg('submit handler error', err); }
            }, { passive: false });
        })();
    })();
</script>
