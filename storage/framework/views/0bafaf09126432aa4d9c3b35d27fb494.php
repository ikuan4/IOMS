<?php $__env->startSection('title', 'Create Notification Recipient'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>Add Notification Recipient</h2>
            <p class="muted">Create a new recipient for contract notifications.</p>
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

    <form method="POST" action="<?php echo e(route('notification-recipients.store')); ?>">
        <?php echo csrf_field(); ?>

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:600px;">

                <div>
                    <label for="name" style="font-size:15px;font-weight:600;">Name <span style="color:#dc2626;">*</span></label><br>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="<?php echo e(old('name')); ?>"
                        required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., John Doe"
                    >
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="designation" style="font-size:15px;font-weight:600;">Designation</label><br>
                    <input
                        type="text"
                        name="designation"
                        id="designation"
                        value="<?php echo e(old('designation')); ?>"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., Contract Manager"
                    >
                    <?php $__errorArgs = ['designation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="email" style="font-size:15px;font-weight:600;">Email <span style="color:#dc2626;">*</span></label><br>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="<?php echo e(old('email')); ?>"
                        required
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., john@example.com"
                    >
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="mobile" style="font-size:15px;font-weight:600;">Mobile</label><br>
                    <input
                        type="text"
                        name="mobile"
                        id="mobile"
                        value="<?php echo e(old('mobile')); ?>"
                        style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;"
                        placeholder="e.g., +91 9876543210"
                    >
                    <?php $__errorArgs = ['mobile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="color:#dc2626;font-size:13px;margin-top:6px;"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label style="font-size:15px;font-weight:500;margin-bottom:8px;display:block;">Status</label>
                    <label class="toggle-switch" style="display:inline-flex;align-items:center;cursor:pointer;gap:12px;">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            <?php echo e(old('is_active', '1') == '1' ? 'checked' : ''); ?>

                            class="toggle-input"
                        >
                        <span class="toggle-slider"></span>
                        <span class="toggle-label" style="font-size:15px;font-weight:500;">Active</span>
                    </label>
                    <p class="muted" style="font-size:13px;margin-top:6px;">Only active recipients will receive notifications.</p>
                </div>

                <style>
                    .toggle-switch { position: relative; }
                    .toggle-input { position: absolute; opacity: 0; width: 0; height: 0; }
                    .toggle-slider {
                        position: relative;
                        display: inline-block;
                        width: 48px;
                        height: 24px;
                        background-color: #cbd5e1;
                        border-radius: 24px;
                        transition: background-color 0.3s;
                    }
                    .toggle-slider::before {
                        content: '';
                        position: absolute;
                        width: 18px;
                        height: 18px;
                        left: 3px;
                        top: 3px;
                        background-color: white;
                        border-radius: 50%;
                        transition: transform 0.3s;
                    }
                    .toggle-input:checked + .toggle-slider {
                        background-color: #22c55e;
                    }
                    .toggle-input:checked + .toggle-slider::before {
                        transform: translateX(24px);
                    }
                    .toggle-input:focus + .toggle-slider {
                        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
                    }
                </style>

            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:12px;">
            <button type="submit" class="btn" style="background:#0B6BBD;color:#fff;padding:12px 24px;border:none;border-radius:10px;font-size:15px;font-weight:500;cursor:pointer;">
                Create Recipient
            </button>
            <a href="<?php echo e(route('notification-recipients.index')); ?>" class="btn" style="background:#6b7280;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-size:15px;font-weight:500;">
                Cancel
            </a>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/notification-recipients/create.blade.php ENDPATH**/ ?>