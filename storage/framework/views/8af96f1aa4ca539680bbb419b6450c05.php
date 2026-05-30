<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; background: #f8fafc; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 0 1.5rem; display: flex; align-items: center; justify-content: space-between; height: 56px; }
        nav a { color: #94a3b8; text-decoration: none; margin-left: 1rem; font-size: .875rem; }
        nav a:hover { color: #fff; }
        nav .brand { font-weight: 700; font-size: 1rem; color: #fff; margin: 0; }
        .container { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: .5rem; border: 1px solid #e2e8f0; padding: 1.5rem; margin-bottom: 1rem; }
        .badge { display: inline-block; padding: .15rem .5rem; border-radius: 9999px; font-size: .75rem; font-weight: 600; background: #dbeafe; color: #1d4ed8; margin: .1rem; }
        .badge.admin { background: #fce7f3; color: #9d174d; }
        .badge.moderator { background: #d1fae5; color: #065f46; }
        .btn { display: inline-block; padding: .5rem 1rem; border-radius: .375rem; font-size: .875rem; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .alert { padding: .75rem 1rem; border-radius: .375rem; margin-bottom: 1rem; font-size: .875rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
<nav>
    <span class="brand"><?php echo e(config('app.name')); ?></span>
    <div>
        <?php if(auth()->guard()->check()): ?>
            <a href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
            <?php if(auth()->user()->isAdmin()): ?>
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('auth.logout')); ?>" style="display:inline">
                <?php echo csrf_field(); ?>
                <button type="submit" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.875rem;margin-left:1rem;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">Logout</button>
            </form>
        <?php else: ?>
            <a href="<?php echo e(route('auth.redirect')); ?>">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <?php if($errors->any()): ?>
        <div class="alert alert-error"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <?php echo $__env->yieldContent('content'); ?>
</div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>