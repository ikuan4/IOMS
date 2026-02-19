<?php $__env->startSection('title', 'View Contract'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $latest = $contract->latestVersion;
        $startIst = $latest?->start_date?->timezone('Asia/Kolkata');
        $endIst   = $latest?->end_date?->timezone('Asia/Kolkata');

        $statusLabel = $contract->status;
        $statusColor = match($statusLabel) {
            'Ongoing'       => '#16a34a',
            'Pending'       => '#2563eb',
            'Expiring Soon' => '#ca8a04',
            'Expired'       => '#dc2626',
            'Inactive'      => '#6b7280',
            default         => '#4b5563',
        };
    ?>

    <div class="header-card">
        <div class="header-left">
            <h2>Contract: <?php echo e($contract->contract_number); ?></h2>
            <p class="muted">
                <?php echo e($contract->contractType->name ?? 'Unknown type'); ?> &mdash;
                <?php echo e($contract->contract_with); ?>

            </p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
            <div>
                <span style="font-size:13px;color:#6b7280;">Status:</span>
                <span style="color:<?php echo e($statusColor); ?>;font-weight:700;margin-left:4px;">
                    <?php echo e($statusLabel); ?>

                </span>
            </div>
            <div class="muted" style="font-size:12px;">
                Grace period: <?php echo e($contract->grace_period_days); ?> days
            </div>
        </div>
    </div>

    
    <div class="card" style="margin-top:12px; padding:16px 20px;">
        <div style="display:flex;flex-wrap:wrap;gap:24px;font-size:14px;">

            <div>
                <div class="muted">Contract Type</div>
                <div style="font-weight:600;">
                    <?php echo e($contract->contractType->name ?? 'N/A'); ?>

                </div>
            </div>

            <div>
                <div class="muted">Contract With</div>
                <div style="font-weight:600;">
                    <?php echo e($contract->contract_with); ?>

                </div>
            </div>

            <div>
                <div class="muted">Start Date (IST)</div>
                <div style="font-weight:600;">
                    <?php if($startIst): ?>
                        <?php echo e($startIst->format('d M Y')); ?>

                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="muted">End Date (IST)</div>
                <div style="font-weight:600;">
                    <?php if($endIst): ?>
                        <?php echo e($endIst->format('d M Y')); ?>

                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <?php if(auth()->user() && auth()->user()->isSuperAdmin()): ?>
                <div>
                    <div class="muted">Branch</div>
                    <div style="font-weight:600;">
                        <?php echo e($contract->branch->name ?? 'N/A'); ?>

                    </div>
                </div>
            <?php endif; ?>

            <div>
                <div class="muted">Active Status</div>
                <div>
                    <?php if($contract->is_active): ?>
                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#d1fae5;color:#065f46;">ACTIVE</span>
                    <?php else: ?>
                        <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#e5e7eb;color:#6b7280;">INACTIVE</span>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="muted">Created By</div>
                <div style="font-weight:600;">
                    <?php echo e(optional($contract->creator)->name ?? 'N/A'); ?>

                    <?php if($contract->created_at): ?>
                        <span class="muted" style="font-size:12px;display:block;">
                            <?php echo e($contract->created_at->timezone('Asia/Kolkata')->format('d M Y, H:i')); ?>

                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="muted">Last Updated By</div>
                <div style="font-weight:600;">
                    <?php echo e(optional($contract->updater)->name ?? 'N/A'); ?>

                    <?php if($contract->updated_at): ?>
                        <span class="muted" style="font-size:12px;display:block;">
                            <?php echo e($contract->updated_at->timezone('Asia/Kolkata')->format('d M Y, H:i')); ?>

                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card" style="margin-top:12px; padding:16px 20px;">
        <h3 style="margin:0 0 12px 0;">Reminders Configured</h3>
        <?php if($contract->reminders->isNotEmpty()): ?>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                <?php $__currentLoopData = $contract->reminders->sortBy('days_before_end'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reminder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="padding:12px 20px;background:#dbeafe;border-radius:8px;text-align:center;">
                        <div style="font-size:20px;font-weight:700;color:#1e40af;"><?php echo e($reminder->days_before_end); ?></div>
                        <div style="font-size:12px;color:#1e3a8a;">days before</div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <span class="muted" style="font-size:13px;">No reminders configured.</span>
        <?php endif; ?>
    </div>

    
    <div class="card" style="margin-top:12px; padding:16px 20px;">
        <h3 style="margin:0 0 12px 0;">Notification Recipients</h3>
        <?php if($contract->notificationRecipients->isNotEmpty()): ?>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
                <?php $__currentLoopData = $contract->notificationRecipients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li style="font-size:14px;color:#4b5563;">
                        <strong><?php echo e($r->name); ?></strong>
                        <?php if($r->designation): ?>
                            <span class="muted">(<?php echo e($r->designation); ?>)</span>
                        <?php endif; ?>
                        <?php if($r->email): ?>
                            &mdash; <?php echo e($r->email); ?>

                        <?php else: ?>
                            <span style="color:#dc2626;font-size:12px;">(No email)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php else: ?>
            <span class="muted" style="font-size:13px;">No recipients assigned.</span>
        <?php endif; ?>
    </div>

    
    <div class="card" style="margin-top:12px; padding:16px 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <div>
                <h3 style="margin:0;">Version History</h3>
                <p class="muted" style="font-size:13px;margin:4px 0 0 0;">
                    All versions of this contract, with their effective dates and attachments.
                </p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                
                <?php if(!$contract->trashed() && Auth::user()->hasPermission('contracts.versions.create')): ?>
                        <a href="<?php echo e(route('contracts.versions.create', $contract)); ?>"
                           style="
                               display:flex;
                               align-items:center;
                               gap:8px;
                               padding:10px 16px;
                               border-radius:8px;
                               background:#3b82f6;
                               color:white;
                               text-decoration:none;
                               font-weight:600;
                               font-size:14px;
                               transition:all 0.2s;
                           "
                           onmouseover="this.style.background='#2563eb'"
                           onmouseout="this.style.background='#3b82f6'"
                           title="Add a new version to this contract">
                            <i data-feather="plus" style="width:18px;height:18px;"></i>
                            Add Version
                        </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if($contract->versions->isEmpty()): ?>
            <span class="muted" style="font-size:13px;">No versions recorded yet.</span>
        <?php else: ?>
            <?php
                // Count active (non-deleted) versions
                $activeVersionsCount = $contract->versions->filter(function($v) {
                    return is_null($v->deleted_at);
                })->count();
            ?>
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                <tr style="text-align:left;border-bottom:1px solid #e5e7eb;">
                    <th style="padding:8px;">Version</th>
                    <th style="padding:8px;">Description</th>
                    <th style="padding:8px;">Start (IST)</th>
                    <th style="padding:8px;">End (IST)</th>
                    <th style="padding:8px;">Attachments</th>
                    <th style="padding:8px; text-align:center;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $contract->versions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $vStart = $version->start_date?->timezone('Asia/Kolkata');
                        $vEnd   = $version->end_date?->timezone('Asia/Kolkata');
                        $vFiles = $version->files ?? collect();
                        $isDeleted = !is_null($version->deleted_at);
                    ?>
                    <tr style="border-bottom:1px solid #f3f4f6;<?php echo e($isDeleted ? 'opacity:0.5;' : ''); ?>">
                        <td style="padding:8px;font-weight:600;">
                            v<?php echo e($version->version_number); ?>

                            <?php if($isDeleted): ?>
                                <span style="color:#dc2626;font-size:12px;font-weight:600;margin-left:4px;">(Deleted)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px;max-width:320px;">
                            <?php if($version->description): ?>
                                <span style="font-size:13px;color:#4b5563;">
                                    <?php echo e(\Illuminate\Support\Str::limit($version->description, 120)); ?>

                                </span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px;">
                            <?php if($vStart): ?>
                                <?php echo e($vStart->format('d M Y')); ?>

                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px;">
                            <?php if($vEnd): ?>
                                <?php echo e($vEnd->format('d M Y')); ?>

                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px;">
                            <?php if($vFiles->isEmpty()): ?>
                                <span class="muted">No files</span>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:4px;">
                                    <?php $__currentLoopData = $vFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <a href="<?php echo e(route('files.download', $vf->storedFile->id)); ?>"
                                           target="_blank"
                                           style="color:#2563eb;text-decoration:none;font-size:13px;display:flex;align-items:center;gap:6px;">
                                            <i data-feather="file" style="width:14px;height:14px;"></i>
                                            <?php echo e($vf->storedFile->original_filename ?? 'File'); ?>

                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:8px; text-align:center; white-space:nowrap;">
                            <?php if(!$isDeleted): ?>
                                
                                <?php if(Auth::user()->hasPermission('contracts.versions.edit')): ?>
                                <a
                                    href="<?php echo e(route('contracts.versions.edit', $version)); ?>"
                                    style="
                                        background:#e0f2fe;
                                        color:#0369a1;
                                        padding:8px 10px;
                                        border-radius:8px;
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        border:none;
                                        cursor:pointer;
                                        margin-right:6px;
                                        text-decoration:none;
                                        font-size:13px;
                                        transition:all 0.2s;
                                    "
                                    onmouseover="this.style.background='#bae6fd'"
                                    onmouseout="this.style.background='#e0f2fe'"
                                    title="Edit this version">
                                    <i data-feather="edit-2" style="width:16px;height:16px;"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if(Auth::user()->hasPermission('contracts.versions.delete')): ?>
                                    <?php if($activeVersionsCount > 1): ?>
                                    <button
                                        type="button"
                                        data-contract-version-delete
                                        data-version-id="<?php echo e($version->id); ?>"
                                        data-version-number="v<?php echo e($version->version_number); ?>"
                                        style="
                                            background:#fee2e2;
                                            color:#dc2626;
                                            padding:8px 10px;
                                            border-radius:8px;
                                            display:inline-flex;
                                            align-items:center;
                                            justify-content:center;
                                            border:none;
                                            cursor:pointer;
                                            font-size:13px;
                                            transition:all 0.2s;
                                        "
                                        onmouseover="this.style.background='#fecaca'"
                                        onmouseout="this.style.background='#fee2e2'"
                                        title="Delete this version">
                                        <i data-feather="trash-2" style="width:16px;height:16px;"></i>
                                    </button>

                                    <form
                                        id="deleteVersionForm-<?php echo e($version->id); ?>"
                                        action="<?php echo e(route('contracts.versions.destroy', $version)); ?>"
                                        method="POST"
                                        style="display:none;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" style="display:none;"></button>
                                    </form>
                                    <?php else: ?>
                                    <span style="
                                        background:#f3f4f6;
                                        color:#9ca3af;
                                        padding:8px 10px;
                                        border-radius:8px;
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        font-size:11px;
                                        cursor:not-allowed;"
                                        title="Cannot delete the only version">
                                        <i data-feather="lock" style="width:14px;height:14px;"></i>
                                    </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                
                                <?php if(Auth::user()->hasPermission('contracts.versions.restore')): ?>
                                <button
                                    type="button"
                                    data-contract-version-restore
                                    data-version-id="<?php echo e($version->id); ?>"
                                    data-version-number="v<?php echo e($version->version_number); ?>"
                                    style="
                                        background:#d1fae5;
                                        color:#065f46;
                                        padding:8px 10px;
                                        border-radius:8px;
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        border:none;
                                        cursor:pointer;
                                        font-size:13px;
                                        transition:all 0.2s;
                                    "
                                    onmouseover="this.style.background='#a7f3d0'"
                                    onmouseout="this.style.background='#d1fae5'"
                                    title="Restore this version">
                                    <i data-feather="rotate-ccw" style="width:16px;height:16px;"></i>
                                </button>

                                <form
                                    id="restoreVersionForm-<?php echo e($version->id); ?>"
                                    action="<?php echo e(route('contracts.versions.restore', $version)); ?>"
                                    method="POST"
                                    style="display:none;">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" style="display:none;"></button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    
    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <?php if(!$contract->trashed()): ?>
            <?php if(Auth::user()->hasPermission('contracts.edit')): ?>
                <a href="<?php echo e(route('contracts.edit', $contract)); ?>"
                   style="
                       display:flex;
                       align-items:center;
                       gap:8px;
                       padding:12px 24px;
                       border-radius:10px;
                       background:#3b82f6;
                       color:white;
                       text-decoration:none;
                       font-weight:600;
                       transition:all 0.2s;
                   "
                   onmouseover="this.style.background='#2563eb'"
                   onmouseout="this.style.background='#3b82f6'">
                    <i data-feather="edit" style="width:18px;height:18px;"></i>
                    Edit Contract
                </a>
            <?php endif; ?>
        <?php endif; ?>

        
        <?php if(Auth::user()->isSuperAdmin() || Auth::user()->isDeveloper()): ?>
        <form method="POST" action="<?php echo e(route('contracts.send-test-notification', $contract)); ?>" style="display:inline-flex;align-items:center;gap:8px;">
            <?php echo csrf_field(); ?>
            <input type="email" name="email" placeholder="Email for test" required
                style="padding:12px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:14px;">
            <button type="submit"
                style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                    padding:12px 20px;
                    border-radius:10px;
                    background:#f59e0b;
                    color:white;
                    border:none;
                    font-weight:600;
                    cursor:pointer;
                    transition:all 0.2s;
                "
                onmouseover="this.style.background='#d97706'"
                onmouseout="this.style.background='#f59e0b'">
                <i data-feather="send" style="width:18px;height:18px;"></i>
                Send Test Email Notification
            </button>
        </form>
        <?php endif; ?>

        <?php if(Auth::user()->isSuperAdmin() || Auth::user()->isDeveloper()): ?>
        <form method="POST" action="<?php echo e(route('contracts.send-test-notification', $contract)); ?>" style="display:inline-flex;align-items:center;gap:8px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="channel" value="telegram">
            <button type="submit"
                style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                    padding:12px 20px;
                    border-radius:10px;
                    background:#10b981;
                    color:white;
                    border:none;
                    font-weight:600;
                    cursor:pointer;
                    transition:all 0.2s;
                "
                onmouseover="this.style.background='#059669'"
                onmouseout="this.style.background='#10b981'">
                <i data-feather="message-circle" style="width:18px;height:18px;"></i>
                Send Test Telegram Notification
            </button>
        </form>
        <?php endif; ?>

        <a href="<?php echo e(route('contracts.index')); ?>"
           style="
               display:flex;
               align-items:center;
               gap:8px;
               padding:12px 24px;
               border-radius:10px;
               background:#6b7280;
               color:white;
               text-decoration:none;
               font-weight:600;
               transition:all 0.2s;
           "
           onmouseover="this.style.background='#4b5563'"
           onmouseout="this.style.background='#6b7280'">
            <i data-feather="arrow-left" style="width:18px;height:18px;"></i>
            Back to List
        </a>
    </div>

    
    <?php if(session('success')): ?>
        <div style="position:fixed;bottom:20px;right:20px;background:#10b981;color:#fff;padding:16px 24px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div style="position:fixed;bottom:20px;right:20px;background:#ef4444;color:#fff;padding:16px 24px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:1000;">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <?php echo $__env->make('partials.confirmation-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/contracts/show.blade.php ENDPATH**/ ?>