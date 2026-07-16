<?php

$layout = $module->get_layout_slug();
$file   = $module->dir . 'includes/post-' . $layout;
$custom = isset( $settings->post_layout ) && 'custom' == $settings->post_layout;

if ( fl_builder_filesystem()->file_exists( $file . '-common.css.php' ) ) {
	include $file . '-common.css.php';
}
if ( ! $custom && fl_builder_filesystem()->file_exists( $file . '.css.php' ) ) {
	include $file . '.css.php';
}

// Equal height image
FLBuilderCSS::rule( array(
	'selector' => ".fl-node-$id .fl-post-grid-image img",
	'media'    => 'default',
	'enabled'  => ( 'columns' === $settings->layout || 'grid' === $settings->layout ) && 'fixed' === $settings->equal_height_image && ! empty( $settings->image_height ),
	'props'    => array(
		'height'     => "{$settings->image_height}px !important",
		'object-fit' => 'cover',
	),
) );

FLBuilderCSS::rule( array(
	'selector' => ".fl-node-$id .fl-post-grid-image img",
	'media'    => 'medium',
	'enabled'  => ( 'columns' === $settings->layout || 'grid' === $settings->layout ) && 'fixed' === $settings->equal_height_image && ! empty( $settings->image_height_medium ),
	'props'    => array(
		'height'     => "{$settings->image_height_medium}px !important",
		'object-fit' => 'cover',
	),
) );

FLBuilderCSS::rule( array(
	'selector' => ".fl-node-$id .fl-post-grid-image img",
	'media'    => 'responsive',
	'enabled'  => ( 'columns' === $settings->layout || 'grid' === $settings->layout ) && 'fixed' === $settings->equal_height_image && ! empty( $settings->image_height_responsive ),
	'props'    => array(
		'height'     => "{$settings->image_height_responsive}px !important",
		'object-fit' => 'cover',
	),
) );

// Image Aspect Ratio
FLBuilderCSS::responsive_rule(
	array(
		'settings'     => $settings,
		'setting_name' => 'image_aspect_ratio',
		'selector'     => ".fl-node-$id .fl-post-grid-image img, .fl-node-$id .fl-post-feed-image img",
		'enabled'      => ( 'columns' === $settings->layout || 'grid' === $settings->layout ) && 'ratio' === $settings->equal_height_image,
		'prop'         => 'aspect-ratio',
	)
);

FLBuilderCSS::rule(
	array(
		'selector' => ".fl-node-$id .fl-post-grid-image img, .fl-node-$id .fl-post-feed-image img",
		'enabled'  => ( 'columns' === $settings->layout || 'grid' === $settings->layout ) && 'ratio' === $settings->equal_height_image,
		'props'    => array(
			'object-fit'      => 'cover',
			'object-position' => 'center center',
		),
	)
);

if ( 'load_more' == $settings->pagination ) {
	FLBuilder::render_module_css( 'button', $id, $module->get_button_settings() );
}
