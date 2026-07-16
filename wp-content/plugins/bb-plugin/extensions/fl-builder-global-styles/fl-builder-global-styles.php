<?php

add_action( 'plugins_loaded', function () {
	// make sure we have at least Themer 1.4.7 or dev version
	if ( defined( 'FL_THEME_BUILDER_VERSION' ) && '{FL_THEME_BUILDER_VERSION}' !== FL_THEME_BUILDER_VERSION && version_compare( FL_THEME_BUILDER_VERSION, '1.4.7', '<' ) ) {

		/**
		 * Polyfill
		 */
		class FLBuilderGlobalStyles {
			public static function get_settings() {
				return (object) [ 'colors' => [] ];
			}
			public static function get_theme_json_js_config() {
				return [ 'color' => [ 'palette' => [] ] ];
			}
		}
		return;
	}
	// Defines
	define( 'FL_BUILDER_GLOBAL_STYLES_DIR', FL_BUILDER_DIR . 'extensions/fl-builder-global-styles/' );
	define( 'FL_BUILDER_GLOBAL_STYLES_URL', FLBuilder::plugin_url() . 'extensions/fl-builder-global-styles/' );

	// Classes
	require_once FL_BUILDER_GLOBAL_STYLES_DIR . 'classes/class-fl-builder-global-styles.php';
}, 11 );
