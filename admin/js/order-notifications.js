/**
 * Admin Order Notification System
 * Polls the server for new orders and displays real-time notifications.
 */
(function () {
    'use strict';

    const POLL_INTERVAL = 5000; // Check every 5 seconds
    const NOTIFICATION_SOUND_URL = 'https://cdn.pixabay.com/audio/2022/12/12/audio_e8c59e09af.mp3'; // Short notification chime

    let lastOrderId = 0;
    let notificationSound = null;
    let unseenCount = 0;
    let isFirstLoad = true;

    // ── Initialization ───────────────────────────────────────────────
    function init() {
        // Pre-load notification sound
        notificationSound = new Audio(NOTIFICATION_SOUND_URL);
        notificationSound.volume = 0.5;

        // Build notification UI elements
        buildNotificationUI();

        // Start polling
        poll();
        setInterval(poll, POLL_INTERVAL);
    }

    // ── Build UI ─────────────────────────────────────────────────────
    function buildNotificationUI() {
        // --- Notification bell in navbar ---
        const navbar = document.querySelector('.navbar .container-fluid');
        if (!navbar) return;

        const bellWrapper = document.createElement('div');
        bellWrapper.className = 'd-flex align-items-center gap-3';
        bellWrapper.style.marginLeft = 'auto';
        bellWrapper.style.marginRight = '12px';

        bellWrapper.innerHTML = `
            <div class="position-relative" id="notif-bell-wrapper" style="cursor:pointer;" title="New orders">
                <i class="fas fa-bell text-white" style="font-size:1.25rem;"></i>
                <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;font-size:.65rem;">
                    0
                </span>
            </div>
        `;

        // Insert before the logout button
        const logoutBtn = navbar.querySelector('a[href="logout.php"]');
        if (logoutBtn) {
            navbar.insertBefore(bellWrapper, logoutBtn);
        } else {
            navbar.appendChild(bellWrapper);
        }

        // Click bell → go to orders page
        document.getElementById('notif-bell-wrapper').addEventListener('click', function () {
            unseenCount = 0;
            updateBadge();
            window.location.href = 'orders.php';
        });

        // --- Toast container (bottom-right) ---
        const toastContainer = document.createElement('div');
        toastContainer.id = 'order-toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1090';
        document.body.appendChild(toastContainer);
    }

    // ── Polling ──────────────────────────────────────────────────────
    function poll() {
        fetch('check_new_orders.php?last_order_id=' + lastOrderId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                // Update latest order id
                if (data.latest_order_id) {
                    lastOrderId = data.latest_order_id;
                }

                // Update pending badge on sidebar link
                updateSidebarBadge(data.pending_count);

                // Show notifications for new orders (skip on first load)
                if (!isFirstLoad && data.new_orders && data.new_orders.length > 0) {
                    data.new_orders.forEach(function (order) {
                        unseenCount++;
                        showToast(order);
                    });

                    // Play sound once for the batch
                    playSound();

                    // Update bell badge
                    updateBadge();

                    // If currently on orders page, refresh the table
                    if (window.location.pathname.indexOf('orders.php') !== -1) {
                        setTimeout(function(){ location.reload(); }, 1500);
                    }
                }

                isFirstLoad = false;
            })
            .catch(function (err) {
                console.warn('Order notification poll error:', err);
            });
    }

    // ── Badge helpers ────────────────────────────────────────────────
    function updateBadge() {
        var badge = document.getElementById('notif-badge');
        if (!badge) return;
        if (unseenCount > 0) {
            badge.textContent = unseenCount > 99 ? '99+' : unseenCount;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }

    function updateSidebarBadge(count) {
        // Find the Orders link in the sidebar
        var sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
        sidebarLinks.forEach(function (link) {
            if (link.textContent.indexOf('Orders') !== -1) {
                var badge = link.querySelector('.order-pending-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'order-pending-badge badge bg-warning text-dark ms-2';
                    badge.style.fontSize = '.7rem';
                    link.appendChild(badge);
                }
                if (count > 0) {
                    badge.textContent = count + ' pending';
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
    }

    // ── Toast notification ───────────────────────────────────────────
    function showToast(order) {
        var container = document.getElementById('order-toast-container');
        if (!container) return;

        var toastId = 'toast-order-' + order.id;
        var toastEl = document.createElement('div');
        toastEl.id = toastId;
        toastEl.className = 'toast show';
        toastEl.setAttribute('role', 'alert');
        toastEl.style.minWidth = '300px';

        toastEl.innerHTML =
            '<div class="toast-header bg-success text-white">' +
                '<i class="fas fa-shopping-bag me-2"></i>' +
                '<strong class="me-auto">New Order #' + order.id + '</strong>' +
                '<small>Just now</small>' +
                '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>' +
            '</div>' +
            '<div class="toast-body">' +
                '<strong>' + escapeHtml(order.customer_name) + '</strong> placed an order for ' +
                '<span class="text-success fw-bold">₹' + order.total_amount + '</span>' +
            '</div>';

        container.appendChild(toastEl);

        // Auto-dismiss after 8 seconds
        setTimeout(function () {
            toastEl.classList.remove('show');
            setTimeout(function () { toastEl.remove(); }, 300);
        }, 8000);

        // Manual close button
        var closeBtn = toastEl.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                toastEl.classList.remove('show');
                setTimeout(function () { toastEl.remove(); }, 300);
            });
        }
    }

    // ── Sound ────────────────────────────────────────────────────────
    function playSound() {
        if (notificationSound) {
            notificationSound.currentTime = 0;
            notificationSound.play().catch(function () {
                // Browser may block autoplay until user interacts with page
            });
        }
    }

    // ── Utility ──────────────────────────────────────────────────────
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Boot ─────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
