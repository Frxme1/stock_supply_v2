<?php
/**
 *  UABB Fancy Text Module front-end JS php file
 *
 *  @package UABB Fancy Text Module
 */

$fancy_text = str_replace( array( "\r\n", "\n", "\r", '<br/>', '<br>' ), '|', $settings->fancy_text );

if ( 'type' === $settings->effect_type ) {

	$strings = $type_speed = $start_delay = $back_speed = $back_delay = $loop = $loop_count = $show_cursor = $cursor_char = ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
	// Order of replacement.
	$txt_order   = array( "\r\n", "\n", "\r", '<br/>', '<br>' );
	$replace     = '|';
	$fancy_text  = addslashes( $settings->fancy_text );
	$str         = str_replace( $txt_order, $replace, $fancy_text );
	$lines       = explode( '|', $str );
	$count_lines = count( $lines );

	$strings = '[';
	foreach ( $lines as $key => $line ) {
		$strings .= '"' . __( trim( htmlspecialchars_decode( wp_strip_all_tags( $line ) ) ), 'uabb' ) . '"'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		if ( ( $count_lines - 1 ) !== $key ) {
			$strings .= ',';
		}
	}
		$strings .= ']';
	$type_speed   = ( ! empty( $settings->typing_speed ) ) ? $settings->typing_speed : 35;
	$start_delay  = ( ! empty( $settings->start_delay ) ) ? $settings->start_delay : 200;
	$back_speed   = ( ! empty( $settings->back_speed ) ) ? $settings->back_speed : 0;
	$back_delay   = ( ! empty( $settings->back_delay ) ) ? $settings->back_delay : 1500;
	$loop         = ( 'no' === $settings->enable_loop ) ? 'false' : 'true';

	if ( 'yes' === $settings->show_cursor ) {
		$show_cursor = 'true';
		$cursor_char = ( ! empty( $settings->cursor_text ) ) ? $settings->cursor_text : '|';
	} else {
		$show_cursor = 'false';
	}
	?>

jQuery( document ).ready(function($) {
	new UABBFancyText({
		id:                 '<?php echo esc_attr( $id ); ?>',
		viewport_position:  90,
		animation:          '<?php echo esc_attr( $settings->effect_type ); ?>',
		strings:            <?php echo $strings; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		typeSpeed:          <?php echo $type_speed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		startDelay:         <?php echo $start_delay; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		backSpeed:          <?php echo $back_speed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		backDelay:          <?php echo $back_delay; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		loop:               <?php echo $loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		showCursor:         <?php echo $show_cursor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		cursorChar:         '<?php echo $cursor_char; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>'
	});
});

	<?php
} elseif ( 'slide_up' === $settings->effect_type ) {
	$speed = $pause = $mouse_pause = ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

	$speed       = ( ! empty( $settings->animation_speed ) ) ? $settings->animation_speed : 200;
	$pause       = ( ! empty( $settings->pause_time ) ) ? $settings->pause_time : 3000;
	$mouse_pause = ( 'yes' === $settings->pause_hover ) ? true : false;
	?>
	jQuery( document ).ready(function($) {
	var wrapper = $('.fl-node-<?php echo esc_attr( $id ); ?>'),
	slide_block = wrapper.find('.uabb-slide-main'),
	slide_block_height = slide_block.find('.uabb-slide_text').height();
	slide_block.height(slide_block_height);

	var UABBFancy_<?php echo esc_attr( $id ); ?> = new UABBFancyText({
		id:                 '<?php echo esc_attr( $id ); ?>',
		viewport_position:  90,
		animation:          '<?php echo esc_attr( $settings->effect_type ); ?>',
		speed:              <?php echo $speed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		pause:              <?php echo $pause; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		mousePause:         Boolean( '<?php echo $mouse_pause; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>' ),
		suffix:             "<?php echo addslashes( $settings->suffix ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>",
		prefix:             "<?php echo addslashes( $settings->prefix ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>",
		alignment:          '<?php echo $settings->alignment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>',
	});

	$( window ).resize(function() {
	UABBFancy_<?php echo esc_attr( $id ); ?>._initFancyText();
	});
	});

	<?php

} else {
	$animation_speed = ( ! empty( $settings->animation_delay ) ) ? $settings->animation_delay : 2500;
	$duration_reveal = ( ! empty( $settings->duration_reveal ) ) ? $settings->duration_reveal : 600;
	$animation_revel = ( ! empty( $settings->animation_revel ) ) ? $settings->letter_delay : 1500;
	$letter_delay    = ( ! empty( $settings->letter_delay ) ) ? $settings->letter_delay : 50;

	?>
	jQuery( document ).ready(function($) {
	new UABBFancyText({
		id:                 '<?php echo esc_attr( $id ); ?>',
		animation:          '<?php echo esc_attr( $settings->effect_type ); ?>',
		animation_speed:     <?php echo $animation_speed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		duration_reveal:     <?php echo $duration_reveal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		animation_revel:     <?php echo $animation_revel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		letter_delay:     <?php echo $letter_delay; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>,
		fancy_text: '<?php echo str_replace( "'", "\'", $fancy_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --- Changing sanitization affecting Fancy Text behavior in the page editor. ?>',
	});
});
<?php }
?>
