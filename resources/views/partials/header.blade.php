<header class="global-header"
        style="position:relative; display:flex; align-items:center; justify-content:space-between; padding:20px 32px; background:var(--card); box-shadow:0 6px 24px rgba(0,0,0,0.07); z-index:100;">

    <style>
        /* Scoped header dropdown styles to match MSHCS layout */
        .user-menu { position:relative; }
        .user-menu-toggle { display:inline-flex; gap:10px; align-items:center; background:transparent; border:0; padding:6px 8px; cursor:pointer; color:var(--text); }
        .user-menu .avatar { width:40px; height:40px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:var(--accent); color:white; font-weight:700; }
        .user-menu .user-name { margin-left:6px; font-weight:700; color:var(--text); }
        /* Make the sidebar toggle (hamburger) theme-aware */
        #sidebarToggle { color: var(--text); }
        #sidebarToggle svg { stroke: currentColor; fill: none; }
        /* Ensure feather/inline svgs inherit theme color */
        .user-menu-toggle svg, .user-menu .user-menu-caret svg, .user-menu-dropdown .dropdown-item svg { stroke: currentColor; fill: none; }
        .user-menu-dropdown { position:absolute; right:0; top:52px; background:var(--card); border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.12); min-width:220px; overflow:hidden; display:none; z-index:200; }
        .user-menu-dropdown.open { display:block; }
        .user-menu-dropdown .dropdown-item { display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--text); text-decoration:none; font-weight:600; }
        .user-menu-dropdown .dropdown-item:hover { background:rgba(0,0,0,0.04); }
        .user-menu-dropdown form { margin:0; }
        .user-menu-dropdown button.dropdown-item { width:100%; text-align:left; border:0; background:transparent; padding:10px 14px; }
        .user-menu-dropdown .dropdown-divider { height:1px; background:rgba(0,0,0,0.06); margin:6px 0; }
    </style>

    {{-- LEFT: LOGO + SIDEBAR TOGGLE --}}
    <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">

        {{-- LOGO --}}
        <a href="{{ route('dashboard') }}" style="display:flex; align-items:center;">
            <img src="{{ asset('images/MSHCS_logo.png') }}"
                 alt="MSHCS Logo"
                 style="height:60px; width:auto; cursor:pointer;">
        </a>

        {{-- SIDEBAR TOGGLE (square button, hamburger) --}}
        <button id="sidebarToggle"
                title="Toggle menu"
                aria-label="Toggle sidebar"
                style="display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:8px; border:0; background:var(--card); box-shadow:var(--shadow); cursor:pointer;">
            <!-- simple hamburger -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 7h18M3 12h18M3 17h18"></path>
            </svg>
        </button>
    </div>

    {{-- CENTER: TITLE --}}
    <div style="flex:1; display:flex; justify-content:center; padding:0 12px; min-width:0;">
        <div id="cms-title"
             style="font-size:26px; font-weight:800; color:var(--accent); letter-spacing:0.5px; text-transform:uppercase; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            INTEGRATED OFFICE MANAGEMENT SYSTEM (IOMS)
        </div>
    </div>

    {{-- RIGHT SECTION --}}
    <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">

        {{-- Branch Switcher (for branch users only) --}}
        @auth
        @if(!auth()->user()->global_role_id && auth()->user()->branches->count() > 0)
            <div class="branch-switcher" style="position:relative;">
                <button
                    type="button"
                    class="branch-switcher-toggle"
                    id="branchSwitcherToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                    style="display:inline-flex; align-items:center; gap:8px; background:var(--card); border:1px solid var(--border, #e5e7eb); padding:8px 12px; border-radius:8px; cursor:pointer; color:var(--text); font-weight:600; font-size:14px;"
                >
                    <span data-feather="briefcase" style="width:18px;height:18px;"></span>
                    <span id="activeBranchName">
                        @php
                            $activeBranch = auth()->user()->branches->firstWhere('id', session('active_branch_id'));
                        @endphp
                        {{ $activeBranch ? $activeBranch->name : 'Select Branch' }}
                    </span>
                    <span data-feather="chevron-down" style="width:16px;height:16px;"></span>
                </button>

                <div class="branch-switcher-dropdown" id="branchSwitcherDropdown" role="menu" style="position:absolute; right:0; top:52px; background:var(--card); border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.12); min-width:220px; overflow:hidden; display:none; z-index:200;">
                    <div style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,0.06); font-weight:700; font-size:13px; color:var(--muted, #6b7280);">
                        SWITCH BRANCH
                    </div>
                    @foreach(auth()->user()->branches as $branch)
                        <form method="POST" action="{{ route('branches.switch.post') }}" style="margin:0;">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                            <button
                                type="submit"
                                class="dropdown-item"
                                role="menuitem"
                                style="width:100%; text-align:left; display:flex; align-items:center; gap:10px; padding:10px 14px; color:var(--text); border:0; background:{{ session('active_branch_id') == $branch->id ? 'rgba(34,197,94,0.1)' : 'transparent' }}; font-weight:600; cursor:pointer;"
                            >
                                @if(session('active_branch_id') == $branch->id)
                                    <span data-feather="check-circle" style="width:18px;height:18px;color:#22c55e;"></span>
                                @else
                                    <span data-feather="circle" style="width:18px;height:18px;"></span>
                                @endif
                                <span>{{ $branch->name }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif
        @endauth

        {{-- Theme Toggle --}}
        <button id="themeToggle" class="theme-toggle" aria-pressed="false" title="Toggle theme">
            <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4"></path>
                <path d="M17.7 17.7l1.4 1.4M2 12h2M20 12h2"></path>
            </svg>

            <svg id="icon-moon" style="display:none" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>

        {{-- User menu (avatar + name + dropdown) --}}
        @php
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
        @endphp
        @auth
            <div class="user-menu">
                <button
                    type="button"
                    class="user-menu-toggle"
                    id="userMenuToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <div class="avatar">
                        @if($user->avatar)
                            <img src="{{ asset('storage/'.$user->avatar) }}" alt="{{ $user->name }}'s avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" />
                        @else
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        @endif
                    </div>
                    <span class="user-name">
                        {{ $user->name }}
                    </span>
                    <span class="user-menu-caret" data-feather="chevron-down"></span>
                </button>

                <div class="user-menu-dropdown" id="userMenuDropdown" role="menu">

                    {{-- Self profile --}}
                    <a href="{{ route('profile.edit') }}" class="dropdown-item" role="menuitem">
                        <span data-feather="user"></span>
                        <span>Update Profile</span>
                    </a>

                    {{-- Divider + Logout --}}
                    <div class="dropdown-divider" aria-hidden="true"></div>
                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                        id="logout-form"
                    >
                        @csrf
                        <button type="button" class="dropdown-item" role="menuitem" style="color:#ef4444;" onclick="event.preventDefault(); showConfirmModal({ type: 'delete', title: 'Logout', subtitle: '', message: 'Are you sure you want to log out?', confirmText: 'Logout', form: document.getElementById('logout-form') });">
                            <span data-feather="log-out"></span>
                            <span>Logout</span>
                        </button>
                    </form>

                </div>

            </div>
        @endauth
    </div>

</header>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle   = document.getElementById('userMenuToggle');
    const dropdown = document.getElementById('userMenuDropdown');

    if (!toggle || !dropdown) return;

    // Toggle dropdown on avatar/name click
    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        
        // Close branch switcher if open
        const branchDropdown = document.getElementById('branchSwitcherDropdown');
        if (branchDropdown) {
            branchDropdown.style.display = 'none';
            const branchToggle = document.getElementById('branchSwitcherToggle');
            if (branchToggle) branchToggle.setAttribute('aria-expanded', 'false');
        }
    });

    // Close when clicking outside
    document.addEventListener('click', function () {
        if (dropdown.classList.contains('open')) {
            dropdown.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
    
    // Branch switcher dropdown
    const branchToggle = document.getElementById('branchSwitcherToggle');
    const branchDropdown = document.getElementById('branchSwitcherDropdown');
    
    if (branchToggle && branchDropdown) {
        branchToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = branchDropdown.style.display === 'block';
            branchDropdown.style.display = isOpen ? 'none' : 'block';
            branchToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            
            // Close user menu if open
            if (dropdown.classList.contains('open')) {
                dropdown.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close branch dropdown when clicking outside
        document.addEventListener('click', function () {
            if (branchDropdown.style.display === 'block') {
                branchDropdown.style.display = 'none';
                branchToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>
@endpush
