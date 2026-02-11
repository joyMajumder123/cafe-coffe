<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$nav_links = [
    'dashboard.php'  => '📊 Dashboard',
    'reports.php'    => '📈 Reports',
    'orders.php'     => '📦 Orders',
    'inquiries.php'  => '💬 Inquiries',
    'menu.php'       => '🍽️ Menu',
    'categories.php' => '📂 Categories',
    'reservation.php'=> '📅 Reservations',
    'staff.php'      => '👥 Staff'
];
?>
<div class="col-md-2 sidebar">
    <ul class="nav flex-column">
        <?php foreach ($nav_links as $path => $label): ?>
            <li class="nav-item">
                <a href="<?php echo $path; ?>" class="nav-link <?php echo $current_page === $path ? 'active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>