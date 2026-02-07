@extends('layouts.dashboard')

@section('title', 'Edit Profile')

<style>
    /* Drag & drop visual state for avatar preview */
    #avatarPreviewContainer {
        position: relative;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    #avatarPreviewContainer.dragover {
        box-shadow: 0 8px 16px rgba(34,197,94,0.08), inset 0 0 0 3px rgba(34,197,94,0.12);
        transform: translateY(-2px);
    }
    /* Subtle shaded overlay that appears only over the dropzone when dragging files */
    #avatarPreviewContainer.dragover::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.06);
        border-radius: 6px;
        pointer-events: none;
    }
    #avatarBtn {
        transition: transform 0.08s ease;
    }
    #avatarBtn:active {
        transform: translateY(1px);
    }
    @media (max-width: 1165px) {
        .profile-form-row {
            flex-wrap: wrap;
        }
        .profile-form-right {
            order: -1;
            width: 100%;
            flex: 0 0 auto;
        }
        .profile-form-right > div {
            width: 100% !important;
        }
        .profile-form-left {
            order: 0;
        }
    }
</style>

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>My Profile</h2>
            <p class="muted">Update your photo, name, email and password.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="card" style="margin-top:12px;background:#d1fae5;border:1px solid #86efac;color:#166534;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="margin-top:12px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @method('PUT')
        @csrf

        <div class="card" style="margin-top:16px;">
            <div class="profile-form-row" style="display:flex;gap:18px;flex-wrap:nowrap;align-items:flex-start;">

                {{-- Left column: basic fields --}}
                <div class="profile-form-left" style="flex:1 1 540px;min-width:260px;max-width:720px;display:flex;flex-direction:column;gap:18px;">

                    <div>
                        <label for="name" style="font-size:15px;font-weight:600;">Name <span style="color:#dc2626;margin-left:2px;">*</span></label><br>
                        <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name', $user->name) }}" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        @error('name')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="mobile" style="font-size:15px;font-weight:600;">Mobile</label><br>
                        <input id="mobile" name="mobile" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" value="{{ old('mobile', $user->mobile) }}" maxlength="20" placeholder="Enter mobile number" oninput="this.value = this.value.replace(/[^0-9]/g, '')" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        @error('mobile')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="email" style="font-size:15px;font-weight:600;">Email</label><br>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        @error('email')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="password" style="font-size:15px;font-weight:600;">New Password <span class="muted" style="font-weight:400;">(leave blank to keep current)</span></label><br>
                        <input id="password" name="password" type="password" autocomplete="new-password" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                        @error('password')<div style="color:#dc2626;font-size:13px;">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" style="font-size:15px;font-weight:600;">Confirm Password</label><br>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                    </div>

                    <div style="padding:12px;background:var(--warning-bg,#fef3c7);border-radius:8px;border:1px solid var(--warning-border,#fbbf24);color:var(--warning-text,#78350f);">
                        <strong>Note:</strong> Your role and branch cannot be changed via this form.
                    </div>

                    <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="submit" style="background:#22c55e;color:white;padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;border:none;display:flex;align-items:center;gap:8px;cursor:pointer;"><span data-feather="save"></span> Save Profile</button>
                        <a href="{{ route('dashboard') }}" style="background:#64748b;color:rgb(253,253,253);padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;text-decoration:none;display:flex;align-items:center;gap:8px;"><span data-feather="arrow-left"></span> Back to Dashboard</a>
                    </div>

                </div>

                {{-- Right column: avatar --}}
                <div class="profile-form-right" style="min-width:260px;display:flex;align-items:stretch;justify-content:center;flex:1 1 420px;">
                    <div style="padding:8px;border-radius:10px;background:var(--card, #fff);text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;width:420px;box-sizing:border-box;margin:0 auto;">
                        <div style="margin-bottom:12px;font-weight:700;font-size:15px;">Profile Photo</div>
                        <div id="avatarPreviewContainer" style="width:320px;height:320px;margin:0 auto;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--muted-bg,#f3f4f6);position:relative;">
                            <img id="avatarPreview" src="{{ $user->avatar_url ?? '' }}" alt="{{ $user->name }} avatar" style="max-width:100%;max-height:100%;display:{{ !empty($user->avatar_url) ? 'block' : 'none' }};object-fit:cover;" />
                            <div id="avatarIcon" style="position:absolute;display:{{ !empty($user->avatar_url) ? 'none' : 'flex' }};align-items:center;justify-content:center;width:100%;height:100%;">
                                <!-- Colored cloud upload icon -->
                                <svg width="192" height="144" viewBox="0 0 96 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <g fill-rule="evenodd">
                                        <path d="M72.6 32.8c-1.6-9.6-9.8-17-19.8-17-6.6 0-12.5 3.6-15.6 8.8-2.7-1.6-5.9-2.6-9.3-2.6-9.6 0-17.4 7.6-17.4 17 0 .8.1 1.6.2 2.4C4.9 42.2 0 47.8 0 54.6 0 63 7.2 69 16 69h56c11 0 20-8.6 20-19.2 0-9.8-7.2-17.1-20.4-17z" fill="#00B5F1"/>
                                        <polygon points="48,30.8 34,46.8 42,46.8 42,62.8 54,62.8 54,46.8 62,46.8" fill="#fff" />
                                    </g>
                                </svg>
                            </div>
                        </div>
                        <div style="margin-top:12px;display:flex;flex-direction:column;align-items:center;gap:8px;">
                            <div style="display:flex;flex-direction:row;align-items:center;gap:8px;">
                                <label for="avatar" id="avatarBtn" style="background:#22c55e;color:#ffffff;padding:10px 18px;border-radius:10px;font-weight:700;cursor:pointer;display:inline-block;font-size:15px;">Choose Photo</label>
                                <button type="button" id="removeAvatarBtn" style="background:#ef4444;color:#ffffff;padding:10px 12px;border-radius:10px;font-weight:700;cursor:pointer;display:inline-block;font-size:15px;border:none;">Remove Photo</button>
                            </div>
                            <input id="avatar" name="avatar" type="file" accept="image/*" style="position:absolute;left:-9999px;" />
                            <input type="hidden" name="remove_avatar" id="remove_avatar" value="0" />
                            <div id="avatarFilename" style="font-size:13px;color:var(--muted,#6b7280);display:{{ !empty($user->avatar_url) ? 'block' : 'none' }};">{{ !empty($user->avatar_url) ? basename(parse_url($user->avatar_url, PHP_URL_PATH) ?? '') : '' }}</div>
                        </div>
                        <div style="margin-top:10px;font-size:13px;color:var(--muted,#6b7280);">Drag and drop an image or click to upload. JPG, PNG. Max 2MB.</div>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <script>
        // Avatar preview and drag-and-drop logic
        const formEl = document.querySelector('form[action="{{ route('profile.update') }}"]');
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        const avatarIcon = document.getElementById('avatarIcon');
        const avatarFilename = document.getElementById('avatarFilename');
        const removeAvatarBtn = document.getElementById('removeAvatarBtn');
        const removeAvatarInput = document.getElementById('remove_avatar');
        const avatarPreviewContainer = document.getElementById('avatarPreviewContainer');

        let previewObjectUrl = null;

        function setPreviewObjectUrl(file) {
            if (!file || !file.type || !file.type.startsWith('image/')) return;

            try {
                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    previewObjectUrl = null;
                }
            } catch (e) {}

            previewObjectUrl = URL.createObjectURL(file);
            avatarPreview.src = previewObjectUrl;
            avatarPreview.style.display = 'block';
            avatarIcon.style.display = 'none';
            avatarFilename.textContent = file.name;
            avatarFilename.style.display = 'block';
            removeAvatarInput.value = '0';
        }

        // File preview helper (FILE-ONLY; no base64 handling)
        function previewFile(file) {
            setPreviewObjectUrl(file);
        }

        // File input change
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Keep in sync with server validation: max 2MB
                const maxBytes = 2 * 1024 * 1024;
                if (file.size && file.size > maxBytes) {
                    alert('Please choose an image up to 2MB.');
                    avatarInput.value = '';
                    return;
                }
                previewFile(file);
            }
        });

        // Remove avatar
        removeAvatarBtn.addEventListener('click', function() {
            try {
                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    previewObjectUrl = null;
                }
            } catch (e) {}
            avatarPreview.src = '';
            avatarPreview.style.display = 'none';
            avatarIcon.style.display = 'flex';
            avatarFilename.style.display = 'none';
            avatarInput.value = '';
            removeAvatarInput.value = '1';
        });

        // Drag and drop events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            avatarPreviewContainer.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when dragging over
        ['dragenter', 'dragover'].forEach(eventName => {
            avatarPreviewContainer.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            avatarPreviewContainer.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            avatarPreviewContainer.classList.add('dragover');
        }

        function unhighlight(e) {
            avatarPreviewContainer.classList.remove('dragover');
        }

        // Handle dropped files
        avatarPreviewContainer.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                const file = files[0];

                // Keep in sync with server validation: max 2MB
                const maxBytes = 2 * 1024 * 1024;
                if (file.size && file.size > maxBytes) {
                    alert('Please choose an image up to 2MB.');
                    return;
                }

                // Create a new FileList-like object and assign to input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                avatarInput.files = dataTransfer.files;

                // Preview the file
                previewFile(file);
            }
        }

        // Enforce FILE-ONLY avatar submissions.
        // Removes any accidental hidden/text "avatar" fields (e.g. base64 strings) before submit.
        if (formEl) {
            formEl.addEventListener('submit', function() {
                try {
                    const badAvatarFields = formEl.querySelectorAll('input[name="avatar"]:not([type="file"]), textarea[name="avatar"], select[name="avatar"]');
                    badAvatarFields.forEach(el => { try { el.remove(); } catch (e) {} });
                } catch (e) {}
            });
        }
    </script>
@endsection
