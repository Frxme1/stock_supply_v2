<?php

$globals = FLBuilderGlobalStyles::get_settings( false );

// Global Button Text Color, BG Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn',
	),
	'props'    => array(
		'color'            => FLBuilderColor::hex_or_rgb( $globals->button_color ),
		'background-color' => FLBuilderColor::hex_or_rgb( $globals->button_background ),
	),
) );

// Button Text Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn',
	),
	'enabled'  => ! empty( $settings->button_text_color ),
	'props'    => array(
		'color' => FLBuilderColor::hex_or_rgb( $settings->button_text_color ),
	),
) );

// Button BG Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn',
	),
	'enabled'  => ! empty( $settings->button_bg_color ),
	'props'    => array(
		'background-color' => FLBuilderColor::hex_or_rgb( $settings->button_bg_color ),
	),
) );

// Button Icon Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn svg path",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn svg path",
	),
	'props'    => array(
		'fill' => $settings->button_icon_color,
	),
) );

// Button Border
FLBuilderCSS::border_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'button_border',
	'selector'     => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn",
		".fl-node-$id .nc-checkout-container .nc-guest-btn",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn',
	),
) );

// Button Text Hover Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn:hover",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn:hover",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn:hover a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn:hover',
	),
	'enabled'  => ! empty( $settings->button_text_hover_color ),
	'props'    => array(
		'color' => FLBuilderColor::hex_or_rgb( $settings->button_text_hover_color ),
	),
) );

// Button Icon Hover Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn:hover svg path",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn:hover svg path",
	),
	'enabled'  => ! empty( $settings->button_icon_hover_color ),
	'props'    => array(
		'fill' => $settings->button_icon_hover_color,
	),
) );

// Button BG Hover Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn:hover",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn:hover",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn:hover a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn:hover',
	),
	'enabled'  => ! empty( $settings->button_bg_hover_color ),
	'props'    => array(
		'background-color' => FLBuilderColor::hex_or_rgb( $settings->button_bg_hover_color ),
	),
) );

// Button Border Hover Color
FLBuilderCSS::rule( array(
	'selector' => array(
		".fl-node-$id .nc-add-to-cart-container .nc-cart-btn:hover",
		".fl-node-$id .nc-checkout-step-container .nc-customer-checkout-btn:hover",
		".fl-node-$id .nc-main-wrapper.nc-cart .nc-checkout-btn:hover a",
		'#nc-cart-drawer .nc-drawer-container .nc-drawer-checkout-btn:hover',
	),
	'enabled'  => ! empty( $settings->button_border_hover_color ),
	'props'    => array(
		'border-color' => FLBuilderColor::hex_or_rgb( $settings->button_border_hover_color ),
	),
) );
