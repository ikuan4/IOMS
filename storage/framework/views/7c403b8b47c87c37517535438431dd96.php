<?php $currentUser = auth()->user(); ?>
<?php if($recipients->isEmpty()): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;">
        <svg style="margin:0 auto 16px;opacity:0.5;" width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <p style="font-size:16px;margin:0;">No notification recipients found.</p>
    </div>
<?php else: ?>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead style="background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                <tr>
                    <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">NAME</th>
                    <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">DESIGNATION</th>
                    <?php if($currentUser && $currentUser->isSuperAdmin()): ?>
                        <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">BRANCH</th>
                    <?php endif; ?>
                    <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">EMAIL</th>
                    <th style="padding:14px 20px;text-align:left;font-size:13px;font-weight:600;color:#6b7280;">MOBILE</th>
                    <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">STATUS</th>
                    <th style="padding:14px 20px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $recipients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recipient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:14px 20px;font-size:15px;font-weight:500;"><?php echo e($recipient->name); ?></td>
                        <td style="padding:14px 20px;font-size:14px;color:#6b7280;"><?php echo e($recipient->designation ?? 'N/A'); ?></td>
                        <?php if($currentUser && $currentUser->isSuperAdmin()): ?>
                            <td style="padding:14px 20px;font-size:14px;color:#6b7280;"><?php echo e($recipient->branch->name ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td style="padding:14px 20px;font-size:14px;">
                            <a href="mailto:<?php echo e($recipient->email); ?>" style="color:#0B6BBD;text-decoration:none;"><?php echo e($recipient->email); ?></a>
                        </td>
                        <td style="padding:14px 20px;font-size:14px;color:#6b7280;"><?php echo e($recipient->mobile ?? 'N/A'); ?></td>
                        <td style="padding:14px 20px;text-align:center;">
                            <?php if($recipient->deleted_at): ?>
                                <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#fee2e2;color:#991b1b;">DELETED</span>
                            <?php elseif($recipient->is_active): ?>
                                <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#d1fae5;color:#065f46;">ACTIVE</span>
                            <?php else: ?>
                                <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#fef3c7;color:#92400e;">INACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 20px;text-align:center;">
                            <div style="display:flex;gap:8px;justify-content:center;">
                                <?php if($recipient->deleted_at): ?>
                                    <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.restore')): ?>
                                        <form method="POST" action="<?php echo e(route('notification-recipients.restore', $recipient->id)); ?>" style="display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" style="background:#dcfce7;color:#15803d;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Restore recipient"><span data-feather="rotate-ccw"></span></button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.view')): ?>
                                        <a href="<?php echo e(route('notification-recipients.show', $recipient->id)); ?>" style="background:#f3f4f6;color:#374151;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="View recipient"><span data-feather="eye"></span></a>
                                    <?php endif; ?>
                                    <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.edit')): ?>
                                        <a href="<?php echo e(route('notification-recipients.edit', $recipient->id)); ?>" style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;" title="Edit recipient"><span data-feather="edit"></span></a>
                                    <?php endif; ?>
                                    <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.delete')): ?>
                                        <form method="POST" action="<?php echo e(route('notification-recipients.destroy', $recipient->id)); ?>" style="display:inline;" onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Recipient', subtitle: 'Are you sure you want to delete <?php echo e(addslashes($recipient->name)); ?>?', message: 'This action will soft delete the recipient. You can restore it later from the deleted recipients section.', confirmText: 'Delete Recipient', form: this});">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;" title="Delete recipient"><span data-feather="trash-2"></span></button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Recipients: <?php echo e($recipients->total()); ?>

            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                <?php
                    $current = $recipients->currentPage();
                    $last = $recipients->lastPage();
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
                <?php $currentPerPage = (int) request()->query('per_page', $recipients->perPage() ?? 10); ?>
                <form method="GET" action="<?php echo e(url()->current()); ?>" id="perPageForm" style="display:flex;align-items:center;gap:8px;">
                    <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>" />
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <label for="per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="per_page" onchange="ajaxFetchRecipients(1)" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                        <?php $__currentLoopData = [5,10,15,20,30]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt); ?>" <?php echo e($currentPerPage == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH E:\xampp\htdocs\IOMS\resources\views/notification-recipients/_recipients_table.blade.php ENDPATH**/ ?>