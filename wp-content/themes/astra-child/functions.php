<?php

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


// /**
//  * Enqueue styles
//  */
// function child_enqueue_styles() {

// 	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

// }

// add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );
if (has_post_thumbnail()) {
    the_post_thumbnail('full'); // หรือขนาดอื่น ๆ เช่น 'medium', 'large'
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
require_once get_stylesheet_directory() . '/controller/export_csv.php';
require_once get_stylesheet_directory() . '/controller/import_csv.php';

// require Request System
require_once get_stylesheet_directory() . '/model/request/form_request.php';
require_once get_stylesheet_directory() . '/model/request/request_dashboard.php';


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
    // check page Logout
    if (isset($_SERVER['REQUEST_URI']) && untrailingslashit($_SERVER['REQUEST_URI']) === '/logout') {
        if (is_user_logged_in()) {
            wp_logout(); // logout user
        }
        wp_redirect(wp_login_url()); // redirect -> login
        exit;
    }
}
add_action('template_redirect', 'logout_redirect');


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
    } elseif ($action === 'Return_to_Owner') {
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
        PRIMARY KEY  (RequestID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_repair);
}
add_action('after_setup_theme', 'stock_supply_setup_db');

// Auto create pages for the Request System
function auto_create_request_pages()
{
    // Create Employee Request Form Page
    $form_page = get_page_by_title('แบบฟอร์มขอยืมอุปกรณ์');
    if (!$form_page) {
        wp_insert_post([
            'post_title' => 'แบบฟอร์มขอยืมอุปกรณ์',
            'post_name' => 'request-device-form',
            'post_content' => '[device_request_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'page_template' => 'template-blank-form.php'
        ]);
    }

    // Create IT Dashboard Page
    $dashboard_page = get_page_by_title('จัดการคำขอยืมอุปกรณ์');
    if (!$dashboard_page) {
        wp_insert_post([
            'post_title' => 'จัดการคำขอยืมอุปกรณ์',
            'post_name' => 'request-dashboard',
            'post_content' => '[device_request_dashboard]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }
}
add_action('admin_init', 'auto_create_request_pages');

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
        h1, h2, h3, h4, h5, h6,
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


