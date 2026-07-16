<?php

/**
 * @class FLTabsModule
 */
class FLTabsModule extends FLBuilderModule {

	/**
	 * @method __construct
	 */
	public function __construct() {
		parent::__construct(array(
			'name'            => __( 'Tabs', 'fl-builder' ),
			'description'     => __( 'Display a collection of tabbed content.', 'fl-builder' ),
			'category'        => __( 'Layout', 'fl-builder' ),
			'partial_refresh' => true,
			'icon'            => 'layout.svg',
			'block_editor'    => true,
		));

		$this->add_css( 'font-awesome-5' );
	}

	/**
	 * Ensure backwards compatibility with old settings.
	 *
	 * @param object $settings A module settings object.
	 * @param object $helper A settings compatibility helper.
	 * @return object
	 */
	public function filter_settings( $settings, $helper ) {
		// exclude current post
		$settings->exclude_self = 'yes';

		if ( ! isset( $settings->content_type ) ) {
			$settings->content_type = 'post_content';
		}

		if ( ! isset( $settings->more_link ) ) {
			$settings->more_link = 'hide';
		}

		return $settings;
	}

	/**
	 * @method render_content
	 */
	public function render_content( $post_id ) {
		if ( FLBuilderModel::is_builder_enabled( $post_id ) ) {

			// Enqueue styles and scripts for the post.
			FLBuilder::enqueue_layout_styles_scripts_by_id( $post_id );

			// Print the styles if we are outside of the head tag.
			if ( did_action( 'wp_enqueue_scripts' ) && ! doing_filter( 'wp_enqueue_scripts' ) ) {
				wp_print_styles();
			}

			// Render the builder content.
			FLBuilder::render_content_by_id( $post_id );
		} else {
			// Render the WP editor content if the builder isn't enabled.
			echo apply_filters( 'the_content', get_the_content( null, false, $post_id ) );
		}
	}

	/**
	 * @method render_excerpt
	 */
	public function render_excerpt( $post_id ) {
		add_filter( 'excerpt_length', array( $this, 'set_custom_excerpt_length' ), 9999 );
		add_filter( 'excerpt_more', array( $this, 'set_custom_excerpt_more' ), 9999 );
		echo '<p>' . get_the_excerpt( $post_id ) . '</p>';
		remove_filter( 'excerpt_more', array( $this, 'set_custom_excerpt_more' ), 9999 );
		remove_filter( 'excerpt_length', array( $this, 'set_custom_excerpt_length' ), 9999 );
	}

	/**
	 * @method render_more_link
	 */
	public function render_more_link( $post_id, $more_link_text = '' ) {

		if ( empty( $more_link_text ) ) {
			return;
		}

		$html   = array();
		$html[] = '<div><a class="fl-tabs-post-more-link"';
		$html[] = 'href="' . esc_url( get_the_permalink() ) . '"';
		$html[] = 'title="' . the_title_attribute( array( 'echo' => false ) ) . '">';
		$html[] = $more_link_text;
		$html[] = '</a></div>';
		echo join( '', $html );
	}

	/**
	 * Set the Post Excerpt
	 *
	 * @since 2.7.1
	 * @return void
	 */
	public function set_custom_excerpt_length( $length ) {
		$exept_length = strval( $this->settings->excerpt_length );
		if ( trim( $exept_length ) === '' ) {
			return $length;
		}
		return intval( $exept_length );
	}

	/**
	 * Set custom 'more' text for the Post Excerpt.
	 *
	 * @since 2.7.1
	 * @return void
	 */
	public function set_custom_excerpt_more( $more ) {
		return $this->settings->excerpt_more_text;
	}
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('FLTabsModule', array(
	'items' => array(
		'title'    => __( 'Items', 'fl-builder' ),
		'sections' => array(
			'general'      => array(
				'title'  => '',
				'fields' => array(
					'source' => array(
						'type'    => 'select',
						'label'   => __( 'Content Source', 'fl-builder' ),
						'default' => 'content',
						'options' => array(
							'post'    => __( 'Post', 'fl-builder' ),
							'content' => __( 'Custom Content', 'fl-builder' ),
						),
						'toggle'  => array(
							'post'    => array(
								'sections' => array( 'post', 'post_display' ),
							),
							'content' => array(
								'sections' => array( 'content' ),
								'fields'   => array( 'content_text_color', 'content_typography' ),
							),
						),
					),
				),
			),
			'post'         => array(
				'title' => __( 'Post', 'fl-builder' ),
				'file'  => FL_BUILDER_DIR . 'includes/ui-simple-loop.php',
			),
			'post_display' => array(
				'title'  => __( 'Display', 'fl-builder' ),
				'fields' => array(
					'content_type'      => array(
						'type'    => 'select',
						'label'   => __( 'Content Type', 'fl-builder' ),
						'default' => 'post_content',
						'options' => array(
							'post_content' => __( 'Post Content', 'fl-builder' ),
							'post_excerpt' => __( 'Post Excerpt', 'fl-builder' ),
						),
						'toggle'  => array(
							'post_excerpt' => array(
								'fields' => array( 'excerpt_length', 'excerpt_more_text', 'more_link', 'more_link_text' ),
							),
						),
					),
					'excerpt_length'    => array(
						'type'    => 'unit',
						'units'   => array( 'words' ),
						'label'   => __( 'Excerpt Length', 'fl-builder' ),
						'default' => '',
						'slider'  => array(
							'min'  => 0,
							'max'  => 1000,
							'step' => 1,
						),
					),
					'excerpt_more_text' => array(
						'type'    => 'text',
						'label'   => __( 'Excerpt More Text', 'fl-builder' ),
						'default' => __( '...', 'fl-builder' ),
					),
					'more_link'         => array(
						'type'    => 'select',
						'label'   => __( 'More Link', 'fl-builder' ),
						'default' => 'hide',
						'options' => array(
							'show' => __( 'Show', 'fl-builder' ),
							'hide' => __( 'Hide', 'fl-builder' ),
						),
						'toggle'  => array(
							'show' => array(
								'fields' => array( 'more_link_text' ),
							),
						),
					),
					'more_link_text'    => array(
						'type'    => 'text',
						'label'   => __( 'More Link Text', 'fl-builder' ),
						'default' => __( 'Read More', 'fl-builder' ),
					),
				),
			),
			'content'      => array(
				'title'  => __( 'Custom Content', 'fl-builder' ),
				'fields' => array(
					'items' => array(
						'type'         => 'form',
						'label'        => __( 'Item', 'fl-builder' ),
						'form'         => 'items_form',
						'preview_text' => 'label',
						'multiple'     => true,
					),
				),
			),
		),
	),
	'style' => array(
		'title'    => __( 'Style', 'fl-builder' ),
		'sections' => array(
			'general' => array(
				'title'  => '',
				'fields' => array(
					'layout'         => array(
						'type'    => 'select',
						'label'   => __( 'Layout', 'fl-builder' ),
						'default' => 'horizontal',
						'options' => array(
							'horizontal' => __( 'Horizontal', 'fl-builder' ),
							'vertical'   => __( 'Vertical', 'fl-builder' ),
						),
						'preview' => array(
							'type' => 'none',
						),
					),
					'bg_color'       => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Background Color', 'fl-builder' ),
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-panels, .fl-tabs-label.fl-tab-active',
							'property' => 'background-color',
						),
					),
					'border_color'   => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Border Color', 'fl-builder' ),
						'default'     => 'e5e5e5',
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-label.fl-tab-active, .fl-tabs-panels',
							'property' => 'border-color',
						),
					),
					'border_width'   => array(
						'type'    => 'unit',
						'label'   => __( 'Border Width', 'fl-builder' ),
						'default' => '',
						'slider'  => array(
							'max' => 20,
						),
						'units'   => array( 'px' ),
						'preview' => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-labels .fl-tabs-label, .fl-tabs-panels',
							'property' => 'border-width',
							'unit'     => 'px',
						),
					),
					'active_tab'     => array(
						'type'    => 'unit',
						'label'   => 'Active Tab',
						'default' => '1',
						'slider'  => array(
							'min'  => 1,
							'max'  => 50,
							'step' => 1,
						),
						'help'    => __( 'If value set exceeds number of tabs, the first tab will be used.  If set to 0 or blank, there will be no active tab on load.', 'fl-builder' ),
					),
					'tabs_on_mobile' => array(
						'type'    => 'select',
						'label'   => __( 'Tab(s) Status on Mobile', 'fl-builder' ),
						'default' => 'open-active',
						'options' => array(
							'open-active' => __( 'Keep Active Tab Open', 'fl-builder' ),
							'close-all'   => __( 'Close All Tabs', 'fl-builder' ),
						),
					),
				),
			),
			'label'   => array(
				'title'  => __( 'Label', 'fl-builder' ),
				'fields' => array(
					'label_text_color'      => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Inactive Label Text Color', 'fl-builder' ),
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type'      => 'css',
							'selector'  => '.fl-tabs-label:not(.fl-tab-active), .fl-tabs-panel-label:not(.fl-tab-active)',
							'property'  => 'color',
							'important' => true,
						),
					),
					'label_bg_color'        => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Inactive Label Background Color', 'fl-builder' ),
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-label:not(.fl-tab-active), .fl-tabs-panel-label:not(.fl-tab-active)',
							'property' => 'background-color',
						),
					),
					'label_active_color'    => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Active Label Text Color', 'fl-builder' ),
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-label.fl-tab-active, .fl-tabs-panel-label.fl-tab-active',
							'property' => 'color',
						),
					),
					'label_active_bg_color' => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Active Label Background Color', 'fl-builder' ),
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-label.fl-tab-active, .fl-tabs-panel-label.fl-tab-active',
							'property' => 'background-color',
						),
					),
					'label_padding'         => array(
						'type'       => 'dimension',
						'label'      => __( 'Padding', 'fl-builder' ),
						'responsive' => true,
						'slider'     => true,
						'units'      => array(
							'px',
							'em',
							'%',
						),
						'preview'    => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-label',
							'property' => 'padding',
						),
					),
					'label_typography'      => array(
						'type'       => 'typography',
						'label'      => __( 'Typography', 'fl-builder' ),
						'responsive' => true,
						'preview'    => array(
							'type'      => 'css',
							'selector'  => '.fl-tabs-label',
							'important' => true,
						),
					),
				),
			),
			'content' => array(
				'title'  => __( 'Content', 'fl-builder' ),
				'fields' => array(
					'content_text_color' => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Text Color', 'fl-builder' ),
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-panel-content',
							'property' => 'color',
						),
					),
					'content_padding'    => array(
						'type'       => 'dimension',
						'label'      => __( 'Padding', 'fl-builder' ),
						'responsive' => true,
						'slider'     => true,
						'units'      => array(
							'px',
							'em',
							'%',
						),
						'preview'    => array(
							'type'     => 'css',
							'selector' => '.fl-tabs-panel-content',
							'property' => 'padding',
						),
					),
					'content_typography' => array(
						'type'       => 'typography',
						'label'      => __( 'Typography', 'fl-builder' ),
						'responsive' => true,
						'preview'    => array(
							'type'      => 'css',
							'selector'  => '.fl-tabs-panel-content',
							'important' => true,
						),
					),
				),
			),
		),
	),
));

/**
 * Register a settings form to use in the "form" field type above.
 */
FLBuilder::register_settings_form('items_form', array(
	'title' => __( 'Add Item', 'fl-builder' ),
	'tabs'  => array(
		'general' => array(
			'title'    => __( 'General', 'fl-builder' ),
			'sections' => array(
				'general'      => array(
					'title'  => '',
					'fields' => array(
						'label' => array(
							'type'        => 'text',
							'label'       => __( 'Label', 'fl-builder' ),
							'connections' => array( 'string' ),
						),
					),
				),
				'content_type' => array(
					'title'  => __( 'Content Type', 'fl-builder' ),
					'fields' => array(
						'saved_layout'   => array(
							'type'    => 'select',
							'label'   => __( 'Type', 'fl-builder' ),
							'default' => 'none',
							'help'    => __( 'This setting allows you to show saved layout in the slide.', 'fl-builder' ),
							'options' => array(
								'row'      => __( 'Saved Row', 'fl-builder' ),
								'column'   => __( 'Saved Column', 'fl-builder' ),
								'module'   => __( 'Saved Module', 'fl-builder' ),
								'template' => __( 'Saved Template', 'fl-builder' ),
								'none'     => __( 'Custom Content', 'fl-builder' ),
							),
							'toggle'  => array(
								'none'     => array(
									'sections' => array( 'content' ),
								),
								'row'      => array(
									'fields' => array( 'saved_row' ),
								),
								'column'   => array(
									'fields' => array( 'saved_column' ),
								),
								'module'   => array(
									'fields' => array( 'saved_module' ),
								),
								'template' => array(
									'fields' => array( 'saved_template' ),
								),
							),
						),
						'saved_row'      => array(
							'type'       => 'select',
							'label'      => __( 'Select Row', 'fl-builder' ),
							'saved_data' => 'row',
						),
						'saved_column'   => array(
							'type'       => 'select',
							'label'      => __( 'Select Column', 'fl-builder' ),
							'saved_data' => 'column',
						),
						'saved_module'   => array(
							'type'       => 'select',
							'label'      => __( 'Select Modules', 'fl-builder' ),
							'saved_data' => 'module',
						),
						'saved_template' => array(
							'type'       => 'select',
							'label'      => __( 'Select Template', 'fl-builder' ),
							'saved_data' => 'layout',
						),
					),
				),
				'content'      => array(
					'title'  => __( 'Content', 'fl-builder' ),
					'fields' => array(
						'content' => array(
							'type'        => 'editor',
							'label'       => '',
							'wpautop'     => false,
							'connections' => array( 'string' ),
						),
					),
				),
			),
		),
	),
));
