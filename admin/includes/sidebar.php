<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

// Navigation links with required permission key
$nav_links = [
    'dashboard.php'  => ['label' => '📊 Dashboard',    'perm' => 'dashboard.view'],
    'reports.php'    => ['label' => '📈 Reports',       'perm' => 'reports.view'],
    'orders.php'     => ['label' => '📦 Orders',        'perm' => 'orders.view'],
    'inquiries.php'  => ['label' => '💬 Inquiries',     'perm' => 'inquiries.view'],
    'menu.php'       => ['label' => '🍽️ Menu',          'perm' => 'menu.view'],
    'categories.php' => ['label' => '📂 Categories',    'perm' => 'categories.view'],
    'reservation.php'=> ['label' => '📅 Reservations',  'perm' => 'reservations.view'],
    'staff.php'      => ['label' => '👥 Staff',         'perm' => 'staff.view'],
    'settings.php'   => ['label' => '⚙️ Settings',      'perm' => 'roles.view'],
];
?>
<div class="col-md-2 sidebar">
    <ul class="nav flex-column">
        <?php foreach ($nav_links as $path => $info):
            // Only show links the user has permission for
            if (function_exists('has_permission') && !has_permission($info['perm'])) {
                continue;
            }
        ?>
            <li class="nav-item">
                <a href="<?php echo $path; ?>" class="nav-link <?php echo $current_page === $path ? 'active' : ''; ?>">
                    <?php echo $info['label']; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (function_exists('rbac_display_name')): ?>
    <div class="mt-4 px-3">
        <small class="text-light opacity-75">
            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars(rbac_display_name()) ?><br>
            <span class="badge bg-secondary mt-1"><?= htmlspecialchars(rbac_role_name()) ?></span>
        </small>
    </div>
    <?php endif; ?>
</div>