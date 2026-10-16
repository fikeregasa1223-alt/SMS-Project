<?php
declare(strict_types=1);

/**
 * SMS — Topbar
 * Path: includes/topbar.php
 */

$user     = current_user();
$initials = '';

if ($user) {
    $parts = preg_split('/\s+/', trim($user['full_name'] ?? ''));
    foreach ($parts as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    $initials = mb_substr($initials, 0, 2);
}

$roleLabel = ucfirst($user['primary_role'] ?? 'Guest');
?>
<header class="topbar">
    <button class="icon-btn" data-sidebar-toggle aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="<?= e(url(role_home($user['primary_role'] ?? 'student'))) ?>">
                    <?= e(function_exists('__') ? __('nav.dashboard') : 'Dashboard') ?>
                </a>
            </li>
            <?php foreach (($breadcrumbs ?? []) as $i => $bc): ?>
                <?php if (!empty($bc['url']) && $i < count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-item"><a href="<?= e(url($bc['url'])) ?>"><?= e($bc['label']) ?></a></li>
                <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($bc['label']) ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="topbar-search d-none d-lg-block ms-auto">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control"
               placeholder="<?= e(function_exists('__') ? __('common.search') : 'Search') ?>…"
               aria-label="Search">
    </div>

    <div class="d-flex align-items-center gap-1 ms-auto ms-lg-2">

        <!-- 🌐 LANGUAGE SWITCHER -->
        <?php if (function_exists('render_language_switcher')): ?>
            <?= render_language_switcher() ?>
        <?php else: ?>
            <!-- Fallback -->
            <div class="dropdown">
                <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Language">
                    <i class="bi bi-translate"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                    <li><a class="dropdown-item" href="?lang=am">አማርኛ</a></li>
                    <li><a class="dropdown-item" href="?lang=om">Afaan Oromoo</a></li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Theme toggle -->
        <button class="icon-btn" data-theme-toggle aria-label="Toggle theme">
            <i class="bi bi-moon-stars-fill"></i>
        </button>

        <!-- Notifications -->
        <button class="icon-btn position-relative" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="dot"></span>
        </button>

        <!-- User menu -->
        <div class="dropdown">
            <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">
                    <?php if (!empty($user['photo'])): ?>
                        <img src="<?= e(url('uploads/profiles/' . $user['photo'])) ?>" alt="">
                    <?php else: ?>
                        <?= e($initials ?: 'U') ?>
                    <?php endif; ?>
                </span>
                <span class="d-none d-md-block text-start">
                    <span class="d-block fw-semibold small"><?= e($user['full_name'] ?? 'User') ?></span>
                    <span class="d-block text-muted" style="font-size:.7rem;"><?= e($roleLabel) ?></span>
                </span>
                <i class="bi bi-chevron-down text-muted d-none d-md-block"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="<?= e(url(($user['primary_role'] ?? 'student') . '/profile.php')) ?>">
                        <i class="bi bi-person"></i>
                        <?= e(function_exists('__') ? __('common.profile') : 'My Profile') ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= e(url('admin/settings/index.php')) ?>">
                        <i class="bi bi-sliders"></i>
                        <?= e(function_exists('__') ? __('common.settings') : 'Account Settings') ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= e(url('auth/change-password.php')) ?>">
                        <i class="bi bi-key"></i>
                        <?= e(function_exists('__') ? __('common.change_password') : 'Change Password') ?>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= e(url('auth/logout.php')) ?>">
                        <i class="bi bi-box-arrow-right"></i>
                        <?= e(function_exists('__') ? __('common.logout') : 'Logout') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>