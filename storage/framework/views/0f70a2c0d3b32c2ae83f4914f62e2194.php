<?php $__env->startSection('content'); ?>
<div class="container">
    <h1>User Details</h1>

    <div class="card">
        <div class="card-body">
            <div style="display:flex;gap:18px;align-items:flex-start;">
                <div style="flex:0 0 160px;">
                    <?php if($user->avatar): ?>
                        <img src="<?php echo e(asset('storage/' . $user->avatar)); ?>" alt="avatar" style="width:150px;height:150px;object-fit:cover;border-radius:8px;" />
                    <?php else: ?>
                        <div style="width:150px;height:150px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="flex:1;">

                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div>
                            <h2 style="margin:0;"><?php echo e($user->name); ?></h2>
                            <div style="color:var(--muted,#6b7280);margin-top:6px;"><?php echo e(optional($user->role)->name ?? 'No Role'); ?> — <?php echo e(optional($user->branch)->name ?? 'No Branch'); ?></div>
                        </div>
                    </div>

                    <div style="margin-top:18px;display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:12px;">
                        <div><strong>Mobile:</strong> <?php echo e($user->mobile ?? '-'); ?></div>
                        <div><strong>Email:</strong> <?php echo e($user->email ?? '-'); ?></div>

                        <div><strong>Role:</strong> <?php echo e(optional($user->role)->name ?? '-'); ?></div>
                        <div><strong>Branch:</strong> <?php echo e(optional($user->branch)->name ?? '-'); ?></div>

                        <div><strong>Active:</strong> <?php echo e($user->active ? 'Yes' : 'No'); ?></div>
                        <div><strong>Deleted At:</strong> <?php echo e(optional($user->deleted_at)->toDateTimeString() ?? '-'); ?></div>

                        <div><strong>Created At:</strong> <?php echo e(optional($user->created_at)->toDateTimeString() ?? '-'); ?></div>
                        <div><strong>Created By:</strong> <?php echo e(optional($user->createdBy)->name ?? '-'); ?></div>

                        <div><strong>Updated At:</strong> <?php echo e(optional($user->updated_at)->toDateTimeString() ?? '-'); ?></div>
                        <div><strong>Updated By:</strong> <?php echo e(optional($user->updatedBy)->name ?? '-'); ?></div>

                        <div><strong>Last Updated At:</strong> <?php echo e(optional($user->last_updated_at)->toDateTimeString() ?? '-'); ?></div>
                        <div><strong>Last Updated By:</strong> <?php echo e(optional($user->lastUpdatedBy)->name ?? '-'); ?></div>

                        <div><strong>Restored At:</strong> <?php echo e(optional($user->restored_at)->toDateTimeString() ?? '-'); ?></div>
                        <div><strong>Restored By:</strong> <?php echo e(optional($user->restoredBy)->name ?? '-'); ?></div>

                        <div><strong>Email Bounce Count:</strong> <?php echo e($user->email_bounce_count ?? 0); ?></div>
                        <div><strong>Last Email Bounce:</strong> <?php echo e(optional($user->email_bounced_at)->toDateTimeString() ?? '-'); ?></div>
                    </div>

                    <div style="margin-top:18px; display:flex; gap:8px;">
                        <?php $backUrl = session('users_list_back_url'); ?>
                        <?php if($backUrl): ?>
                            <a href="<?php echo e($backUrl); ?>" title="Back to previous page" style="background:#22c55e;color:#ffffff;padding:12px 16px;border-radius:10px;border:1px solid #16a34a;display:inline-flex;align-items:center;gap:10px;cursor:pointer;margin-right:6px;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Back to previous page</a>
                        <?php else: ?>
                            <a href="#" onclick="history.back(); return false;" title="Back to previous page" style="background:#22c55e;color:#ffffff;padding:12px 16px;border-radius:10px;border:1px solid #16a34a;display:inline-flex;align-items:center;gap:10px;cursor:pointer;margin-right:6px;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Back to previous page</a>
                        <?php endif; ?>
                        <a href="<?php echo e(route('users.edit', $user)); ?>" style="background:#e0f2fe;color:#0369a1;padding:12px 16px;border-radius:10px;border:1px solid #bfdbfe;display:inline-flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;font-size:15px;height:44px;text-decoration:none;">Edit</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/users/show.blade.php ENDPATH**/ ?>