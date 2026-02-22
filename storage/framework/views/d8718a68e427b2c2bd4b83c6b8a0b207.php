<table style="width:100%; border-collapse:collapse;">
    <thead>
    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
        <th style="padding:8px;">ID</th>
        <th style="padding:8px;">Action</th>
        <th style="padding:8px;">Model</th>
        <th style="padding:8px;">Record ID</th>
        <th style="padding:8px;">User</th>
        <th style="padding:8px;">IP</th>
        <th style="padding:8px;">When</th>
        <th style="padding:8px;">Changes</th>
    </tr>
    </thead>
    <tbody>
    <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $hasOld = !empty($log->old_values);
            $hasNew = !empty($log->new_values);
            $type = $log->auditable_type ? class_basename($log->auditable_type) : '—';
        ?>
        <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:8px; font-weight:800;"><?php echo e($log->id); ?></td>
            <td style="padding:8px;">
                <span style="background:#e0f2fe;color:#0369a1;padding:4px 10px;border-radius:999px;font-size:13px;font-weight:800;">
                    <?php echo e($log->action); ?>

                </span>
            </td>
            <td style="padding:8px;">
                <div style="font-weight:800;"><?php echo e($type); ?></div>
                <div class="muted" style="font-size:12px;max-width:320px;word-break:break-all;"><?php echo e($log->auditable_type ?? '—'); ?></div>
            </td>
            <td style="padding:8px;"><?php echo e($log->auditable_id ?? '—'); ?></td>
            <td style="padding:8px;">
                <div style="font-weight:800;"><?php echo e($log->user->name ?? 'System'); ?></div>
                <div class="muted" style="font-size:12px;"><?php echo e($log->user->email ?? ''); ?></div>
            </td>
            <td style="padding:8px;"><?php echo e($log->ip_address ?? '—'); ?></td>
            <td style="padding:8px; white-space:nowrap;"><?php echo e($log->created_at?->format('Y-m-d H:i') ?? '—'); ?></td>
            <td style="padding:8px;">
                <?php if(!$hasOld && !$hasNew): ?>
                    <span class="muted">—</span>
                <?php else: ?>
                    <?php if($hasOld): ?>
                        <span style="background:#fee2e2;color:#b91c1c;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:900;margin-right:6px;">OLD</span>
                    <?php endif; ?>
                    <?php if($hasNew): ?>
                        <span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:900;">NEW</span>
                    <?php endif; ?>
                    <details style="margin-top:8px;">
                        <summary style="cursor:pointer;color:#2563eb;font-weight:900;">View JSON</summary>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
                            <div>
                                <div class="muted" style="font-size:12px;font-weight:900;margin-bottom:6px;">Old</div>
                                <pre style="white-space:pre-wrap;word-break:break-word;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:10px;max-height:220px;overflow:auto;"><?php echo e(json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                            </div>
                            <div>
                                <div class="muted" style="font-size:12px;font-weight:900;margin-bottom:6px;">New</div>
                                <pre style="white-space:pre-wrap;word-break:break-word;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:10px;max-height:220px;overflow:auto;"><?php echo e(json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" style="padding:12px;">No audit logs found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
        Total Logs: <?php echo e($auditLogs->total()); ?>

    </div>

    <div style="flex:1; display:flex; justify-content:center;">
        <?php
            $current = $auditLogs->currentPage();
            $last = $auditLogs->lastPage();
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
            $currentPerPage = (int) request()->query('per_page', $auditLogs->perPage() ?? 20);
        ?>
        <form method="GET" action="<?php echo e(url()->current()); ?>" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
            <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>" />
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
            <select name="per_page" id="per_page" onchange="ajaxFetchAuditLogs(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                <?php $__currentLoopData = [10,15,20,30,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($opt); ?>" <?php echo e($currentPerPage == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>
    </div>
</div>
<?php /**PATH E:\xampp\htdocs\IOMS\resources\views/audit-logs/_audit_logs_table.blade.php ENDPATH**/ ?>