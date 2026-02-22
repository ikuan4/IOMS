<?php $__env->startSection('title', 'Contract Types'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>CONTRACT TYPE MANAGEMENT MODULE</h2>
            <p class="muted">Manage contract types, activation state, and usage metadata.</p>
        </div>
    </div>

    <?php
        $currentStatus = $status ?? 'all';
        $baseParams = ['search' => $search];

        $cards = [
            'all' => [ 'label' => 'All Types', 'count' => $statusCounts['all'] ?? 0, ],
            'active' => [ 'label' => 'Active Types', 'count' => $statusCounts['active'] ?? 0, ],
            'inactive' => [ 'label' => 'Inactive Types', 'count' => $statusCounts['inactive'] ?? 0, ],
            'deleted' => [ 'label' => 'Deleted Types', 'count' => $statusCounts['deleted'] ?? 0, ],
        ];
    ?>

    <div style="margin-top:12px;">
        <div id="contractTypeCardsContainer" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
            <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(array_merge($baseParams, ['status' => $key]), fn($v) => $v !== null && $v !== '');
                ?>

                <a href="<?php echo e(route('contract-types.index', $params)); ?>" class="contract-type-filter-card" style="text-decoration:none;color:inherit;">
                    <div class="card" style="padding:16px 18px;border-radius:12px;border:2px solid <?php echo e($isActiveCard ? '#22c55e' : '#e5e7eb'); ?>;box-shadow:<?php echo e($isActiveCard ? '0 0 0 1px rgba(34,197,94,0.15)' : 'none'); ?>;display:flex;flex-direction:column;justify-content:space-between;height:100%;">
                        <div style="font-size:14px; opacity:0.8;"><?php echo e($card['label']); ?></div>
                        <div style="margin-top:8px; font-size:24px; font-weight:700;"><?php echo e($card['count']); ?></div>
                        <?php if($isActiveCard): ?>
                            <div style="margin-top:8px; font-size:12px; color:#16a34a;">Currently applied to table ↓</div>
                        <?php else: ?>
                            <div style="margin-top:8px; font-size:12px; opacity:0.6;">Click to filter table</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div id="toggleContractTypeCardsContainer" style="display:none; justify-content:flex-end; margin-top:12px;">
            <button id="toggleContractTypeCardsBtn" onclick="toggleContractTypeCards()" style="display:flex;align-items:center;justify-content:center;border:none;background:transparent;color:#22c55e;cursor:pointer;transition:all 0.3s;padding:8px;" onmouseover="this.style.color='#16a34a'; this.style.transform='scale(1.2)'" onmouseout="this.style.color='#22c55e'; this.style.transform='scale(1)'">
                <svg id="toggleContractTypeIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline></svg>
            </button>
        </div>
    </div>

    <script>
        let contractTypeCardsVisible = false;
        function checkContractTypeCardWrapping() {
            const container = document.getElementById('contractTypeCardsContainer');
            const toggleContainer = document.getElementById('toggleContractTypeCardsContainer');
            if (!container || !toggleContainer) return;
            const cards = Array.from(container.querySelectorAll('.contract-type-filter-card'));
            if (cards.length === 0) return;
            cards.forEach(card => card.style.display = 'block');
            const firstCardTop = cards[0].getBoundingClientRect().top;
            const wrappedCards = [];
            cards.forEach((card) => { if (Math.abs(card.getBoundingClientRect().top - firstCardTop) > 10) wrappedCards.push(card); });
            if (wrappedCards.length > 0) {
                toggleContainer.style.display = 'flex';
                if (!contractTypeCardsVisible) wrappedCards.forEach(card => card.style.display = 'none');
            } else {
                toggleContainer.style.display = 'none';
                contractTypeCardsVisible = false;
            }
        }
        function toggleContractTypeCards() {
            const container = document.getElementById('contractTypeCardsContainer');
            const toggleIcon = document.getElementById('toggleContractTypeIcon');
            if (!container) return;
            const cards = Array.from(container.querySelectorAll('.contract-type-filter-card'));
            const firstCardTop = cards[0].getBoundingClientRect().top;
            contractTypeCardsVisible = !contractTypeCardsVisible;
            cards.forEach(card => {
                if (Math.abs(card.getBoundingClientRect().top - firstCardTop) > 10) {
                    card.style.display = contractTypeCardsVisible ? 'block' : 'none';
                }
            });
            if (contractTypeCardsVisible) toggleIcon.innerHTML = '<polyline points="7 11 12 6 17 11"></polyline><polyline points="7 18 12 13 17 18"></polyline>'; else toggleIcon.innerHTML = '<polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline>';
        }
        window.addEventListener('load', checkContractTypeCardWrapping);
        window.addEventListener('resize', () => { contractTypeCardsVisible = false; checkContractTypeCardWrapping(); });
        setTimeout(checkContractTypeCardWrapping, 100);
    </script>

    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px;">
        <form method="GET" action="<?php echo e(route('contract-types.index')); ?>" id="searchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="status" value="<?php echo e($status ?? 'all'); ?>">
            <input type="hidden" name="per_page" id="searchPerPage" value="<?php echo e(request()->query('per_page', 10)); ?>">
            <input type="text" name="search" id="contractTypeSearchInput" value="<?php echo e($search); ?>" placeholder="Search by name, code, or description..." oninput="debouncedContractTypeSearch()" style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;min-width:330px;width:330px;font-size:15px;" />
        </form>

        <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('create', \App\Models\ContractType::class)): ?>
        <a href="<?php echo e(route('contract-types.create')); ?>" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
            <span data-feather="plus"></span>
            Add Contract Type
        </a>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:12px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
            <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
                <th style="padding:8px;">Name</th>
                <th style="padding:8px;">Code</th>
                <?php if(auth()->user() && auth()->user()->isSuperAdmin()): ?>
                    <th style="padding:8px;">Branch</th>
                <?php endif; ?>
                <th style="padding:8px;">Description</th>
                <th style="padding:8px;">Status</th>
                <th style="padding:8px; text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $contractTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $isDeleted = !is_null($type->deleted_at); ?>
                <tr style="border-bottom:1px solid #f3f4f6; height:62px; <?php echo e($isDeleted ? 'opacity:1;' : ''); ?>">
                    <td style="padding:8px;"><?php echo e($type->name); ?></td>
                    <td style="padding:8px;">
                        <span style="background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:6px;font-size:13px;font-weight:600;"><?php echo e($type->code); ?></span>
                    </td>
                    <?php if(auth()->user() && auth()->user()->isSuperAdmin()): ?>
                        <td style="padding:8px;"><?php echo e($type->branch->name ?? 'N/A'); ?></td>
                    <?php endif; ?>
                    <td style="padding:8px;"><?php echo e(Str::limit($type->description ?? 'No description', 50)); ?></td>
                    <td style="padding:8px;">
                        <?php if($isDeleted): ?>
                            <span style="color:#f10101;font-weight:600;">Deleted</span>
                        <?php else: ?>
                            <?php if($type->is_active): ?>
                                <span style="color:#16a34a;font-weight:600;">Active</span>
                            <?php else: ?>
                                <span style="color:#dc2626;font-weight:600;">Inactive</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px; text-align:right; white-space:nowrap;">
                        <?php if(!$isDeleted): ?>
                            <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('update', $type)): ?>
                            <a href="<?php echo e(route('contract-types.edit', $type)); ?>" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit contract type"><span data-feather="edit"></span></a>
                            <?php endif; ?>
                            <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('delete', $type)): ?>
                            <form action="<?php echo e(route('contract-types.destroy', $type)); ?>" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Contract Type', subtitle: 'This will soft delete the contract type', message: 'Are you sure you want to delete <?php echo e(addslashes($type->name)); ?>?', confirmText: 'Delete Contract Type', checkDependenciesUrl: '<?php echo e(route('contract-types.check_delete_dependencies', $type)); ?>', form: this});">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Soft delete contract type"><span data-feather="trash-2"></span></button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('restore', $type)): ?>
                            <form action="<?php echo e(route('contract-types.restore', $type->id)); ?>" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Contract Type', subtitle: 'This will restore the contract type', message: 'Are you sure you want to restore <?php echo e($type->name); ?>?', confirmText: 'Restore Contract Type', form: this});">
                                <?php echo csrf_field(); ?>
                                <?php if(!empty($search)): ?><input type="hidden" name="search" value="<?php echo e($search); ?>"><?php endif; ?>
                                <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore contract type"><span data-feather="rotate-ccw"></span></button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6" style="padding:12px;">No contract types found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Contract Types: <?php echo e($contractTypes->total()); ?>

            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                <?php
                    $current = $contractTypes->currentPage();
                    $last = $contractTypes->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $start + 4);
                    if ($end - $start < 4) { $start = max(1, $end - 4); }
                    $baseParams = request()->except(['page']);
                ?>

                <nav aria-label="Pagination" style="display:inline-flex;align-items:center;gap:6px;background:var(--card);padding:6px 10px;border-radius:8px;">
                    
                    <?php $firstColor = $current > 1 ? '#2563eb' : '#000'; ?>
                    <?php if($current > 1): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => 1]))); ?>" aria-label="First page" style="padding:6px 10px;border-radius:6px;background:transparent;color:<?php echo e($firstColor); ?>;text-decoration:none;font-weight:800;font-size:14px;">&laquo;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:6px 10px;color:<?php echo e($firstColor); ?>;font-weight:800;font-size:14px;">&laquo;</span>
                    <?php endif; ?>

                    
                    <?php $prevColor = $current > 1 ? '#2563eb' : '#000'; ?>
                    <?php if($current > 1): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current - 1]))); ?>" aria-label="Previous page" style="padding:8px 12px;border-radius:6px;background:transparent;color:<?php echo e($prevColor); ?>;text-decoration:none;font-weight:800;font-size:15px;">&lt;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:8px 12px;color:<?php echo e($prevColor); ?>;font-weight:800;font-size:15px;">&lt;</span>
                    <?php endif; ?>

                    
                    <?php for($p = $start; $p <= $end; $p++): ?>
                        <?php if($p == $current): ?>
                            <span aria-current="page" style="padding:9px 14px;border-radius:8px;background:#f3f4f6;color:#374151;font-weight:800;border:1px solid #e5e7eb;font-size:15px;"><?php echo e($p); ?></span>
                        <?php else: ?>
                            <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $p]))); ?>" style="padding:8px 12px;border-radius:6px;background:transparent;color:var(--text);text-decoration:none;font-weight:800;font-size:14px;" onmouseover="this.style.border='1px solid #22c55e';this.style.background='rgba(34,197,94,0.06)';" onmouseout="this.style.border='none';this.style.background='transparent';"><?php echo e($p); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    
                    <?php $nextColor = $current < $last ? '#2563eb' : '#000'; ?>
                    <?php if($current < $last): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current + 1]))); ?>" aria-label="Next page" style="padding:8px 12px;border-radius:6px;background:transparent;color:<?php echo e($nextColor); ?>;text-decoration:none;font-weight:800;font-size:15px;">&gt;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:8px 12px;color:<?php echo e($nextColor); ?>;font-weight:800;font-size:15px;">&gt;</span>
                    <?php endif; ?>

                    
                    <?php $lastColor = $current < $last ? '#2563eb' : '#000'; ?>
                    <?php if($current < $last): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $last]))); ?>" aria-label="Last page" style="padding:6px 10px;border-radius:6px;background:transparent;color:<?php echo e($lastColor); ?>;text-decoration:none;font-weight:800;font-size:14px;">&raquo;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:6px 10px;color:<?php echo e($lastColor); ?>;font-weight:800;font-size:14px;">&raquo;</span>
                    <?php endif; ?>
                </nav>
            </div>

            <div style="flex:1; min-width:180px; display:flex; justify-content:flex-end; align-items:center; gap:8px;">
                <?php
                    $currentPerPage = (int) request()->query('per_page', $contractTypes->perPage() ?? 10);
                ?>
                <form method="GET" action="<?php echo e(url()->current()); ?>" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
                    <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>" />
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="per_page" onchange="ajaxFetchContractTypes(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                        <?php $__currentLoopData = [5,10,15,20,30]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt); ?>" <?php echo e($currentPerPage == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>
        </div>
    </div>



    <script>
        // AJAX search for contract types
        let __contractTypeSearchTimer = null;
        function debouncedContractTypeSearch() {
            clearTimeout(__contractTypeSearchTimer);
            __contractTypeSearchTimer = setTimeout(() => { ajaxFetchContractTypes(1); }, 300);
        }

        function ajaxFetchContractTypes(page = 1) {
            const form = document.getElementById('searchForm');
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
                    const wrapper = document.querySelector('.card[style*="margin-top:12px; overflow-x:auto;"]');
                    if (wrapper) {
                        wrapper.outerHTML = `<div class="card" style="margin-top:12px; overflow-x:auto;">${html}</div>`;
                        bindPaginationLinks();
                        try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                    }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindPaginationLinks(){
            const table = document.querySelector('.card[style*="margin-top:12px; overflow-x:auto;"] table');
            if (!table) return;
            const wrapper = table.closest('.card');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                try {
                    const url = new URL(href, location.origin);
                    if (url.searchParams.has('page')) {
                        a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchContractTypes(url.searchParams.get('page')); });
                    }
                } catch(e) {}
            });
            // Also handle per-page select
            const perPageSelect = document.getElementById('per_page');
            if (perPageSelect) {
                perPageSelect.onchange = null; // Remove old handler
                perPageSelect.addEventListener('change', function(){ ajaxFetchContractTypes(1); });
            }
        }

        window.addEventListener('load', function(){
            bindPaginationLinks();
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/contract-types/index.blade.php ENDPATH**/ ?>