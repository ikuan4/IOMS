<?php $__env->startSection('title', 'Manage Branches'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>BRANCHES</h2>
            <p class="muted">Manage organizational branches.</p>
        </div>
    </div>

    <div style="display:flex;gap:12px;align-items:flex-end;justify-content:space-between;margin-top:12px;">
        <div style="display:flex;gap:12px;align-items:flex-end;">
            <form method="GET" action="<?php echo e(route('branches.index')); ?>" id="branchSearchForm" style="display:flex;gap:12px;align-items:flex-end;">
                <input type="hidden" name="per_page" id="branchSearchPerPage" value="<?php echo e(request()->query('per_page', 10)); ?>">
                <input type="text" name="search" id="branchSearchInput" value="<?php echo e(request('search')); ?>" placeholder="Search branches..." oninput="debouncedBranchSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
            </form>

            <?php if(env('DEV_SHOW_ACTIONS', false) || auth()->user()->can('create', \App\Models\Branch::class)): ?>
            <a href="<?php echo e(route('branches.create')); ?>" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                <span data-feather="plus"></span>
                Add Branch
            </a>
            <?php endif; ?>
        </div>

        <?php if(auth()->user() && auth()->user()->isSuperAdmin()): ?>
        <a href="<?php echo e(route('branches.export_system_users')); ?>" style="background:#f8fafc;color:#0f172a;padding:12px 16px;border-radius:10px;text-decoration:none;border:1px solid #e2e8f0;display:inline-flex;align-items:center;gap:10px;height:44px;font-weight:700;font-size:15px;margin-right:20px;">
            <img src="<?php echo e(asset('images/excel-logo.png')); ?>" alt="Excel" style="width:28px;height:28px;display:block;" />
            <span>Export CSV</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:12px;">
        <div id="branchesTableWrapper">
            <?php echo $__env->make('branches._branches_table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <script>
        // AJAX search & pagination for branches
        let __branchSearchTimer = null;
        function debouncedBranchSearch() {
            clearTimeout(__branchSearchTimer);
            __branchSearchTimer = setTimeout(() => { ajaxFetchBranches(1); }, 300);
        }

        function ajaxFetchBranches(page = 1) {
            const form = document.getElementById('branchSearchForm');
            const params = new URLSearchParams(new FormData(form));
            const perPageSelect = document.getElementById('branch_per_page');
            if (perPageSelect) params.set('per_page', perPageSelect.value);
            params.set('page', page);
            const url = `${location.pathname}?${params.toString()}`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network error');
                    return r.text();
                })
                .then(html => {
                    document.getElementById('branchesTableWrapper').innerHTML = html;
                    bindPaginationLinks();
                    // re-render feather icons if available
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { /* ignore */ }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindPaginationLinks(){
            const wrapper = document.getElementById('branchesTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                const url = new URL(href, location.origin);
                if (url.searchParams.has('page')) {
                    a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchBranches(url.searchParams.get('page')); });
                }
            });
        }

        window.addEventListener('load', function(){
            bindPaginationLinks();
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/branches/index.blade.php ENDPATH**/ ?>