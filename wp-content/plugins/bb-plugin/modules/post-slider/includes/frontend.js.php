<?php

	// set defaults
	$autoplay = ! empty( $settings->speed ) ? esc_js( $settings->speed * 1000 ) : '1000';
	$speed = ! empty( $settings->transitionDuration ) ? esc_js( $settings->transitionDuration * 1000 ) : '1000'; // @codingStandardsIgnoreLine

?>

(function($) {

	$(function() {

		new FLBuilderPostSlider({
			id: '<?php echo $id; ?>',
		<?php if ( isset( $settings->navigation ) && 'yes' == $settings->navigation ) : ?>
			navigationControls: true,
		<?php endif; ?>
			settings: {
			<?php if ( isset( $settings->transition ) ) : ?>
				mode: '<?php echo esc_js( $settings->transition ); ?>',
			<?php endif; ?>
			<?php if ( isset( $settings->pagination ) && 'no' == $settings->pagination ) : ?>
				pager: false,
			<?php endif; ?>
			<?php if ( isset( $settings->auto_play ) ) : ?>
				auto: <?php echo esc_js( $settings->auto_play ); ?>,
			<?php else : ?>
				auto: false,
			<?php endif; ?>
				pause: <?php echo $autoplay; ?>,
				speed: <?php echo $speed; ?>,
			<?php if ( isset( $settings->slider_loop ) ) : ?>
				infiniteLoop: <?php echo esc_js( $settings->slider_loop ); ?>,
			<?php else : ?>
				infiniteLoop: false,
			<?php endif; ?>
				adaptiveHeight: true,
				controls: false,
				autoHover: true,
				ariaLive: false,
				onSlideBefore: function(ele, oldIndex, newIndex) {
					$('.fl-node-<?php echo $id; ?> .fl-post-slider-navigation a').addClass('disabled');
					$('.fl-node-<?php echo $id; ?> .bx-controls .bx-pager-link').addClass('disabled');
				},
				onSlideAfter: function( ele, oldIndex, newIndex ) {
					$('.fl-node-<?php echo $id; ?> .fl-post-slider-navigation a').removeClass('disabled');
					$('.fl-node-<?php echo $id; ?> .bx-controls .bx-pager-link').removeClass('disabled');
				}
			}
		});

	});

})(jQuery);
