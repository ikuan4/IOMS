<?php $__env->startSection('title', 'Manage Contracts'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        /* Custom select arrow so we can control its position (move ~10px left) */
        #contracts_branch_id,
        #contracts_contract_type_id,
        #per_page {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%236b7280' d='M5.25 7.5 10 12.25 14.75 7.5l1.5 1.5L10 15.25 3.75 9z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 22px center;
            background-size: 16px 16px;
            padding-right: 48px !important;
            background-color: var(--card, #fff);
            color: var(--text, #111827);
        }
    </style>

    <div class="header-card">
        <div class="header-left">
            <h2>CONTRACT MANAGEMENT</h2>
            <p class="muted">Manage contracts, versions, and notifications.</p>
        </div>
    </div>

    
    <?php
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();
        $currentStatus = request('status') ?? 'all';

        $cards = [
            'all' => ['label' => 'All Contracts', 'count' => $statusCounts['all'] ?? 0, 'color' => '#0B6BBD'],
            'ongoing' => ['label' => 'Ongoing', 'count' => $statusCounts['ongoing'] ?? 0, 'color' => '#10b981'],
            'pending' => ['label' => 'Pending', 'count' => $statusCounts['pending'] ?? 0, 'color' => '#f59e0b'],
            'expiring' => ['label' => 'Expiring Soon', 'count' => $statusCounts['expiring'] ?? 0, 'color' => '#ef4444'],
            'expired' => ['label' => 'Expired', 'count' => $statusCounts['expired'] ?? 0, 'color' => '#dc2626'],
            'inactive' => ['label' => 'Inactive', 'count' => $statusCounts['inactive'] ?? 0, 'color' => '#6b7280'],
        ];
    ?>

    <div style="margin-top:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px;">
            <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isActive = $currentStatus === $key;
                    $params = array_merge(request()->all(), ['status' => $key]);
                    unset($params['page']);
                    $url = route('contracts.index', $params);
                ?>
                <a href="<?php echo e($url); ?>"
                   style="display:block;padding:20px;background:<?php echo e($isActive ? $card['color'] : '#ffffff'); ?>;
                   border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.08);
                   text-decoration:none;color:<?php echo e($isActive ? '#ffffff' : '#1f2937'); ?>;transition:all 0.15s ease;"
                   onmouseover="if(!<?php echo e($isActive ? 'true' : 'false'); ?>) { this.style.boxShadow='0 4px 10px rgba(0,0,0,0.12)';this.style.transform='translateY(-2px)'; }"
                   onmouseout="if(!<?php echo e($isActive ? 'true' : 'false'); ?>) { this.style.boxShadow='0 2px 6px rgba(0,0,0,0.08)';this.style.transform='none'; }">
                    <div style="font-size:15px;font-weight:500;margin-bottom:8px;"><?php echo e($card['label']); ?></div>
                    <div style="font-size:32px;font-weight:700;"><?php echo e($card['count']); ?></div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    
    <div class="card" style="margin-top:20px;padding:20px;">
        <form method="GET" action="<?php echo e(route('contracts.index')); ?>" style="display:flex;gap:16px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="status" value="<?php echo e(request('status', 'all')); ?>">

            
            <?php if($isSuperAdmin): ?>
                <div style="flex:1;min-width:180px;">
                    <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">BRANCH</label>
                    <select name="branch_id" id="contracts_branch_id" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;background-color:var(--card,#fff);color:var(--text,#111827);">
                        <option value="">All Branches</option>
                        <?php $__currentLoopData = $branches ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($branch): ?>
                            <option value="<?php echo e(optional($branch)->id); ?>" <?php echo e(optional($branch)->id && request('branch_id') == optional($branch)->id ? 'selected' : ''); ?>>
                                <?php echo e(optional($branch)->name); ?>

                            </option>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            
            <div style="flex:1;min-width:180px;">
                <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">CONTRACT TYPE</label>
                <select name="contract_type_id" id="contracts_contract_type_id" style="width:100%;padding:12px 14px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;background-color:var(--card,#fff);color:var(--text,#111827);">
                    <option value="">All Types</option>
                    <?php $__currentLoopData = $contractTypes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($type): ?>
                        <option value="<?php echo e(optional($type)->id); ?>" <?php echo e(optional($type)->id && request('contract_type_id') == optional($type)->id ? 'selected' : ''); ?>>
                            <?php echo e(optional($type)->name); ?>

                        </option>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            
            <div style="flex:1;min-width:250px;">
                <label style="font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;display:block;">SEARCH</label>
                <input
                    type="text"
                    name="search"
                    placeholder="Search by contract number, company name..."
                    value="<?php echo e(request('search')); ?>"
                    style="padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;width:100%;font-size:15px;"
                >
            </div>

            
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 20px;border:none;border-radius:10px;font-weight:500;cursor:pointer;">
                    Apply
                </button>
                <a href="<?php echo e(route('contracts.index')); ?>" class="btn" style="background:#6b7280;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:500;">
                    Reset
                </a>
                <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.create')): ?>
                    <a href="<?php echo e(route('contracts.create')); ?>" style="background:#22c55e;color:white;padding:10px 24px;border-radius:10px;font-weight:1000;width:220px;display:flex;justify-content:center;align-items:center;gap:8px;white-space:nowrap;text-decoration:none;">
                        <span data-feather="plus"></span>
                        New Contract
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    
    <div class="card" style="margin-top:20px;padding:0;overflow:hidden;">
        <?php if($contracts->isEmpty()): ?>
            <div style="padding:40px;text-align:center;color:#6b7280;">
                <svg style="margin:0 auto 16px;opacity:0.5;" width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p style="font-size:16px;margin:0;">No contracts found.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                        <tr>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">CONTRACT NO.</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">TYPE</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">CONTRACT WITH</th>
                            <?php if($isSuperAdmin): ?>
                                <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">BRANCH</th>
                            <?php endif; ?>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">START DATE</th>
                            <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">END DATE</th>
                            <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">STATUS</th>
                            <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $contracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $latestVersion = $contract->latestVersion;
                                $canViewContract = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.view');
                                $statusColors = [
                                    'Ongoing' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                                    'Pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                                    'Expiring Soon' => ['bg' => '#fecaca', 'text' => '#991b1b'],
                                    'Expired' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                    'Inactive' => ['bg' => '#e5e7eb', 'text' => '#6b7280'],
                                ];
                                $statusColor = $statusColors[$contract->status] ?? ['bg' => '#e5e7eb', 'text' => '#6b7280'];
                            ?>
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:14px 20px;">
                                    <?php if($canViewContract): ?>
                                        <a href="<?php echo e(route('contracts.show', $contract->id)); ?>" style="font-size:15px;font-weight:600;color:#0B6BBD;text-decoration:none;"><?php echo e($contract->contract_number); ?></a>
                                    <?php else: ?>
                                        <span style="font-size:15px;font-weight:600;color:var(--text,#111827);"><?php echo e($contract->contract_number); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:14px 20px;font-size:14px;"><?php echo e(optional($contract->contractType)->name ?? 'N/A'); ?></td>
                                <td style="padding:14px 20px;font-size:14px;font-weight:500;"><?php echo e($contract->contract_with); ?></td>
                                <?php if($isSuperAdmin): ?>
                                    <td style="padding:14px 20px;font-size:14px;color:#6b7280;"><?php echo e(optional($contract->branch)->name ?? 'N/A'); ?></td>
                                <?php endif; ?>
                                <td style="padding:14px 20px;font-size:14px;">
                                    <?php echo e($latestVersion ? $latestVersion->start_date->timezone('Asia/Kolkata')->format('d M Y') : 'N/A'); ?>

                                </td>
                                <td style="padding:14px 20px;font-size:14px;">
                                    <?php echo e($latestVersion ? $latestVersion->end_date->timezone('Asia/Kolkata')->format('d M Y') : 'N/A'); ?>

                                </td>
                                <td style="padding:14px 20px;text-align:center;">
                                    <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;
                                        background:<?php echo e($statusColor['bg']); ?>;color:<?php echo e($statusColor['text']); ?>;">
                                        <?php echo e(strtoupper($contract->status)); ?>

                                    </span>
                                </td>
                                <td style="padding:14px 20px;text-align:center;">
                                    <div style="display:flex;gap:8px;justify-content:center;">
                                        <?php if($canViewContract): ?>
                                            <a href="<?php echo e(route('contracts.show', $contract->id)); ?>" style="background:#f3f4f6;color:#374151;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="View contract"><span data-feather="eye"></span></a>
                                        <?php endif; ?>
                                        <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.edit')): ?>
                                            <a href="<?php echo e(route('contracts.edit', $contract->id)); ?>" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;text-decoration:none;" title="Edit contract"><span data-feather="edit"></span></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Contracts: <?php echo e($contracts->total()); ?>

            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                <?php
                    $current = $contracts->currentPage();
                    $last = $contracts->lastPage();
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
                    $currentPerPage = (int) request()->query('per_page', $contracts->perPage() ?? 10);
                ?>
                <form method="GET" action="<?php echo e(url()->current()); ?>" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
                    <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>" />
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="per_page" onchange="document.getElementById('perPageForm').submit()" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background-color:var(--card);color:var(--text,inherit);">
                        <?php $__currentLoopData = [5,10,15,20,30]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt); ?>" <?php echo e($currentPerPage == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>
        </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if(session('success')): ?>
        <div style="position:fixed;bottom:20px;right:20px;background:#10b981;color:#fff;padding:16px 24px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;">
            <?php echo e(session('success')); ?>

        </div>
        <script>
            setTimeout(() => {
                const alert = document.querySelector('[style*="position:fixed"]');
                if(alert) alert.remove();
            }, 3000);
        </script>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/contracts/index.blade.php ENDPATH**/ ?>