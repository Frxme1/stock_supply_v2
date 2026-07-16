(function($) {
	$(function() {

		new FLBuilderSubscribeForm({
			id: '<?php echo $id; ?>',
			successAction: '<?php echo esc_js( $settings->success_action ); ?>',
			successUrl: '<?php echo esc_url( trim( $settings->success_url ) ); ?>',
		});
	});

})(jQuery);
