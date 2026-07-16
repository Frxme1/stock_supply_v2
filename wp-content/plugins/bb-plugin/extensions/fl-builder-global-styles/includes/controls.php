<?php

FLBuilder::register_settings_form('styles', array(
	'title' => __( 'Global Styles', 'fl-builder' ),
	'reset' => true,
	'tabs'  => array(
		'elements' => array(
			'title'    => __( 'Elements', 'fl-builder' ),
			'sections' => array(
				'text'    => array(
					'title'  => __( 'Text', 'fl-builder' ),
					'fields' => array(
						'text_color'      => array(
							'type'        => 'color',
							'label'       => __( 'Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'text_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'Typography', 'fl-builder' ),
							'responsive' => true,
						),
					),
				),
				'heading' => array(
					'title'     => __( 'Heading', 'fl-builder' ),
					'collapsed' => true,
					'fields'    => array(
						'h1_color'      => array(
							'type'        => 'color',
							'label'       => __( 'H1 Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'h1_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'H1 Typography', 'fl-builder' ),
							'responsive' => true,
						),
						'h2_color'      => array(
							'type'        => 'color',
							'label'       => __( 'H2 Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'h2_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'H2 Typography', 'fl-builder' ),
							'responsive' => true,
						),
						'h3_color'      => array(
							'type'        => 'color',
							'label'       => __( 'H3 Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'h3_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'H3 Typography', 'fl-builder' ),
							'responsive' => true,
						),
						'h4_color'      => array(
							'type'        => 'color',
							'label'       => __( 'H4 Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'h4_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'H4 Typography', 'fl-builder' ),
							'responsive' => true,
						),
						'h5_color'      => array(
							'type'        => 'color',
							'label'       => __( 'H5 Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'h5_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'H5 Typography', 'fl-builder' ),
							'responsive' => true,
						),
						'h6_color'      => array(
							'type'        => 'color',
							'label'       => __( 'H6 Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'h6_typography' => array(
							'type'       => 'typography',
							'label'      => __( 'H6 Typography', 'fl-builder' ),
							'responsive' => true,
						),
					),
				),
				'link'    => array(
					'title'     => __( 'Link', 'fl-builder' ),
					'collapsed' => true,
					'fields'    => array(
						'link_color'       => array(
							'type'        => 'color',
							'label'       => __( 'Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'link_hover_color' => array(
							'type'        => 'color',
							'label'       => __( 'Hover Color', 'fl-builder' ),
							'show_reset'  => true,
							'show_alpha'  => true,
							'default'     => '',
							'connections' => array( 'color' ),
						),
						'link_typography'  => array(
							'type'       => 'typography',
							'label'      => __( 'Typography', 'fl-builder' ),
							'responsive' => true,
						),
					),
				),
				'button'  => array(
					'title'     => __( 'Button', 'fl-builder' ),
					'collapsed' => true,
					'fields'    => array(
						'button_color'              => array(
							'type'        => 'color',
							'label'       => __( 'Color', 'fl-builder' ),
							'default'     => '',
							'show_reset'  => true,
							'show_alpha'  => true,
							'connections' => array( 'color' ),
						),
						'button_hover_color'        => array(
							'type'        => 'color',
							'label'       => __( 'Hover Color', 'fl-builder' ),
							'default'     => '',
							'show_reset'  => true,
							'show_alpha'  => true,
							'connections' => array( 'color' ),
						),
						'button_background'         => array(
							'type'        => 'color',
							'label'       => __( 'Background', 'fl-builder' ),
							'default'     => '',
							'show_reset'  => true,
							'show_alpha'  => true,
							'connections' => array( 'color' ),
						),
						'button_hover_background'   => array(
							'type'        => 'color',
							'label'       => __( 'Hover Background', 'fl-builder' ),
							'default'     => '',
							'show_reset'  => true,
							'show_alpha'  => true,
							'connections' => array( 'color' ),
						),
						'button_typography'         => array(
							'type'       => 'typography',
							'label'      => __( 'Typography', 'fl-builder' ),
							'responsive' => true,
						),
						'button_border'             => array(
							'type'       => 'border',
							'label'      => __( 'Border', 'fl-builder' ),
							'responsive' => true,
						),
						'button_border_hover_color' => array(
							'type'        => 'color',
							'connections' => array( 'color' ),
							'label'       => __( 'Border Hover Color', 'fl-builder' ),
							'default'     => '',
							'show_reset'  => true,
							'show_alpha'  => true,
							'connections' => array( 'color' ),
						),
					),
				),
			),
		),
		'colors'   => array(
			'title'    => __( 'Colors', 'fl-builder' ),
			'sections' => array(
				'colors' => array(
					'title'  => __( 'Colors', 'fl-builder' ),
					'fields' => array(
						'colors' => array(
							'type'     => 'global-color',
							'label'    => __( 'Global Color', 'fl-builder' ),
							'multiple' => true,
						),
					),
				),
				'prefix' => array(
					'title'     => __( 'Prefix', 'fl-builder' ),
					'collapsed' => true,
					'fields'    => array(
						'prefix' => array(
							'type'        => 'text',
							'label'       => __( 'CSS Variable Prefix', 'fl-builder' ),
							'placeholder' => __( 'fl-global', 'fl-builder' ),
						),
					),
				),
			),
		),
	),
));
