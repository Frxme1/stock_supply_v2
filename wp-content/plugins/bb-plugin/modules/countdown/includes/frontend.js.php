(function($) {
	$(function() {
		new FLBuilderCountdown({
			id: '<?php echo $id; ?>',
			time: '<?php echo $module->get_time(); ?>',
			type: '<?php echo esc_js( $settings->layout ); ?>',
			redirect: '<?php echo esc_js( $settings->redirect_when ); ?>',
			redirect_url: '<?php echo esc_js( $settings->redirect_url ); ?>',
		});
	});
})(jQuery);
