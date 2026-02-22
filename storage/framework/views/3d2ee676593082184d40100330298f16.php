<?php $__env->startSection('title', 'Audit Logs'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        /* Custom select arrow so we can control its position */
        #auditable_type {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%236b7280' d='M5.25 7.5 10 12.25 14.75 7.5l1.5 1.5L10 15.25 3.75 9z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            /* move arrow 10px left from typical right padding */
            background-position: right 26px center;
            background-size: 16px 16px;
            padding-right: 52px !important;
        }

        #user_id {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%236b7280' d='M5.25 7.5 10 12.25 14.75 7.5l1.5 1.5L10 15.25 3.75 9z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            /* move arrow 10px left from typical right padding */
            background-position: right 26px center;
            background-size: 16px 16px;
            padding-right: 52px !important;
        }
    </style>

    <div class="header-card">
        <div class="header-left">
            <h2>AUDIT LOGS</h2>
            <p class="muted">Super Admin-only log of system changes and actions.</p>
        </div>
    </div>

    <div class="card" style="margin-top:16px;padding:20px;">
        <form method="GET" action="<?php echo e(route('audit-logs.index')); ?>" id="auditLogSearchForm" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:0 0 auto;min-width:260px;max-width:420px;">
                <label for="search" class="muted" style="font-size:12px;font-weight:800;display:block;margin-bottom:6px;">Search</label>
                <input
                    type="text"
                    name="search"
                    id="search"
                    value="<?php echo e($search ?? ''); ?>"
                    placeholder="action, model, record id, user, ip..."
                    oninput="debouncedAuditLogSearch()"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;"
                >
            </div>

            <div style="flex:0 0 auto;min-width:260px;">
                <label for="auditable_type" class="muted" style="font-size:12px;font-weight:800;display:block;margin-bottom:6px;">Model (auditable_type)</label>
                <select
                    name="auditable_type"
                    id="auditable_type"
                    onchange="ajaxFetchAuditLogs(1)"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;background-color:var(--card,#fff);color:var(--text,#111827);"
                >
                    <?php
                        $currentAuditableType = (string) ($auditableType ?? '');
                        $options = $auditableTypeOptions ?? ['' => 'All Models'];
                        if ($currentAuditableType !== '' && !array_key_exists($currentAuditableType, $options)) {
                            $options[$currentAuditableType] = $currentAuditableType;
                        }
                    ?>
                    <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e(((string) $value === $currentAuditableType) ? 'selected' : ''); ?>>
                            <?php echo e($label); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div style="flex:0 0 auto;min-width:260px;max-width:520px;">
                <label for="user_id" class="muted" style="font-size:12px;font-weight:800;display:block;margin-bottom:6px;">User</label>
                <select
                    name="user_id"
                    id="user_id"
                    onchange="ajaxFetchAuditLogs(1)"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;background-color:var(--card,#fff);color:var(--text,#111827);"
                >
                    <?php
                        $currentUserId = $userId === null ? '' : (string) $userId;
                        $uOptions = $userOptions ?? ['' => 'All Users', 'system' => 'System'];
                        if ($currentUserId !== '' && !array_key_exists($currentUserId, $uOptions)) {
                            $uOptions[$currentUserId] = ctype_digit($currentUserId) ? ('User #' . $currentUserId) : $currentUserId;
                        }
                    ?>
                    <?php $__currentLoopData = $uOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e(((string) $value === $currentUserId) ? 'selected' : ''); ?>>
                            <?php echo e($label); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <input type="hidden" name="per_page" id="per_page_hidden" value="<?php echo e(request()->query('per_page', 20)); ?>">
        </form>
    </div>

    <div class="card" style="margin-top:12px;overflow:hidden;">
        <div id="auditLogsTableWrapper">
            <?php echo $__env->make('audit-logs._audit_logs_table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <script>
        let __auditLogSearchTimer = null;
        function debouncedAuditLogSearch() {
            clearTimeout(__auditLogSearchTimer);
            __auditLogSearchTimer = setTimeout(() => { ajaxFetchAuditLogs(1); }, 300);
        }

        function ajaxFetchAuditLogs(page = 1) {
            const form = document.getElementById('auditLogSearchForm');
            const params = new URLSearchParams(new FormData(form));
            const perPageSelect = document.getElementById('per_page');
            if (perPageSelect) params.set('per_page', perPageSelect.value);
            params.set('page', page);
            const url = `${location.pathname}?${params.toString()}`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network error');
                    return r.text();
                })
                .then(html => {
                    document.getElementById('auditLogsTableWrapper').innerHTML = html;
                    bindAuditLogPaginationLinks();
                    try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindAuditLogPaginationLinks(){
            const wrapper = document.getElementById('auditLogsTableWrapper');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                try {
                    const url = new URL(href, location.origin);
                    if (url.searchParams.has('page')) {
                        a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchAuditLogs(url.searchParams.get('page')); });
                    }
                } catch(e) {}
            });
        }

        window.addEventListener('load', function(){
            bindAuditLogPaginationLinks();
        });

        document.addEventListener('spa:navigated', function(){
            try { bindAuditLogPaginationLinks(); } catch (e) {}
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/audit-logs/index.blade.php ENDPATH**/ ?>