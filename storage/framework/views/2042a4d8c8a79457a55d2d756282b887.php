<table style="width:100%; border-collapse:collapse;">
    <thead>
    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
        <th style="padding:8px;">Name</th>
        <?php if(auth()->user() && auth()->user()->isSuperAdmin()): ?>
            <th style="padding:8px;">Branch</th>
        <?php endif; ?>
        <th style="padding:8px;">Description</th>
        <th style="padding:8px;">Status</th>
        <th style="padding:8px; text-align:right;">Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php $__empty_1 = true; $__currentLoopData = $ticketTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $isDeleted = !is_null($type->deleted_at); ?>
        <tr style="border-bottom:1px solid #f3f4f6; height:62px; <?php echo e($isDeleted ? 'opacity:1;' : ''); ?>">
            <td style="padding:8px;"><?php echo e($type->name); ?></td>
            <?php if(auth()->user() && auth()->user()->isSuperAdmin()): ?>
                <td style="padding:8px;"><?php echo e($type->branch->name ?? 'N/A'); ?></td>
            <?php endif; ?>
            <td style="padding:8px;"><?php echo e(Str::limit($type->description ?? 'No description', 70)); ?></td>
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
                        <a href="<?php echo e(route('ticket-types.edit', $type)); ?>" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit ticket type"><span data-feather="edit"></span></a>
                    <?php endif; ?>
                    <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('delete', $type)): ?>
                        <form action="<?php echo e(route('ticket-types.destroy', $type)); ?>" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Ticket Type', subtitle: 'This will soft delete the ticket type', message: 'Are you sure you want to delete <?php echo e(addslashes($type->name)); ?>?', confirmText: 'Delete Ticket Type', checkDependenciesUrl: '<?php echo e(route('ticket-types.check_delete_dependencies', $type)); ?>', form: this});">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Soft delete ticket type"><span data-feather="trash-2"></span></button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if(auth()->user()->isSuperAdmin() || auth()->user()->can('restore', $type)): ?>
                        <form action="<?php echo e(route('ticket-types.restore', $type->id)); ?>" method="POST" style="display:inline-block;" onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Ticket Type', subtitle: 'This will restore the ticket type', message: 'Are you sure you want to restore <?php echo e($type->name); ?>?', confirmText: 'Restore Ticket Type', form: this});">
                            <?php echo csrf_field(); ?>
                            <?php if(!empty($search)): ?><input type="hidden" name="search" value="<?php echo e($search); ?>"><?php endif; ?>
                            <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore ticket type"><span data-feather="rotate-ccw"></span></button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="5" style="padding:12px;">No ticket types found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
        Total Ticket Types: <?php echo e($ticketTypes->total()); ?>

    </div>

    <div style="flex:1; display:flex; justify-content:center;">
        <?php
            $current = $ticketTypes->currentPage();
            $last = $ticketTypes->lastPage();
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
            $currentPerPage = (int) request()->query('per_page', $ticketTypes->perPage() ?? 10);
        ?>
        <form method="GET" action="<?php echo e(url()->current()); ?>" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
            <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>" />
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
            <select name="per_page" id="per_page" onchange="ajaxFetchTicketTypes(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                <?php $__currentLoopData = [5,10,15,20,30]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($opt); ?>" <?php echo e($currentPerPage == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>
    </div>
</div>
<?php /**PATH E:\xampp\htdocs\IOMS\resources\views/ticket-types/_ticket_types_table.blade.php ENDPATH**/ ?>