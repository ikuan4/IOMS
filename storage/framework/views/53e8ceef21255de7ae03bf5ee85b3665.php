<?php $__env->startSection('title','Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div>
            <h3>Dashboard</h3>
            <p class="muted">Welcome, <?php echo e(auth()->user()?->name ?? 'User'); ?>.</p>
        </div>
        <!-- logout button removed; use header menu to log out -->
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/dashboard.blade.php ENDPATH**/ ?>