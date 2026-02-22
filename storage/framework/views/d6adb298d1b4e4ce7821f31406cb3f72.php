<?php $__env->startSection('title', 'Edit User'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>Edit User</h2>
            <p class="muted">Update user details, active status, and bounce data.</p>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="card" style="margin-top:12px;">
            <ul style="margin:0;padding-left:18px;">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li style="color:#dc2626;"><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e($user->exists ? route('users.update', $user) : route('users.store')); ?>" class="user-form" enctype="multipart/form-data">
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('users._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/users/edit.blade.php ENDPATH**/ ?>