<?php $__env->startSection('title', 'Welcome — ' . config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="card" style="text-align:center; padding: 3rem 1.5rem;">
    <h1 style="font-size:2rem; margin:0 0 .5rem;">Laravel + Keycloak</h1>
    <p style="color:#64748b; margin:0 0 2rem;">Production-ready OIDC authentication starter kit for SaaS apps.</p>

    <?php if(auth()->guard()->guest()): ?>
        <a href="<?php echo e(route('auth.redirect')); ?>" class="btn btn-primary" style="font-size:1rem; padding:.75rem 2rem;">
            Login with Keycloak
        </a>
    <?php else: ?>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-primary" style="font-size:1rem; padding:.75rem 2rem;">
            Go to Dashboard
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="margin-top:0;">What's included</h2>
    <ul style="line-height:2; color:#475569;">
        <li>OIDC Authorization Code flow via Socialite + Keycloak provider</li>
        <li>Automatic access token refresh with configurable expiry margin</li>
        <li>Keycloak realm roles mapped to Laravel Gate abilities</li>
        <li>Role-based middleware: <code>keycloak.auth</code>, <code>keycloak.role:app-admin</code></li>
        <li>Auto user creation / profile sync on first login</li>
        <li>Proper logout — revokes refresh token + Keycloak end-session</li>
        <li>Docker Compose: Keycloak 26 + PostgreSQL + Nginx + PHP 8.3-FPM</li>
    </ul>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/welcome.blade.php ENDPATH**/ ?>