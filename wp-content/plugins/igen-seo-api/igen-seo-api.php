<?php
/**
 * Plugin Name: IGen SEO API
 * Description: Register Yoast SEO meta fields to make them accessible through REST API for reading and writing.
 * Version: 1.0.0
 * Author: IGen Team
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: igen-seo-api
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Yoast SEO meta fields for REST API access
 *
 * This function registers Yoast SEO meta fields to make them accessible
 * through the WordPress REST API for reading and writing operations.
 *
 * @since 1.0.0
 */
function igen_register_yoast_meta_fields() {
    // Define supported post types
    $post_types = array( 'page', 'post' );
    
    // Define Yoast SEO meta fields to register
    $fields = array( 
        '_yoast_wpseo_title',      // SEO title
        '_yoast_wpseo_metadesc',   // Meta description
        '_yoast_wpseo_focuskw'     // Focus keyword
    );

    // Register meta fields for each post type
    foreach ( $post_types as $post_type ) {
        foreach ( $fields as $field ) {
            register_post_meta( $post_type, $field, array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => 'igen_meta_auth_callback',
                'description'  => sprintf( 
                    // translators: %s is the field name (title, metadesc, or focuskw)
                    __( 'Yoast SEO %s field', 'igen-seo-api' ), 
                    ucfirst( str_replace( '_yoast_wpseo_', '', $field ) )
                ),
            ) );
        }
    }
}

/**
 * Authentication callback for meta fields
 *
 * @since 1.0.0
 * @return bool True if user can edit posts, false otherwise
 */
function igen_meta_auth_callback() {
    return current_user_can( 'edit_posts' );
}

// Hook the registration function to init
add_action( 'init', 'igen_register_yoast_meta_fields' );

/**
 * Check if Yoast SEO plugin is active
 *
 * @since 1.0.0
 * @return bool True if Yoast SEO is active, false otherwise
 */
function igen_is_yoast_seo_active() {
    return is_plugin_active( 'wordpress-seo/wp-seo.php' ) || 
           is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' );
}

/**
 * Display admin notice if Yoast SEO is not active
 *
 * @since 1.0.0
 */
function igen_yoast_seo_admin_notice() {
    if ( ! igen_is_yoast_seo_active() ) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e( 'IGen SEO API', 'igen-seo-api' ); ?>:</strong>
                <?php esc_html_e( 'This plugin requires Yoast SEO to be installed and activated.', 'igen-seo-api' ); ?>
                <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=yoast+seo&tab=search&type=term' ) ); ?>">
                    <?php esc_html_e( 'Install Yoast SEO', 'igen-seo-api' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'igen_yoast_seo_admin_notice' );

/**
 * Only register meta fields if Yoast SEO is active
 *
 * @since 1.0.0
 */
function igen_register_yoast_meta_fields_safe() {
    if ( ! igen_is_yoast_seo_active() ) {
        return;
    }
    
    igen_register_yoast_meta_fields();
}
remove_action( 'init', 'igen_register_yoast_meta_fields' );
add_action( 'init', 'igen_register_yoast_meta_fields_safe' );

/**
 * Plugin activation hook
 *
 * @since 1.0.0
 */
function igen_wordpress_activate() {
    // Flush rewrite rules on activation
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'igen_wordpress_activate' );

/**
 * Plugin deactivation hook
 *
 * @since 1.0.0
 */
function igen_wordpress_deactivate() {
    // Flush rewrite rules on deactivation
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'igen_wordpress_deactivate' );
