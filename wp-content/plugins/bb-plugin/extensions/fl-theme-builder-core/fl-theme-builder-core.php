<?php

// Defines
define( 'FL_THEME_BUILDER_CORE_DIR', FL_BUILDER_DIR . 'extensions/fl-theme-builder-core/' );
define( 'FL_THEME_BUILDER_CORE_URL', FL_BUILDER_URL . 'extensions/fl-theme-builder-core/' );

add_action( 'plugins_loaded', function () {

	if ( defined( 'FL_THEME_BUILDER_VERSION' ) && class_exists( 'FLThemeBuilderFieldConnections' ) ) {
		return;
	}

	require_once FL_THEME_BUILDER_CORE_DIR . 'classes/class-fl-page-data.php';
	require_once FL_THEME_BUILDER_CORE_DIR . 'classes/class-fl-taxonomy-terms-walker.php';
	require_once FL_THEME_BUILDER_CORE_DIR . 'classes/class-fl-page-walker.php';
	require_once FL_THEME_BUILDER_CORE_DIR . 'classes/class-fl-theme-builder-field-connections.php';
	require_once FL_THEME_BUILDER_CORE_DIR . 'classes/class-fl-theme-builder-layout-data.php';
	require_once FL_THEME_BUILDER_CORE_DIR . 'classes/class-fl-theme-builder-rules-location.php';

	// Dont do expensive query
	if ( ! defined( 'FL_THEME_BUILDER_VERSION' ) ) {
		remove_action( 'wp', array( 'FLThemeBuilderFieldConnections', 'connect_all_layout_settings' ) );
	}
}, 11 );
