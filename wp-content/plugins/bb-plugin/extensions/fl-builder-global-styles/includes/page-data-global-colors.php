<?php

/**
 * Global color
 */
FLPageData::add_group( 'bb', array(
	'label'  => __( 'Global Colors', 'fl-builder' ),
	'render' => false,
) );

$settings      = self::get_settings( false );
$prefix        = ! empty( $settings->prefix ) ? self::label_to_key( $settings->prefix ) : 'fl-global';
$global_colors = isset( $settings->colors ) ? $settings->colors : false;

if ( ! empty( $global_colors ) ) {
	foreach ( $global_colors as $color ) {
		if ( empty( $color ) || empty( $color['color'] ) || empty( $color['label'] ) ) {
			continue;
		}

		FLPageData::add_site_property( 'global_color_' . $color['uid'], array(
			'label'  => '<span class="prefix">' . __( 'Global -', 'fl-builder' ) . '</span>' . $color['label'] . '<span class="swatch" style="background-color:' . FLBuilderColor::hex_or_rgb( $color['color'] ) . ';"></span>',
			'group'  => 'bb',
			'type'   => 'color',
			'getter' => function () use ( $prefix, $color ) {
				return 'var(--' . $prefix . '-' . self::label_to_key( $color['label'] ) . ')';
			},
		) );
	}
}

/**
 * Gutenberg color
 */
if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
	$settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
	/**
	 * @since 2.8
	 * @see fl_wp_core_global_colors
	 */
	$colors = isset( $settings['color']['palette']['default'] ) ? apply_filters( 'fl_wp_core_global_colors', $settings['color']['palette']['default'] ) : array();
	if ( ! empty( $colors ) ) {
		FLPageData::add_group( 'core', array(
			'label'  => __( 'WordPress Colors', 'fl-builder' ),
			'render' => false,
		) );

		foreach ( $colors as $color ) {
			FLPageData::add_site_property( 'theme_color_' . $color['slug'], array(
				'label'  => '<span class="prefix">' . __( 'WordPress -', 'fl-builder' ) . '</span>' . $color['name'] . '<span class="swatch" style="background-color:' . FLBuilderColor::hex_or_rgb( $color['color'] ) . ';"></span>',
				'group'  => 'core',
				'type'   => 'color',
				'getter' => function () use ( $color ) {
					return 'var(--wp--preset--color--' . $color['slug'] . ')';
				},
			) );
		}
	}

	if ( ! empty( $settings['color']['palette']['theme'] ) ) {
		FLPageData::add_group( 'theme', array(
			'label'  => __( 'Theme', 'fl-builder' ),
			'render' => false,
		) );

		foreach ( $settings['color']['palette']['theme'] as $color ) {
			$name = isset( $color['name'] ) ? $color['name'] : ucfirst( $color['slug'] );
			FLPageData::add_site_property( 'theme_color_' . $color['slug'], array(
				'label'  => '<span class="prefix">' . __( 'Theme -', 'fl-builder' ) . '</span>' . $name . '<span class="swatch" style="background-color:' . FLBuilderColor::hex_or_rgb( $color['color'] ) . ';"></span>',
				'group'  => 'theme',
				'type'   => 'color',
				'getter' => function () use ( $color ) {
					return 'var(--wp--preset--color--' . $color['slug'] . ')';
				},
			) );
		}
	}
}
