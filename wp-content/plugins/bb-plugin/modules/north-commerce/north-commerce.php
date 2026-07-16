<?php

/**
 * @class FLNorthCommerceModule
 */
class FLNorthCommerceModule extends FLBuilderModule {

	/**
	 * @method __construct
	 */
	public function __construct() {
		$enabled = class_exists( 'North_Commerce' );

		parent::__construct(array(
			'name'            => __( 'North Commerce', 'fl-builder' ),
			'description'     => __( 'Display products or categories from your North Commerce store.', 'fl-builder' ),
			'category'        => __( 'North Commerce', 'fl-builder' ),
			'icon'            => 'shopping-cart.svg',
			'enabled'         => $enabled,
			'partial_refresh' => true,
		));
	}

	public function wrapper_open() {
		$settings     = $this->settings;
		$class_list   = array();
		$class_list[] = 'fl-north-commerce';

		if ( ! empty( $settings->layout ) ) {
			$class_list[] = 'fl-north-commerce-' . sanitize_html_class( $settings->layout );
		}

		$class_attr = 'class="' . esc_attr( join( ' ', $class_list ) ) . '"';

		echo '<div ' . $class_attr . '>';
	}

	public function wrapper_close() {
		echo '</div>';
	}

	public function show_content() {
		$settings  = $this->settings;
		$nc_layout = $settings->nc_layout;

		if ( empty( $nc_layout ) ) {
			return;
		}

		$nc_attrs = '';
		if ( 'nc-product' === $nc_layout ) {
			$product_slug = esc_attr( $settings->product_slug );
			if ( ! empty( $product_slug ) ) {
				$nc_attrs = 'product_slug="' . $product_slug . '"';
			}
		}

		$short_code   = "[$nc_layout $nc_attrs]";
		$html_content = do_shortcode( $short_code );

		echo $html_content;
	}
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('FLNorthCommerceModule', array(
	'general'   => array(
		'title'    => __( 'General', 'fl-builder' ),
		'sections' => array(
			'general' => array(
				'title'  => '',
				'fields' => array(
					'nc_layout'    => array(
						'type'    => 'select',
						'label'   => __( 'Layout', 'fl-builder' ),
						'default' => '',
						'options' => array(
							''                   => __( 'None', 'fl-builder' ),
							'nc-product'         => __( 'Product Page', 'fl-builder' ),
							'nc-product-gallery' => __( 'Product Gallery', 'fl-builder' ),
							'nc-product-slider'  => __( 'Product Slider', 'fl-builder' ),
							'nc-cart'            => __( 'Cart', 'fl-builder' ),
							'nc-checkout'        => __( 'Checkout', 'fl-builder' ),
						),
						'toggle'  => array(
							'nc-product' => array(
								'fields' => array( 'product_slug' ),
							),
						),
					),
					'product_slug' => array(
						'type'    => 'text',
						'label'   => __( 'Product Slug', 'fl-builder' ),
						'default' => '',
					),
				),
			),
		),
	),
	'style_tab' => array(
		'title'    => __( 'Style', 'fl-builder' ),
		'sections' => array(
			'button_section'        => array(
				'title'  => 'Button',
				'fields' => array(
					'button_style'            => array(
						'label'   => __( 'Style', 'fl-builder' ),
						'type'    => 'button-group',
						'default' => 'default_style',
						'options' => array(
							'default_style' => __( 'Default Style', 'fl-builder' ),
							'hover_style'   => __( 'Hover Style', 'fl-builder' ),
						),
						'toggle'  => array(
							'default_style' => array(
								'fields' => array( 'button_text_color', 'button_icon_color', 'button_bg_color', 'button_border' ),
							),
							'hover_style'   => array(
								'fields' => array( 'button_text_hover_color', 'button_icon_hover_color', 'button_bg_hover_color' ),
							),
						),
					),
					'button_text_color'       => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Text Color', 'fl-builder' ),
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '{node} .nc-add-to-cart-container .nc-cart-btn, {node} .nc-checkout-step-container .nc-customer-checkout-btn',
							'property' => 'color',
						),
					),
					'button_icon_color'       => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Icon Color', 'fl-builder' ),
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '{node} .nc-add-to-cart-container .nc-cart-btn svg path, {node} .nc-checkout-step-container .nc-customer-checkout-btn svg path',
							'property' => 'fill',
						),
					),
					'button_bg_color'         => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Background Color', 'fl-builder' ),
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type'     => 'css',
							'selector' => '{node} .nc-add-to-cart-container .nc-cart-btn, {node} .nc-checkout-step-container .nc-customer-checkout-btn',
							'property' => 'background-color',
						),
					),
					'button_text_hover_color' => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Text Hover Color', 'fl-builder' ),
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type' => 'none',
						),
					),
					'button_icon_hover_color' => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Icon Hover Color', 'fl-builder' ),
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type' => 'none',
						),
					),
					'button_bg_hover_color'   => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Background Hover Color', 'fl-builder' ),
						'default'     => '',
						'show_reset'  => true,
						'show_alpha'  => true,
						'preview'     => array(
							'type' => 'none',
						),
					),
				),
			),
			'button_border_section' => array(
				'title'  => 'Button Border',
				'fields' => array(
					'button_border'             => array(
						'type'       => 'border',
						'label'      => __( 'Border', 'fl-builder' ),
						'responsive' => true,
						'preview'    => array(
							'type'     => 'css',
							'selector' => '{node} .nc-add-to-cart-container .nc-cart-btn, {node} .nc-checkout-step-container .nc-customer-checkout-btn',
							'property' => 'border',
						),
					),
					'button_border_hover_color' => array(
						'type'        => 'color',
						'connections' => array( 'color' ),
						'label'       => __( 'Border Hover Color', 'fl-builder' ),
						'show_alpha'  => true,
						'show_reset'  => true,
						'preview'     => array(
							'type' => 'none',
						),
					),
				),
			),
		),
	),
));
