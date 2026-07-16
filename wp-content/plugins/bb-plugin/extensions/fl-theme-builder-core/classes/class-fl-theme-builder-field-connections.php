<?php

/**
 * Handles the base logic for connecting fields in the
 * builder UI to dynamic data such as the post title.
 *
 * @since 1.0
 */
final class FLThemeBuilderFieldConnections {

	/**
	 * Cache data for field connections menus.
	 *
	 * @since 1.0
	 * @access private
	 * @var array $menu_data
	 */
	static private $menu_data = array();

	/**
	 * An array of cached settings that have been connected.
	 *
	 * @since 1.0
	 * @access private
	 * @var array $connected_settings
	 */
	static private $connected_settings = array();

	/**
	 * An array of cached page data object keys.
	 *
	 * @since 1.0
	 * @access private
	 * @var array $page_data_object_keys
	 */
	static private $page_data_object_keys = array();

	/**
	 * Whether the connection has rendered inside the post-grid module loop.
	 *
	 * @since 1.3.2
	 * @access public
	 * @var boolean $in_post_grid_loop
	 */
	static public $in_post_grid_loop = null;

	/**
	 * An array of compound fields that support field connections.
	 *
	 * @since 2.8
	 * @access private
	 * @var array $supported_compound_fields
	 */
	static private $supported_compound_fields = array(
		'border',
		'gradient',
		'typography',
		'background',
	);

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0
	 * @return void
	 */
	static public function init() {
		// Actions
		add_action( 'wp', __CLASS__ . '::connect_all_layout_settings' );
		add_action( 'wp', __CLASS__ . '::hook_scripts' );
		add_action( 'fl_builder_before_control', __CLASS__ . '::render_connection', 10, 4 );
		add_action( 'fl_builder_before_render_ajax_layout', __CLASS__ . '::connect_all_layout_settings' );
		add_action( 'fl_builder_posts_module_before_posts', __CLASS__ . '::posts_grid_before_posts', 10 );
		add_action( 'fl_builder_posts_module_after_posts', __CLASS__ . '::posts_grid_after_posts', 10 );
		add_action( 'fl_builder_module_data_repeater_before_posts', __CLASS__ . '::posts_grid_before_posts', 10 );
		add_action( 'fl_builder_module_data_repeater_after_posts', __CLASS__ . '::posts_grid_after_posts', 10 );

		// Filters
		add_filter( 'fl_builder_node_settings', __CLASS__ . '::connect_node_settings', 10, 2 );
		add_filter( 'fl_builder_before_render_shortcodes', __CLASS__ . '::parse_shortcodes' );

		// Shortcodes
		add_shortcode( 'wpbb', __CLASS__ . '::parse_shortcode' );
		add_shortcode( 'wpbb-if', __CLASS__ . '::parse_conditional_shortcode' );

		// Frontend AJAX
		FLBuilderAJAX::add_action( 'render_connection_settings', __CLASS__ . '::ajax_render_settings_form', array( 'object', 'property', 'type', 'settings' ) );
	}

	/**
	 * Hook Themer scripts if the builder is active.
	 *
	 * @since 2.9
	 * @return void
	 */
	static public function hook_scripts() {
		if ( FLBuilderModel::is_builder_active() ) {
			add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_scripts' );
			add_action( 'wp_footer', __CLASS__ . '::js_templates' );
		}
	}

	/**
	 * Enqueues styles and scripts for field connections.
	 *
	 * @since 1.0
	 * @return void
	 */
	static public function enqueue_scripts() {
		$slug = 'fl-theme-builder-field-connections';

		wp_enqueue_style( $slug, FL_THEME_BUILDER_CORE_URL . 'css/' . $slug . '.css', array(), FL_BUILDER_VERSION );
		wp_enqueue_style( 'tether', FL_THEME_BUILDER_CORE_URL . 'css/tether.min.css', array(), FL_BUILDER_VERSION );

		wp_enqueue_script( $slug, FL_THEME_BUILDER_CORE_URL . 'js/' . $slug . '.js', array( 'jquery' ), FL_BUILDER_VERSION );
		wp_enqueue_script( 'tether', FL_THEME_BUILDER_CORE_URL . 'js/tether.min.js', array( 'jquery' ), FL_BUILDER_VERSION );
	}

	/**
	 * Includes the JS templates used in the builder interface.
	 *
	 * @since 1.0
	 * @return void
	 */
	static public function js_templates() {
		include FL_THEME_BUILDER_CORE_DIR . 'includes/field-connection-js-templates.php';
	}

	/**
	 * Renders the settings form for a connection
	 * via an AJAX request.
	 *
	 * @since 1.0
	 * @param string $object
	 * @param string $property
	 * @param string $type
	 * @param object $settings
	 * @return void
	 */
	static public function ajax_render_settings_form( $object, $property, $type, $settings ) {
		remove_action( 'fl_builder_before_control', __CLASS__ . '::render_connection', 10, 4 );

		$property = FLPageData::get_property( $object, $property );
		$form     = FLBuilder::render_settings_form( $type, $settings );

		if ( isset( $property['form']['css'] ) ) {
			$form['html'] .= '<link rel="stylesheet" href="' . $property['form']['css'] . '" />';
		}
		if ( isset( $property['form']['js'] ) ) {
			$form['html'] .= '<script src="' . $property['form']['js'] . '"></script>';
		}

		add_action( 'fl_builder_before_control', __CLASS__ . '::render_connection', 10, 4 );

		return $form;
	}

	/**
	 * Renders the connection for a field's control.
	 *
	 * @since 1.0
	 * @param string $name
	 * @param string $value
	 * @param array  $field
	 * @param object  $settings
	 * @return void
	 */
	static public function render_connection( $name, $value, $field, $settings ) {
		global $post;

		if ( ! isset( $field['connections'] ) || ! $field['connections'] ) {
			self::render_compound_field_connection( $name, $field, $settings );
			return;
		}

		$properties = FLPageData::get_properties();
		$objects    = self::get_page_data_object_keys( $post->ID );
		$connection = false;
		$form       = false;
		$menu_data  = self::get_menu_data( $objects, $field['connections'] );

		if ( isset( $settings->connections ) ) {

			$settings->connections = (array) $settings->connections;

			if ( isset( $settings->connections[ $name ] ) ) {

				if ( is_string( $settings->connections[ $name ] ) ) {
					$settings->connections[ $name ] = json_decode( $settings->connections[ $name ] );
				}
				if ( is_array( $settings->connections[ $name ] ) ) {
					$settings->connections[ $name ] = (object) $settings->connections[ $name ];
				}
				if ( is_object( $settings->connections[ $name ] ) && in_array( $settings->connections[ $name ]->object, $objects ) ) {
					$connection = $settings->connections[ $name ];
				}
			}
		}

		if ( $connection ) {

			if ( ! isset( $properties[ $connection->object ][ $connection->property ] ) ) {
				$connection = false;
			} elseif ( ! self::property_supports_post_type( $properties[ $connection->object ][ $connection->property ] ) ) {
				$connection = false;
			} else {
				$property = FLPageData::get_property( $connection->object, $connection->property );
				$form     = $property['form'] ? $property['form']['id'] : false;
			}
		}

		include FL_THEME_BUILDER_CORE_DIR . 'includes/field-connection.php';
	}

	/**
	 * Renders the connection for a compound field.
	 *
	 * @since 2.8
	 * @param string $name
	 * @param array  $field
	 * @param object  $settings
	 * @return void
	 */
	static public function render_compound_field_connection( $name, $field, $settings ) {
		if ( ! in_array( $field['type'], self::$supported_compound_fields ) ) {
			return;
		}

		$sizes       = [ 'default', 'large', 'medium', 'responsive' ];
		$connections = [];

		foreach ( $sizes as $size ) {
			$key        = 'default' === $size ? $name : $name . '_' . $size;
			$connection = null;

			if ( isset( $settings->connections ) ) {
				$settings->connections = (array) $settings->connections;

				if ( isset( $settings->connections[ $key ] ) && ! empty( $settings->connections[ $key ] ) ) {
					$connection          = $settings->connections[ $key ];
					$connections[ $key ] = json_encode( $connection );
				}
			}

			if ( ! $connection ) {
				$connections[ $key ] = '';
			}

			if ( ! isset( $field['responsive'] ) || ! $field['responsive'] ) {
				break;
			}
		}

		include FL_THEME_BUILDER_CORE_DIR . 'includes/field-connection-compound.php';
	}

	/**
	 * Renders the label for a connection.
	 *
	 * @since 1.0
	 * @param object $connection
	 * @return void
	 */
	static public function render_label( $connection = false ) {
		if ( ! $connection ) {
			return;
		}

		$properties = FLPageData::get_properties();

		echo $properties[ $connection->object ][ $connection->property ]['label'];
	}

	/**
	 * Returns the menu data for a field connections menu.
	 *
	 * @since 1.0
	 * @param array $objects An array of objects to show connections for.
	 * @param array $connections The connections that are supported.
	 * @return array
	 */
	static public function get_menu_data( $objects = array( 'site' ), $connections = array() ) {
		global $post;

		$cache_key = implode( '_', $objects ) . '_' . implode( '_', $connections );

		if ( isset( self::$menu_data[ $cache_key ] ) ) {
			return self::$menu_data[ $cache_key ];
		}

		$groups     = FLPageData::get_groups();
		$properties = FLPageData::get_properties();
		$menu       = array();

		// Add groups to the menu data.
		foreach ( $groups as $group_key => $group ) {
			if ( ! $group['render'] ) {
				continue;
			}
			$menu[ $group_key ] = array(
				'label'      => $group['label'],
				'properties' => array(
					'archive' => array(),
					'post'    => array(),
					'site'    => array(),
				),
			);
		}

		// Add properties to the menu data.
		foreach ( $objects as $object ) {

			foreach ( $properties[ $object ] as $key => $data ) {

				if ( ! isset( $menu[ $data['group'] ] ) ) {
					continue;
				} elseif ( ! self::property_supports_post_type( $data ) ) {
					continue;
				} elseif ( is_array( $data['type'] ) ) {
					if ( 0 === count( array_intersect( $data['type'], $connections ) ) ) {
						continue;
					}
				} elseif ( 'all' != $data['type'] && ! in_array( $data['type'], $connections ) ) {
					continue;
				}

				$menu[ $data['group'] ]['properties'][ $data['object'] ][ $key ] = $data;
			}
		}

		// Remove any empty groups from the menu data.
		foreach ( $menu as $group_key => $group ) {

			$no_archive = 0 === count( $group['properties']['archive'] ); // @codingStandardsIgnoreLine
			$no_post    = 0 === count( $group['properties']['post'] ); // @codingStandardsIgnoreLine
			$no_site    = 0 === count( $group['properties']['site'] ); // @codingStandardsIgnoreLine

			if ( $no_archive && $no_post && $no_site ) {
				unset( $menu[ $group_key ] );
			}
		}

		self::$menu_data[ $cache_key ] = $menu;

		return $menu;
	}

	/**
	 * Checks to see if a post property supports
	 * the current post type.
	 *
	 * @since 1.0
	 * @param array $property
	 * @return bool
	 */
	static public function property_supports_post_type( $property ) {
		global $post;

		if ( 'post' == $property['object'] && 'all' != $property['post_type'] ) {
			if ( is_array( $property['post_type'] ) && ! in_array( $post->post_type, $property['post_type'] ) ) {
				return false;
			} elseif ( $post->post_type != $property['post_type'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns an array of page data object keys.
	 *
	 * @since 1.0
	 * @param int $post_id
	 * @return array
	 */
	static public function get_page_data_object_keys( $post_id ) {
		if ( isset( self::$page_data_object_keys[ $post_id ] ) ) {
			return self::$page_data_object_keys[ $post_id ];
		}

		$layout_type = get_post_meta( $post_id, '_fl_theme_layout_type', true );

		if ( 'singular' == $layout_type ) {
			$keys = array( 'post', 'site' );
		} else {
			$keys = array( 'archive', 'post', 'site' );
		}

		self::$page_data_object_keys[ $post_id ] = $keys;

		return $keys;
	}

	/**
	 * Connects and caches all layout node settings before the page
	 * renders for the first post in the query. This ensures that
	 * settings are connected while in the loop as not all WordPress
	 * functions for posts work outside the loop. We also have to go
	 * through the entire loop as not doing so appears to cause issues,
	 * most notably with Yoast SEO (see issue #13).
	 *
	 * @since 1.0
	 * @return void
	 */
	static public function connect_all_layout_settings() {
		if ( ! self::is_connecting_allowed() ) {
			return;
		}

		$layout_ids  = FLThemeBuilderLayoutData::get_current_page_layout_ids();
		$node_status = FLBuilderModel::get_node_status();
		$connected   = false;

		if ( empty( $layout_ids ) ) {
			return;
		}

		FLThemeBuilderRulesLocation::set_preview_query();

		while ( have_posts() ) {

			the_post();

			if ( ! $connected ) {

				foreach ( $layout_ids as $layout_id ) {

					$data = FLBuilderModel::get_layout_data( $node_status, $layout_id );

					foreach ( $data as $node ) {
						FLBuilderModel::get_node_settings( $node );
					}
				}
			}

			$connected = true;
		}

		rewind_posts();

		FLThemeBuilderRulesLocation::reset_preview_query();
	}

	/**
	 * Sets flag that determine we are inside the post grid loop.
	 *
	 * @since 1.3.2
	 * @return void
	 */
	static public function posts_grid_before_posts() {
		self::$in_post_grid_loop = true;
	}

	/**
	 * Resets flag for post grid loop.
	 *
	 * @since 1.3.2
	 * @return void
	 */
	static public function posts_grid_after_posts() {
		self::$in_post_grid_loop = false;
	}

	/**
	 * Connects any settings that have a field connection for
	 * a node. Settings aren't connected if a builder settings
	 * form is rendering or saving since we don't want connected
	 * values being saved or displayed in settings forms.
	 *
	 * @since 1.0
	 * @param object $settings The settings object for a node.
	 * @param object $node The node object.
	 * @return object
	 */
	static public function connect_node_settings( $settings, $node ) {
		global $post, $wp_the_query, $fl_term_query, $fl_term;
		$repeater = array();
		$nested   = array();

		// Get the connection cache key.
		if ( is_object( $wp_the_query->post ) && 'fl-theme-layout' === $wp_the_query->post->post_type ) {
			$cache_key = $node->node;
		} else {
			$cache_key = $post && isset( $post->ID ) ? $node->node . '_' . $post->ID : $node->node;
		}

		// check for bb loop
		if ( isset( $fl_term_query ) && isset( $fl_term ) && ! self::$in_post_grid_loop ) {
			$cache_key = $node->node . '_term_' . $fl_term->term_id;
		} elseif ( isset( self::$in_post_grid_loop ) ) {
			if ( self::$in_post_grid_loop && $post && isset( $post->ID ) ) {
				$cache_key = $node->node . '_' . $post->ID;
			}
		} elseif ( function_exists( 'acf_get_loop' ) && acf_get_loop( 'active' ) ) {
			$cache_key = $node->node . '_' . get_row_index();
		}

		/**
		 * @since 1.3.1
		 * @see fl_themer_builder_connect_node_settings_cache_key
		 */
		$cache_key = apply_filters( 'fl_themer_builder_connect_node_settings_cache_key', $cache_key, $settings, $node );

		// Gather any repeater or nested settings.
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) && count( $value ) && isset( $value[0]->connections ) ) {
				$repeater[] = $key;
			} elseif ( is_object( $value ) && isset( $value->connections ) ) {
				$nested[] = $key;
			}
		}
				// Return if we don't have connections.
		if ( ! isset( $settings->connections ) && empty( $repeater ) && empty( $nested ) ) {
			return $settings;
		}
				// Return if connecting isn't allowed right now.
		if ( ! self::is_connecting_allowed() ) {
			return $settings;
		}
				// Return cached connections?
		if ( isset( self::$connected_settings[ $cache_key ] ) ) {
			return self::$connected_settings[ $cache_key ];
		}

		// Connect the main settings object.
		$settings = self::connect_settings( $settings );

		// Connect any repeater settings.
		foreach ( $repeater as $key ) {
			for ( $i = 0; $i < count( $settings->$key ); $i++ ) {
				$settings->{ $key }[ $i ] = self::connect_settings( $settings->{ $key }[ $i ] );
			}
		}

		// Connect any nested settings.
		foreach ( $nested as $key ) {
			$settings->{ $key } = self::connect_settings( $settings->{ $key } );
		}

		// Cache the connected settings.
		self::$connected_settings[ $cache_key ] = $settings;

		return $settings;
	}

	/**
	 * Loops through the settings for a settings object and
	 * connects any settings that have a field connection.
	 *
	 * @since 1.0
	 * @param object $settings The settings object for a node.
	 * @return object
	 */
	static public function connect_settings( $settings ) {
		global $post;

		// Return if we don't have connections.
		if ( ! isset( $settings->connections ) ) {
			return $settings;
		}

		// Loop through the settings and connect them.
		foreach ( $settings->connections as $key => $data ) {

			if ( is_string( $data ) ) {
				$data = json_decode( $data );
			}

			if ( ! empty( $data ) && ( is_object( $data ) || is_array( $data ) ) ) {

				$data = (object) $data;

				// Connect a flat connection.
				if ( isset( $data->property ) ) {
					if ( isset( $data->output ) && 'css' === $data->output ) {
						self::connect_css_var( $data );
					} else {
						$settings = self::connect_setting( $settings, $key, $data );
					}
					continue;
				}

				// Connect a compound connection.
				$result = self::get_compound_connection_data( $key, $data );

				foreach ( $result as $k => $v ) {
					if ( isset( $v->output ) && 'css' === $v->output ) {
						self::connect_css_var( $v );
					} else {
						$settings = self::connect_setting( $settings, $k, $v );
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Gets the object paths and data for a compound connection.
	 *
	 * @since 2.8
	 * @param object $key The base key for the object path.
	 * @param object $data The data for the compound connection.
	 * @return array
	 */
	static public function get_compound_connection_data( $key, $data ) {
		$result = [];

		if ( empty( $data ) ) {
			return $result;
		}

		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$v = (object) $v;
			}

			$path = "$key.$k";

			if ( isset( $v->property ) ) {
				$result[ $path ] = $v;
			} else {
				$nested = self::get_compound_connection_data( $path, $v );
				$result = array_merge( $result, $nested );
			}
		}

		return $result;
	}

	/**
	 * Connects a single setting to a field connection.
	 *
	 * @since 2.8
	 * @param object $settings The settings object for a node.
	 * @param object $key The connection key.
	 * @param object $data The connection data.
	 * @return object
	 */
	static public function connect_setting( $settings, $key, $data ) {
		global $post;

		$property       = FLPageData::get_property( $data->object, $data->property );
		$data->settings = isset( $data->settings ) ? (object) $data->settings : null;
		$value          = '';

		if ( ! $property ) {
			return $settings;
		} elseif ( isset( $property['placeholder'] ) && is_object( $post ) && 'fl-theme-layout' == $post->post_type ) {
			$value = $property['placeholder'];
		} else {
			$value = FLPageData::get_value( $data->object, $data->property, $data->settings );
		}

		if ( 'background' === $data->field ) {
			$settings = self::set_background_setting( $settings, $key, $value );
		} elseif ( 'photo' === $data->field ) {
			$settings = self::set_photo_setting( $settings, $key, $value );
		} elseif ( strstr( $key, '.' ) ) {
			$settings = self::set_compound_setting( $settings, $key, $value );
		} else {
			$settings->{ $key } = $value;
		}

		return $settings;
	}

	/**
	 * Sets the values for photo connections.
	 *
	 * @since 2.9
	 * @param object $settings The settings object for a node.
	 * @param object $key The setting key.
	 * @param object $data The value to set.
	 * @return object
	 */
	static public function set_photo_setting( $settings, $key, $value ) {
		$settings->{ $key } = $value;

		if ( is_array( $settings->{ $key } ) ) {
			$settings->{ $key . '_src' } = $settings->{ $key }['url'];
			$settings->{ $key }          = $settings->{ $key }['id'];
		} else {
			$settings->{ $key . '_src' } = $settings->{ $key };
			$settings->{ $key }          = -1;
		}

		return $settings;
	}

	/**
	 * Sets a single layer connection for background fields.
	 *
	 * @since 2.9
	 * @param object $settings The settings object for a node.
	 * @param object $key The object path to the setting key.
	 * @param object $data The value to set.
	 * @return object
	 */
	static public function set_background_setting( $settings, $key, $value ) {
		$parts    = explode( '.layer_', $key );
		$base_key = $parts[0];
		$layer_id = $parts[1];

		if ( ! isset( $settings->{ $base_key } ) ) {
			return $settings;
		} elseif ( ! is_array( $settings->{ $base_key }['layers'] ) ) {
			return $settings;
		}

		$setting = $settings->{ $base_key };
		$layers  = $setting['layers'];
		$css     = $setting['css'];

		foreach ( $layers as $layer_key => $layer ) {

			// Find the layer to connect to this value
			if ( (string) $layer->id === (string) $layer_id ) {

				// Handle color layer connections
				if ( 'color' === $layer->type ) {
					$current_color = $layer->state->color;

					if ( $current_color !== $value ) {
						$settings->{ $base_key }['layers'][ $layer_key ]->state->color = $value;
						$settings->{ $base_key }['css']                                = str_replace( $current_color, $value, $css );
					}
				}

				break;
			}
		}

		return $settings;
	}

	/**
	 * Sets a single setting for a compound connection.
	 *
	 * @since 2.8
	 * @param object $settings The settings object for a node.
	 * @param object $path The object path to the setting.
	 * @param object $data The value to set.
	 * @return object
	 */
	static public function set_compound_setting( $settings, $path, $value ) {
		$parts   = explode( '.', $path );
		$segment =& $settings;

		foreach ( $parts as $part ) {
			if ( is_array( $segment ) ) {
				if ( ! isset( $segment[ $part ] ) ) {
					$segment[ $part ] = [];
				}
				$segment =& $segment[ $part ];
			} elseif ( is_object( $segment ) ) {
				if ( ! isset( $segment->{ $part } ) ) {
					$segment->{ $part } = new \stdClass();
				}

				$segment =& $segment->{ $part };
			} else {
				// Invalid path, points to child of scalar (throw exception?)
				return $settings;
			}
		}
		$segment = $value;
		return $settings;
	}

	/**
	 * Registers a CSS var for a connected field.
	 *
	 * @since 2.9
	 * @param object $data
	 * @return void
	 */
	static public function connect_css_var( $data ) {
		$property = FLPageData::get_property( $data->object, $data->property );

		if ( ! $property ) {
			return;
		}

		// Don't output global color vars as they are already on the page.
		$is_global_color = str_starts_with( $property['key'], 'global_color_' );
		$is_theme_color  = str_starts_with( $property['key'], 'theme_color_' );

		if ( $is_global_color || $is_theme_color ) {
			return;
		}

		// Get the CSS var key and value.
		$key   = self::get_css_var_key( $data );
		$value = self::get_css_var_value( $data, $property );

		// Register the var.
		FLBuilderCSSVars::register( $key, $value );
	}

	/**
	 * Builds the key for a CSS var connection.
	 *
	 * @since 2.9
	 * @param object $data
	 * @return string
	 */
	static public function get_css_var_key( $data ) {
		$object   = $data->object;
		$property = $data->property;
		$suffix   = '';

		// Handle custom field keys
		if ( $data->settings && is_object( $data->settings ) ) {
			if ( 'custom_field' === $property ) {
				$suffix = "-{$data->settings->key}";
			} elseif ( str_starts_with( $property, 'acf' ) ) {
				$suffix = "-{$data->settings->name}";
			}
		}

		return str_replace( '_', '-', sanitize_key( "--fl-$object-$property$suffix" ) );
	}

	/**
	 * Retrieves the value for a CSS var connection.
	 *
	 * @since 2.9
	 * @param object $data
	 * @param array $property
	 * @return string
	 */
	static public function get_css_var_value( $data, $property ) {
		$value = FLPageData::get_value( $data->object, $data->property, $data->settings );

		// Handle photo connections.
		if ( 'photo' === $property['type'] ) {
			if ( is_array( $value ) ) {
				$value = $value['url'];
			}
			$value = "url($value)";
		}

		return $value;
	}

	/**
	 * Parse all "wpbb" shortcodes here instead of relying on do_shortcode
	 * since as of WordPress 4.2.3 that doesn't allow you to put shortcodes
	 * in HTML attributes or styles.
	 *
	 * @since 1.0
	 * @param string $content
	 * @return string
	 */
	static public function parse_shortcodes( $content, $tags = null ) {
		$tags    = $tags ? $tags : array( 'wpbb' );
		$pattern = get_shortcode_regex( $tags );
		$content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );
		return $content;
	}

	/**
	 * Connects a field connection through a shortcode.
	 *
	 * @since 1.0
	 * @param array $attrs
	 * @return string
	 */
	static public function parse_shortcode( $attrs ) {
		global $post;

		if ( ! isset( $attrs ) || ! isset( $attrs[0] ) ) {
			return;
		}

		$type     = explode( ':', $attrs[0] );
		$settings = null;

		if ( count( $type ) < 2 ) {
			return '';
		}

		if ( count( $attrs ) > 1 ) {
			unset( $attrs[0] );
			$settings = (object) $attrs;
		}

		$property = FLPageData::get_property( $type[0], $type[1] );

		if ( ! $property ) {
			return '';
		} elseif ( isset( $property['placeholder'] ) && is_object( $post ) && 'fl-theme-layout' == $post->post_type ) {
			return $property['placeholder'];
		}

		$content = FLPageData::get_value( $type[0], $type[1], $settings );

		// if we have content then return it.
		if ( $content ) {
			return $content;
		}

		// do we have any defaults passed?
		if ( isset( $attrs['wpbb_default'] ) ) {
			return do_shortcode( '[' . $attrs['wpbb_default'] . ']' );
		} elseif ( isset( $attrs['default'] ) ) {
			return $attrs['default'];
		}
		return '';
	}

	/**
	 * Parses conditional wpbb-if shortcodes.
	 *
	 * @since 1.0
	 * @param array  $attrs
	 * @param string $content
	 * @return string
	 */
	static public function parse_conditional_shortcode( $attrs, $content = '' ) {
		if ( ! isset( $attrs ) || ! isset( $attrs[0] ) ) {
			return __( 'Incorrect wpbb-if shortcode attributes.', 'fl-builder' );
		}

		$parts = explode( ':', $attrs[0] );

		if ( count( $parts ) < 2 ) {
			return __( 'Incorrect wpbb-if shortcode attributes.', 'fl-builder' );
		}

		$else     = false;
		$not      = 0 === strpos( $parts[0], '!' ); // @codingStandardsIgnoreLine
		$attrs[0] = str_replace( '!', '', $attrs[0] );
		$value    = self::parse_shortcode( $attrs );
		if ( false !== strpos( $content, '[wpbb-else]' ) ) {
			$else    = substr( $content, strpos( $content, '[wpbb-else]' ) );
			$content = str_replace( $else, '', $content );
		}

		if ( $not && empty( $value ) ) {
			return do_shortcode( $content );
		} elseif ( ! $not && $value ) {
			return do_shortcode( $content );
		}
		if ( $else ) {
			return do_shortcode( str_replace( '[wpbb-else]', '', $else ) );
		}
		return '';
	}

	/**
	 * Checks to see if returning connected settings is
	 * currently allowed or not.
	 *
	 * @since 1.0
	 * @return bool
	 */
	static public function is_connecting_allowed() {
		if ( FLBuilderModuleBlocks::is_block_editor() ) {
			return true;
		} elseif ( defined( 'DOING_AJAX' ) ) {

			if ( FLBuilderModel::is_builder_active() ) {

				$action = 'fl_builder_before_render_ajax_layout';

				if ( doing_action( $action ) || did_action( $action ) ) {
					return true;
				} else {
					return false;
				}
			}
		}

		return ! is_admin();
	}

	/**
	 * General compare function used in shortcodess
	 */
	static public function general_compare( $settings, $value ) {

		if ( ! isset( $settings->exp ) ) {
			return $value;
		}

		$meta = is_string( $value ) ? untrailingslashit( $value ) : $value;

		$expression = $settings->exp;

		$compare = untrailingslashit( $settings->value );

		switch ( $expression ) {
			case 'less':
				return ( intval( $meta ) < intval( $compare ) ) ? $meta : '';
				break;

			case 'lessequals':
				return ( intval( $meta ) <= intval( $compare ) ) ? $meta : '';
				break;

			case 'greater':
				return ( intval( $meta ) > intval( $compare ) ) ? $meta : '';
				break;

			case 'greaterequals':
				return ( intval( $meta ) >= intval( $compare ) ) ? $meta : '';
				break;

			case 'equals':
				return ( $meta === $compare ) ? $meta : '';
				break;

			case 'notequals':
				return ( $meta !== $compare ) ? $meta : '';
				break;

			case 'contains':
				if ( is_string( $meta ) ) {
					return strstr( $meta, $compare );
				}
				if ( is_array( $meta ) ) {
					return in_array( $compare, $meta );
				}
				break;

			default:
				break;
		}
		return $meta;
	}
}

FLThemeBuilderFieldConnections::init();
