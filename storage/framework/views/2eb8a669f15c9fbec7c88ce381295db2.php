<?php $__env->startSection('title', 'Edit Role'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>Edit Role: <?php echo e($role->name); ?></h2>
            <p class="muted">Update role information and settings.</p>
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

    <form method="POST" action="<?php echo e(route('roles.update', $role->id)); ?>" onsubmit="event.preventDefault(); checkRoleDeactivate(this, '<?php echo e($role->id); ?>', '<?php echo e(addslashes($role->name)); ?>');">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="card" style="margin-top:16px;">
            <div style="display:flex;flex-direction:column;gap:18px;max-width:540px;">

                
                <?php
                    /** @var \App\Models\User|null $currentUser */
                    $currentUser = auth()->user();
                    $isDeveloper = $currentUser ? $currentUser->isSuperAdmin() : false;
                ?>
                <?php if($isDeveloper): ?>
                    <div>
                        <label for="branch_id" style="font-size:15px;font-weight:600;">Branch</label><br>
                        <select name="branch_id" id="branch_id" style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;">
                            <option value="">-- Select Branch (optional) --</option>
                            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($b->id); ?>" <?php echo e((old('branch_id', $role->branch_id) == $b->id) ? 'selected' : ''); ?>><?php echo e($b->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['branch_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div style="color:#dc2626;font-size:13px;"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <p class="muted" style="font-size:13px;margin-top:6px;">Assign this role to a specific branch. If left empty, role applies globally.</p>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="branch_id" value="<?php echo e($currentUser->branch_id); ?>">
                <?php endif; ?>

                
                <div>
                    <label for="name" style="font-size:15px;font-weight:600;">
                        Role Name
                        <span style="color:#dc2626;margin-left:2px;">*</span>
                    </label><br>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="<?php echo e(old('name', $role->name)); ?>"
                        required
                        autofocus
                        style="
                            width:100%;
                            padding:14px 16px;
                            border-radius:10px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                        "
                    >
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="color:#dc2626;font-size:13px;"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        A unique name for this role (e.g., "Regional Manager", "Content Editor")
                    </p>
                </div>

                
                <div>
                    <label for="description" style="font-size:15px;font-weight:600;">
                        Description
                    </label><br>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        style="
                            width:100%;
                            padding:14px 16px;
                            border-radius:10px;
                            border:1px solid #d0d7e0;
                            font-size:15px;
                            resize:vertical;
                        "
                    ><?php echo e(old('description', $role->description)); ?></textarea>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div style="color:#dc2626;font-size:13px;"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <p class="muted" style="font-size:13px;margin-top:6px;">
                        Brief description of the role's purpose and responsibilities
                    </p>
                </div>

                
                <div>
                    <label style="font-size:15px;font-weight:600;">Status</label><br>
                    <div style="
                        display:inline-flex;
                        align-items:center;
                        gap:10px;
                        user-select:none;
                        margin-top:8px;
                    ">
                        <div class="toggle-switch" onclick="toggleCheckbox(event, this)" style="
                            width:48px;
                            height:26px;
                            background:<?php echo e(old('is_active', $role->is_active) ? '#22c55e' : '#cbd5e1'); ?>;
                            border-radius:13px;
                            position:relative;
                            transition:all 0.3s;
                            cursor:pointer;
                        ">
                            <div class="toggle-knob" style="
                                width:20px;
                                height:20px;
                                background:white;
                                border-radius:50%;
                                position:absolute;
                                top:3px;
                                left:<?php echo e(old('is_active', $role->is_active) ? '25px' : '3px'); ?>;
                                transition:all 0.3s;
                                box-shadow:0 2px 4px rgba(0,0,0,0.2);
                            "></div>
                        </div>
                        <input
                            type="checkbox"
                            id="is_active_checkbox"
                            name="is_active"
                            value="1"
                            <?php echo e(old('is_active', $role->is_active) ? 'checked' : ''); ?>

                            style="display:none;"
                        >
                        <span>Active (role can be assigned to users)</span>
                    </div>
                </div>

            </div>
        </div>

        
        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <button
                type="submit"
                style="
                    background:#22c55e;
                    color:white;
                    padding:14px 24px;
                    border-radius:10px;
                    font-weight:1000;
                    font-size:15px;
                    border:none;
                    display:flex;
                    align-items:center;
                    gap:8px;
                    cursor:pointer;
                "
            >
                <span data-feather="save"></span> Update Role
            </button>

            <a
                href="<?php echo e(route('roles.index')); ?>"
                style="
                    background:#f51607;
                    color:rgb(253, 253, 253);
                    padding:14px 24px;
                    border-radius:10px;
                    font-weight:1000;
                    font-size:15px;
                    text-decoration:none;
                    display:flex;
                    align-items:center;
                    gap:8px;
                "
            >
                <span data-feather="x-circle"></span> Cancel
            </a>
        </div>
    </form>
    <?php echo $__env->make('partials.confirmation-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function toggleCheckbox(event, toggleSwitch) {
        // Prevent event bubbling
        event.stopPropagation();
        event.preventDefault();

        // Get the checkbox (now it's the next sibling)
        const checkbox = toggleSwitch.nextElementSibling;
        checkbox.checked = !checkbox.checked;

        const knob = toggleSwitch.querySelector('.toggle-knob');

        if (checkbox.checked) {
            toggleSwitch.style.background = '#22c55e';
            knob.style.left = '25px';
        } else {
            toggleSwitch.style.background = '#cbd5e1';
            knob.style.left = '3px';
        }
    }
</script>
<script>
    async function checkRoleDeactivate(form, roleId, roleName) {
        try {
            // Before checking anything, ensure unchecked checkbox is handled
            const checkbox = form.querySelector('#is_active_checkbox');
            if (checkbox && !checkbox.checked) {
                // Add hidden field to ensure 0 is sent for unchecked
                let hiddenField = form.querySelector('input[name="is_active"][type="hidden"]');
                if (!hiddenField) {
                    hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'is_active';
                    hiddenField.value = '0';
                    form.appendChild(hiddenField);
                }
                // Remove the checkbox so it doesn't send value="1"
                checkbox.remove();
            }

            // Determine if the form is attempting to deactivate the role
            const wantsActive = checkbox && checkbox.checked;

            // If still active or no change, just submit
            if (wantsActive) {
                form.submit();
                return;
            }

            const url = `<?php echo e(url('/')); ?>/roles/${roleId}/mapped-active-users`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            const count = parseInt(data.count || 0, 10);

            if (count <= 0) {
                form.submit();
                return;
            }

            let input = form.querySelector('input[name="deactivate_mapped_users"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'deactivate_mapped_users';
                form.appendChild(input);
            }
            input.value = '1';

            showConfirmModal({
                type: 'delete',
                title: 'Confirm Deactivation',
                subtitle: '',
                message: `Role "${roleName}" is assigned to ${count} active user${count===1? '' : 's'}. Confirm deactivation? This will set those users to inactive.`,
                confirmText: 'Deactivate Role',
                form: form
            });
        } catch (e) {
            console.error(e);
            form.submit();
        }
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/roles/edit.blade.php ENDPATH**/ ?>