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

// Redirect old QR code URLs to the new system
add_action('template_redirect', function() {
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
        filemtime(get_stylesheet_directory_uri() . '/css/style.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/device_dashboard.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_device_dashboard.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_monitor_dashboard.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_laptop_dashboard.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_accessories_dashboard.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_receive_device.css') //auto-refresh cache
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_maintenance.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_maintenance_styles');



// Load Bootstrap CSS and JS from CDN
function load_bootstrap_cdn()
{
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
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
        filemtime(get_stylesheet_directory_uri() . '/css/style_action_menu.css') //auto-refresh cache
    );
}
add_action('wp_enqueue_scripts', 'enqueue_action_menu_styles');


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

    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);

    wp_enqueue_script('sweetalert_delete_details', get_stylesheet_directory_uri() . '/js/sweetalert_delete_details.js', array('sweetalert2'), null, true);
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
