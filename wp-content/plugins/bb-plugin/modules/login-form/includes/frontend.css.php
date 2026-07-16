<?php

$form_selector    = ".fl-node-$id .fl-login-form.login";
$input_selector   = ".fl-node-$id .fl-login-form .fl-form-field input[type=text],.fl-node-$id .fl-login-form .fl-form-field input[type=password]";
$buttons_selector = ".fl-node-$id .fl-form-button a.fl-button";
$logout_settings  = $module->get_button_settings( 'lo_btn_' );
$login_settings   = $module->get_button_settings( 'btn_' );



// Form padding
FLBuilderCSS::dimension_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'form_padding',
	'selector'     => $form_selector,
	'unit'         => 'px',
	'props'        => array(
		'padding-top'    => 'form_padding_top',
		'padding-right'  => 'form_padding_right',
		'padding-bottom' => 'form_padding_bottom',
		'padding-left'   => 'form_padding_left',
	),
) );

// Form background color
FLBuilderCSS::rule( array(
	'selector' => $form_selector,
	'props'    => array(
		'background-color' => $settings->form_bg_color,
	),
) );

// Form hover background
FLBuilderCSS::rule( array(
	'selector' => $form_selector . ':hover',
	'props'    => array(
		'background-color' => $settings->form_bg_hover_color,
	),
) );

// Form Border - Settings
FLBuilderCSS::border_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'form_border',
	'selector'     => $form_selector,
) );

// Form Border - Hover Settings
if ( ! empty( $settings->form_border_hover ) && is_array( $settings->form_border ) ) {
	$settings->form_border['color'] = $settings->form_border_hover;
}

FLBuilderCSS::border_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'form_border_hover',
	'selector'     => $form_selector . ':hover',
) );

// Default input styles
FLBuilderCSS::rule( array(
	'selector' => $input_selector,
	'props'    => array(
		'border-radius' => '4px',
		'font-size'     => '16px',
		//  'line-height'   => '16px',
			'padding'   => '10px 24px',
	),
) );

FLBuilderCSS::rule( array(
	'selector' => '.fl-remember-forget',
	'enabled'  => 'yes' === $settings->forget && 'yes' === $settings->remember,
	'props'    => array(
		'float' => 'right',
	),
) );

// Button typography
FLBuilderCSS::typography_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'btn_typography',
	'selector'     => $buttons_selector,
) );

FLBuilderCSS::typography_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'input_typography',
	'selector'     => $input_selector,
) );

// Input color
FLBuilderCSS::rule( array(
	'selector' => ".fl-node-$id .fl-login-form .fl-form-field input[type=text]::placeholder, .fl-node-$id .fl-login-form .fl-form-field input[type=password]::placeholder," . $input_selector,
	'enabled'  => ! empty( $settings->input_color ),
	'props'    => array(
		'color' => $settings->input_color,
	),
) );

// Input Border
FLBuilderCSS::border_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'input_border',
	'selector'     => $input_selector,
) );

FLBuilderCSS::border_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'input_border_hover',
	'selector'     => ".fl-node-$id .fl-login-form .fl-form-field input[type=text]:hover, .fl-node-$id .fl-login-form .fl-form-field input[type=password]:hover, .fl-node-$id .fl-login-form .fl-form-field input[type=text]:focus, .fl-node-$id .fl-login-form .fl-form-field input[type=password]:focus",
) );

// Input padding
FLBuilderCSS::dimension_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'input_padding',
	'selector'     => $input_selector,
	'unit'         => 'px',
	'props'        => array(
		'padding-top'    => 'input_padding_top',
		'padding-right'  => 'input_padding_right',
		'padding-bottom' => 'input_padding_bottom',
		'padding-left'   => 'input_padding_left',
	),
) );

// Button padding
FLBuilderCSS::dimension_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'btn_padding',
	'selector'     => $buttons_selector,
	'unit'         => 'px',
	'props'        => array(
		'padding-top'    => 'btn_padding_top',
		'padding-right'  => 'btn_padding_right',
		'padding-bottom' => 'btn_padding_bottom',
		'padding-left'   => 'btn_padding_left',
	),
) );

// Icon
if ( isset( $settings->un_color ) && ! empty( $settings->un_color ) ) { ?>
	.fl-node-<?php echo $id; ?> .fl-login-form .fl-form-name-wrap i {
		color: <?php echo FLBuilderColor::hex_or_rgb( $settings->un_color ); ?>;
	}
	<?php
}

if ( isset( $settings->pw_color ) && ! empty( $settings->pw_color ) ) {
	?>
	.fl-node-<?php echo $id; ?> .fl-login-form .fl-form-password-wrap i {
		color: <?php echo FLBuilderColor::hex_or_rgb( $settings->pw_color ); ?>;
	}
	<?php
}
if ( ! empty( $settings->un_icon ) ) {
	if ( 'before' == $settings->icon_position ) {
		?>
		<?php
		FLBuilderCSS::rule( array(
			'selector' => ".fl-node-$id .fl-login-form .fl-form-field .fl-icon",
			'props'    => array(
				'left' => '15px',
			),
		) );

		// Add default left padding if it is not set.
		if ( empty( $settings->input_padding_left ) ) {
			FLBuilderCSS::rule( array(
				'selector' => $input_selector,
				'props'    => array(
					'padding-left' => '40px',
				),
			) );
		}
	} else {
		FLBuilderCSS::rule( array(
			'selector' => ".fl-node-$id .fl-login-form .fl-form-field .fl-icon",
			'props'    => array(
				'right' => '15px',
			),
		) );
	}

	FLBuilderCSS::rule( array(
		'selector' => ".fl-node-$id .fl-login-form .fl-form-field .fl-icon i::before",
		'props'    => array(
			'font-size' => $settings->icon_size . $settings->icon_size_unit,
		),
	) );

	FLBuilderCSS::rule( array(
		'selector' => ".fl-node-$id .fl-login-form .fl-form-field .fl-icon",
		'props'    => array(
			'top' => $settings->top_spacing . 'px',
		),
	) );

}
// Input border radius
FLBuilderCSS::border_field_rule( array(
	'settings'     => $settings,
	'setting_name' => 'input_border',
	'selector'     => $input_selector,
) );

// Button CSS
if ( ! is_user_logged_in() || FLBuilderModel::is_builder_active() ) {
	FLBuilder::render_module_css( 'button', $id, $login_settings );
}

// css for logout
FLBuilderCSS::rule( array(
	'selector' => ".fl-node-$id .fl-form-button.log-out a",
	'enabled'  => $logout_settings['bg_color'],
	'props'    => array(
		'background-color' => $logout_settings['bg_color'],
		'border-color'     => $logout_settings['bg_color'],
	),
) );

FLBuilderCSS::rule( array(
	'selector' => ".fl-node-$id .fl-form-button.log-out a:hover",
	'enabled'  => $logout_settings['bg_color_hover'],
	'props'    => array(
		'background-color' => $logout_settings['bg_color_hover'],
	),
) );

// shared styles
$logout_settings['text_color']       = $login_settings['text_color'];
$logout_settings['text_hover_color'] = $login_settings['text_hover_color'];

if ( is_user_logged_in() && ! FLBuilderModel::is_builder_active() ) {
	FLBuilder::render_module_css( 'button', $id, $logout_settings );
}
