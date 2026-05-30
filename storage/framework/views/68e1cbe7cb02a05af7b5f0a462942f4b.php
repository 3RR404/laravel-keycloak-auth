<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<h1 style="margin-bottom:1.5rem;">Dashboard</h1>

<div class="card">
    <h2 style="margin-top:0;">Welcome, <?php echo e($user->name); ?></h2>
    <p style="color:#475569; margin:.25rem 0;"><?php echo e($user->email); ?></p>

    <div style="margin-top:1rem;">
        <strong>Roles:</strong>
        <?php $__empty_1 = true; $__currentLoopData = $user->keycloak_roles ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <span class="badge <?php echo e($role === 'app-admin' ? 'admin' : ($role === 'app-moderator' ? 'moderator' : '')); ?>">
                <?php echo e($role); ?>

            </span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span style="color:#94a3b8; font-size:.875rem;">No roles assigned</span>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Permissions</h3>
    <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
        <thead>
            <tr style="border-bottom:2px solid #e2e8f0;">
                <th style="text-align:left; padding:.5rem;">Ability</th>
                <th style="text-align:left; padding:.5rem;">Access</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = ['admin', 'manage-users', 'manage-content', 'view-reports', 'view-content']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ability): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:.5rem;"><code><?php echo e($ability); ?></code></td>
                <td style="padding:.5rem;">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($ability)): ?>
                        <span style="color:#16a34a; font-weight:600;">✓ Allowed</span>
                    <?php else: ?>
                        <span style="color:#dc2626;">✗ Denied</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>

<?php if($user->isAdmin()): ?>
<div class="card" style="border-color:#fca5a5; background:#fff5f5;">
    <h3 style="margin-top:0; color:#9d174d;">Admin Panel</h3>
    <p style="color:#be185d; font-size:.875rem;">You have admin access.</p>
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-danger">Go to Admin</a>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>