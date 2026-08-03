<?php
if (!defined('ABSPATH')) {
    exit;
}

date_default_timezone_set('Asia/Bangkok');


/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0');


// Enqueue styles
function child_enqueue_styles()
{
    wp_enqueue_style('astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), filemtime(get_stylesheet_directory() . '/style.css'), 'all');
}
add_action('wp_enqueue_scripts', 'child_enqueue_styles', 15);
if (has_post_thumbnail()) {
    the_post_thumbnail('full'); // หรือขนาดอื่น ๆ เช่น 'medium', 'large'
}



// Helper to parse search query string (extract Device ID from URL if full URL is scanned/pasted)
function stock_supply_parse_search_query($search)
{
    if (!is_string($search))
        return '';
    $search = trim($search);
    if (empty($search))
        return '';
    if (preg_match('/[?&]view=([^&]+)/i', $search, $matches)) {
        return trim(urldecode($matches[1]));
    }
    return $search;
}

// Helper to format Reason/Details in History Log nicely
function stock_supply_format_history_description($desc, $action = '', $device_id = '')
{
    global $wpdb;

    if (empty($desc) || $desc === '-') {
        return '-';
    }

    $desc = trim($desc);

    // If Action is Maintenance, check if specific repair details exist in Maintenance table
    if (strcasecmp($action, 'Maintenance') === 0 && !empty($device_id)) {
        $maint_details = $wpdb->get_var($wpdb->prepare(
            "SELECT Details FROM Maintenance WHERE DeviceID = %s ORDER BY MaintenanceID DESC LIMIT 1",
            $device_id
        ));
        if (!empty($maint_details) && strpos($maint_details, 'via QR') === false) {
            $desc = $maint_details;
        }
    }

    // Strip redundant Device ID prefixes
    if (!empty($device_id)) {
        $desc = preg_replace('/^Device ID ' . preg_quote($device_id, '/') . '\s*/i', '', $desc);
    }
    $desc = preg_replace('/^Device ID [A-Za-z0-9_-]+\s*/i', '', $desc);
    $desc = ucfirst(trim($desc));

    // Convert status arrows
    $desc = str_replace(' -> ', ' &rarr; ', $desc);

    // Style Status badge
    $desc = preg_replace_callback('/\(Status:\s*([^\)]+)\)/i', function ($matches) {
        return '<span class="badge bg-light text-dark border ms-1" style="font-weight: 500; font-size: 0.8rem;">Status: ' . esc_html($matches[1]) . '</span>';
    }, $desc);

    // Style Reason / Note labels with a line break for cleaner reading
    $desc = preg_replace('/(Reason\/Note|Reason|Note):/i', '<br><strong style="color: #475569; display: inline-block; margin-top: 4px;">$1:</strong>', $desc);
    
    // Clean up if the string started with <br>
    $desc = preg_replace('/^<br>/i', '', trim($desc));

    return $desc;
}

// require Controller
require_once get_stylesheet_directory() . '/controller/device_actions.php';

// require Model
require_once get_stylesheet_directory() . '/model/history/history.php';
require_once get_stylesheet_directory() . '/model/laptop/laptop.php';
require_once get_stylesheet_directory() . '/model/monitor/monitor.php';
require_once get_stylesheet_directory() . '/model/employee/form_add_employee.php';
require_once get_stylesheet_directory() . '/model/accessories/accessories.php';
require_once get_stylesheet_directory() . '/model/maintenance/maintenance.php';
require_once get_stylesheet_directory() . '/model/device/receive-device.php';
require_once get_stylesheet_directory() . '/model/device/edit-device.php';
require_once get_stylesheet_directory() . '/model/device/device-form-handler.php';
require_once get_stylesheet_directory() . '/model/device/device_form_add.php';
require_once get_stylesheet_directory() . '/model/employee/form_edit_employee.php';




// require Shared Components
require_once get_stylesheet_directory() . '/model/shared/sectors_donut.php';

// require Dashboard
require_once get_stylesheet_directory() . '/model/dashboard/device_dashboard.php';
require_once get_stylesheet_directory() . '/model/dashboard/monitor_dashboard.php';
require_once get_stylesheet_directory() . '/model/dashboard/laptop_dashboard.php';
require_once get_stylesheet_directory() . '/model/dashboard/accessories_dashboard.php';
require_once get_stylesheet_directory() . '/model/dashboard/employee_dashboard.php';
require_once get_stylesheet_directory() . '/model/dashboard/maintenance_dashboard.php';

// require view
require_once get_stylesheet_directory() . '/view/formDevice.php';
require_once get_stylesheet_directory() . '/view/formEmployee.php';
require_once get_stylesheet_directory() . '/view/formMaintenance.php';
require_once get_stylesheet_directory() . '/view/view_device_details.php';

// Keep the history retention policy out of the History page request.
function astra_child_cleanup_expired_history()
{
    global $wpdb;

    $cutoff = wp_date('Y-m-d H:i:s', strtotime('-12 months'));
    $wpdb->query($wpdb->prepare(
        'DELETE FROM History_new WHERE Date < %s',
        $cutoff
    ));
}
add_action('astra_child_cleanup_expired_history', 'astra_child_cleanup_expired_history');

function astra_child_schedule_history_cleanup()
{
    if (!wp_next_scheduled('astra_child_cleanup_expired_history')) {
        wp_schedule_event(time(), 'daily', 'astra_child_cleanup_expired_history');
    }
}
add_action('init', 'astra_child_schedule_history_cleanup');

// Redirect old QR code URLs to the new system
add_action('template_redirect', function () {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (strpos($request_uri, 'view-device.php') !== false && isset($_GET['id'])) {
        $device_id = sanitize_text_field($_GET['id']);
        // Find the base path by removing 'view-device.php...' from the URI
        $base_path = preg_replace('/view-device\.php.*/', '', $request_uri);
        // Redirect to the new format
        wp_redirect(home_url($base_path . '?view=' . urlencode($device_id)), 301);
        exit;
    }
});
// Section Login 
function login_system()
{
    if (
        !is_user_logged_in() &&
        !in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php']) &&
        !is_admin()
    ) {
        auth_redirect(); // Throw it into the system and return to the previous page
    }
}
add_action('template_redirect', 'login_system');


// Redirect after login -> /home for all role and admin
function login_redirect_all_roles($redirect_to, $request, $user)
{
    if (isset($user->roles) && is_array($user->roles)) {
        return home_url('/home/'); // redirect -> /home
    }
    return $redirect_to;
}
add_filter('login_redirect', 'login_redirect_all_roles', 10, 3);


// show admin bar for admin 
function show_admin_bar_for_admins_only()
{
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'show_admin_bar_for_admins_only');



// not show bar all role
// add_filter('show_admin_bar', '__return_false');




// section cookie 1 day
function cookie_login($expiration, $user_id, $remember)
{
    $one_day = 60 * 60 * 24;
    return $one_day;
}
add_filter('auth_cookie_expiration', 'cookie_login', 99, 3);





// section logout
function logout_redirect()
{
    $req_uri = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $path_slug = trim(untrailingslashit($req_uri), '/');

    // check page Logout (matches /logout, /stock_supply/logout, or page slug 'logout')
    if (is_page('logout') || $path_slug === 'logout' || str_ends_with($path_slug, '/logout')) {
        if (is_user_logged_in()) {
            wp_logout(); // logout user
        }
        wp_redirect(wp_login_url()); // redirect -> login
        exit;
    }
}
add_action('template_redirect', 'logout_redirect', 5);


// style css
function enqueue_device_form_styles()
{
    wp_enqueue_style(
        'device-form-style',
        get_stylesheet_directory_uri() . '/css/style.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_device_form_styles');




// styles Dashboard
function enqueue_device_dashboard_styles()
{
    wp_enqueue_style(
        'device-dashboard-style',
        get_stylesheet_directory_uri() . '/css/device_dashboard.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/device_dashboard.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_device_dashboard_styles');




// style device_dashboard
function enqueue_device_dashboard()
{
    wp_enqueue_style(
        'style-device-dashboard',
        get_stylesheet_directory_uri() . '/css/style_device_dashboard.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_device_dashboard.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_device_dashboard');


//  style monitor dashboard
function enqueue_monitor_dashboard()
{
    wp_enqueue_style(
        'style-monitor-dashboard',
        get_stylesheet_directory_uri() . '/css/style_monitor_dashboard.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_monitor_dashboard.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_monitor_dashboard');




//  style laptop dashboard
function enqueue_laptop_dashboard()
{
    wp_enqueue_style(
        'style-laptop-dashboard',
        get_stylesheet_directory_uri() . '/css/style_laptop_dashboard.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_laptop_dashboard.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_laptop_dashboard');


//  style accessories dashboard
function enqueue_accessories_dashboard()
{
    wp_enqueue_style(
        'style-accessories-dashboard',
        get_stylesheet_directory_uri() . '/css/style_accessories_dashboard.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_accessories_dashboard.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_accessories_dashboard');







// styles Receive Device
function enqueue_receive_device_styles()
{
    wp_enqueue_style(
        'receive-device-style',
        get_stylesheet_directory_uri() . '/css/style_receive_device.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_receive_device.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_receive_device_styles');



// styles Maintenance
function enqueue_maintenance_styles()
{
    wp_enqueue_style(
        'maintenance-style',
        get_stylesheet_directory_uri() . '/css/style_maintenance.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_maintenance.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_maintenance_styles');



// Load Bootstrap CSS and JS from CDN
function load_bootstrap_cdn()
{
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'load_bootstrap_cdn');



// style_action_menu
function enqueue_action_menu_styles()
{
    wp_enqueue_style(
        'action-menu-style',
        get_stylesheet_directory_uri() . '/css/style_action_menu.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/style_action_menu.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_action_menu_styles');


// Particles Background Component
function enqueue_particles_background()
{
    wp_enqueue_script(
        'particles-background-script',
        get_stylesheet_directory_uri() . '/js/particles.js',
        [],
        filemtime(get_stylesheet_directory() . '/js/particles.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_particles_background');

// Animated Dropdown Component
function enqueue_animated_dropdown()
{
    wp_enqueue_style(
        'animated-dropdown-style',
        get_stylesheet_directory_uri() . '/css/animated-dropdown.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/animated-dropdown.css')
    );
    wp_enqueue_script(
        'animated-dropdown-script',
        get_stylesheet_directory_uri() . '/js/animated-dropdown.js',
        [],
        filemtime(get_stylesheet_directory() . '/js/animated-dropdown.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_animated_dropdown');

// Shadcn Filters Component
function enqueue_shadcn_filters()
{
    wp_enqueue_style(
        'shadcn-filters-style',
        get_stylesheet_directory_uri() . '/css/shadcn_filters.css',
        [],
        filemtime(get_stylesheet_directory() . '/css/shadcn_filters.css')
    );
}
add_action('wp_enqueue_scripts', 'enqueue_shadcn_filters');

// Material Design Theme (loads last to override all styles)
function enqueue_material_theme()
{
    wp_enqueue_style(
        'material-theme',
        get_stylesheet_directory_uri() . '/css/material_theme.css',
        ['bootstrap-css', 'device-form-style', 'device-dashboard-style', 'action-menu-style'],
        filemtime(get_stylesheet_directory() . '/css/material_theme.css')
    );
}
add_action('wp_enqueue_scripts', 'enqueue_material_theme', 99);



// sweetalert2
function load_sweetalert_delete_script()
{

    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);

    wp_enqueue_script('sweetalert_delete', get_stylesheet_directory_uri() . '/js/sweetalert_delete.js', array('sweetalert2'), null, true);
}
add_action('wp_enqueue_scripts', 'load_sweetalert_delete_script');


function load_sweetalert_delete_details_script()
{
    // check enqueue SweetAlert2
    if (!wp_script_is('sweetalert2', 'enqueued')) {
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
    }
    wp_enqueue_script('sweetalert_delete_details', get_stylesheet_directory_uri() . '/js/sweetalert_delete_details.js', array('sweetalert2'), null, true);
    wp_enqueue_script('sweetalert_retire', get_stylesheet_directory_uri() . '/js/sweetalert_retire.js', array('sweetalert2'), '1.1', true);
    wp_enqueue_script('ajax_filter_reset', get_stylesheet_directory_uri() . '/js/ajax_filter_reset.js', array(), filemtime(get_stylesheet_directory() . '/js/ajax_filter_reset.js'), true);
}
add_action('wp_enqueue_scripts', 'load_sweetalert_delete_details_script');







// Redirect to Stock Supply Front-page
function change_visit_site_link($wp_admin_bar)
{
    if ($node = $wp_admin_bar->get_node('site-name')) {
        $node->href = home_url('/home/');
        $wp_admin_bar->add_node($node);
    }
}
add_action('admin_bar_menu', 'change_visit_site_link', 999);

function redirect_root_to_homepage()
{
    if (is_front_page() && !is_admin()) {
        wp_redirect(home_url('/home/'), 301);
        exit;
    }
}
add_action('template_redirect', 'redirect_root_to_homepage');






function show_featured_image_before_content($content)
{
    if (is_singular() && has_post_thumbnail()) {
        $featured_image = get_the_post_thumbnail(null, 'full', array('class' => 'featured-image'));
        $content = $featured_image . $content;
    }
    return $content;
}
add_filter('the_content', 'show_featured_image_before_content');



// function enable_yoast_meta_rest_api() {
//     $meta_keys = [
//         '_yoast_wpseo_title',
//         '_yoast_wpseo_metadesc'
//     ];

//     foreach ( $meta_keys as $key ) {
//         register_post_meta( 'post', $key, [
//             'show_in_rest' => true,   // เปิดให้ REST API ใช้
//             'single' => true,
//             'type' => 'string',
//         ]);
//     }
// }
// add_action( 'init', 'enable_yoast_meta_rest_api' );






// ===========================================
// Function: Adjust Sidebar Height Based on Content Area
// Description: Embed jQuery to dynamically match sidebar height to content height.
// Author: [Pearchan]
// Date: [05/26/2025]
// ===========================================
function adjust_sidebar_height_script()
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            function adjustSidebarHeight() {
                var contentHeight = $('.content-area').outerHeight();
                var sidebar = $('#nav_menu-3.widget_nav_menu');

                if (sidebar.length && contentHeight) {
                    sidebar.css('min-height', contentHeight + 'px');
                }
            }

            adjustSidebarHeight();

            $(window).on('resize', function () {
                adjustSidebarHeight();
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'adjust_sidebar_height_script');

// Email Notification Helper
function stock_supply_send_email($action, $device_id, $owner_id, $reason = '')
{
    global $wpdb;
    if (empty($owner_id) || empty($device_id))
        return false;

    // Get Owner details
    $owner = $wpdb->get_row($wpdb->prepare("SELECT Email, Nickname, FirstName, LastName FROM Owners WHERE OwnerID = %d", $owner_id));
    if (!$owner || empty($owner->Email))
        return false;

    $device = null;
    $device_desc = '';

    // If action is Request-related, $device_id is actually $request_id
    if ($action !== 'RequestSubmitted' && $action !== 'RequestRejected') {
        // Get Device details
        $device = $wpdb->get_row($wpdb->prepare("
            SELECT d.DeviceID, b.BrandName as Brand, d.Model, d.SerialNumber, c.CategoryName 
            FROM Devices d
            LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
            LEFT JOIN Brands b ON d.BrandID = b.BrandID
            WHERE d.DeviceID = %s
        ", $device_id));

        if (!$device)
            return false;
        $device_desc = esc_html($device->CategoryName . ' - ' . $device->Brand . ' ' . $device->Model . ' (SN: ' . $device->SerialNumber . ')');
    }

    $to = $owner->Email;
    $subject = '';
    $message = '<div style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 8px;">';

    $name = !empty($owner->FirstName) ? $owner->FirstName . ' ' . $owner->LastName : $owner->Nickname;
    $message .= '<h3>Dear ' . esc_html($name) . ',</h3>';

    if ($action === 'Assign') {
        $subject = 'IT Device Assigned to You (' . $device->DeviceID . ')';
        $message .= '<p>This is to inform you that the following IT device has been assigned to you:</p>';
        $message .= '<p><strong>Device ID:</strong> ' . esc_html($device->DeviceID) . '<br>';
        $message .= '<strong>Details:</strong> ' . $device_desc . '</p>';
        $message .= '<p>Please keep this device in good condition.</p>';
    } elseif ($action === 'Return') {
        $subject = 'IT Device Return Confirmation (' . $device->DeviceID . ')';
        $message .= '<p>We have successfully received the returned IT device from you:</p>';
        $message .= '<p><strong>Device ID:</strong> ' . esc_html($device->DeviceID) . '<br>';
        $message .= '<strong>Details:</strong> ' . $device_desc . '</p>';
        $message .= '<p>Thank you for taking care of the device.</p>';
    } elseif ($action === 'Maintenance') {
        $subject = 'IT Device Sent for Maintenance (' . $device->DeviceID . ')';
        $message .= '<p>Your assigned IT device has been sent for maintenance:</p>';
        $message .= '<p><strong>Device ID:</strong> ' . esc_html($device->DeviceID) . '<br>';
        $message .= '<strong>Details:</strong> ' . $device_desc . '<br>';
        if (!empty($reason)) {
            $message .= '<strong>Reason:</strong> <span style="color: #d9534f;">' . esc_html($reason) . '</span></p>';
        } else {
            $message .= '</p>';
        }
        $message .= '<p>We will notify you once the maintenance is complete.</p>';
    } elseif ($action === 'Return_to_Owner' || $action === 'ReturnToOwner') {
        $subject = 'IT Device Maintenance Completed (' . $device->DeviceID . ')';
        $message .= '<p>The maintenance for your assigned IT device has been completed and it has been returned to you:</p>';
        $message .= '<p><strong>Device ID:</strong> ' . esc_html($device->DeviceID) . '<br>';
        $message .= '<strong>Details:</strong> ' . $device_desc . '</p>';
        $message .= '<p>Please verify that your device is functioning properly.</p>';
    } elseif ($action === 'RequestSubmitted') {
        $subject = 'IT Device Request Submitted (Req #' . $device_id . ')';
        $message .= '<p>Your request for an IT device has been successfully submitted:</p>';
        $message .= '<p><strong>Request ID:</strong> ' . esc_html($device_id) . '<br>';
        $message .= '<strong>Reason:</strong> ' . esc_html($reason) . '</p>';
        $message .= '<p>The IT team will review your request and get back to you shortly.</p>';
    } elseif ($action === 'RequestRejected') {
        $subject = 'IT Device Request Rejected (Req #' . $device_id . ')';
        $message .= '<p>We regret to inform you that your request for an IT device has been rejected:</p>';
        $message .= '<p><strong>Request ID:</strong> ' . esc_html($device_id) . '<br>';
        if (!empty($reason)) {
            $message .= '<strong>Reason for rejection:</strong> <span style="color: #d9534f;">' . esc_html($reason) . '</span></p>';
        } else {
            $message .= '</p>';
        }
        $message .= '<p>If you have any questions, please contact the IT department.</p>';
    } elseif ($action === 'RepairApproved') {
        $subject = 'IT Repair Request Approved (' . $device->DeviceID . ')';
        $message .= '<p>Your request to repair the following IT device has been approved:</p>';
        $message .= '<p><strong>Device ID:</strong> ' . esc_html($device->DeviceID) . '<br>';
        $message .= '<strong>Details:</strong> ' . $device_desc . '<br>';
        $message .= '<strong>Reported Issue:</strong> ' . esc_html($reason) . '</p>';
        $message .= '<p>The device has now been formally sent for Maintenance.</p>';
    } elseif ($action === 'RepairRejected') {
        $subject = 'IT Repair Request Rejected (' . $device->DeviceID . ')';
        $message .= '<p>We regret to inform you that your request to repair the following IT device has been rejected:</p>';
        $message .= '<p><strong>Device ID:</strong> ' . esc_html($device->DeviceID) . '<br>';
        $message .= '<strong>Details:</strong> ' . $device_desc . '<br>';
        if (!empty($reason)) {
            $message .= '<strong>Reason for rejection:</strong> <span style="color: #d9534f;">' . esc_html($reason) . '</span></p>';
        } else {
            $message .= '</p>';
        }
        $message .= '<p>If you have any questions, please contact the IT department.</p>';

    } else {
        return false;
    }

    $message .= '<hr style="border:0; border-top: 1px solid #eee; margin: 20px 0;">';
    $message .= '<p style="font-size: 12px; color: #777;">This is an automated email. Please do not reply.</p>';
    $message .= '</div>';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    return wp_mail($to, $subject, $message, $headers);
}

// DB Setup
function stock_supply_setup_db()
{
    global $wpdb;
    $table_name = 'Device_Requests';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        RequestID int(11) NOT NULL AUTO_INCREMENT,
        OwnerID int(11) NOT NULL,
        CategoryID int(11) NOT NULL,
        Reason text NOT NULL,
        Status varchar(50) NOT NULL DEFAULT 'Pending',
        AssignedDeviceID varchar(100) DEFAULT NULL,
        RequestDate datetime DEFAULT '0000-00-00 00:00:00',
        ActionDate datetime DEFAULT NULL,
        IT_Admin_Email varchar(100) DEFAULT NULL,
        PRIMARY KEY  (RequestID)
    ) $charset_collate;";


    // Create Repair_Requests table
    $table_repair = 'Repair_Requests';
    $sql_repair = "CREATE TABLE $table_repair (
        RequestID int(11) NOT NULL AUTO_INCREMENT,
        OwnerID int(11) NOT NULL,
        DeviceID varchar(100) NOT NULL,
        Reason text NOT NULL,
        Status varchar(50) NOT NULL DEFAULT 'Pending',
        RequestDate datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        ActionDate datetime DEFAULT NULL,
        PRIMARY KEY  (RequestID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_repair);

    // Ensure ActionDate exists in Repair_Requests
    $repair_act = $wpdb->get_results("SHOW COLUMNS FROM Repair_Requests LIKE 'ActionDate'");
    if (empty($repair_act)) {
        $wpdb->query("ALTER TABLE Repair_Requests ADD COLUMN ActionDate DATETIME DEFAULT NULL AFTER RequestDate");
    }

    // Sync status for Repair_Requests that were already processed into Maintenance
    $wpdb->query("
        UPDATE Repair_Requests r
        INNER JOIN Maintenance m ON r.DeviceID = m.DeviceID
        SET r.Status = 'Approved', r.ActionDate = m.CreatedAt
        WHERE r.Status = 'Pending'
    ");

    // Sync status for Repair_Requests where device has been repaired and returned (previously Approved and no longer in Maintenance)
    $wpdb->query("
        UPDATE Repair_Requests r
        INNER JOIN Devices d ON r.DeviceID = d.DeviceID
        INNER JOIN Statuses s ON d.StatusID = s.StatusID
        SET r.Status = 'Completed', r.ActionDate = NOW()
        WHERE r.Status = 'Approved' AND s.StatusName != 'Maintenance'
    ");

    // Restore any Repair_Requests that were mistakenly marked as Completed back to Pending so Admin can Approve/Reject
    $wpdb->query("
        UPDATE Repair_Requests r
        INNER JOIN Devices d ON r.DeviceID = d.DeviceID
        INNER JOIN Statuses s ON d.StatusID = s.StatusID
        SET r.Status = 'Pending', r.ActionDate = NULL
        WHERE r.Status = 'Completed' AND s.StatusName = 'In Use'
          AND r.Reason NOT LIKE 'Rejected%'
    ");

    // Ensure ExpectedReturnDate exists in Device_Requests
    $req_col = $wpdb->get_results("SHOW COLUMNS FROM Device_Requests LIKE 'ExpectedReturnDate'");
    if (empty($req_col)) {
        $wpdb->query("ALTER TABLE Device_Requests ADD COLUMN ExpectedReturnDate DATE DEFAULT NULL AFTER RequestDate");
    }

    // Ensure BorrowDate exists in Device_Requests
    $req_bcol = $wpdb->get_results("SHOW COLUMNS FROM Device_Requests LIKE 'BorrowDate'");
    if (empty($req_bcol)) {
        $wpdb->query("ALTER TABLE Device_Requests ADD COLUMN BorrowDate DATE DEFAULT NULL AFTER RequestDate");
    }

    // Ensure ExpectedReturnDate and LastNotifiedDate exist in Devices
    $dev_exp = $wpdb->get_results("SHOW COLUMNS FROM Devices LIKE 'ExpectedReturnDate'");
    if (empty($dev_exp)) {
        $wpdb->query("ALTER TABLE Devices ADD COLUMN ExpectedReturnDate DATE DEFAULT NULL");
    }

    $dev_notif = $wpdb->get_results("SHOW COLUMNS FROM Devices LIKE 'LastNotifiedDate'");
    if (empty($dev_notif)) {
        $wpdb->query("ALTER TABLE Devices ADD COLUMN LastNotifiedDate DATE DEFAULT NULL");
    }

    // Ensure Photo column exists in History_new and Maintenance
    $hist_photo = $wpdb->get_results("SHOW COLUMNS FROM History_new LIKE 'Photo'");
    if (empty($hist_photo)) {
        $wpdb->query("ALTER TABLE History_new ADD COLUMN Photo VARCHAR(255) DEFAULT NULL");
    }

    $maint_photo = $wpdb->get_results("SHOW COLUMNS FROM Maintenance LIKE 'Photo'");
    if (empty($maint_photo)) {
        $wpdb->query("ALTER TABLE Maintenance ADD COLUMN Photo VARCHAR(255) DEFAULT NULL");
    }

    // Clean up 'Other' category from Categories table
    $wpdb->query("DELETE FROM Categories WHERE LOWER(CategoryName) = 'other'");
}
add_action('after_setup_theme', 'stock_supply_setup_db');





// ==========================================
// Page Transition Loading Screen
// ==========================================
function stock_supply_add_page_loader()
{
    ?>
    <style>
        /* Loading Screen Overlay */
        #stock-supply-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: #f8fafc;
            /* Sleek light background */
            z-index: 999999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hide loader class */
        #stock-supply-loader.loader-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        /* Conveyor Loop Animation */
        @keyframes loading-ui-conveyor-loop {
            0% {
                transform: translateX(var(--loader-start-x));
            }

            100% {
                transform: translateX(var(--loader-end-x));
            }
        }

        .conveyor-loop-container {
            position: relative;
            display: inline-flex;
            height: 1em;
            width: var(--loader-width);
            align-items: center;
            overflow: hidden;
            font-family: monospace;
            font-size: 2.5rem;
            line-height: 1;
            color: #1e293b;
            user-select: none;
        }

        .conveyor-track {
            pointer-events: none;
            position: absolute;
            inset: 0;
            white-space: nowrap;
        }

        .conveyor-glyph {
            pointer-events: none;
            position: absolute;
            top: 0;
            left: 0;
            display: flex;
            height: 100%;
            width: 1ch;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: #f8fafc;
            /* Matches loader background to mask track */
            animation: loading-ui-conveyor-loop 1.8s linear infinite;
        }

        /* Optional loading text */
        .ss-loading-text {
            margin-top: 24px;
            font-family: 'Inter', 'Prompt', sans-serif;
            color: #475569;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            animation: ss-pulse 1.5s infinite;
        }

        @keyframes ss-pulse {
            0% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.5;
            }
        }
    </style>

    <div id="stock-supply-loader">
        <span class="conveyor-loop-container" style="--loader-width: 10ch; --loader-start-x: -2ch; --loader-end-x: 12ch;">
            <span class="conveyor-track">░░░░░░░░░░</span>
            <span class="conveyor-glyph" style="z-index: 30; animation-delay: 0s;">█</span>
            <span class="conveyor-glyph" style="z-index: 20; animation-delay: 0.05s;">▓</span>
            <span class="conveyor-glyph" style="z-index: 10; animation-delay: 0.1s;">▒</span>
        </span>
        <div class="ss-loading-text">Loading</div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var loader = document.getElementById("stock-supply-loader");
            if (!loader) return;

            // Function to hide loader
            function hideLoader() {
                loader.classList.add("loader-hidden");
            }

            // Function to show loader
            function showLoader() {
                loader.classList.remove("loader-hidden");
            }

            // Hide loader when the page has fully loaded
            window.addEventListener("load", function () {
                setTimeout(hideLoader, 200); // slight delay for smooth transition
            });

            // Fallback: hide loader after 5 seconds just in case something hangs
            setTimeout(hideLoader, 5000);

            // Show loader when navigating away via normal links
            var links = document.querySelectorAll("a:not([target='_blank']):not([href^='#']):not([href^='mailto:']):not([href^='tel:']):not(.no-loader)");

            links.forEach(function (link) {
                link.addEventListener("click", function (e) {
                    // Ignore clicks with modifiers (ctrl, shift, meta) or middle click
                    if (e.ctrlKey || e.shiftKey || e.metaKey || e.button === 1) return;

                    var href = this.getAttribute("href");
                    var isJsVoid = href && href.toLowerCase().indexOf('javascript:') === 0;
                    var isSamePageAnchor = href && href.indexOf(window.location.pathname + '#') !== -1;

                    if (href && !isJsVoid && !isSamePageAnchor && !this.hasAttribute("download")) {
                        showLoader();
                        // Fallback: if navigation doesn't happen within 3 seconds, hide the loader
                        setTimeout(hideLoader, 3000);
                    }
                });
            });

            // Hide loader when navigating back/forward using browser cache (BFCache)
            window.addEventListener("pageshow", function (event) {
                if (event.persisted) {
                    hideLoader();
                }
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'stock_supply_add_page_loader', 100);

// Shining Header Text Animation (Scope: ONLY H1 headers for animation, ALL headers for font)
function stock_supply_add_shining_header_styles()
{
    ?>
    <style id="shining-header-styles">
        @keyframes shiningText {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: 0% 0;
            }
        }

        /* Apply modern clean font (Inter + Prompt) to ALL headings */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .entry-title,
        .section-title,
        .next-section-title,
        .vd-title,
        .vd-history-title,
        .card-title,
        .modal-title,
        .page-title,
        .site-title,
        .shining-text,
        .shining-header {
            font-family: 'Inter', 'Prompt', 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        /* Shining Gradient Animation applied ONLY to H1 headings */
        h1,
        h1.entry-title,
        h1[itemprop="headline"],
        .entry-title,
        .shining-text,
        .shining-header,
        .dashboard-container h1 {
            background-image: linear-gradient(110deg, #111827 0%, #111827 40%, #ffffff 50%, #111827 60%, #111827 100%) !important;
            background-size: 200% 100% !important;
            background-repeat: repeat-x !important;
            -webkit-background-clip: text !important;
            background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            color: transparent !important;
            animation: shiningText 4.5s linear infinite !important;
        }

        h1 *,
        h1.entry-title *,
        .entry-title *,
        .shining-text *,
        .shining-header *,
        .dashboard-container h1 * {
            -webkit-text-fill-color: inherit !important;
            color: inherit !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'stock_supply_add_shining_header_styles', 999);
add_action('wp_footer', 'stock_supply_add_shining_header_styles', 999);

// =========================================================================
// AJAX Endpoints for Universal QR Code Scanner & Instant Action Hub
// =========================================================================
function stock_supply_ajax_get_device_details()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized access']);
    }
    check_ajax_referer('stock_supply_ajax_nonce', 'nonce');

    global $wpdb;
    $raw_code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $code = stock_supply_parse_search_query($raw_code);

    if (empty($code)) {
        wp_send_json_error(['message' => 'No code provided']);
    }

    $device = $wpdb->get_row($wpdb->prepare("
        SELECT d.DeviceID, d.SerialNumber, d.Model, d.ReceiveDate, d.ReturnDate, d.ExpectedReturnDate, d.RepairDate,
               c.CategoryName, b.BrandName, s.StatusName,
               o.OwnerID, o.FirstName, o.LastName, o.Nickname, dep.DepartmentName
        FROM Devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        LEFT JOIN Statuses s ON d.StatusID = s.StatusID
        LEFT JOIN Owners o ON d.OwnerID = o.OwnerID
        LEFT JOIN Departments dep ON d.DepartmentID = dep.DepartmentID
        WHERE d.DeviceID = %s OR d.SerialNumber = %s
    ", $code, $code));

    if ($device) {
        $owner_id = $device->OwnerID;
        if (empty($owner_id)) {
            // Fallback 1: Check Repair_Requests
            $owner_id = $wpdb->get_var($wpdb->prepare(
                "SELECT OwnerID FROM Repair_Requests WHERE DeviceID = %s ORDER BY RequestID DESC LIMIT 1",
                $device->DeviceID
            ));
        }
        if (empty($owner_id)) {
            // Fallback 2: Check History_new for last owner
            $hist_owner = $wpdb->get_var($wpdb->prepare(
                "SELECT Owner FROM History_new WHERE DeviceID = %s AND Owner IS NOT NULL AND Owner != '' AND Owner != '-' ORDER BY Date DESC LIMIT 1",
                $device->DeviceID
            ));
            if ($hist_owner) {
                $owner_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT OwnerID FROM Owners WHERE Nickname = %s OR CONCAT(FirstName, ' ', LastName) = %s LIMIT 1",
                    $hist_owner,
                    $hist_owner
                ));
            }
        }

        $owner_name = '-';
        if ($owner_id) {
            $owner_info = $wpdb->get_row($wpdb->prepare("SELECT Nickname, FirstName, LastName FROM Owners WHERE OwnerID = %d", $owner_id));
            if ($owner_info) {
                $owner_name = !empty($owner_info->Nickname) ? $owner_info->Nickname : trim($owner_info->FirstName . ' ' . ($owner_info->LastName ?? ''));
            }
        } elseif (!empty($device->Nickname)) {
            $owner_name = $device->Nickname;
        } elseif (!empty($device->FirstName)) {
            $owner_name = trim($device->FirstName . ' ' . ($device->LastName ?? ''));
        }

        $owners = $wpdb->get_results("
            SELECT o.OwnerID, o.Nickname, o.FirstName, o.LastName, p.PositionName, dep.DepartmentName
            FROM Owners o
            LEFT JOIN Positions p ON o.PositionID = p.PositionID
            LEFT JOIN Departments dep ON o.DepartmentID = dep.DepartmentID
            WHERE o.StatusID = 1
            ORDER BY o.Nickname ASC
        ");

        wp_send_json_success([
            'DeviceID' => $device->DeviceID,
            'SerialNumber' => $device->SerialNumber ?: '-',
            'CategoryName' => $device->CategoryName ?: 'Device',
            'BrandName' => $device->BrandName ?: '-',
            'Model' => $device->Model ?: '-',
            'StatusName' => $device->StatusName ?: 'Unknown',
            'OwnerName' => $owner_name,
            'OwnerID' => $owner_id ?: $device->OwnerID,
            'DepartmentName' => $device->DepartmentName ?: '-',
            'ReceiveDate' => $device->ReceiveDate ?: '-',
            'ExpectedReturnDate' => $device->ExpectedReturnDate ?: '-',
            'RepairDate' => $device->RepairDate ?: '-',
            'all_owners' => $owners
        ]);
    } else {
        wp_send_json_error(['message' => "Device Not Found for code: {$code}"]);
    }
}
add_action('wp_ajax_get_scanned_device_details', 'stock_supply_ajax_get_device_details');

function stock_supply_ajax_quick_action()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized access']);
    }
    check_ajax_referer('stock_supply_ajax_nonce', 'nonce');

    global $wpdb;
    $device_id = isset($_POST['device_id']) ? sanitize_text_field($_POST['device_id']) : '';
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $current_user = wp_get_current_user();
    $admin_email = $current_user->user_email ?? 'admin';

    if (empty($device_id)) {
        wp_send_json_error(['message' => 'Invalid Device ID']);
    }

    $dev = $wpdb->get_row($wpdb->prepare("SELECT * FROM Devices WHERE DeviceID = %s", $device_id));
    if (!$dev) {
        wp_send_json_error(['message' => 'Device not found']);
    }

    // Process photo upload if provided
    $photo_url = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        $uploaded_file = $_FILES['photo'];
        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
        if ($movefile && !isset($movefile['error'])) {
            $photo_url = $movefile['url'];
        }
    }

    $prev_owner_id = $dev->OwnerID;
    if (empty($prev_owner_id)) {
        $prev_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT OwnerID FROM Repair_Requests WHERE DeviceID = %s ORDER BY RequestID DESC LIMIT 1",
            $device_id
        ));
    }
    if (empty($prev_owner_id)) {
        $last_hist_owner = $wpdb->get_var($wpdb->prepare(
            "SELECT Owner FROM History_new WHERE DeviceID = %s AND Owner IS NOT NULL AND Owner != '' AND Owner != '-' ORDER BY Date DESC LIMIT 1",
            $device_id
        ));
        if ($last_hist_owner) {
            $prev_owner_id = $wpdb->get_var($wpdb->prepare(
                "SELECT OwnerID FROM Owners WHERE Nickname = %s OR CONCAT(FirstName, ' ', LastName) = %s LIMIT 1",
                $last_hist_owner,
                $last_hist_owner
            ));
        }
    }
    $prev_owner = $prev_owner_id ? ($wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $prev_owner_id)) ?: 'Unknown') : 'Unknown';

    if ($action_type === 'return') {
        $avail_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
        $wpdb->query('START TRANSACTION');
        $step1 = $wpdb->update('Devices', [
            'StatusID' => $avail_status,
            'ReturnDate' => current_time('Y-m-d'),
            'DepartmentID' => null,
            'ReceiveDate' => null,
            'RepairDate' => null,
            'ExpectedReturnDate' => null,
            'LastNotifiedDate' => null
        ], ['DeviceID' => $device_id]);
        $step2 = ($step1 !== false) ? $wpdb->update('Devices', ['OwnerID' => null, 'PositionID' => null], ['DeviceID' => $device_id]) : false;

        if ($step1 !== false && $step2 !== false) {
            $wpdb->query('COMMIT');
            $wpdb->insert('History_new', [
                'DeviceID' => $device_id,
                'Action' => 'Return',
                'Date' => current_time('mysql'),
                'Description' => "Quick Return via QR Scan (Returned by {$prev_owner})",
                'user_email' => $admin_email,
                'CategoryID' => $dev->CategoryID,
                'Owner' => $prev_owner,
                'Photo' => $photo_url
            ]);
            $email_sent = false;
            if ($prev_owner_id && function_exists('stock_supply_send_email')) {
                $email_sent = stock_supply_send_email('Return', $device_id, $prev_owner_id);
            }
            $msg = "Device {$device_id} successfully checked in!";
            if ($email_sent)
                $msg .= " Notification email sent.";
            wp_send_json_success(['message' => $msg]);
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Database error on return']);
        }
    } elseif ($action_type === 'assign') {
        $owner_id = isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0;
        $new_due_date = isset($_POST['new_due_date']) ? sanitize_text_field($_POST['new_due_date']) : null;

        if (!$owner_id) {
            wp_send_json_error(['message' => 'Please select an employee']);
        }

        $in_use_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");
        $owner_info = $wpdb->get_row($wpdb->prepare("SELECT Nickname, FirstName, LastName, DepartmentID, PositionID, Email FROM Owners WHERE OwnerID = %d", $owner_id));
        $owner_name = $owner_info ? ($owner_info->Nickname ?: $owner_info->FirstName) : 'Unknown';

        $wpdb->update('Devices', [
            'StatusID' => $in_use_status,
            'OwnerID' => $owner_id,
            'DepartmentID' => $owner_info ? $owner_info->DepartmentID : null,
            'PositionID' => $owner_info ? $owner_info->PositionID : null,
            'ReceiveDate' => current_time('Y-m-d')
        ], ['DeviceID' => $device_id]);

        $wpdb->insert('History_new', [
            'DeviceID' => $device_id,
            'Action' => 'Assign Device',
            'Date' => current_time('mysql'),
            'Description' => "Assigned to {$owner_name} via QR Scan Hub",
            'user_email' => $admin_email,
            'CategoryID' => $dev->CategoryID,
            'Owner' => $owner_name,
            'Photo' => $photo_url
        ]);

        $email_sent = false;
        if ($owner_id && function_exists('stock_supply_send_email')) {
            $email_sent = stock_supply_send_email('Assign', $device_id, $owner_id);
        }

        $msg = "Device {$device_id} successfully assigned to {$owner_name}!";
        if ($email_sent) {
            $msg .= " Notification email sent to " . esc_html($owner_info->Email) . ".";
        } elseif ($owner_info && empty($owner_info->Email)) {
            $msg .= " (Note: Employee has no email address configured).";
        }

        $dev_cat = $wpdb->get_var($wpdb->prepare("SELECT CategoryName FROM Categories WHERE CategoryID = %d", $dev->CategoryID));
        $category_slug = $dev_cat ? strtolower(str_replace(' ', '-', $dev_cat)) : 'laptop';
        $redirect_url = home_url('/' . sanitize_title($category_slug) . '/?view=' . urlencode($device_id));

        wp_send_json_success(['message' => $msg, 'redirect_url' => $redirect_url]);
    } elseif ($action_type === 'maintenance') {
        $maint_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Maintenance'");
        $wpdb->update('Devices', ['StatusID' => $maint_status, 'RepairDate' => current_time('Y-m-d')], ['DeviceID' => $device_id]);

        $wpdb->insert('Maintenance', [
            'DeviceID' => $device_id,
            'RepairDate' => current_time('Y-m-d'),
            'Details' => "Sent to Maintenance via QR Scan Hub",
            'user_email' => $admin_email,
            'Photo' => $photo_url,
            'CreatedAt' => current_time('mysql'),
            'UpdatedAt' => current_time('mysql'),
        ]);

        $wpdb->insert('History_new', [
            'DeviceID' => $device_id,
            'Action' => 'Maintenance',
            'Date' => current_time('mysql'),
            'Description' => "Sent to Maintenance via QR Scan Hub",
            'user_email' => $admin_email,
            'CategoryID' => $dev->CategoryID,
            'Owner' => $prev_owner,
            'Photo' => $photo_url
        ]);

        $email_sent = false;
        if ($prev_owner_id && function_exists('stock_supply_send_email')) {
            $email_sent = stock_supply_send_email('Maintenance', $device_id, $prev_owner_id, 'Sent to Maintenance via Admin QR Hub');
        }

        $msg = "Device {$device_id} status updated to Maintenance!";
        if ($email_sent)
            $msg .= " Notification email sent.";
        wp_send_json_success(['message' => $msg]);
    } elseif ($action_type === 'available') {
        $avail_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
        $wpdb->update('Devices', [
            'StatusID' => $avail_status,
            'RepairDate' => null
        ], ['DeviceID' => $device_id]);
        $wpdb->insert('History_new', [
            'DeviceID' => $device_id,
            'Action' => 'Available',
            'Date' => current_time('mysql'),
            'Description' => "Marked Available via QR Scan Hub",
            'user_email' => $admin_email,
            'CategoryID' => $dev->CategoryID,
            'Owner' => '-',
            'Photo' => $photo_url
        ]);
        wp_send_json_success(['message' => "Device {$device_id} is now Available!"]);
    } elseif ($action_type === 'retired') {
        $retired_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Retired'");
        $wpdb->update('Devices', [
            'StatusID' => $retired_status,
            'OwnerID' => null,
            'PositionID' => null
        ], ['DeviceID' => $device_id]);
        $wpdb->insert('History_new', [
            'DeviceID' => $device_id,
            'Action' => 'Retired',
            'Date' => current_time('mysql'),
            'Description' => "Marked as Retired via QR Scan Hub",
            'user_email' => $admin_email,
            'CategoryID' => $dev->CategoryID,
            'Owner' => $prev_owner,
            'Photo' => $photo_url
        ]);
        wp_send_json_success(['message' => "Device {$device_id} has been Retired!"]);
    } elseif ($action_type === 'return_to_owner') {
        $inuse_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");
        $target_owner_id = $dev->OwnerID ?: $prev_owner_id;

        if ($target_owner_id) {
            $wpdb->delete('Maintenance', ['DeviceID' => $device_id], ['%s']);
            $wpdb->delete('Maintenances', ['DeviceID' => $device_id], ['%s']);
            $owner_info = $wpdb->get_row($wpdb->prepare("SELECT Nickname, FirstName, LastName, DepartmentID, PositionID, Email FROM Owners WHERE OwnerID = %d", $target_owner_id));
            $owner_nickname = $owner_info ? ($owner_info->Nickname ?: $owner_info->FirstName) : $prev_owner;

            $wpdb->update('Devices', [
                'StatusID' => $inuse_status,
                'OwnerID' => $target_owner_id,
                'DepartmentID' => $owner_info ? $owner_info->DepartmentID : null,
                'PositionID' => $owner_info ? $owner_info->PositionID : null,
                'RepairDate' => null
            ], ['DeviceID' => $device_id]);

            $wpdb->insert('History_new', [
                'DeviceID' => $device_id,
                'Action' => 'Return to Owner',
                'Date' => current_time('mysql'),
                'Description' => "Device repaired and returned to owner ({$owner_nickname}) via QR Scan Hub",
                'user_email' => $admin_email,
                'CategoryID' => $dev->CategoryID,
                'Owner' => $owner_nickname,
                'Photo' => $photo_url
            ]);

            $wpdb->update(
                'Repair_Requests',
                ['Status' => 'Completed', 'ActionDate' => current_time('mysql')],
                ['DeviceID' => $device_id]
            );

            $email_sent = false;
            if (function_exists('stock_supply_send_email')) {
                $email_sent = stock_supply_send_email('Return_to_Owner', $device_id, $target_owner_id);
            }

            $msg = "Device {$device_id} repaired and returned to owner ({$owner_nickname})!";
            if ($email_sent && $owner_info && !empty($owner_info->Email)) {
                $msg .= " Notification email sent to " . esc_html($owner_info->Email) . ".";
            } elseif ($owner_info && empty($owner_info->Email)) {
                $msg .= " (Note: Employee has no email address configured).";
            }

            wp_send_json_success(['message' => $msg]);
        } else {
            wp_send_json_error(['message' => 'No original owner found for this device.']);
        }
    } else {
        wp_send_json_error(['message' => 'Unknown action']);
    }
}
add_action('wp_ajax_quick_device_action', 'stock_supply_ajax_quick_action');

/**
 * Helper function to retrieve sidebar notification badge counts
 */
function stock_supply_get_sidebar_badges()
{
    global $wpdb;

    // 1. Pending Requests (Disabled)
    $pending_requests_count = 0;

    // 2. Devices under Maintenance
    $maintenance_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Maintenance'");
    $maintenance_count = 0;
    if ($maintenance_status_id) {
        $maintenance_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM Devices WHERE StatusID = %d",
            $maintenance_status_id
        ));
    }

    return [
        'requests' => $pending_requests_count,
        'maintenance' => $maintenance_count,
        'total' => $pending_requests_count + $maintenance_count
    ];
}

// Disable soft-keyboard auto-capitalization on text inputs and textareas
add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function disableAutocapitalize(container) {
                const root = container || document;
                root.querySelectorAll('input[type="text"], input[type="search"], textarea, .form-control').forEach(function (el) {
                    if (!el.hasAttribute('autocapitalize')) {
                        el.setAttribute('autocapitalize', 'off');
                    }
                    if (!el.hasAttribute('autocorrect')) {
                        el.setAttribute('autocorrect', 'off');
                    }
                });
            }
            disableAutocapitalize();
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            disableAutocapitalize(node);
                        }
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
    <?php
});

// ============================================================
// Offboard Employee — AJAX Endpoints
// ============================================================

/**
 * AJAX: Get all devices currently assigned to an employee (OwnerID).
 */
function stock_supply_ajax_get_employee_devices()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    check_ajax_referer('stock_supply_ajax_nonce', 'nonce');

    global $wpdb;
    $owner_id = isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0;

    if ($owner_id <= 0) {
        wp_send_json_error(['message' => 'Invalid Owner ID']);
    }

    $devices = $wpdb->get_results($wpdb->prepare("
        SELECT d.DeviceID, d.Model, d.SerialNumber,
               c.CategoryName,
               s.StatusName
        FROM Devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Statuses s   ON d.StatusID   = s.StatusID
        WHERE d.OwnerID = %d
        ORDER BY c.CategoryName ASC, d.DeviceID ASC
    ", $owner_id));

    // Also get owner info
    $owner = $wpdb->get_row($wpdb->prepare(
        "SELECT Nickname, FirstName, LastName FROM Owners WHERE OwnerID = %d",
        $owner_id
    ));

    wp_send_json_success([
        'devices' => $devices ?: [],
        'owner'   => $owner ? [
            'nickname'  => $owner->Nickname,
            'full_name' => trim($owner->FirstName . ' ' . $owner->LastName),
        ] : null,
        'count'   => count($devices ?: []),
    ]);
}
add_action('wp_ajax_get_employee_devices', 'stock_supply_ajax_get_employee_devices');

/**
 * AJAX: Offboard employee — unassign ALL devices and return to stock.
 */
function stock_supply_ajax_offboard_employee()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    check_ajax_referer('stock_supply_ajax_nonce', 'nonce');

    global $wpdb;
    $owner_id = isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0;

    if ($owner_id <= 0) {
        wp_send_json_error(['message' => 'Invalid Owner ID']);
    }

    // Get available status
    $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
    if (!$available_status_id) {
        wp_send_json_error(['message' => 'Available status not found in database']);
    }

    // Get owner info for history
    $owner_nickname = $wpdb->get_var($wpdb->prepare(
        "SELECT Nickname FROM Owners WHERE OwnerID = %d",
        $owner_id
    ));
    $safe_owner = $owner_nickname ?: '-';

    // Get all devices assigned to this owner
    $devices = $wpdb->get_results($wpdb->prepare(
        "SELECT DeviceID, Model, SerialNumber, CategoryID FROM Devices WHERE OwnerID = %d",
        $owner_id
    ));

    if (empty($devices)) {
        wp_send_json_error(['message' => 'No devices found for this employee']);
    }

    $current_user  = wp_get_current_user();
    $user_email    = $current_user->user_email ?? 'unknown@domain.com';
    $return_date   = current_time('Y-m-d');
    $success_count = 0;
    $errors        = [];

    $wpdb->query('START TRANSACTION');

    foreach ($devices as $dev) {
        $updated = $wpdb->update(
            'Devices',
            [
                'StatusID'           => $available_status_id,
                'OwnerID'            => null,
                'DepartmentID'       => null,
                'ReceiveDate'        => null,
                'ReturnDate'         => null,
                'RepairDate'         => null,
                'PositionID'         => null,
                'ExpectedReturnDate' => null,
                'LastNotifiedDate'   => null,
            ],
            ['DeviceID' => $dev->DeviceID]
        );

        if ($updated !== false) {
            $safe_category_id = !empty($dev->CategoryID) ? $dev->CategoryID : null;

            $wpdb->insert('History_new', [
                'DeviceID'    => $dev->DeviceID,
                'Action'      => 'Offboard Return',
                'Date'        => current_time('mysql'),
                'Description' => "Offboard: Device {$dev->DeviceID} ({$dev->Model}) returned to stock from {$safe_owner}",
                'user_email'  => $user_email,
                'CategoryID'  => $safe_category_id,
                'Owner'       => $safe_owner,
            ]);
            $success_count++;
        } else {
            $errors[] = $dev->DeviceID;
        }
    }

    if ($success_count > 0 && empty($errors)) {
        $wpdb->query('COMMIT');
    } elseif ($success_count > 0) {
        // Partial success — still commit what worked
        $wpdb->query('COMMIT');
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'Failed to update any devices']);
    }

    wp_send_json_success([
        'message'       => "Successfully returned {$success_count} device(s) to stock.",
        'success_count' => $success_count,
        'error_count'   => count($errors),
        'error_ids'     => $errors,
    ]);
}
add_action('wp_ajax_offboard_employee', 'stock_supply_ajax_offboard_employee');

// ============================================================
// Quick Swap Device — AJAX Endpoints
// ============================================================

/**
 * AJAX: Get options for Quick Swap (Old device info + list of Available replacement devices)
 */
function stock_supply_ajax_get_quick_swap_options()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    check_ajax_referer('stock_supply_ajax_nonce', 'nonce');

    global $wpdb;
    $old_device_id = isset($_POST['old_device_id']) ? sanitize_text_field($_POST['old_device_id']) : '';

    if (empty($old_device_id)) {
        wp_send_json_error(['message' => 'Invalid Device ID']);
    }

    // Get old device info
    $old_device = $wpdb->get_row($wpdb->prepare("
        SELECT d.DeviceID, d.Model, d.SerialNumber, d.CategoryID, d.OwnerID, d.DepartmentID, d.PositionID,
               c.CategoryName, b.BrandName,
               s.StatusName,
               o.Nickname as OwnerNickname, CONCAT(o.FirstName, ' ', o.LastName) as OwnerFullName,
               dep.DepartmentName
        FROM Devices d
        LEFT JOIN Categories c   ON d.CategoryID   = c.CategoryID
        LEFT JOIN Brands b       ON d.BrandID      = b.BrandID
        LEFT JOIN Statuses s     ON d.StatusID     = s.StatusID
        LEFT JOIN Owners o       ON d.OwnerID      = o.OwnerID
        LEFT JOIN Departments dep ON d.DepartmentID = dep.DepartmentID
        WHERE d.DeviceID = %s
    ", $old_device_id));

    if (!$old_device) {
        wp_send_json_error(['message' => 'Old device not found']);
    }

    // Get available replacement devices ONLY in the same category
    $available_devices = $wpdb->get_results($wpdb->prepare("
        SELECT d.DeviceID, d.Model, d.SerialNumber, d.CategoryID,
               c.CategoryName, b.BrandName
        FROM Devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Brands b     ON d.BrandID    = b.BrandID
        INNER JOIN Statuses s  ON d.StatusID   = s.StatusID
        WHERE s.StatusName = 'Available' AND d.DeviceID != %s AND d.CategoryID = %d
        ORDER BY d.DeviceID ASC
    ", $old_device_id, $old_device->CategoryID));

    wp_send_json_success([
        'old_device'        => $old_device,
        'available_devices' => $available_devices ?: [],
    ]);
}
add_action('wp_ajax_get_quick_swap_options', 'stock_supply_ajax_get_quick_swap_options');

/**
 * AJAX: Perform Quick Swap Device
 * Send old device to Maintenance + Reassign new available device to old owner
 */
function stock_supply_ajax_quick_swap_device()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    check_ajax_referer('stock_supply_ajax_nonce', 'nonce');

    global $wpdb;
    $old_device_id = isset($_POST['old_device_id']) ? sanitize_text_field($_POST['old_device_id']) : '';
    $new_device_id = isset($_POST['new_device_id']) ? sanitize_text_field($_POST['new_device_id']) : '';
    $repair_reason = isset($_POST['repair_reason']) ? sanitize_textarea_field($_POST['repair_reason']) : '';

    if (empty($old_device_id) || empty($new_device_id)) {
        wp_send_json_error(['message' => 'Please select both old faulty device and new replacement device']);
    }

    if (empty($repair_reason)) {
        wp_send_json_error(['message' => 'Please specify the maintenance / fault reason for the old device']);
    }

    // Fetch Status IDs
    $maint_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Maintenance'");
    $inuse_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");

    if (!$maint_status_id || !$inuse_status_id) {
        wp_send_json_error(['message' => 'Required statuses not found in database']);
    }

    // Fetch Old Device
    $old_dev = $wpdb->get_row($wpdb->prepare("SELECT * FROM Devices WHERE DeviceID = %s", $old_device_id));
    if (!$old_dev) {
        wp_send_json_error(['message' => 'Old device not found']);
    }

    // Fetch New Replacement Device
    $new_dev = $wpdb->get_row($wpdb->prepare("SELECT * FROM Devices WHERE DeviceID = %s", $new_device_id));
    if (!$new_dev) {
        wp_send_json_error(['message' => 'New replacement device not found']);
    }

    $current_user = wp_get_current_user();
    $user_email   = $current_user->user_email ?? 'unknown@domain.com';
    $today_date   = current_time('Y-m-d');
    $now_mysql    = current_time('mysql');

    // Get owner nickname for history
    $owner_nickname = '-';
    if (!empty($old_dev->OwnerID)) {
        $owner_nickname = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $old_dev->OwnerID)) ?: '-';
    }

    $wpdb->query('START TRANSACTION');

    // ----------------------------------------------------
    // 1. Old Device -> Send to Maintenance
    // ----------------------------------------------------
    $inserted_maint = $wpdb->insert('Maintenance', [
        'DeviceID'   => $old_device_id,
        'RepairDate' => $today_date,
        'Details'    => $repair_reason,
        'user_email' => $user_email,
        'Photo'      => null,
        'CreatedAt'  => $now_mysql,
        'UpdatedAt'  => $now_mysql,
    ]);

    $updated_old = $wpdb->update('Devices', [
        'StatusID'     => $maint_status_id,
        'OwnerID'      => null,
        'DepartmentID' => null,
        'ReceiveDate'  => null,
        'RepairDate'   => $today_date,
        'ReturnDate'   => null,
        'PositionID'   => null,
    ], ['DeviceID' => $old_device_id]);

    // History for Old Device
    $wpdb->insert('History_new', [
        'DeviceID'    => $old_device_id,
        'Action'      => 'Quick Swap Maintenance',
        'Date'        => $now_mysql,
        'Description' => "Quick Swap: Device {$old_device_id} ({$old_dev->Model}) sent to Maintenance from {$owner_nickname}. Reason: {$repair_reason}",
        'user_email'  => $user_email,
        'CategoryID'  => $old_dev->CategoryID,
        'Owner'       => $owner_nickname,
    ]);

    // ----------------------------------------------------
    // 2. New Device -> Reassign to Employee
    // ----------------------------------------------------
    $updated_new = $wpdb->update('Devices', [
        'StatusID'     => $inuse_status_id,
        'OwnerID'      => $old_dev->OwnerID,
        'DepartmentID' => $old_dev->DepartmentID,
        'PositionID'   => $old_dev->PositionID,
        'ReceiveDate'  => $today_date,
        'ReturnDate'   => null,
    ], ['DeviceID' => $new_device_id]);

    // History for New Device
    $wpdb->insert('History_new', [
        'DeviceID'    => $new_device_id,
        'Action'      => 'Quick Swap Reassign',
        'Date'        => $now_mysql,
        'Description' => "Quick Swap: Replacement Device {$new_device_id} ({$new_dev->Model}) assigned to {$owner_nickname} (Replaced {$old_device_id})",
        'user_email'  => $user_email,
        'CategoryID'  => $new_dev->CategoryID,
        'Owner'       => $owner_nickname,
    ]);

    if ($inserted_maint !== false && $updated_old !== false && $updated_new !== false) {
        $wpdb->query('COMMIT');
        wp_send_json_success([
            'message' => "Quick Swap completed! Device {$old_device_id} sent to Maintenance. Device {$new_device_id} assigned to {$owner_nickname}.",
        ]);
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'Failed to complete Quick Swap operation. Database error.']);
    }
}
add_action('wp_ajax_quick_swap_device', 'stock_supply_ajax_quick_swap_device');

/**
 * Global JavaScript for Quick Swap Device Modal
 */
add_action('wp_footer', function () {
    if (!is_user_logged_in()) return;
    ?>
    <script>
        window.quickSwapDevice = function(oldDeviceID) {
            if (!oldDeviceID) return;

            const ajaxUrl = '<?= admin_url('admin-ajax.php') ?>';
            const ajaxNonce = '<?= wp_create_nonce("stock_supply_ajax_nonce") ?>';

            if (typeof Swal === 'undefined') {
                alert('SweetAlert2 library is missing.');
                return;
            }

            Swal.fire({
                title: 'Loading device details...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('action', 'get_quick_swap_options');
            formData.append('nonce', ajaxNonce);
            formData.append('old_device_id', oldDeviceID);

            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire('Error', data.data ? data.data.message : 'Could not load device details.', 'error');
                        return;
                    }

                    const oldDev = data.data.old_device;
                    const availables = data.data.available_devices;

                    let availableOptionsHtml = `<option value="">-- Select replacement ${oldDev.CategoryName || 'device'} --</option>`;
                    if (availables.length === 0) {
                        availableOptionsHtml = `<option value="">❌ No available replacement devices in ${oldDev.CategoryName || 'this category'}</option>`;
                    } else {
                        availables.forEach(dev => {
                            const brandModel = (dev.BrandName || '') + ' ' + (dev.Model || '');
                            availableOptionsHtml += `<option value="${dev.DeviceID}">[${dev.DeviceID}] ${brandModel} - SN: ${dev.SerialNumber || '-'}</option>`;
                        });
                    }

                    const modalHtml = `
                        <div style="text-align: left; font-size: 0.92rem; font-family: sans-serif;">
                            <div style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px;">
                                <div style="font-weight: 700; color: #c2410c; margin-bottom: 4px; font-size: 0.95rem;">
                                    <i class="fa-solid fa-laptop-medical"></i> Faulty Device (to Maintenance):
                                </div>
                                <div style="color: #431407;">
                                    <strong>${oldDev.DeviceID}</strong> - ${oldDev.BrandName || ''} ${oldDev.Model || ''} (${oldDev.CategoryName || ''})<br>
                                    <small style="color: #7c2d12;">Held by: <strong>${oldDev.OwnerNickname || '-'}</strong> ${oldDev.OwnerFullName ? '(' + oldDev.OwnerFullName + ')' : ''} | Dept: ${oldDev.DepartmentName || '-'}</small>
                                </div>
                            </div>

                            <div style="margin-bottom: 14px;">
                                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    1. Fault / Maintenance Reason <span style="color: #ef4444;">*</span>
                                </label>
                                <textarea id="swap-repair-reason" class="swal2-textarea" placeholder="Describe the issue (e.g. Screen broken, Won't power on, Battery failure)" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 0.88rem; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 75px;"></textarea>
                            </div>

                            <div style="margin-bottom: 8px;">
                                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                    2. Select Replacement Device <span style="color: #ef4444;">*</span>
                                </label>
                                <select id="swap-new-device-id" class="swal2-select" style="margin: 0; width: 100%; box-sizing: border-box; font-size: 0.88rem; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 42px;">
                                    ${availableOptionsHtml}
                                </select>
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: '⚡ Quick Swap Device',
                        html: modalHtml,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-solid fa-arrows-rotate"></i> Confirm Quick Swap',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#f59e0b',
                        cancelButtonColor: '#6b7280',
                        customClass: { popup: 'quick-swap-popup' },
                        preConfirm: () => {
                            const reason = document.getElementById('swap-repair-reason').value.trim();
                            const newDevId = document.getElementById('swap-new-device-id').value;

                            if (!reason) {
                                Swal.showValidationMessage('Please enter the fault or maintenance reason.');
                                return false;
                            }
                            if (!newDevId) {
                                Swal.showValidationMessage('Please pick a replacement device.');
                                return false;
                            }
                            return { newDevId: newDevId, reason: reason };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Swapping devices...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });

                            const swapData = new FormData();
                            swapData.append('action', 'quick_swap_device');
                            swapData.append('nonce', ajaxNonce);
                            swapData.append('old_device_id', oldDeviceID);
                            swapData.append('new_device_id', result.value.newDevId);
                            swapData.append('repair_reason', result.value.reason);

                            fetch(ajaxUrl, { method: 'POST', body: swapData })
                                .then(res => res.json())
                                .then(resp => {
                                    if (resp.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Quick Swap Complete!',
                                            text: resp.data.message,
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire('Error', resp.data ? resp.data.message : 'Could not swap devices.', 'error');
                                    }
                                })
                                .catch(err => {
                                    Swal.fire('Error', 'Connection error: ' + err.message, 'error');
                                });
                        }
                    });
                })
                .catch(err => {
                    Swal.fire('Error', 'Failed to fetch options: ' + err.message, 'error');
                });
        };
    </script>
    <?php
});

