<?php
$sidebar_badges = function_exists('stock_supply_get_sidebar_badges') ? stock_supply_get_sidebar_badges() : ['requests' => 0, 'maintenance' => 0, 'total' => 0];
$requests_count = $sidebar_badges['requests'];
$maintenance_count = $sidebar_badges['maintenance'];

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$user_display_name = $is_logged_in ? ($current_user->display_name ?: $current_user->user_login) : 'Guest';
$user_email = $is_logged_in ? strtolower($current_user->user_email) : '';
$user_initial = strtoupper(mb_substr($user_display_name, 0, 1));
$user_role_label = 'Staff';
$user_role_class = 'staff';

if ($is_logged_in) {
    $roles = (array) $current_user->roles;
    if (in_array('administrator', $roles)) {
        $user_role_label = 'Admin';
        $user_role_class = 'admin';
    } elseif (in_array('stock_staff', $roles)) {
        $user_role_label = 'Stock Staff';
        $user_role_class = 'stock-staff';
    } elseif (in_array('editor', $roles)) {
        $user_role_label = 'Editor';
        $user_role_class = 'editor';
    } else {
        $user_role_label = 'Staff';
        $user_role_class = 'staff';
    }
}
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
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/cropped-Icon-TBS.png" alt="Logo" width="28"
                height="28">
        </div>
        <span id="sidebar-scramble-text" class="sidebar-logo-text" data-text="Stock Supply">Stock Supply</span>
    </div>

    <!-- Navigation links -->
    <nav class="sidebar-nav-links">
        <!-- Dashboard -->
        <a href="<?php echo esc_url(home_url('/home/')); ?>"
            class="sidebar-link <?php echo (is_page('home')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-house" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Dashboard</span>
        </a>

        <!-- Monitor -->
        <a href="<?php echo esc_url(home_url('/monitor/')); ?>"
            class="sidebar-link <?php echo (is_page('monitor')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-desktop" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Monitor</span>
        </a>

        <!-- Laptop -->
        <a href="<?php echo esc_url(home_url('/laptop/')); ?>"
            class="sidebar-link <?php echo (is_page('laptop')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-laptop" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Laptop</span>
        </a>

        <!-- Accessories -->
        <a href="<?php echo esc_url(home_url('/accessories/')); ?>"
            class="sidebar-link <?php echo (is_page('accessories')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-keyboard" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Accessories</span>
        </a>

        <!-- Maintenance -->
        <a href="<?php echo esc_url(home_url('/maintenance/')); ?>"
            class="sidebar-link <?php echo (is_page('maintenance')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-screwdriver-wrench" style="font-size: 20px;"></i>
                <?php if ($maintenance_count > 0): ?>
                    <span
                        class="sidebar-icon-badge warning"><?php echo $maintenance_count > 99 ? '99+' : $maintenance_count; ?></span>
                <?php endif; ?>
            </span>
            <span class="sidebar-link-text">Maintenance</span>
            <?php if ($maintenance_count > 0): ?>
                <span
                    class="sidebar-badge warning"><?php echo $maintenance_count > 99 ? '99+' : $maintenance_count; ?></span>
            <?php endif; ?>
        </a>

        <!-- History -->
        <a href="<?php echo esc_url(home_url('/history/')); ?>"
            class="sidebar-link <?php echo (is_page('history')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-clock-rotate-left" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">History</span>
        </a>

        <!-- Employees -->
        <a href="<?php echo esc_url(home_url('/owner/')); ?>"
            class="sidebar-link <?php echo (is_page('owner')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-users" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Employees</span>
        </a>






        <!-- Add Device -->
        <a href="<?php echo esc_url(home_url('/add-device/')); ?>"
            class="sidebar-link <?php echo (is_page('add-device')) ? 'active' : ''; ?>">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-plus" style="font-size: 20px;"></i>
            </span>
            <span class="sidebar-link-text">Add Device</span>
        </a>
    </nav>

    <!-- Bottom section: User Profile + Logout -->
    <div class="sidebar-bottom">
        <?php if ($is_logged_in): ?>
            <div class="sidebar-user-widget">
                <div class="sidebar-user-avatar-wrap">
                    <div class="sidebar-user-avatar">
                        <span><?php echo esc_html($user_initial); ?></span>
                    </div>
                    <span class="sidebar-user-online-dot" title="Online"></span>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name-row">
                        <span class="sidebar-user-name" title="<?php echo esc_attr($user_display_name); ?>"><?php echo esc_html($user_display_name); ?></span>
                        <span class="sidebar-user-role-badge <?php echo esc_attr($user_role_class); ?>"><?php echo esc_html($user_role_label); ?></span>
                    </div>
                    <?php if (!empty($user_email)): ?>
                        <span class="sidebar-user-email" title="<?php echo esc_attr($user_email); ?>"><?php echo esc_html($user_email); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
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

<!-- ========== MOBILE BOTTOM NAVIGATION BAR (Modern Floating Interactive Menu) ========== -->
<nav id="mobile-bottom-nav" class="mobile-bottom-nav menu" role="navigation">
    <a href="<?php echo esc_url(home_url('/home/')); ?>"
        class="bottom-nav-item menu__item <?php echo (is_page('home')) ? 'active' : ''; ?>">
        <div class="menu__icon">
            <i class="fa-solid fa-house icon"></i>
        </div>
        <strong class="menu__text <?php echo (is_page('home')) ? 'active' : ''; ?>">Home</strong>
    </a>
    <a href="<?php echo esc_url(home_url('/monitor/')); ?>"
        class="bottom-nav-item menu__item <?php echo (is_page('monitor')) ? 'active' : ''; ?>">
        <div class="menu__icon">
            <i class="fa-solid fa-desktop icon"></i>
        </div>
        <strong class="menu__text <?php echo (is_page('monitor')) ? 'active' : ''; ?>">Monitor</strong>
    </a>

    <!-- Prominent Central QR Scan Floating Action Button (FAB) -->
    <div class="bottom-nav-fab-wrapper">
        <button type="button" id="mobile-qr-fab-btn" class="bottom-nav-fab" aria-label="Scan QR Code"
            title="Scan QR Code">
            <i class="fa-solid fa-qrcode"></i>
            <span class="fab-pulse-ring"></span>
        </button>
        <span class="fab-label">Scan QR</span>
    </div>

    <a href="<?php echo esc_url(home_url('/laptop/')); ?>"
        class="bottom-nav-item menu__item <?php echo (is_page('laptop')) ? 'active' : ''; ?>">
        <div class="menu__icon">
            <i class="fa-solid fa-laptop icon"></i>
        </div>
        <strong class="menu__text <?php echo (is_page('laptop')) ? 'active' : ''; ?>">Laptop</strong>
    </a>
    <button type="button" id="mobile-bottom-more-btn" class="bottom-nav-item menu__item">
        <div class="menu__icon">
            <i class="fa-solid fa-bars icon"></i>
        </div>
        <strong class="menu__text">More</strong>
        <?php if ($sidebar_badges['total'] > 0): ?>
            <span class="mobile-btn-badge-dot" style="top: 6px; right: 12px;"></span>
        <?php endif; ?>
    </button>
</nav>

<!-- ========== MOBILE OVERLAY ========== -->
<div id="mobile-sidebar-overlay" class="mobile-sidebar-overlay"></div>

<!-- ========== MOBILE SIDEBAR PANEL ========== -->
<div id="mobile-sidebar-panel" class="mobile-sidebar-panel">
    <div class="mobile-sidebar-header">
        <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/tbs.png" alt="Logo" width="28" height="28"
            style="flex-shrink:0;">
        <span id="mobile-scramble-text" class="mobile-sidebar-title" data-text="Stock Supply">Stock Supply</span>
        <button id="mobile-close-btn" class="mobile-close-btn" aria-label="Close Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <nav class="mobile-nav-links">
        <a href="<?php echo esc_url(home_url('/home/')); ?>"
            class="mobile-link <?php echo (is_page('home')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-house" style="font-size: 20px;"></i> Dashboard</span>
        </a>
        <a href="<?php echo esc_url(home_url('/monitor/')); ?>"
            class="mobile-link <?php echo (is_page('monitor')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-desktop" style="font-size: 20px;"></i> Monitor</span>
        </a>
        <a href="<?php echo esc_url(home_url('/laptop/')); ?>"
            class="mobile-link <?php echo (is_page('laptop')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-laptop" style="font-size: 20px;"></i> Laptop</span>
        </a>
        <a href="<?php echo esc_url(home_url('/accessories/')); ?>"
            class="mobile-link <?php echo (is_page('accessories')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-keyboard" style="font-size: 20px;"></i> Accessories</span>
        </a>
        <a href="<?php echo esc_url(home_url('/maintenance/')); ?>"
            class="mobile-link <?php echo (is_page('maintenance')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-screwdriver-wrench" style="font-size: 20px;"></i> Maintenance</span>
            <?php if ($maintenance_count > 0): ?>
                <span
                    class="mobile-badge warning"><?php echo $maintenance_count > 99 ? '99+' : $maintenance_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(home_url('/history/')); ?>"
            class="mobile-link <?php echo (is_page('history')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-clock-rotate-left" style="font-size: 20px;"></i> History</span>
        </a>
        <a href="<?php echo esc_url(home_url('/owner/')); ?>"
            class="mobile-link <?php echo (is_page('owner')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-users" style="font-size: 20px;"></i> Employees</span>
        </a>



        <a href="<?php echo esc_url(home_url('/add-device/')); ?>"
            class="mobile-link <?php echo (is_page('add-device')) ? 'active' : ''; ?>">
            <span><i class="fa-solid fa-plus" style="font-size: 20px;"></i> Add Device</span>
        </a>
        <?php if ($is_logged_in): ?>
            <div class="mobile-user-card">
                <div class="mobile-user-avatar-wrap">
                    <div class="mobile-user-avatar">
                        <span><?php echo esc_html($user_initial); ?></span>
                    </div>
                    <span class="mobile-user-online-dot" title="Online"></span>
                </div>
                <div class="mobile-user-info">
                    <div class="mobile-user-name-row">
                        <span class="mobile-user-name"><?php echo esc_html($user_display_name); ?></span>
                        <span class="sidebar-user-role-badge <?php echo esc_attr($user_role_class); ?>"><?php echo esc_html($user_role_label); ?></span>
                    </div>
                    <?php if (!empty($user_email)): ?>
                        <span class="mobile-user-email"><?php echo esc_html($user_email); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <a href="<?php echo esc_url(home_url('/logout/')); ?>" class="mobile-link mobile-logout">
            <span><i class="fa-solid fa-right-from-bracket" style="font-size: 20px;"></i> Logout</span>
        </a>
    </nav>
</div>



<style>
    /* ============================================================
   SIDEBAR STYLES — Modern Dark Design
   Deep Slate + Indigo-Violet Accent
   ============================================================ */

    /* ---- Desktop Sidebar ---- */
    .custom-sidebar-nav {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 64px;
        background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.06);
        display: flex;
        flex-direction: column;
        z-index: 9999;
        overflow: hidden;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.25);
    }

    .custom-sidebar-nav:hover {
        width: 260px;
        box-shadow: 4px 0 32px rgba(0, 0, 0, 0.35);
    }

    /* Logo area */
    .sidebar-logo-area {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
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
        border-radius: 6px;
        filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.3));
    }

    .sidebar-logo-text {
        font-weight: 700;
        font-size: 1rem;
        background: linear-gradient(135deg, #818cf8, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        white-space: nowrap;
        opacity: 0;
        transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: -0.01em;
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
        scrollbar-width: none;
    }

    .sidebar-nav-links::-webkit-scrollbar {
        display: none;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.5);
        text-decoration: none !important;
        font-weight: 500;
        font-size: 0.875rem;
        white-space: nowrap;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .sidebar-link:hover {
        background-color: rgba(99, 102, 241, 0.12);
        color: rgba(255, 255, 255, 0.9);
        transform: translateX(2px);
    }

    .sidebar-link.active {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(139, 92, 246, 0.85));
        color: #fff;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    .sidebar-link.active:hover {
        background: linear-gradient(135deg, #6366f1, #7c3aed);
        color: #fff;
        transform: translateX(0);
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
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
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
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
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
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.5);
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
        color: rgba(255, 255, 255, 0.8);
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
        border: 2px solid #0f172a;
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
        padding: 8px 8px 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        flex-shrink: 0;
    }

    /* User Widget in Desktop Sidebar */
    .sidebar-user-widget {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px;
        border-radius: 12px;
        margin-bottom: 6px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        transition: all 0.25s ease;
        box-sizing: border-box;
    }

    .custom-sidebar-nav:hover .sidebar-user-widget {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(99, 102, 241, 0.25);
    }

    .sidebar-user-avatar-wrap {
        position: relative;
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
    }

    .sidebar-user-online-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #0f172a;
        box-shadow: 0 0 6px #10b981;
    }

    .sidebar-user-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
        opacity: 0;
        transform: translateX(-6px);
        transition: opacity 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        overflow: hidden;
    }

    .custom-sidebar-nav:hover .sidebar-user-info {
        opacity: 1;
        transform: translateX(0);
    }

    .sidebar-user-name-row {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }

    .sidebar-user-name {
        font-weight: 700;
        font-size: 0.88rem;
        color: #ffffff;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sidebar-user-role-badge {
        font-size: 0.62rem;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        flex-shrink: 0;
    }

    .sidebar-user-role-badge.admin {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.35);
    }

    .sidebar-user-role-badge.stock-staff {
        background: rgba(99, 102, 241, 0.2);
        color: #c7d2fe;
        border: 1px solid rgba(99, 102, 241, 0.35);
    }

    .sidebar-user-role-badge.staff {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
        border: 1px solid rgba(16, 185, 129, 0.35);
    }

    .sidebar-user-email {
        font-size: 0.72rem;
        color: rgba(255, 255, 255, 0.45);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-top: 1px;
        text-transform: lowercase !important;
    }

    .sidebar-logout {
        color: rgba(255, 255, 255, 0.4);
    }

    .sidebar-logout:hover {
        background-color: rgba(239, 68, 68, 0.12);
        color: #f87171;
    }

    /* Mobile User Card */
    .mobile-user-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        margin: 12px 4px 6px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        box-sizing: border-box;
    }

    .mobile-user-avatar-wrap {
        position: relative;
        width: 38px;
        height: 38px;
        flex-shrink: 0;
    }

    .mobile-user-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
    }

    .mobile-user-online-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #111827;
        box-shadow: 0 0 6px #10b981;
    }

    .mobile-user-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1;
    }

    .mobile-user-name-row {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: space-between;
    }

    .mobile-user-name {
        font-weight: 700;
        font-size: 0.9rem;
        color: #ffffff;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mobile-user-email {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.5);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-transform: lowercase !important;
        margin-top: 2px;
    }


    /* ---- Mobile Sidebar ---- */
    .mobile-sidebar-bar {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 56px;
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        align-items: center;
        padding: 0 16px;
        gap: 12px;
        z-index: 9998;
        font-family: 'Inter', 'Roboto', sans-serif;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }

    .mobile-menu-btn:hover {
        background-color: rgba(99, 102, 241, 0.15);
    }

    .mobile-logo-text {
        font-weight: 700;
        font-size: 1.1rem;
        background: linear-gradient(135deg, #818cf8, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .mobile-sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.65);
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(4px);
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
        background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
        border-right: 1px solid rgba(99, 102, 241, 0.15);
        z-index: 10001;
        display: flex;
        flex-direction: column;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: 'Inter', 'Roboto', sans-serif;
        box-shadow: 8px 0 40px rgba(0, 0, 0, 0.4);
    }

    .mobile-sidebar-panel.show {
        left: 0;
    }

    .mobile-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid rgba(99, 102, 241, 0.15);
    }

    .mobile-sidebar-title {
        font-weight: 700;
        font-size: 1.1rem;
        background: linear-gradient(135deg, #818cf8, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .mobile-close-btn {
        background: none;
        border: none;
        padding: 8px;
        cursor: pointer;
        color: rgba(255, 255, 255, 0.6);
        border-radius: 50%;
        transition: background-color 0.2s;
    }

    .mobile-close-btn:hover {
        background-color: rgba(99, 102, 241, 0.15);
        color: #fff;
    }

    .mobile-nav-links {
        display: flex;
        flex-direction: column;
        padding: 12px 12px;
        gap: 2px;
        flex: 1;
        overflow-y: auto;
        scrollbar-width: none;
    }

    .mobile-nav-links::-webkit-scrollbar {
        display: none;
    }

    .mobile-link:hover {
        background-color: rgba(99, 102, 241, 0.12);
        color: rgba(255, 255, 255, 0.9);
    }

    .mobile-link.active {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(139, 92, 246, 0.85));
        color: #fff;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
    }

    .mobile-logout:hover {
        background-color: rgba(239, 68, 68, 0.12);
        color: #f87171;
    }



    :root {
        --component-inactive-color: #64748b;
        --component-bg: rgba(255, 255, 255, 0.94);
        --component-shadow: 0 12px 32px -4px rgba(99, 102, 241, 0.25), 0 4px 16px rgba(0, 0, 0, 0.08);
        --component-active-bg: rgba(99, 102, 241, 0.12);
        --component-line-inactive-color: rgba(226, 232, 240, 0.9);
        --component-active-color-default: #6366f1;
    }

    .dark {
        --component-inactive-color: #94a3b8;
        --component-bg: rgba(15, 23, 42, 0.94);
        --component-shadow: 0 12px 32px -4px rgba(0, 0, 0, 0.5);
        --component-active-bg: rgba(99, 102, 241, 0.2);
        --component-line-inactive-color: rgba(51, 65, 85, 0.8);
        --component-active-color-default: #818cf8;
    }

    @keyframes iconBounce {

        0%,
        100% {
            transform: translateY(0);
        }

        20% {
            transform: translateY(-0.35em);
        }

        40% {
            transform: translateY(0);
        }

        60% {
            transform: translateY(-0.12em);
        }

        80% {
            transform: translateY(0);
        }
    }

    /* ---- Mobile Bottom Floating Pill Navigation Bar ---- */
    .mobile-bottom-nav,
    .menu {
        display: none;
        position: fixed !important;
        bottom: 12px !important;
        left: 12px !important;
        right: 12px !important;
        width: calc(100% - 24px) !important;
        max-width: 480px !important;
        margin: 0 auto !important;
        height: 64px !important;
        background: var(--component-bg) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border: 1.5px solid var(--component-line-inactive-color) !important;
        box-shadow: var(--component-shadow) !important;
        border-radius: 9999px !important;
        z-index: 9997 !important;
        align-items: center !important;
        justify-content: space-evenly !important;
        padding: 0 8px !important;
        box-sizing: border-box !important;
    }

    .bottom-nav-item,
    .menu__item {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 1 !important;
        height: 50px !important;
        color: var(--component-inactive-color) !important;
        text-decoration: none !important;
        font-size: 0.72rem !important;
        font-weight: 500 !important;
        gap: 2px !important;
        background: transparent !important;
        border: none !important;
        padding: 4px 6px !important;
        cursor: pointer !important;
        border-radius: 9999px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        box-sizing: border-box !important;
    }

    .menu__icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bottom-nav-item i,
    .menu__icon .icon {
        font-size: 1.25rem !important;
        transition: transform 0.25s ease, color 0.25s ease !important;
    }

    .menu__text {
        font-size: 0.68rem !important;
        font-weight: 500 !important;
        color: var(--component-inactive-color) !important;
        transition: color 0.25s ease, font-weight 0.25s ease !important;
    }

    .bottom-nav-item:hover,
    .menu__item:hover,
    .bottom-nav-item.active,
    .menu__item.active {
        color: var(--component-active-color-default) !important;
        background-color: var(--component-active-bg) !important;
    }

    .bottom-nav-item:hover i,
    .bottom-nav-item:hover .icon,
    .bottom-nav-item.active i,
    .bottom-nav-item.active .icon {
        animation: iconBounce 0.6s ease !important;
        color: var(--component-active-color-default) !important;
    }

    .bottom-nav-item.active .menu__text,
    .menu__text.active {
        color: var(--component-active-color-default) !important;
        font-weight: 700 !important;
    }

    .bottom-nav-item.active::after,
    .menu__item.active::after {
        content: '' !important;
        position: absolute !important;
        bottom: 3px !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: 18px !important;
        height: 3px !important;
        background-color: var(--component-active-color-default) !important;
        border-radius: 99px !important;
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.7) !important;
        transition: all 0.3s ease !important;
    }

    /* FAB Wrapper & Button */
    .bottom-nav-fab-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        top: -14px;
        z-index: 9998;
    }

    .bottom-nav-fab {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff;
        border: 3px solid #ffffff;
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        outline: none;
    }

    .bottom-nav-fab i {
        font-size: 1.4rem;
    }

    .bottom-nav-fab:hover {
        transform: scale(1.08) translateY(-2px);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.55);
    }

    .bottom-nav-fab:active {
        transform: scale(0.96);
    }

    .fab-label {
        font-size: 0.68rem;
        font-weight: 700;
        color: #4f46e5;
        margin-top: 2px;
        letter-spacing: 0.02em;
    }

    .fab-pulse-ring {
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid rgba(99, 102, 241, 0.6);
        animation: fab-pulse 2.2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        pointer-events: none;
    }

    @keyframes fab-pulse {
        0% {
            transform: scale(0.95);
            opacity: 0.8;
        }

        70% {
            transform: scale(1.35);
            opacity: 0;
        }

        100% {
            transform: scale(1.35);
            opacity: 0;
        }
    }

    @media (max-width: 768px) {
        .custom-sidebar-nav {
            display: none !important;
        }



        .mobile-bottom-nav {
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

        .mobile-bottom-nav {
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
        var moreBtn = document.getElementById("mobile-bottom-more-btn");

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
        if (moreBtn) moreBtn.addEventListener("click", openMobile);
        if (closeBtn) closeBtn.addEventListener("click", closeMobile);
        if (overlay) overlay.addEventListener("click", closeMobile);

        // ========== Mobile Bottom FAB QR Scanner trigger ==========
        var qrFabBtn = document.getElementById("mobile-qr-fab-btn");
        if (qrFabBtn) {
            qrFabBtn.addEventListener("click", function (e) {
                e.preventDefault();
                var pageQrBtn = document.getElementById("dash-btn-start-qr");
                if (pageQrBtn) {
                    var dashQrBar = pageQrBtn.closest('.dash-qr-bar') || document.querySelector('.dash-qr-bar');
                    if (dashQrBar) {
                        dashQrBar.classList.add('active-scan');
                        dashQrBar.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    setTimeout(function () {
                        pageQrBtn.click();
                    }, 100);
                } else {
                    window.location.href = "<?php echo esc_url(home_url('/?scan=1')); ?>";
                }
            });
        }

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