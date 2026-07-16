<?php
/**
 *  UABB Caldera Forms Styler Module front-end CSS php file
 *
 *   @package UABB Caldera Forms Styler Module
 */

	$settings->form_title_color               = FLBuilderColor::hex_or_rgb( $settings->form_title_color );
	$settings->form_desc_color                = FLBuilderColor::hex_or_rgb( $settings->form_desc_color );
	$settings->field_bg_color                 = FLBuilderColor::hex_or_rgb( $settings->field_bg_color );
	$settings->fields_border_active_color     = FLBuilderColor::hex_or_rgb( $settings->fields_border_active_color );
	$settings->field_active_bg_color          = FLBuilderColor::hex_or_rgb( $settings->field_active_bg_color );
	$settings->label_color                    = FLBuilderColor::hex_or_rgb( $settings->label_color );
	$settings->input_field_color              = FLBuilderColor::hex_or_rgb( $settings->input_field_color );
	$settings->required_asterisk_color        = FLBuilderColor::hex_or_rgb( $settings->required_asterisk_color );
	$settings->button_bg_color                = FLBuilderColor::hex_or_rgb( $settings->button_bg_color );
	$settings->button_bg_hover_color          = FLBuilderColor::hex_or_rgb( $settings->button_bg_hover_color );
	$settings->btn_text_color                 = FLBuilderColor::hex_or_rgb( $settings->btn_text_color );
	$settings->btn_text_hover_color           = FLBuilderColor::hex_or_rgb( $settings->btn_text_hover_color );
	$settings->button_border_hover_color      = FLBuilderColor::hex_or_rgb( $settings->button_border_hover_color );
	$settings->sec_button_bg_color            = FLBuilderColor::hex_or_rgb( $settings->sec_button_bg_color );
	$settings->sec_button_bg_hover_color      = FLBuilderColor::hex_or_rgb( $settings->sec_button_bg_hover_color );
	$settings->sec_btn_text_color             = FLBuilderColor::hex_or_rgb( $settings->sec_btn_text_color );
	$settings->sec_btn_text_hover_color       = FLBuilderColor::hex_or_rgb( $settings->sec_btn_text_hover_color );
	$settings->sec_button_border_hover_color  = FLBuilderColor::hex_or_rgb( $settings->sec_button_border_hover_color );
	$settings->error_message_color            = FLBuilderColor::hex_or_rgb( $settings->error_message_color );
	$settings->error_input_field_border_color = FLBuilderColor::hex_or_rgb( $settings->error_input_field_border_color );
	$settings->success_msg_color              = FLBuilderColor::hex_or_rgb( $settings->success_msg_color );
	$settings->success_bg_color               = FLBuilderColor::hex_or_rgb( $settings->success_bg_color );
	$settings->form_bg_color                  = FLBuilderColor::hex_or_rgb( $settings->form_bg_color );
	$settings->radio_cb_color                 = FLBuilderColor::hex_or_rgb( $settings->radio_cb_color );
	$settings->radio_cb_checked_color         = FLBuilderColor::hex_or_rgb( $settings->radio_cb_checked_color );
	$settings->radio_cb_border_color          = FLBuilderColor::hex_or_rgb( $settings->radio_cb_border_color );
	$settings->radio_checkbox_color           = FLBuilderColor::hex_or_rgb( $settings->radio_checkbox_color );
	$settings->star_icon_selected_color       = FLBuilderColor::hex_or_rgb( $settings->star_icon_selected_color );
	$settings->star_inactive_color            = FLBuilderColor::hex_or_rgb( $settings->star_inactive_color );
	$settings->section_field_bg_color         = FLBuilderColor::hex_or_rgb( $settings->section_field_bg_color );
	$settings->section_title_color            = FLBuilderColor::hex_or_rgb( $settings->section_title_color );
	$settings->section_description_color      = FLBuilderColor::hex_or_rgb( $settings->section_description_color );
	$settings->input_placeholder_color        = FLBuilderColor::hex_or_rgb( $settings->input_placeholder_color );


	// Button Background Gradient.
if ( ! empty( $settings->button_bg_color ) ) {
	$bg_grad_start = FLBuilderColor::hex_or_rgb( FLBuilderColor::adjust_brightness( $settings->button_bg_color, 30, 'lighten' ) );
}

?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content {
<?php
if ( 'color' === esc_attr( $settings->form_bg_type ) ) {
	if ( isset( $settings->form_bg_color ) ) {

		echo ( ! empty( $settings->form_bg_color ) ) ? 'background:' . esc_attr( $settings->form_bg_color ) . ';' : '';
	}
} elseif ( 'gradient' === esc_attr( $settings->form_bg_type ) ) {
	if ( isset( $settings->form_bg_gradient ) ) {

		echo ( ! empty( $settings->form_bg_gradient ) ) ? 'background:' . esc_attr( FLBuilderColor::gradient( $settings->form_bg_gradient ) ) . ';' : '';
	}
}
?>
}

<?php
if ( class_exists( 'FLBuilderCSS' ) ) {
	// Form Padding - Settings.
	FLBuilderCSS::dimension_field_rule(
		array(
			'settings'     => $settings,
			'setting_name' => 'form_spacing_dimension',
			'selector'     => ".fl-node-$id .uabb-fluent-form-content",
			'unit'         => 'px',
			'props'        => array(
				'padding-top'    => 'form_spacing_dimension_top',
				'padding-right'  => 'form_spacing_dimension_right',
				'padding-bottom' => 'form_spacing_dimension_bottom',
				'padding-left'   => 'form_spacing_dimension_left',
			),
		)
	);
	if ( isset( $settings->form_border ) ) {
		// Form Border - Settings.
		FLBuilderCSS::border_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_border',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content",
			)
		);
	}
	// Field Padding - Settings.
	FLBuilderCSS::dimension_field_rule(
		array(
			'settings'     => $settings,
			'setting_name' => 'field_padding',
			'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff-el-form-control, .fl-node-$id .uabb-fluent-form-content .select2-container--default .select2-selection--multiple",
			'unit'         => 'px',
			'props'        => array(
				'padding-top'    => 'field_padding_top',
				'padding-right'  => 'field_padding_right',
				'padding-bottom' => 'field_padding_bottom',
				'padding-left'   => 'field_padding_left',
			),
		)
	);
	if ( isset( $settings->form_fields_border ) ) {
		// Field Border - Settings.
		FLBuilderCSS::border_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_fields_border',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff-el-form-control, .fl-node-$id .uabb-fluent-form-content .select2-container--default .select2-selection--multiple",
			)
		);
	}
	if ( isset( $settings->ff_title_desc_align ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'ff_title_desc_align',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .uabb-ff-form-title,.fl-node-$id .uabb-fluent-form-content .uabb-ff-form-description",
				'prop'         => 'text-align',
			)
		);
	}
	if ( isset( $settings->form_title_bottom_margin ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_title_bottom_margin',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .uabb-ff-form-title",
				'prop'         => 'margin-bottom',
				'unit'         => 'px',
			)
		);
	}
	if ( isset( $settings->form_desc_bottom_margin ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_desc_bottom_margin',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .uabb-ff-form-description",
				'prop'         => 'margin-bottom',
				'unit'         => 'px',
			)
		);
	}
	if ( isset( $settings->form_fields_spacing ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_fields_spacing',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-field_container, .fl-node-$id .uabb-fluent-form-content .ff-el-group",
				'prop'         => 'margin-bottom',
				'unit'         => 'px',
			)
		);
	}
	if ( isset( $settings->label_bottom_spacing ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'label_bottom_spacing',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-input--label label",
				'prop'         => 'margin-bottom',
				'unit'         => 'px',
			)
		);
	}
	if ( isset( $settings->button_align ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'button_align',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper",
				'prop'         => 'text-align',
			)
		);
	}
	// Button Padding - Settings.
	FLBuilderCSS::dimension_field_rule(
		array(
			'settings'     => $settings,
			'setting_name' => 'button_padding',
			'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button",
			'unit'         => 'px',
			'props'        => array(
				'padding-top'    => 'button_padding_top',
				'padding-right'  => 'button_padding_right',
				'padding-bottom' => 'button_padding_bottom',
				'padding-left'   => 'button_padding_left',
			),
		)
	);

	if ( isset( $settings->button_border ) ) {
		// Button Border - Settings.
		FLBuilderCSS::border_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'button_border',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button",
			)
		);
	}
	// Secondary Button Padding - Settings.
	FLBuilderCSS::dimension_field_rule(
		array(
			'settings'     => $settings,
			'setting_name' => 'sec_button_padding',
			'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform span.ff_upload_btn.ff-btn, .fl-node-$id .uabb-fluent-form-content .fluentform button.ff-btn.ff-btn-secondary",
			'unit'         => 'px',
			'props'        => array(
				'padding-top'    => 'sec_button_padding_top',
				'padding-right'  => 'sec_button_padding_right',
				'padding-bottom' => 'sec_button_padding_bottom',
				'padding-left'   => 'sec_button_padding_left',
			),
		)
	);

	if ( isset( $settings->sec_button_border ) ) {
		// Secondary Button Border - Settings.
		FLBuilderCSS::border_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'sec_button_border',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform span.ff_upload_btn.ff-btn, .fl-node-$id .uabb-fluent-form-content .fluentform button.ff-btn.ff-btn-secondary",
			)
		);
	}
}
?>

<?php
if ( isset( $settings->form_title_color ) ) {
	?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .uabb-ff-form-title {

		<?php
		echo ( ! empty( $settings->form_title_color ) ) ? 'color:' . esc_attr( $settings->form_title_color ) . ';' : '';
		?>
}
	<?php } ?>

	<?php
	if ( isset( $settings->form_desc_color ) ) {
		?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .uabb-ff-form-description {

		<?php
		echo ( ! empty( $settings->form_desc_color ) ) ? 'color:' . esc_attr( $settings->form_desc_color ) . ';' : '';
		?>
}
	<?php } ?>

<?php
if ( isset( $settings->field_bg_color ) ) {
	?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff-el-form-control,
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .select2-container--default .select2-selection--multiple{

		<?php
		echo ( ! empty( $settings->field_bg_color ) ) ? 'background:' . esc_attr( $settings->field_bg_color ) . ';' : '';
		?>
}
	<?php } ?>

<?php
if ( isset( $settings->fields_border_active_color ) && '' !== $settings->fields_border_active_color ) {
	?>
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff-el-form-control:focus {

		border-color: <?php echo esc_attr( $settings->fields_border_active_color ); ?>;
	}
<?php } ?>

<?php
if ( isset( $settings->field_active_bg_color ) && '' !== $settings->field_active_bg_color ) {
	?>
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff-el-form-control:focus {

		background-color: <?php echo esc_attr( $settings->field_active_bg_color ); ?>;
	}
<?php } ?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-input--label label {
	<?php

	if ( isset( $settings->label_color ) ) {

		echo ( ! empty( $settings->label_color ) ) ? 'color:' . esc_attr( $settings->label_color ) . ';' : '';

	}
	?>
}

<?php

if ( isset( $settings->input_field_color ) ) {
	?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff-el-form-control,
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .select2-container--default .select2-selection--multiple,
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff_net_table tbody tr td label {
	<?php

	echo ( ! empty( $settings->input_field_color ) ) ? 'color:' . esc_attr( $settings->input_field_color ) . ';' : '';
	?>
}
<?php } ?>

<?php

if ( isset( $settings->input_placeholder_color ) ) {
	?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff-el-form-control::-webkit-input-placeholder {
	<?php

	echo ( ! empty( $settings->input_placeholder_color ) ) ? 'color:' . esc_attr( $settings->input_placeholder_color ) . ';' : '';
	?>
}
<?php } ?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-input--label.ff-el-is-required.asterisk-right label:after {
	<?php

	if ( isset( $settings->required_asterisk_color ) ) {

		echo ( ! empty( $settings->required_asterisk_color ) ) ? 'color:' . esc_attr( $settings->required_asterisk_color ) . '' : '';

	}
	?>
}
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-section-break {
	background-color: <?php echo ( $settings->section_field_bg_color ) ? esc_attr( $settings->section_field_bg_color ) : 'transparent'; ?>;
	<?php if ( $settings->section_description_color ) { ?>
	color: <?php echo esc_attr( $settings->section_description_color ); ?>;
	<?php } ?>
}

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-section-break .ff-el-section-title {
	<?php
	if ( isset( $settings->section_title_color ) ) {

		echo ( ! empty( $settings->section_title_color ) ) ? 'color:' . esc_attr( $settings->section_title_color ) . '' : '';

	}
	?>
}

<?php
	// Section - Border.
if ( class_exists( 'FLBuilderCSS' ) ) {
	if ( isset( $settings->section_field_border ) ) {

		FLBuilderCSS::border_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'section_field_border',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-section-break",
			)
		);
	}

	// Section Title Typography.
	if ( isset( $settings->section_title_typography ) ) {

		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'section_title_typography',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-section-break .ff-el-section-title",
			)
		);
	}
	// Section Description Typography.
	if ( isset( $settings->section_description_typography ) ) {

		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'section_description_typography',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-section-break",
			)
		);
	}
	// Section - Margin.
		FLBuilderCSS::dimension_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'section_field_margin',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-section-break",
				'unit'         => 'px',
				'props'        => array(
					'margin-top'    => 'section_field_margin_top',
					'margin-right'  => 'section_field_margin_right',
					'margin-bottom' => 'section_field_margin_bottom',
					'margin-left'   => 'section_field_margin_left',
				),
			)
		);

	// Section - Padding.
		FLBuilderCSS::dimension_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'section_field_padding',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-section-break",
				'unit'         => 'px',
				'props'        => array(
					'padding-top'    => 'section_field_padding_top',
					'padding-right'  => 'section_field_padding_right',
					'padding-bottom' => 'section_field_padding_bottom',
					'padding-left'   => 'section_field_padding_left',
				),
			)
		);
}
?>
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-ratings.jss-ff-el-ratings svg {
	<?php

	if ( isset( $settings->star_inactive_color ) ) {

		echo ( ! empty( $settings->star_inactive_color ) ) ? 'fill:' . esc_attr( $settings->star_inactive_color ) . '' : '';

	}
	?>
}
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-ratings.jss-ff-el-ratings label.active svg {
	<?php

	if ( isset( $settings->star_icon_selected_color ) ) {

		echo ( ! empty( $settings->star_icon_selected_color ) ) ? 'fill:' . esc_attr( $settings->star_icon_selected_color ) . '' : '';

	}
	?>
}

<?php
// Radio & Checkbox.
if ( 'yes' === $settings->override_checkbox_radio_style ) {
	?>

	<?php
	$font_size = $settings->radio_cb_size / 1.2;
	?>
	/* Radio & Checkbox */
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio],
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:focus,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox],
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:focus {
		-webkit-appearance: none;
		-moz-appearance: none;
		outline: none;
		width: <?php echo esc_attr( $settings->radio_cb_size ); ?>px !important;
		height: <?php echo esc_attr( $settings->radio_cb_size ); ?>px !important;
		background-color: <?php echo esc_attr( $settings->radio_cb_color ); ?>;
		border: <?php echo esc_attr( $settings->radio_cb_border_width ); ?>px solid <?php echo esc_attr( $settings->radio_cb_border_color ); ?>;
		padding: 2px;
	}
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio],
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:focus,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:focus:before {
		<?php if ( $settings->radio_cb_radius >= 0 ) { ?>
			border-radius: <?php echo esc_attr( $settings->radio_cb_radius ); ?>px;
		<?php } ?>
	}
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox],
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:focus,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:focus:before {
		<?php if ( $settings->radio_cb_radius >= 0 ) { ?>
			border-radius: <?php echo esc_attr( $settings->radio_cb_checkbox_radius ); ?>px;
		<?php } ?>
	}

	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:focus:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:focus:before {
		content: "";
		width: 100%;
		height: 100%;
		padding: 0;
		margin: 0;
		display: block;
		font-weight: bold;
		text-align: center;
		max-height: 30px;
	}

	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:checked:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=checkbox]:focus:checked:before {
		content: "\2714";
		color: <?php echo esc_attr( $settings->radio_cb_checked_color ); ?>;
		font-size: calc(<?php echo esc_attr( $font_size ); ?>px - <?php echo esc_attr( $settings->radio_cb_border_width ); ?>px );
	}
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:checked:before,
	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform input[type=radio]:focus:checked:before{
		background: <?php echo esc_attr( $settings->radio_cb_checked_color ); ?>;
	}
	.fl-builder-content .fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-form-check.ff-el-form-check-:not(:last-child) {

	<?php echo ( '' !== $settings->check_radio_items_spacing ) ? 'margin-right: ' . esc_attr( $settings->check_radio_items_spacing ) . 'px !important;' : ''; ?>
	<?php echo ( '' !== $settings->check_radio_items_spacing ) ? 'margin-bottom: ' . esc_attr( $settings->check_radio_items_spacing ) . 'px !important;' : ''; ?>
}

	.fl-builder-content .fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff-el-form-check-label {
	<?php
	if ( isset( $settings->radio_checkbox_color ) ) {

		echo ( ! empty( $settings->radio_checkbox_color ) ) ? 'color:' . esc_attr( $settings->radio_checkbox_color ) . '' : '';
	}
	?>
}
	<?php
	// Radio & Checkout Typography.
	if ( class_exists( 'FLBuilderCSS' ) ) {
		if ( isset( $settings->radio_check_typography ) ) {

			FLBuilderCSS::typography_field_rule(
				array(
					'settings'     => $settings,
					'setting_name' => 'radio_check_typography',
					'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff-el-form-check-label",
				)
			);
		}
	}
	?>
<?php } ?>


<?php
// Primary Submit Button style.
?>
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button {
	<?php
	if ( 'color' === esc_attr( $settings->button_bg_type ) ) {
		if ( isset( $settings->button_bg_color ) ) {

			echo ( ! empty( $settings->button_bg_color ) ) ? 'background:' . esc_attr( $settings->button_bg_color ) . ';' : '';

		}
	} elseif ( 'gradient' === esc_attr( $settings->button_bg_type ) ) {

		if ( ! empty( $settings->button_bg_color ) ) {
			?>
		background: -moz-linear-gradient(top,  <?php echo esc_attr( $bg_grad_start ); ?> 0%, <?php echo esc_attr( $settings->button_bg_color ); ?> 100%); /* FF3.6+ */
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,<?php echo esc_attr( $bg_grad_start ); ?>), color-stop(100%,<?php echo esc_attr( $settings->button_bg_color ); ?>)); /* Chrome,Safari4+ */
		background: -webkit-linear-gradient(top,  <?php echo esc_attr( $bg_grad_start ); ?> 0%,<?php echo esc_attr( $settings->button_bg_color ); ?> 100%); /* Chrome10+,Safari5.1+ */
		background: -o-linear-gradient(top,  <?php echo esc_attr( $bg_grad_start ); ?> 0%,<?php echo esc_attr( $settings->button_bg_color ); ?> 100%); /* Opera 11.10+ */
		background: -ms-linear-gradient(top,  <?php echo esc_attr( $bg_grad_start ); ?> 0%,<?php echo esc_attr( $settings->button_bg_color ); ?> 100%); /* IE10+ */
		background: linear-gradient(to bottom,  <?php echo esc_attr( $bg_grad_start ); ?> 0%,<?php echo esc_attr( $settings->button_bg_color ); ?> 100%); /* W3C */
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='<?php echo esc_attr( $bg_grad_start ); ?>', endColorstr='<?php echo esc_attr( $settings->button_bg_color ); ?>',GradientType=0 ); /* IE6-9 */
			<?php
		}
		if ( isset( $settings->button_bg_gradient ) ) {

			echo ( ! empty( $settings->button_bg_gradient ) ) ? 'background:' . esc_attr( FLBuilderColor::gradient( $settings->button_bg_gradient ) ) . ';' : '';
		}
	}

	if ( isset( $settings->btn_text_color ) ) {

		echo ( ! empty( $settings->btn_text_color ) ) ? 'color:' . esc_attr( $settings->btn_text_color ) . ';' : '';

	}
	?>
	<?php if ( isset( $settings->btn_margin_top ) && '' !== $settings->btn_margin_top ) { ?>
		margin-top: <?php echo esc_attr( $settings->btn_margin_top ); ?>px;
	<?php } ?>
	<?php if ( 'full' === $settings->btn_width ) { ?>
		width:100%;
		<?php
	} elseif ( 'custom' === $settings->btn_width ) {
		?>

		<?php if ( '' !== $settings->btn_custom_width ) { ?>
			width: <?php echo esc_attr( $settings->btn_custom_width ); ?>px;
		<?php } ?>

		<?php if ( '' !== $settings->btn_custom_height ) { ?>
			min-height: <?php echo esc_attr( $settings->btn_custom_height ); ?>px;
		<?php } ?>

		<?php } ?>
}

<?php if ( isset( $settings->button_border_hover_color ) && '' !== $settings->button_border_hover_color ) { ?>

	.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button:hover {
		border-color: <?php echo esc_attr( $settings->button_border_hover_color ); ?>;
	}
<?php } ?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button:hover {
	<?php
	if ( isset( $settings->btn_text_hover_color ) ) {
		echo ( ! empty( $settings->btn_text_hover_color ) ) ? 'color:' . esc_attr( $settings->btn_text_hover_color ) . ';' : '';
	}
	if ( isset( $settings->button_bg_hover_color ) ) {
		echo ( ! empty( $settings->button_bg_hover_color ) ) ? 'background:' . esc_attr( $settings->button_bg_hover_color ) . ';' : '';
	}
	?>
}
<?php
// Seconday button style.
?>
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform span.ff_upload_btn.ff-btn,
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform button.ff-btn.ff-btn-secondary {
	<?php
	if ( isset( $settings->sec_button_bg_color ) ) {

		echo ( ! empty( $settings->sec_button_bg_color ) ) ? 'background:' . esc_attr( $settings->sec_button_bg_color ) . ';' : '';

	}

	if ( isset( $settings->sec_btn_text_color ) ) {

		echo ( ! empty( $settings->sec_btn_text_color ) ) ? 'color:' . esc_attr( $settings->sec_btn_text_color ) . ';' : '';

	}
	?>

}

<?php if ( isset( $settings->sec_button_border_hover_color ) && '' !== $settings->sec_button_border_hover_color ) { ?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform span.ff_upload_btn.ff-btn:hover,
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform button.ff-btn.ff-btn-secondary:hover {
		border-color: <?php echo esc_attr( $settings->sec_button_border_hover_color ); ?>;
	}
<?php } ?>

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform span.ff_upload_btn.ff-btn:hover,
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .fluentform button.ff-btn.ff-btn-secondary:hover {
	<?php
	if ( isset( $settings->sec_btn_text_hover_color ) ) {
		echo ( ! empty( $settings->sec_btn_text_hover_color ) ) ? 'color:' . esc_attr( $settings->sec_btn_text_hover_color ) . ';' : '';
	}
	if ( isset( $settings->sec_button_bg_hover_color ) ) {
		echo ( ! empty( $settings->sec_button_bg_hover_color ) ) ? 'background:' . esc_attr( $settings->sec_button_bg_hover_color ) . ';' : '';
	}
	?>
}
.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-is-error .error {
	<?php

	if ( isset( $settings->error_message_color ) ) {

		echo ( ! empty( $settings->error_message_color ) ) ? 'color:' . esc_attr( $settings->error_message_color ) . ';' : '';

	}
	?>
}

.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-el-is-error .ff-el-form-control {
	<?php if ( $settings->error_input_field_border_color || $settings->error_input_field_border_width ) { ?>
	border-style: solid;
	border-color: <?php echo esc_attr( $settings->error_input_field_border_color ); ?>;
	border-width: <?php echo esc_attr( $settings->error_input_field_border_width ); ?>px;
	<?php } ?>
}


.fl-node-<?php echo esc_attr( $id ); ?> .uabb-fluent-form-content .ff-message-success {
	<?php

	if ( isset( $settings->success_msg_color ) ) {

		echo ( ! empty( $settings->success_msg_color ) ) ? 'color:' . esc_attr( $settings->success_msg_color ) . ';' : '';

	}
	if ( isset( $settings->success_bg_color ) ) {

		echo ( ! empty( $settings->success_bg_color ) ) ? 'background:' . esc_attr( $settings->success_bg_color ) . ';' : '';

	}
	?>
}

<?php

if ( class_exists( 'FLBuilderCSS' ) ) {


	if ( isset( $settings->error_msg_sapcing ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'error_msg_sapcing',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-is-error .error.text-danger",
				'prop'         => 'margin-top',
				'unit'         => 'px',
			)
		);
	}

	if ( isset( $settings->btn_margin_top ) ) {
		FLBuilderCSS::responsive_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'btn_margin_top',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button",
				'prop'         => 'margin-top',
				'unit'         => 'px',
			)
		);
	}

	if ( isset( $settings->success_message_border ) ) {
		FLBuilderCSS::border_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'success_message_border',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-message-success",
			)
		);
	}

	if ( isset( $settings->form_title_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_title_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .uabb-ff-form-title",
			)
		);
	}

	if ( isset( $settings->form_desc_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_desc_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .uabb-ff-form-description",
			)
		);
	}

	if ( isset( $settings->field_label_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'field_label_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-input--label label",
			)
		);
	}

	if ( isset( $settings->input_placeholder_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'input_placeholder_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff-el-form-control, .fl-node-$id .uabb-fluent-form-content table.ff-table.ff-checkable-grids.ff_flexible_table",
			)
		);
	}

	if ( isset( $settings->button_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'button_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff_submit_btn_wrapper button, .fl-node-$id .uabb-fluent-form-content .fluentform span.ff_upload_btn.ff-btn, .fl-node-$id .uabb-fluent-form-content .fluentform button.ff-btn.ff-btn-secondary",
			)
		);
	}

	if ( isset( $settings->form_radio_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'form_radio_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .fluentform .ff-el-form-check-label",
			)
		);
	}

	if ( isset( $settings->error_msg_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'error_msg_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-el-is-error .error",
			)
		);
	}

	if ( isset( $settings->success_msg_typo ) ) {
		FLBuilderCSS::typography_field_rule(
			array(
				'settings'     => $settings,
				'setting_name' => 'success_msg_typo',
				'selector'     => ".fl-node-$id .uabb-fluent-form-content .ff-message-success",
			)
		);
	}
}
?>
