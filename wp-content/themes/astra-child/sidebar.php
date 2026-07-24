<?php
$sidebar_badges = function_exists('stock_supply_get_sidebar_badges') ? stock_supply_get_sidebar_badges() : ['requests' => 0, 'maintenance' => 0, 'total' => 0];
$requests_count = $sidebar_badges['requests'];
$maintenance_count = $sidebar_badges['maintenance'];
?>
<!-- ============================================================
   Custom Sidebar - Animated Expand/Collapse
   Replaces Astra default sidebar
   Font: Roboto (Material Theme)
   ============================================================ -->

<!-- ========== DESKTOP SIDEBAR ========== -->
<aside id="custom-sidebar" class="custom-sidebar-nav">
    <!-- Logo area -->
    <div class="sidebar-logo-area">
        <div class="sidebar-logo-icon">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/cropped-Icon-TBS.png" alt="Logo" width="28" height="28">
        </div>
        <span id="sidebar-scramble-text" class="sidebar-logo-text" data-text="Stock Supply">Stock Supply</span>
    </div>

    <!-- Navigation links -->
    <nav class="sidebar-nav-links">
        <!-- Dashboard -->
        <a href="<?php echo esc_url(home_url('/home/')); ?>" class="sidebar-link <?php echo (is_page('home')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-house" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Dashboard</span>
        </a>

        <!-- Monitor -->
        <a href="<?php echo esc_url(home_url('/monitor/')); ?>" class="sidebar-link <?php echo (is_page('monitor')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-desktop" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Monitor</span>
        </a>

        <!-- Laptop -->
        <a href="<?php echo esc_url(home_url('/laptop/')); ?>" class="sidebar-link <?php echo (is_page('laptop')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-laptop" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Laptop</span>
        </a>

        <!-- Accessories -->
        <a href="<?php echo esc_url(home_url('/accessories/')); ?>" class="sidebar-link <?php echo (is_page('accessories')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-keyboard" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Accessories</span>
        </a>

        <!-- Maintenance -->
        <a href="<?php echo esc_url(home_url('/maintenance/')); ?>" class="sidebar-link <?php echo (is_page('maintenance')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-screwdriver-wrench" style="font-size: 20px;"></i>
                <?php if ($maintenance_count > 0): ?>
                    <span class="sidebar-icon-badge warning"><?php echo $maintenance_count > 99 ? '99+' : $maintenance_count; ?></span>
                <?php endif; ?>
            </span>
            <span class="sidebar-link-text">Maintenance</span>
            <?php if ($maintenance_count > 0): ?>
                <span class="sidebar-badge warning"><?php echo $maintenance_count > 99 ? '99+' : $maintenance_count; ?></span>
            <?php endif; ?>
        </a>

        <!-- History -->
        <a href="<?php echo esc_url(home_url('/history/')); ?>" class="sidebar-link <?php echo (is_page('history')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-clock-rotate-left" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">History</span>
        </a>

        <!-- Employees -->
        <a href="<?php echo esc_url(home_url('/owner/')); ?>" class="sidebar-link <?php echo (is_page('owner')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-users" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Employees</span>
        </a>

        <!-- Requests -->
        <a href="<?php echo esc_url(home_url('/request-dashboard/')); ?>" class="sidebar-link <?php echo (is_page('request-dashboard')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-list-check" style="font-size: 20px;"></i>
                <?php if ($requests_count > 0): ?>
                    <span class="sidebar-icon-badge danger"><?php echo $requests_count > 99 ? '99+' : $requests_count; ?></span>
                <?php endif; ?>
            </span>
            <span class="sidebar-link-text">Requests</span>
            <?php if ($requests_count > 0): ?>
                <span class="sidebar-badge danger"><?php echo $requests_count > 99 ? '99+' : $requests_count; ?></span>
            <?php endif; ?>
        </a>

        <!-- Add Device -->
        <a href="<?php echo esc_url(home_url('/add-device/')); ?>" class="sidebar-link <?php echo (is_page('add-device')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-plus" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Add Device</span>
        </a>
    </nav>

    <!-- Bottom section: User + Logout -->
    <div class="sidebar-bottom">
        <a href="<?php echo esc_url(home_url('/logout/')); ?>" class="sidebar-link sidebar-logout">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-right-from-bracket" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Logout</span>
        </a>
    </div>
</aside>

<!-- ========== MOBILE TOP BAR ========== -->
<div id="mobile-sidebar-bar" class="mobile-sidebar-bar">
    <button id="mobile-menu-btn" class="mobile-menu-btn" aria-label="Open Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
        <?php if ($sidebar_badges['total'] > 0): ?>
            <span class="mobile-btn-badge-dot"></span>
        <?php endif; ?>
    </button>
    <span class="mobile-logo-text">Stock Supply</span>
</div>

<!-- ========== MOBILE OVERLAY ========== -->
<div id="mobile-sidebar-overlay" class="mobile-sidebar-overlay"></div>

<!-- ========== MOBILE SIDEBAR PANEL ========== -->
<div id="mobile-sidebar-panel" class="mobile-sidebar-panel">
    <div class="mobile-sidebar-header">
        <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/tbs.png" alt="Logo" width="28" height="28" style="flex-shrink:0;">
        <span id="mobile-scramble-text" class="mobile-sidebar-title" data-text="Stock Supply">Stock Supply</span>
        <button id="mobile-close-btn" class="mobile-close-btn" aria-label="Close Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <nav class="mobile-nav-links">
        <a href="<?php echo esc_url(home_url('/home/')); ?>" class="mobile-link <?php echo (is_page('home')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-house" style="font-size: 20px;"></i> Dashboard</span>
        </a>
        <a href="<?php echo esc_url(home_url('/monitor/')); ?>" class="mobile-link <?php echo (is_page('monitor')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-desktop" style="font-size: 20px;"></i> Monitor</span>
        </a>
        <a href="<?php echo esc_url(home_url('/laptop/')); ?>" class="mobile-link <?php echo (is_page('laptop')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-laptop" style="font-size: 20px;"></i> Laptop</span>
        </a>
        <a href="<?php echo esc_url(home_url('/accessories/')); ?>" class="mobile-link <?php echo (is_page('accessories')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-keyboard" style="font-size: 20px;"></i> Accessories</span>
        </a>
        <a href="<?php echo esc_url(home_url('/maintenance/')); ?>" class="mobile-link <?php echo (is_page('maintenance')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-screwdriver-wrench" style="font-size: 20px;"></i> Maintenance</span>
            <?php if ($maintenance_count > 0): ?>
                <span class="mobile-badge warning"><?php echo $maintenance_count > 99 ? '99+' : $maintenance_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(home_url('/history/')); ?>" class="mobile-link <?php echo (is_page('history')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-clock-rotate-left" style="font-size: 20px;"></i> History</span>
        </a>
        <a href="<?php echo esc_url(home_url('/owner/')); ?>" class="mobile-link <?php echo (is_page('owner')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-users" style="font-size: 20px;"></i> Employees</span>
        </a>
        <a href="<?php echo esc_url(home_url('/request-dashboard/')); ?>" class="mobile-link <?php echo (is_page('request-dashboard')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-list-check" style="font-size: 20px;"></i> Requests</span>
            <?php if ($requests_count > 0): ?>
                <span class="mobile-badge danger"><?php echo $requests_count > 99 ? '99+' : $requests_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(home_url('/add-device/')); ?>" class="mobile-link <?php echo (is_page('add-device')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-plus" style="font-size: 20px;"></i> Add Device</span>
        </a>
        <a href="<?php echo esc_url(home_url('/logout/')); ?>" class="mobile-link mobile-logout">
            <span><i class="fa-solid fa-right-from-bracket" style="font-size: 20px;"></i> Logout</span>
        </a>
    </nav>
</div>



<style>
/* ============================================================
   SIDEBAR STYLES — Animated Expand/Collapse
   Font: Roboto (from Material Theme)
   ============================================================ */

/* ---- Desktop Sidebar ---- */
.custom-sidebar-nav {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 64px;  /* collapsed width */
    background-color: #f5f5f5;
    border-right: 1px solid rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.custom-sidebar-nav:hover {
    width: 260px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15), 0 3px 6px rgba(0, 0, 0, 0.10);
}

/* Logo area */
.sidebar-logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 20px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.12);
    min-height: 64px;
    flex-shrink: 0;
}

.sidebar-logo-icon {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sidebar-logo-icon img {
    width: 28px;
    height: 28px;
    object-fit: contain;
    border-radius: 4px;
}

.sidebar-logo-text {
    font-weight: 700;
    font-size: 1rem;
    color: #1976D2;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.custom-sidebar-nav:hover .sidebar-logo-text {
    opacity: 1;
}

/* Navigation links */
.sidebar-nav-links {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 12px 8px;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 8px;
    color: rgba(0, 0, 0, 0.60);
    text-decoration: none !important;
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.sidebar-link:hover {
    background-color: rgba(25, 118, 210, 0.08);
    color: #1976D2;
}

.sidebar-link.active {
    background-color: #1976D2;
    color: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
}

.sidebar-link.active:hover {
    background-color: #1565C0;
    color: #fff;
}

.sidebar-link-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.sidebar-icon-badge {
    position: absolute;
    top: -5px;
    right: -7px;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    font-size: 10px;
    font-weight: 700;
    line-height: 16px;
    text-align: center;
    border-radius: 999px;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: 2;
}

.sidebar-icon-badge.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.sidebar-icon-badge.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.custom-sidebar-nav:hover .sidebar-icon-badge {
    opacity: 0;
    transform: scale(0.6);
}

.sidebar-badge {
    margin-left: auto;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    color: #ffffff;
    opacity: 0;
    transform: translateX(6px);
    transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.sidebar-badge.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.sidebar-badge.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.custom-sidebar-nav:hover .sidebar-badge {
    opacity: 1;
    transform: translateX(0);
}

.mobile-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 8px;
    color: rgba(0, 0, 0, 0.60);
    text-decoration: none !important;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.mobile-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
    color: #ffffff;
    margin-left: auto;
}

.mobile-badge.danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.mobile-badge.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.mobile-menu-btn {
    position: relative;
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: rgba(0, 0, 0, 0.87);
    border-radius: 50%;
    transition: background-color 0.2s;
}

.mobile-btn-badge-dot {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 9px;
    height: 9px;
    background-color: #ef4444;
    border-radius: 50%;
    border: 2px solid #f5f5f5;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
    }
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}


.sidebar-link-text {
    opacity: 0;
    transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(-4px);
}

.custom-sidebar-nav:hover .sidebar-link-text {
    opacity: 1;
    transform: translateX(0);
}

/* Bottom section */
.sidebar-bottom {
    padding: 8px 8px 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.12);
    flex-shrink: 0;
}

.sidebar-logout {
    color: rgba(0, 0, 0, 0.60);
}

.sidebar-logout:hover {
    background-color: rgba(211, 47, 47, 0.08);
    color: #D32F2F;
}


/* ---- Mobile Sidebar ---- */
.mobile-sidebar-bar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    background-color: #f5f5f5;
    border-bottom: 1px solid rgba(0, 0, 0, 0.12);
    align-items: center;
    padding: 0 16px;
    gap: 12px;
    z-index: 9998;
    font-family: 'Roboto', sans-serif;
}

.mobile-menu-btn:hover {
    background-color: rgba(0, 0, 0, 0.08);
}

.mobile-logo-text {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1976D2;
}

.mobile-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mobile-sidebar-overlay.show {
    display: block;
    opacity: 1;
}

.mobile-sidebar-panel {
    position: fixed;
    top: 0;
    left: -100%;
    width: 280px;
    height: 100vh;
    background: #fff;
    z-index: 10001;
    display: flex;
    flex-direction: column;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'Roboto', sans-serif;
    box-shadow: 0 15px 25px rgba(0, 0, 0, 0.15);
}

.mobile-sidebar-panel.show {
    left: 0;
}

.mobile-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.mobile-sidebar-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1976D2;
}

.mobile-close-btn {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: rgba(0, 0, 0, 0.87);
    border-radius: 50%;
    transition: background-color 0.2s;
}

.mobile-close-btn:hover {
    background-color: rgba(0, 0, 0, 0.08);
}

.mobile-nav-links {
    display: flex;
    flex-direction: column;
    padding: 12px 12px;
    gap: 2px;
    flex: 1;
    overflow-y: auto;
}

.mobile-link:hover {
    background-color: rgba(25, 118, 210, 0.08);
    color: #1976D2;
}

.mobile-link.active {
    background-color: #1976D2;
    color: #fff;
}

.mobile-logout:hover {
    background-color: rgba(211, 47, 47, 0.08);
    color: #D32F2F;
}



/* ---- Responsive: Show mobile, hide desktop ---- */
@media (max-width: 768px) {
    .custom-sidebar-nav {
        display: none !important;
    }
    .mobile-sidebar-bar {
        display: flex !important;
    }
    /* Push content down on mobile to avoid top-bar overlap */
    #content.site-content {
        margin-left: 0 !important;
        margin-top: 56px !important;
    }
}

/* ---- Desktop: Push main content to account for sidebar ---- */
@media (min-width: 769px) {
    .mobile-sidebar-bar {
        display: none !important;
    }
    #content.site-content {
        margin-left: 64px;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
}
</style>


<script>
document.addEventListener("DOMContentLoaded", function () {
    // ========== Mobile sidebar toggle ==========
    var menuBtn = document.getElementById("mobile-menu-btn");
    var closeBtn = document.getElementById("mobile-close-btn");
    var overlay = document.getElementById("mobile-sidebar-overlay");
    var panel = document.getElementById("mobile-sidebar-panel");

    function openMobile() {
        panel.classList.add("show");
        overlay.classList.add("show");
        document.body.style.overflow = "hidden";
        // Trigger mobile scramble on open
        scrambleText(document.getElementById("mobile-scramble-text"));
    }

    function closeMobile() {
        panel.classList.remove("show");
        overlay.classList.remove("show");
        document.body.style.overflow = "";
    }

    if (menuBtn) menuBtn.addEventListener("click", openMobile);
    if (closeBtn) closeBtn.addEventListener("click", closeMobile);
    if (overlay) overlay.addEventListener("click", closeMobile);


    // ========== Text Scramble Animation ==========
    var CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    function scrambleText(el) {
        if (!el) return;
        var originalText = el.getAttribute("data-text") || el.innerText;
        var duration = 1.2;
        var speed = 0.03;
        var steps = Math.ceil(duration / speed);
        var step = 0;

        // Clear any existing interval on this element
        if (el._scrambleInterval) clearInterval(el._scrambleInterval);

        el._scrambleInterval = setInterval(function () {
            var progress = step / steps;
            var scrambled = "";

            for (var i = 0; i < originalText.length; i++) {
                if (originalText[i] === " ") {
                    scrambled += " ";
                    continue;
                }
                if (progress * originalText.length > i) {
                    scrambled += originalText[i];
                } else {
                    scrambled += CHARS[Math.floor(Math.random() * CHARS.length)];
                }
            }

            step++;

            if (step > steps) {
                clearInterval(el._scrambleInterval);
                el._scrambleInterval = null;
                el.innerText = originalText;
            } else {
                el.innerText = scrambled;
            }
        }, speed * 1000);
    }

    // ========== Desktop Sidebar: scramble on hover ==========
    var sidebar = document.getElementById("custom-sidebar");
    var desktopScrambleEl = document.getElementById("sidebar-scramble-text");

    if (sidebar && desktopScrambleEl) {
        sidebar.addEventListener("mouseenter", function () {
            scrambleText(desktopScrambleEl);
        });
    }

    // Run once on page load for desktop
    scrambleText(desktopScrambleEl);
});
</script>
