<?php $__env->startSection('title','Edit Branch'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>Edit Branch</h2>
            <p class="muted">Update branch details.</p>
        </div>
    </div>

    <div class="card" style="margin-top:12px; padding:16px;">
        <form action="<?php echo e(route('branches.update', $branch->id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div style="margin-bottom:12px;">
                <label style="display:block;margin-bottom:6px;font-size:15px;font-weight:600;">Branch Name</label>
                <input type="text" name="name" value="<?php echo e(old('name', $branch->name)); ?>" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" />
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
                <a href="<?php echo e(route('branches.index')); ?>" style="background:#f51607;color:#fff;padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;text-decoration:none;display:flex;align-items:center;gap:8px;">
                    <span data-feather="x-circle"></span> Cancel
                </a>
                <button type="submit" style="background:#22c55e;color:white;padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;border:none;display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <span data-feather="save"></span> Save
                </button>
            </div>
        </form>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/branches/edit.blade.php ENDPATH**/ ?>