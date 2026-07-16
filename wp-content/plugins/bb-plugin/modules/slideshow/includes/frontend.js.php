<?php

$source = $module->get_source();

if ( ! empty( $source ) ) :

	?>
YUI({'logExclude': { 'yui': true } }).use('fl-slideshow', function(Y){

	if( null === Y.one('.fl-node-<?php echo $id; ?> .fl-slideshow-container') ) {
		return;
	}

	var oldSlideshow = Y.one('.fl-node-<?php echo $id; ?> .fl-slideshow-container .fl-slideshow'),
		newSlideshow = new Y.FL.Slideshow({
			autoPlay: <?php echo esc_js( $settings->auto_play ); ?>,
			<?php if ( 'url' == $settings->click_action ) : ?>
			clickAction: 'url',
			clickActionUrl: '<?php echo esc_js( $settings->click_action_url ); ?>',
			<?php endif; ?>
			color: '<?php echo esc_js( $settings->color ); ?>',
			<?php if ( $settings->crop ) : ?>
			crop: true,
			<?php endif; ?>
			height: <?php echo esc_js( $settings->height ); ?>,
			imageNavEnabled: <?php echo esc_js( $settings->image_nav ); ?>,
			likeButtonEnabled: <?php echo esc_js( $settings->facebook ); ?>,
			<?php if ( 'none' != $settings->nav_type ) : ?>
			navButtons: [<?php $module->get_nav_buttons(); ?>],
			navButtonsLeft: [<?php $module->get_nav_buttons_left(); ?>],
			navButtonsRight: [<?php $module->get_nav_buttons_right(); ?>],
			<?php endif; ?>
			<?php if ( $settings->nav_overlay ) : ?>
			navOverlay: true,
			<?php endif; ?>
			navPosition: '<?php echo esc_js( $settings->nav_position ); ?>',
			navType: '<?php echo esc_js( $settings->nav_type ); ?>',
			<?php if ( $settings->nav_overlay ) : ?>
			overlayHideDelay: <?php echo intval( $settings->overlay_hide_delay ) * 1000; ?>,
			overlayHideOnMousemove: <?php echo esc_js( $settings->overlay_hide ); ?>,
			<?php endif; ?>
			pinterestButtonEnabled: <?php echo esc_js( $settings->pinterest ); ?>,
			protect: <?php echo esc_js( $settings->protect ); ?>,
			randomize: <?php echo esc_js( $settings->randomize ); ?>,
			<?php if ( $global_settings->responsive_enabled ) : ?>
			responsiveThreshold: <?php echo $global_settings->responsive_breakpoint; ?>,
			<?php endif; ?>
			source: [{<?php echo $source; ?>}],
			speed: <?php echo intval( $settings->speed ) * 1000; ?>,
			tweetButtonEnabled: <?php echo esc_js( $settings->twitter ); ?>,
			thumbsImageHeight: <?php echo esc_js( $settings->thumbs_size ); ?>,
			thumbsImageWidth: <?php echo esc_js( $settings->thumbs_size ); ?>,
			transition: '<?php echo esc_js( $settings->transition ); ?>',
			transitionDuration: <?php echo esc_js( $settings->transitionDuration ); // @codingStandardsIgnoreLine ?>
		});

	if(oldSlideshow) {
		oldSlideshow.remove(true);
	}

	newSlideshow.render('.fl-node-<?php echo $id; ?> .fl-slideshow-container');

	Y.one('.fl-node-<?php echo $id; ?> .fl-slideshow-container').setStyle( 'height', 'auto' );
});
<?php endif; ?>
