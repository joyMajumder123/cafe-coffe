<?php
/**
 * Customer Sidebar Navigation
 * 
 * A responsive sidebar for authenticated customer pages (profile, orders, checkout).
 * - Desktop (≥992px): Fixed left sidebar column, always visible
 * - Tablet (768–991px): Collapsible off-canvas sidebar, triggered by floating button
 * - Mobile (<768px): Full-width off-canvas overlay, triggered by floating button
 *
 * Usage: include this file inside a .container or .container-fluid on any user-page.
 * It renders both the floating toggle button and the sidebar markup.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sidebar_page = basename($_SERVER['PHP_SELF']);
$sidebar_customer_name = $_SESSION['customer_name'] ?? 'Guest';
$sidebar_customer_email = $_SESSION['customer_email'] ?? '';
$sidebar_is_logged_in = !empty($_SESSION['customer_id']);

// Navigation items — icon, label, href, linked pages (for active state)
$sidebar_nav_items = [
    [
        'icon'   => 'fas fa-user-circle',
        'label'  => 'My Profile',
        'href'   => 'profile.php',
        'pages'  => ['profile.php'],
    ],
    [
        'icon'   => 'fas fa-receipt',
        'label'  => 'Order History',
        'href'   => 'order_history.php',
        'pages'  => ['order_history.php'],
    ],
    [
        'icon'   => 'fas fa-shopping-cart',
        'label'  => 'Checkout',
        'href'   => 'checkout.php',
        'pages'  => ['checkout.php'],
    ],
    [
        'icon'   => 'fas fa-utensils',
        'label'  => 'Browse Menu',
        'href'   => 'menulist.php',
        'pages'  => ['menulist.php'],
    ],
    [
        'icon'   => 'fas fa-calendar-check',
        'label'  => 'Reservations',
        'href'   => 'contact.php',
        'pages'  => ['contact.php'],
    ],
    [
        'icon'   => 'fas fa-home',
        'label'  => 'Home',
        'href'   => 'index.php',
        'pages'  => ['index.php'],
    ],
];
?>

<!-- ============================================
     CUSTOMER SIDEBAR — FLOATING TOGGLE (tablet/mobile only)
     ============================================ -->
<?php if ($sidebar_is_logged_in): ?>
<button class="customer-sidebar-toggle d-lg-none" id="customerSidebarToggle"
        aria-label="Toggle sidebar navigation" aria-expanded="false" aria-controls="customerSidebar">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay backdrop for off-canvas -->
<div class="customer-sidebar-overlay d-lg-none" id="customerSidebarOverlay"></div>

<!-- ============================================
     CUSTOMER SIDEBAR — MAIN PANEL
     ============================================ -->
<aside class="customer-sidebar" id="customerSidebar" role="navigation" aria-label="Customer account navigation">
    <!-- Close button (tablet/mobile only) -->
    <button class="customer-sidebar-close d-lg-none" id="customerSidebarClose" aria-label="Close sidebar">
        <i class="fas fa-times"></i>
    </button>

    <!-- Profile summary -->
    <div class="customer-sidebar-profile">
        <div class="customer-sidebar-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="customer-sidebar-name"><?php echo htmlspecialchars($sidebar_customer_name); ?></div>
        <?php if ($sidebar_customer_email): ?>
            <div class="customer-sidebar-email"><?php echo htmlspecialchars($sidebar_customer_email); ?></div>
        <?php endif; ?>
    </div>

    <!-- Navigation links -->
    <nav class="customer-sidebar-nav">
        <ul>
            <?php foreach ($sidebar_nav_items as $item): ?>
                <?php
                    $is_active = in_array($sidebar_page, $item['pages'], true);
                ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>"
                       class="<?php echo $is_active ? 'active' : ''; ?>"
                       <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Bottom actions -->
    <div class="customer-sidebar-footer">
        <a href="customer_logout.php" class="customer-sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<?php endif; ?>

<!-- ============================================
     SIDEBAR STYLES (self-contained)
     ============================================ -->
<style>
/* ------- CSS Custom Properties (inherit from user-design.css) ------- */
.customer-sidebar,
.customer-sidebar-toggle {
    --sb-bg: #1f1f1f;
    --sb-bg-hover: #2a2a2a;
    --sb-text: #d4d4d4;
    --sb-text-active: #fff;
    --sb-gold: #c5a059;
    --sb-gold-glow: rgba(197, 160, 89, 0.25);
    --sb-width: 260px;
    --sb-radius: 16px;
    --sb-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== FLOATING TOGGLE BUTTON (tablet / mobile) ===== */
.customer-sidebar-toggle {
    position: fixed;
    bottom: 24px;
    left: 18px;
    z-index: 1090;
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 50%;
    background: var(--sb-gold);
    color: #fff;
    font-size: 1.2rem;
    box-shadow: 0 4px 14px var(--sb-gold-glow);
    cursor: pointer;
    transition: transform var(--sb-transition), box-shadow var(--sb-transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.customer-sidebar-toggle:hover,
.customer-sidebar-toggle:focus-visible {
    transform: scale(1.1);
    box-shadow: 0 6px 20px var(--sb-gold-glow);
    outline: none;
}

.customer-sidebar-toggle[aria-expanded="true"] {
    transform: rotate(90deg) scale(1.05);
}

/* ===== OVERLAY BACKDROP ===== */
.customer-sidebar-overlay {
    position: fixed;
    inset: 0;
    z-index: 1094;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(2px);
    opacity: 0;
    visibility: hidden;
    transition: opacity var(--sb-transition), visibility var(--sb-transition);
}

.customer-sidebar-overlay.open {
    opacity: 1;
    visibility: visible;
}

/* ===== SIDEBAR PANEL ===== */
.customer-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1095;
    width: var(--sb-width);
    height: 100vh;
    height: 100dvh; /* dynamic viewport height for mobile browsers */
    background: var(--sb-bg);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: var(--sb-gold) transparent;
    transition: transform var(--sb-transition), box-shadow var(--sb-transition);
}

/* Webkit scrollbar */
.customer-sidebar::-webkit-scrollbar {
    width: 4px;
}
.customer-sidebar::-webkit-scrollbar-thumb {
    background: var(--sb-gold);
    border-radius: 4px;
}

/* --- Desktop: fixed sidebar with spacer column approach --- */
@media (min-width: 992px) {
    .customer-sidebar {
        position: fixed;
        top: 90px; /* below the fixed navbar */
        left: 24px;
        height: calc(100vh - 110px);
        border-radius: var(--sb-radius);
        box-shadow: 0 12px 30px rgba(22, 22, 22, 0.10);
        flex-shrink: 0;
    }
}

/* --- Tablet & Mobile: off-canvas slide-in --- */
@media (max-width: 991.98px) {
    .customer-sidebar {
        transform: translateX(-100%);
        box-shadow: none;
    }

    .customer-sidebar.open {
        transform: translateX(0);
        box-shadow: 8px 0 30px rgba(0, 0, 0, 0.25);
    }
}

/* ===== CLOSE BUTTON ===== */
.customer-sidebar-close {
    position: absolute;
    top: 14px;
    right: 14px;
    background: none;
    border: none;
    color: var(--sb-text);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 4px;
    line-height: 1;
    transition: color var(--sb-transition);
}

.customer-sidebar-close:hover {
    color: var(--sb-gold);
}

/* ===== PROFILE SECTION ===== */
.customer-sidebar-profile {
    text-align: center;
    padding: 32px 20px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.customer-sidebar-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sb-gold), #a68545);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 1.5rem;
    color: #fff;
    box-shadow: 0 4px 12px var(--sb-gold-glow);
}

.customer-sidebar-name {
    font-weight: 600;
    font-size: 1.05rem;
    color: var(--sb-text-active);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.customer-sidebar-email {
    font-size: 0.8rem;
    color: var(--sb-text);
    opacity: 0.7;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ===== NAVIGATION LIST ===== */
.customer-sidebar-nav {
    flex: 1;
    padding: 16px 12px;
}

.customer-sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.customer-sidebar-nav li + li {
    margin-top: 4px;
}

.customer-sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 10px;
    color: var(--sb-text);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: background var(--sb-transition), color var(--sb-transition), box-shadow var(--sb-transition);
}

.customer-sidebar-nav a i {
    width: 22px;
    text-align: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: color var(--sb-transition);
}

.customer-sidebar-nav a:hover {
    background: var(--sb-bg-hover);
    color: var(--sb-text-active);
}

.customer-sidebar-nav a:hover i {
    color: var(--sb-gold);
}

.customer-sidebar-nav a.active {
    background: linear-gradient(135deg, rgba(197, 160, 89, 0.18), rgba(197, 160, 89, 0.08));
    color: var(--sb-gold);
    font-weight: 600;
    box-shadow: inset 3px 0 0 var(--sb-gold);
}

.customer-sidebar-nav a.active i {
    color: var(--sb-gold);
}

/* ===== FOOTER / LOGOUT ===== */
.customer-sidebar-footer {
    padding: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    margin-top: auto;
}

.customer-sidebar-logout {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 10px;
    color: #e57373;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: background var(--sb-transition), color var(--sb-transition);
}

.customer-sidebar-logout:hover {
    background: rgba(229, 115, 115, 0.12);
    color: #ef5350;
}

.customer-sidebar-logout i {
    width: 22px;
    text-align: center;
    font-size: 1rem;
}

/* ===== RESPONSIVE REFINEMENTS ===== */

/* Large desktops */
@media (min-width: 1200px) {
    .customer-sidebar {
        --sb-width: 270px;
    }
}

/* Tablets */
@media (min-width: 768px) and (max-width: 991.98px) {
    .customer-sidebar {
        --sb-width: 280px;
    }
    .customer-sidebar-toggle {
        bottom: 28px;
        left: 20px;
        width: 52px;
        height: 52px;
    }
}

/* Small phones */
@media (max-width: 575.98px) {
    .customer-sidebar {
        --sb-width: 82vw; /* fluid width on small screens */
        max-width: 300px;
    }
    .customer-sidebar-toggle {
        bottom: 20px;
        left: 14px;
        width: 46px;
        height: 46px;
        font-size: 1.1rem;
    }
    .customer-sidebar-profile {
        padding: 24px 16px 16px;
    }
    .customer-sidebar-avatar {
        width: 52px;
        height: 52px;
        font-size: 1.25rem;
    }
    .customer-sidebar-name {
        font-size: 0.95rem;
    }
    .customer-sidebar-nav a {
        padding: 10px 14px;
        font-size: 0.9rem;
    }
}

/* Very small phones (≤360px) */
@media (max-width: 360px) {
    .customer-sidebar {
        --sb-width: 88vw;
        max-width: 280px;
    }
}

/* ===== BODY SHIFT FOR DESKTOP SIDEBAR LAYOUT ===== */
/* When a page includes the sidebar in a flex row, the main content area
   automatically fills the remaining space via Bootstrap's col class.
   No body-level margin tricks needed. */

/* Reduce the floating toggle z-index so it doesn't overlap modals */
.modal-open .customer-sidebar-toggle {
    z-index: 1040;
}

/* Print: hide sidebar entirely */
@media print {
    .customer-sidebar,
    .customer-sidebar-toggle,
    .customer-sidebar-overlay {
        display: none !important;
    }
}
</style>

<!-- ============================================
     SIDEBAR JAVASCRIPT (self-contained)
     ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar  = document.getElementById('customerSidebar');
    var toggle   = document.getElementById('customerSidebarToggle');
    var overlay  = document.getElementById('customerSidebarOverlay');
    var closeBtn = document.getElementById('customerSidebarClose');

    if (!sidebar || !toggle) return; // Not logged in — elements don't exist

    function openSidebar() {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
        // Trap focus: move focus into sidebar
        if (closeBtn) closeBtn.focus();
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        toggle.focus();
    }

    // Toggle button click
    toggle.addEventListener('click', function () {
        var isOpen = sidebar.classList.contains('open');
        isOpen ? closeSidebar() : openSidebar();
    });

    // Close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    // Overlay click
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Escape key closes sidebar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Auto-close on window resize to desktop
    var mql = window.matchMedia('(min-width: 992px)');
    function handleResize(e) {
        if (e.matches && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    }
    if (mql.addEventListener) {
        mql.addEventListener('change', handleResize);
    } else if (mql.addListener) {
        mql.addListener(handleResize); // Safari < 14
    }

    // Swipe-to-close gesture (mobile)
    var touchStartX = 0;
    var touchCurrentX = 0;
    var isSwiping = false;

    sidebar.addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
        isSwiping = true;
    }, { passive: true });

    sidebar.addEventListener('touchmove', function (e) {
        if (!isSwiping) return;
        touchCurrentX = e.touches[0].clientX;
        var diff = touchStartX - touchCurrentX;
        // Only track leftward swipes
        if (diff > 0) {
            sidebar.style.transform = 'translateX(' + (-diff) + 'px)';
        }
    }, { passive: true });

    sidebar.addEventListener('touchend', function () {
        if (!isSwiping) return;
        isSwiping = false;
        var diff = touchStartX - touchCurrentX;
        sidebar.style.transform = '';
        if (diff > 80) { // Threshold: 80px swipe left = close
            closeSidebar();
        }
    }, { passive: true });
});
</script>
