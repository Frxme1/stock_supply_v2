<?php

/**
 * Global styles.
 *
 * @since 2.8
 */
final class FLBuilderGlobalStyles {

	/**
	 * Cached settings.
	 *
	 * @access private
	 * @var array $settings
	 */
	static private $settings;

	/**
	 * CSS variables.
	 *
	 * @since 2.8
	 * @var string $css_vars
	 */
	static private $css_vars = array();

	/**
	 * Initializes the logic and actions for global styles.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function init() {
		// actions.
		add_action( 'setup_theme', __CLASS__ . '::load_settings', 1 );
		add_action( 'wp', __CLASS__ . '::register_ajax_actions', 1 );
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_global_styles_scripts', 9 );
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_global_styles_preview_scripts', 20 );
		add_action( 'fl_builder_render_custom_css_for_editing', __CLASS__ . '::render_custom_css_for_editing' );
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::render_fonts_for_global_css' );
		add_action( 'fl_page_data_add_properties', __CLASS__ . '::add_page_data_properties' );
		add_action( 'after_setup_theme', __CLASS__ . '::wp_theme_json_data_theme', 11 );

		// filters.
		add_filter( 'fl_builder_js_config_settings_forms', __CLASS__ . '::add_js_config_for_settings_forms', 10, 1 );
		add_filter( 'fl_builder_ui_js_config', __CLASS__ . '::add_js_config_for_settings_forms', 10, 1 );
		add_filter( 'fl_builder_ui_js_config', __CLASS__ . '::add_theme_json_js_config', 10, 1 );
		add_filter( 'fl_builder_ui_js_config', __CLASS__ . '::add_global_color_labels_js_config', 10, 1 );
		add_filter( 'fl_builder_global_css_string', __CLASS__ . '::inject_global_css_string', 10, 1 );
		add_filter( 'wp_theme_json_data_user', __CLASS__ . '::filter_theme_json_data_user' );
	}

	/**
	 * Load settings.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function load_settings() {
		require_once FL_BUILDER_GLOBAL_STYLES_DIR . 'includes/controls.php';
	}

	/**
	 * Register AJAX actions.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function register_ajax_actions() {
		FLBuilderAJAX::add_action( 'generate_global_style_css', __CLASS__ . '::generate_css_ajax', array( 'global_settings' ) );
		FLBuilderAJAX::add_action( 'save_global_styles', __CLASS__ . '::save_settings', array( 'settings' ) );
		FLBuilderAJAX::add_action( 'reset_global_styles', __CLASS__ . '::reset_settings' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function enqueue_global_styles_scripts() {
		if ( FLBuilderModel::is_builder_active() ) {
			$ver = FL_BUILDER_VERSION;

			wp_enqueue_script( 'fl-builder-global-styles', FL_BUILDER_GLOBAL_STYLES_URL . 'js/fl-builder-global-styles.js', array( 'jquery' ), $ver );
		}
	}

	static public function enqueue_global_styles_preview_scripts() {
		if ( FLBuilderModel::is_builder_active() ) {
			$ver = FL_BUILDER_VERSION;

			wp_enqueue_script( 'fl-builder-global-styles-preview', FL_BUILDER_GLOBAL_STYLES_URL . 'js/fl-builder-global-styles-preview.js', array( 'jquery' ), $ver );
		}
	}

	/**
	 * Get the JS configuration for settings forms.
	 *
	 * @since 2.8.0
	 * @param array $config The current JS configuration.
	 * @return array The updated JS configuration.
	 */
	static public function add_js_config_for_settings_forms( $config ) {
		$config['styles'] = self::get_settings( false );
		if ( empty( $config['styles']->colors ) ) {
			$config['styles']->colors[] = array();
		}
		return $config;
	}

	/**
	 * Adds the JS configuration for theme.json styles.
	 *
	 * @since 2.8.0
	 * @param array $config The current JS configuration.
	 * @return array The updated JS configuration.
	 */
	static public function add_theme_json_js_config( $config ) {
		$config['themeJSON'] = self::get_theme_json_js_config();

		return $config;
	}

	static public function wp_theme_json_data_theme() {
		// Using $_SERVER here since REST_REQUEST is not available yet.
		if ( isset( $_GET['fl_builder'] ) || strstr( $_SERVER['REQUEST_URI'], 'fl-controls/' ) ) {
			return false;
		}
		$palette  = (array) current( (array) get_theme_support( 'editor-color-palette' ) );
		$settings = self::get_settings( false );
		$new_data = array();
		if ( ! empty( $settings->colors ) ) {
			foreach ( $settings->colors as $color ) {
				$color = (object) $color;

				if ( ! isset( $color->label ) || ! isset( $color->color ) || empty( $color->color ) ) {
					continue;
				}

				$key = str_replace( array( '_', ' ' ), '-', strtolower( $color->label ) );
				$key = preg_replace( '/[^A-Za-z0-9\-]/', '', $key );

				$new_data[] = array(
					'slug'  => $key,
					'color' => FLBuilderColor::hex_or_rgb( $color->color ),
					'name'  => esc_html( $color->label ),
				);
			}
		}
		add_theme_support( 'editor-color-palette', array_merge( array_filter( $palette ), $new_data ) );
	}

	/**
	 * Returns the JS configuration for theme.json styles.
	 *
	 * @since 2.9
	 * @return array
	 */
	static public function get_theme_json_js_config() {
		$theme_json = [
			'color' => [
				'palette' => [],
			],
		];

		if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();

			if ( ! empty( $settings['color']['palette']['default'] ) ) {
				$theme_json['color']['palette']['default'] = $settings['color']['palette']['default'];
			}

			if ( ! empty( $settings['color']['palette']['theme'] ) ) {
				$theme_json['color']['palette']['theme'] = $settings['color']['palette']['theme'];
			}
		}

		// Must be filtered this way for backwards compat. Previously, the
		// entire BB config array was filtered.
		$filtered = apply_filters( 'fl_builder_global_colors_json', [
			'themeJSON' => $theme_json,
		] );

		return $filtered['themeJSON'];
	}

	/**
	 * Adds global color labels to the builder's JS configuration.
	 *
	 * @since 2.8.0
	 * @param array $config The current JS configuration.
	 * @return array The updated JS configuration.
	 */
	static public function add_global_color_labels_js_config( $config ) {
		$labels     = [];
		$properties = FLPageData::get_properties();

		foreach ( $properties['site'] as $key => $value ) {
			$labels[ $key ] = $value['label'];
		}

		$config['globalColorLabels'] = $labels;

		return $config;
	}

	/**
	 * Generate global CSS string.
	 *
	 * @since 2.8.0
	 * @param string $css The current global CSS.
	 * @return string The updated global CSS.
	 */
	static public function inject_global_css_string( $css ) {
		if ( ! FLBuilderModel::is_builder_active() ) {
			$css .= self::generate_css();
		}
		return $css;
	}

	/**
	 * Render custom CSS for editing.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function render_custom_css_for_editing() {
		if ( FLBuilderUIIFrame::is_enabled() ) {
			if ( FLBuilderUIIFrame::is_iframe_request() ) {
				echo '<style id="fl-builder-global-styles">' . self::generate_css() . '</style>';
			}
		} else {
			echo '<style id="fl-builder-global-styles">' . self::generate_css() . '</style>';
		}
	}

	/**
	 * Render fonts for global CSS.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function render_fonts_for_global_css() {
		// Enqueue fonts for global styles.
		FLBuilderFonts::add_fonts_for_global_css( self::get_settings() );
	}

	/**
	 * Add page data properties.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function add_page_data_properties() {
		require_once FL_BUILDER_GLOBAL_STYLES_DIR . 'includes/page-data-global-colors.php';
	}

	/**
	 * Inject BB Global colors with theme.json
	 *
	 * @since 2.8.0
	 * @param object $theme_json Theme json object.
	 */
	static public function filter_theme_json_data_user( $theme_json ) {
		$settings = self::get_settings( false );
		$old_data = $theme_json->get_data();
		$new_data = array(
			'version'  => 2,
			'settings' => array(
				'color' => array(
					'palette' => array(
						'custom' => array(),
					),
				),
			),
		);

		if ( ! empty( $settings->colors ) ) {
			foreach ( $settings->colors as $color ) {
				$color = (object) $color;

				if ( ! isset( $color->label ) || ! isset( $color->color ) || empty( $color->color ) ) {
					continue;
				}

				$key = str_replace( array( '_', ' ' ), '-', strtolower( $color->label ) );
				$key = preg_replace( '/[^A-Za-z0-9\-]/', '', $key );

				$new_data['settings']['color']['palette']['custom'][] = array(
					'slug'  => $key,
					'color' => FLBuilderColor::hex_or_rgb( $color->color ),
					'name'  => esc_html( $color->label ),
				);
			}
		}

		if ( ! empty( $old_data['settings']['color']['palette']['custom'] ) ) {
			$new_data['settings']['color']['palette']['custom'] = array_merge(
				$old_data['settings']['color']['palette']['custom'],
				$new_data['settings']['color']['palette']['custom']
			);
		}

		return $theme_json->update_with( $new_data );
	}

	/**
	 * Get the global styles.
	 *
	 * @since 2.8.0
	 * @return object
	 */
	static public function get_settings( $with_connection = true ) {
		if ( null === self::$settings ) {
			$settings = get_option( '_fl_builder_styles' );
			$defaults = FLBuilderModel::get_settings_form_defaults( 'styles' );

			if ( ! $settings ) {
				$settings = new StdClass();
			}

			// Merge in defaults and cache settings
			self::$settings = (object) array_merge( (array) $defaults, (array) $settings );
		}

		if ( $with_connection ) {
			return FLThemeBuilderFieldConnections::connect_settings( clone self::$settings );
		}

		return self::$settings;
	}

	/**
	 * Save the global styles.
	 *
	 * @since 2.8.0
	 * @param array $settings The new global settings.
	 * @return object
	 */
	static public function save_settings( $settings = array() ) {
		$old_settings = self::get_settings( false );
		$settings     = FLBuilderModel::sanitize_global( $settings );
		$new_settings = (object) array_merge( (array) $old_settings, (array) $settings );

		// assign uid to global colors
		if ( ! empty( $new_settings->colors ) ) {
			foreach ( $new_settings->colors as $key => $color ) {
				if ( empty( $color ) ) {
					continue;
				}

				if ( empty( $color['uid'] ) ) {
					$new_settings->colors[ $key ]['uid'] = substr( md5( mt_rand() ), 0, 9 );
				}
			}
		}

		FLBuilderModel::delete_asset_cache_for_all_posts();
		self::$settings = null;

		FLBuilderUtils::update_option( '_fl_builder_styles', $new_settings, true );

		return self::get_settings( false );
	}

	/**
	 * Reset the global styles.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	static public function reset_settings() {
		FLBuilderUtils::update_option( '_fl_builder_styles', array(), true );
	}

	/**
	 * Generate global styles css ajax.
	 *
	 * @since 2.8.0
	 * @return string
	 */
	static public function generate_css_ajax( $settings ) {
		if ( empty( $settings ) ) {
			$settings = self::get_settings();
		} else {
			// make sure it is object.
			$settings = (object) $settings;

			// connect.
			$settings = FLThemeBuilderFieldConnections::connect_settings( $settings );
		}

		// return parsed value.
		return self::generate_css( $settings );
	}

	/**
	 * Generate global styles css.
	 *
	 * @since 2.8
	 * @param array $settings
	 * @return string
	 */
	static public function generate_css( $settings = array() ) {
		if ( empty( (array) $settings ) ) {
			$settings = self::get_settings();
		}

		// make sure it is object.
		$settings = (object) $settings;

		// css variables.
		FLBuilderCSS::rule( array(
			'selector' => ':root',
			'props'    => self::generate_css_vars( $settings ),
		) );

		// text.
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content:not(.fl-builder-empty)',
			'props'    => array(
				'color' => $settings->text_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content:not(.fl-builder-empty)',
			'settings'     => $settings,
			'setting_name' => 'text_typography',
		) );

		// h1
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content h1, .fl-builder-content h1 a, .fl-builder-content h1 span',
			'props'    => array(
				'color' => $settings->h1_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content h1',
			'settings'     => $settings,
			'setting_name' => 'h1_typography',
		) );

		// h2
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content h2, .fl-builder-content h2 a, .fl-builder-content h2 span',
			'props'    => array(
				'color' => $settings->h2_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content h2',
			'settings'     => $settings,
			'setting_name' => 'h2_typography',
		) );

		// h3
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content h3, .fl-builder-content h3 a, .fl-builder-content h3 span',
			'props'    => array(
				'color' => $settings->h3_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content h3',
			'settings'     => $settings,
			'setting_name' => 'h3_typography',
		) );

		// h4
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content h4, .fl-builder-content h4 a, .fl-builder-content h4 span',
			'props'    => array(
				'color' => $settings->h4_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content h4',
			'settings'     => $settings,
			'setting_name' => 'h4_typography',
		) );

		// h5
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content h5, .fl-builder-content h5 a, .fl-builder-content h5 span',
			'props'    => array(
				'color' => $settings->h5_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content h5',
			'settings'     => $settings,
			'setting_name' => 'h5_typography',
		) );

		// h6
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content h6, .fl-builder-content h6 a, .fl-builder-content h6 span',
			'props'    => array(
				'color' => $settings->h6_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content h6',
			'settings'     => $settings,
			'setting_name' => 'h6_typography',
		) );

		// a
		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content a:not(.fl-builder-submenu-link)',
			'props'    => array(
				'color' => $settings->link_color,
			),
		) );

		FLBuilderCSS::rule( array(
			'selector' => '.fl-builder-content a:not(.fl-builder-submenu-link):hover',
			'props'    => array(
				'color' => $settings->link_hover_color,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => '.fl-builder-content a:not(.fl-builder-submenu-link)',
			'settings'     => $settings,
			'setting_name' => 'link_typography',
		) );

		// button
		FLBuilderCSS::rule( array(
			'selector' => array(
				'.fl-builder-content button:not(.fl-menu-mobile-toggle)',
				'.fl-builder-content input[type=button]',
				'.fl-builder-content input[type=submit]',
				'.fl-builder-content a.fl-button',
				'.fl-builder-content button:not(.fl-menu-mobile-toggle) *',
				'.fl-builder-content input[type=button] *',
				'.fl-builder-content input[type=submit] *',
				'.fl-builder-content a.fl-button *',
				'.fl-builder-content button:visited',
				'.fl-builder-content input[type=button]:visited',
				'.fl-builder-content input[type=submit]:visited',
				'.fl-builder-content a.fl-button:visited',
				'.fl-builder-content button:visited *',
				'.fl-builder-content input[type=button]:visited *',
				'.fl-builder-content input[type=submit]:visited *',
				'.fl-builder-content a.fl-button:visited *',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle)',
				'.fl-page .fl-builder-content input[type=button]',
				'.fl-page .fl-builder-content input[type=submit]',
				'.fl-page .fl-builder-content a.fl-button',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle) *',
				'.fl-page .fl-builder-content input[type=button] *',
				'.fl-page .fl-builder-content input[type=submit] *',
				'.fl-page .fl-builder-content a.fl-button *',
				'.fl-page .fl-builder-content button:visited',
				'.fl-page .fl-builder-content input[type=button]:visited',
				'.fl-page .fl-builder-content input[type=submit]:visited',
				'.fl-page .fl-builder-content a.fl-button:visited',
				'.fl-page .fl-builder-content button:visited *',
				'.fl-page .fl-builder-content input[type=button]:visited *',
				'.fl-page .fl-builder-content input[type=submit]:visited *',
				'.fl-page .fl-builder-content a.fl-button:visited *',
			),
			'props'    => array(
				'color' => $settings->button_color,
			),
		) );

		FLBuilderCSS::rule( array(
			'selector' => array(
				'.fl-builder-content button:not(.fl-menu-mobile-toggle):hover',
				'.fl-builder-content input[type=button]:hover',
				'.fl-builder-content input[type=submit]:hover',
				'.fl-builder-content a.fl-button:hover',
				'.fl-builder-content button:not(.fl-menu-mobile-toggle):hover *',
				'.fl-builder-content input[type=button]:hover *',
				'.fl-builder-content input[type=submit]:hover *',
				'.fl-builder-content a.fl-button:hover *',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle):hover',
				'.fl-page .fl-builder-content input[type=button]:hover',
				'.fl-page .fl-builder-content input[type=submit]:hover',
				'.fl-page .fl-builder-content a.fl-button:hover',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle):hover *',
				'.fl-page .fl-builder-content input[type=button]:hover *',
				'.fl-page .fl-builder-content input[type=submit]:hover *',
				'.fl-page .fl-builder-content a.fl-button:hover *',
			),
			'props'    => array(
				'color' => $settings->button_hover_color,
			),
		) );

		FLBuilderCSS::rule( array(
			'selector' => array(
				'.fl-builder-content .fl-module-content:not(:has(.fl-inline-editor)) button:not(.fl-menu-mobile-toggle)',
				'.fl-builder-content input[type=button]',
				'.fl-builder-content input[type=submit]',
				'.fl-builder-content a.fl-button',
				'.fl-builder-content button:visited',
				'.fl-builder-content input[type=button]:visited',
				'.fl-builder-content input[type=submit]:visited',
				'.fl-builder-content a.fl-button:visited',
				'.fl-page .fl-builder-content .fl-module-content:not(:has(.fl-inline-editor)) button:not(.fl-menu-mobile-toggle)',
				'.fl-page .fl-builder-content input[type=button]',
				'.fl-page .fl-builder-content input[type=submit]',
				'.fl-page .fl-builder-content a.fl-button',
				'.fl-page .fl-builder-content button:visited',
				'.fl-page .fl-builder-content input[type=button]:visited',
				'.fl-page .fl-builder-content input[type=submit]:visited',
				'.fl-page .fl-builder-content a.fl-button:visited',
			),
			'props'    => array(
				'background-color' => $settings->button_background,
			),
		) );

		FLBuilderCSS::rule( array(
			'selector' => array(
				'.fl-builder-content .fl-module-content:not(:has(.fl-inline-editor)) button:not(.fl-menu-mobile-toggle):hover',
				'.fl-builder-content input[type=button]:hover',
				'.fl-builder-content input[type=submit]:hover',
				'.fl-builder-content a.fl-button:hover',
				'.fl-page .fl-builder-content .fl-module-content:not(:has(.fl-inline-editor)) button:not(.fl-menu-mobile-toggle):hover',
				'.fl-page .fl-builder-content input[type=button]:hover',
				'.fl-page .fl-builder-content input[type=submit]:hover',
				'.fl-page .fl-builder-content a.fl-button:hover',
			),
			'props'    => array(
				'background-color' => $settings->button_hover_background,
			),
		) );

		FLBuilderCSS::typography_field_rule( array(
			'selector'     => array(
				'.fl-builder-content button:not(.fl-menu-mobile-toggle)',
				'.fl-builder-content input[type=button]',
				'.fl-builder-content input[type=submit]',
				'.fl-builder-content a.fl-button',
				'.fl-builder-content button:visited',
				'.fl-builder-content input[type=button]:visited',
				'.fl-builder-content input[type=submit]:visited',
				'.fl-builder-content a.fl-button:visited',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle)',
				'.fl-page .fl-builder-content input[type=button]',
				'.fl-page .fl-builder-content input[type=submit]',
				'.fl-page .fl-builder-content a.fl-button',
				'.fl-page .fl-builder-content button:visited',
				'.fl-page .fl-builder-content input[type=button]:visited',
				'.fl-page .fl-builder-content input[type=submit]:visited',
				'.fl-page .fl-builder-content a.fl-button:visited',
			),
			'settings'     => $settings,
			'setting_name' => 'button_typography',
		) );

		FLBuilderCSS::border_field_rule( array(
			'selector'     => array(
				'.fl-builder-content button:not(.fl-menu-mobile-toggle)',
				'.fl-builder-content input[type=button]',
				'.fl-builder-content input[type=submit]',
				'.fl-builder-content a.fl-button',
				'.fl-builder-content button:visited',
				'.fl-builder-content input[type=button]:visited',
				'.fl-builder-content input[type=submit]:visited',
				'.fl-builder-content a.fl-button:visited',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle)',
				'.fl-page .fl-builder-content input[type=button]',
				'.fl-page .fl-builder-content input[type=submit]',
				'.fl-page .fl-builder-content a.fl-button',
				'.fl-page .fl-builder-content button:visited',
				'.fl-page .fl-builder-content input[type=button]:visited',
				'.fl-page .fl-builder-content input[type=submit]:visited',
				'.fl-page .fl-builder-content a.fl-button:visited',
			),
			'settings'     => $settings,
			'setting_name' => 'button_border',
		) );

		FLBuilderCSS::rule( array(
			'selector' => array(
				'.fl-builder-content button:not(.fl-menu-mobile-toggle):hover',
				'.fl-builder-content input[type=button]:hover',
				'.fl-builder-content input[type=submit]:hover',
				'.fl-builder-content a.fl-button:hover',
				'.fl-page .fl-builder-content button:not(.fl-menu-mobile-toggle):hover',
				'.fl-page .fl-builder-content input[type=button]:hover',
				'.fl-page .fl-builder-content input[type=submit]:hover',
				'.fl-page .fl-builder-content a.fl-button:hover',
			),
			'props'    => array(
				'border-color' => $settings->button_border_hover_color,
			),
		) );

		/**
		 * Fires before generating the global CSS.
		 *
		 * This action hook allows developers to execute custom code before the global CSS
		 * is generated.
		 */
		do_action( 'fl_builder_before_generate_global_css' );

		// create buffer.
		ob_start();

		// output css strings.
		FLBuilderCSS::render();

		// clean global css vars
		self::$css_vars = array();

		// Generate css strings for global styles.
		return ob_get_clean();
	}

	/**
	 * Generate global colors css only.
	 *
	 * @since 2.9
	 * @param array $settings
	 * @return string
	 */
	static public function generate_global_colors_css( $settings = [] ) {
		if ( empty( $settings ) ) {
			$settings = self::get_settings();
		}

		// make sure it is object.
		$settings = (object) $settings;

		// css variables.
		FLBuilderCSS::rule( array(
			'selector' => ':root',
			'props'    => self::generate_global_colors_css_vars( $settings ),
		) );

		// create buffer.
		ob_start();

		// output css strings.
		FLBuilderCSS::render();

		// clean global css vars
		self::$css_vars = array();

		// Generate css strings for global styles.
		return ob_get_clean();
	}

	/**
	 * Generate global colors variables.
	 *
	 * @since 2.9
	 * @param array $settings
	 * @return array
	 */
	static public function generate_global_colors_css_vars( $settings ) {
		if ( empty( $settings ) ) {
			return self::$css_vars;
		}

		// global colors
		if ( ! empty( $settings->colors ) ) {
			foreach ( $settings->colors as $color ) {
				$color = (object) $color;

				if ( empty( $color->label ) || empty( $color->color ) ) {
					continue;
				}

				$color->prefix = $settings->prefix;

				self::extract_color_var( $color, 'color', self::label_to_key( $color->label ) );
			}
		}

		return self::$css_vars;
	}

	/**
	 * Generate global styles variables.
	 *
	 * @since 2.8
	 * @param array $settings
	 * @return array
	 */
	static public function generate_css_vars( $settings ) {
		if ( empty( $settings ) ) {
			return self::$css_vars;
		}

		// global colors
		self::generate_global_colors_css_vars( $settings );

		// global styles
		self::extract_color_var( $settings, 'text_color', 'text-color' );
		self::extract_compound_vars( $settings, 'typography', 'text_typography', 'text' );

		self::extract_color_var( $settings, 'h1_color', 'h1-color' );
		self::extract_compound_vars( $settings, 'typography', 'h1_typography', 'h1' );

		self::extract_color_var( $settings, 'h2_color', 'h2-color' );
		self::extract_compound_vars( $settings, 'typography', 'h2_typography', 'h2' );

		self::extract_color_var( $settings, 'h3_color', 'h3-color' );
		self::extract_compound_vars( $settings, 'typography', 'h3_typography', 'h3' );

		self::extract_color_var( $settings, 'h4_color', 'h4-color' );
		self::extract_compound_vars( $settings, 'typography', 'h4_typography', 'h4' );

		self::extract_color_var( $settings, 'h5_color', 'h5-color' );
		self::extract_compound_vars( $settings, 'typography', 'h5_typography', 'h5' );

		self::extract_color_var( $settings, 'h6_color', 'h6-color' );
		self::extract_compound_vars( $settings, 'typography', 'h6_typography', 'h6' );

		self::extract_color_var( $settings, 'link_color', 'link-color' );
		self::extract_color_var( $settings, 'link_hover_color', 'link-hover-color' );
		self::extract_compound_vars( $settings, 'typography', 'link_typography', 'link' );

		self::extract_color_var( $settings, 'button_color', 'button-color' );
		self::extract_color_var( $settings, 'button_hover_color', 'button-hover-color' );
		self::extract_color_var( $settings, 'button_background', 'button-background' );
		self::extract_color_var( $settings, 'button_hover_background', 'button-hover-background' );
		self::extract_compound_vars( $settings, 'typography', 'button_typography', 'button' );
		self::extract_compound_vars( $settings, 'border', 'button_border', 'button' );

		return self::$css_vars;
	}

	/**
	 * Convert label to key.
	 *
	 * @since 2.8
	 * @param array $label
	 * @return string
	 */
	static public function label_to_key( $label ) {
		if ( empty( $label ) ) {
			return '';
		}

		$label = str_replace( array( '_', ' ' ), '-', strtolower( trim( $label ) ) );
		$label = preg_replace( '/[^A-Za-z0-9\-]/', '', $label );

		return $label;
	}

	/**
	 * Extract variable from color setting.
	 *
	 * @since 2.8
	 * @param array $settings
	 * @param array $setting_key
	 * @param array $var_key
	 * @return void
	 */
	static public function extract_color_var( $settings, $setting_key, $var_key ) {
		if ( empty( (array) $settings ) || empty( $setting_key ) || empty( $var_key ) ) {
			return;
		}

		$prefix = ! empty( $settings->prefix ) ? self::label_to_key( $settings->prefix ) : 'fl-global';
		$prefix = "--{$prefix}-";

		self::$css_vars[ $prefix . $var_key ] = FLBuilderColor::hex_or_rgb( $settings->{$setting_key} );
	}

	/**
	 * Extract variable from compound setting.
	 *
	 * @since 2.8
	 * @param array $settings
	 * @param array $type
	 * @param array $setting_key
	 * @param array $var_key
	 * @return void
	 */
	static public function extract_compound_vars( $settings, $type, $setting_key, $var_key ) {
		if ( empty( $settings ) || empty( $type ) || empty( $setting_key ) || empty( $var_key ) ) {
			return;
		}

		$global_settings = FLBuilderModel::get_global_settings();
		$breakpoints     = array( '', 'large', 'medium', 'responsive' );
		$prefix          = ! empty( $settings->prefix ) ? sanitize_key( trim( $settings->prefix ) ) : 'fl-global';
		$prefix          = "--{$prefix}-";

		foreach ( $breakpoints as $breakpoint ) {
			if ( ! $global_settings->responsive_enabled ) {
				continue;
			}

			$suffix   = empty( $breakpoint ) ? '' : "-{$breakpoint}";
			$name     = empty( $breakpoint ) ? $setting_key : "{$setting_key}_{$breakpoint}";
			$setting  = isset( $settings->{$name} ) ? $settings->{$name} : null;
			$callback = "{$type}_field_props";
			$props    = array();

			if ( is_object( $setting ) ) {
				$setting = (array) $setting;

				foreach ( $setting as $key => $value ) {
					if ( is_object( $value ) ) {
						$setting[ $key ] = (array) $value;
					}
				}
			}

			if ( ! is_array( $setting ) ) {
				continue;
			}

			if ( method_exists( 'FLBuilderCSS', $callback ) ) {
				$props = call_user_func( array( 'FLBuilderCSS', $callback ), $setting );
			}

			if ( ! empty( $props ) ) {
				foreach ( $props as $prop_key => $prop_val ) {
					if ( empty( $prop_val ) ) {
						continue;
					}

					self::$css_vars[ $prefix . "{$var_key}-" . $prop_key . $suffix ] = $prop_val;
				}

				unset( $props );
			}
		}
	}
}

FLBuilderGlobalStyles::init();
