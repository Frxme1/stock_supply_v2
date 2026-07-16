<?php
/**
 * Plugin Name: Ultimate Addons for Beaver Builder
 * Plugin URI: http://www.ultimatebeaver.com/
 * Description: Ultimate Addons is a premium extension for Beaver Builder that adds 70+ modules, 250+ templates and works on top of any Beaver Builder Package. (Free, Standard, Pro & Agency) You can use it with any WordPress theme.
 * Version: 1.36.10
 * Author: Brainstorm Force
 * Author URI: http://www.brainstormforce.com
 * Text Domain: uabb
 *
 * @package Ultimate Addons For Beaver Builder
 */

/**
 * Custom modules
 */
if ( ! class_exists( 'BB_Ultimate_Addon' ) ) {
	/**
	 * This class initializes BB Ultiamte Addons
	 *
	 * @class BB_Ultimate_Addon
	 */
	class BB_Ultimate_Addon {

		/**
		 * Constructor function that initializes required actions and hooks
		 *
		 * @since 1.0
		 */
		public function __construct() {

			$this->define_constant();
			register_activation_hook( __FILE__, array( $this, 'activation_reset' ) );
			// UABB Initialize.
			require_once 'classes/class-uabb-init.php';
			add_filter( 'bsf_is_product_bundled', array( $this, 'remove_astra_pro_bundled_products' ), 20, 3 );
		}
		/**
		 * Function which defines the constand for UABB plugin
		 */
		public function define_constant() {

			define( 'BB_ULTIMATE_ADDON_VER', '1.36.10' );
			define( 'BB_ULTIMATE_ADDON_DIR', plugin_dir_path( __FILE__ ) );
			define( 'BB_ULTIMATE_ADDON_URL', plugins_url( '/', __FILE__ ) );
			define( 'BSF_REMOVE_UABB_FROM_REGISTRATION_LISTING', true );
		}

		/**
		 * Function which resets the plugin activation if necessary memmory not found
		 *
		 * @Since 1.4.0
		 */
		public function activation_reset() {

			$no_memory = $this->check_memory_limit();

			if ( true === $no_memory && ! defined( 'WP_CLI' ) ) {

				$msg  = __( 'Unfortunately, plugin could not be activated as the memory allocated by your host has almost exhausted. UABB plugin recommends that your site should have 15M PHP memory remaining. ', 'uabb' );
				$msg .= '<br/><br/>' . __( 'Please check ', 'uabb' ) . '<a target="_blank" rel="noopener" href="https://www.ultimatebeaver.com/docs/increase-memory-limit-site/">' . __( 'this article', 'uabb' ) . '</a> ';
				$msg .= __( ' for solution or contact ', 'uabb' ) . '<a target="_blank" rel="noopener" href="http://store.brainstormforce.com/support">' . __( ' support', 'uabb' ) . '</a>.';
				$msg .= '<br/><br/><a class="button button-primary" href="' . network_admin_url( 'plugins.php' ) . '">' . __( 'Return to Plugins Page', 'uabb' ) . '</a>';

				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( wp_kses_post( $msg ) );
			}

			delete_option( 'uabb_hide_branding' );

			// Force check graupi bundled products.
			update_site_option( 'bsf_force_check_extensions', true );
		}

		/**
		 * Memory Limit function that checks for memory limit for the UABB plugin
		 *
		 * @Since 1.4.0
		 */
		public function check_memory_limit() {

			$memory_limit  = ini_get( 'memory_limit' );      // Total Memory.
			$peak_memory   = memory_get_peak_usage( true );  // Available Memory.
			$uabb_required = 14999999;                       // Required Memory for UABB.

			if ( '-1' !== $memory_limit ) {
				if ( preg_match( '/^(\d+)(.)$/', $memory_limit, $matches ) ) {

					switch ( $matches[2] ) {
						case 'K':
						case 'k':
							$memory_limit = $matches[1] * 1024;
							break;
						case 'M':
						case 'm':
							$memory_limit = $matches[1] * 1024 * 1024;
							break;
						case 'G':
						case 'g':
							$memory_limit = $matches[1] * 1024 * 1024 * 1024;
							break;
					}
				}

				if ( $memory_limit - $peak_memory <= $uabb_required ) {
					return true;
				} else {
					return false;
				}
			}
		}

		/**
		 * Remove bundled products for Astra Pro Sites.
		 * For Astra Pro Sites the bundled products are only used for one click plugin installation when importing the Astra Site.
		 * License Validation and product updates are managed separately for all the products.
		 *
		 * @since x.x.x
		 *
		 * @param  array  $product_parent  Array of parent product ids.
		 * @param  String $bsf_product    Product ID or  Product init or Product name based on $search_by.
		 * @param  String $search_by      Reference to search by id | init | name of the product.
		 *
		 * @return array                 Array of parent product ids.
		 */
		public function remove_astra_pro_bundled_products( $product_parent, $bsf_product, $search_by ) {

			// Bundled plugins are installed when the demo is imported on Ajax request and bundled products should be unchanged in the ajax.
			if ( ! defined( 'DOING_AJAX' ) && ! defined( 'WP_CLI' ) ) {

				$key = array_search( 'astra-pro-sites', $product_parent, true );

				if ( false !== $key ) {
					unset( $product_parent[ $key ] );
				}
			}

			return $product_parent;
		}

	}

	new BB_Ultimate_Addon();
}

/**
 * Load brainstorm product updater
 */

$bsf_core_version_file = realpath( dirname( __FILE__ ) . '/admin/bsf-core/version.yml' );

if ( is_file( $bsf_core_version_file ) ) {
	global $bsf_core_version, $bsf_core_path;
	$bsf_core_dir = realpath( dirname( __FILE__ ) . '/admin/bsf-core/' );
	$version      = file_get_contents( $bsf_core_version_file );// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reading local file is OK.
	if ( version_compare( $version, $bsf_core_version ? $bsf_core_version : '0.0.0', '>' ) ) {
		$bsf_core_version = $version;
		$bsf_core_path    = $bsf_core_dir;
	}
}

if ( ! function_exists( 'bsf_core_load' ) ) {

	/**
	 * Responsible for checking BSF Core files and version number
	 *
	 * @since 0.1.0
	 */
	function bsf_core_load() {

		global $bsf_core_version, $bsf_core_path;

		if ( is_file( realpath( $bsf_core_path . '/index.php' ) ) ) {
			include_once realpath( $bsf_core_path . '/index.php' );
		}
	}
}

add_action( 'init', 'bsf_core_load', 999 );
