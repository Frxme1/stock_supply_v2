(function($) {

	$(function() {
		new FLBuilderSearchForm({
			id: '<?php echo $id; ?>',
			layout: '<?php echo esc_js( $settings->layout ); ?>',
			btnAction: '<?php echo esc_js( $settings->btn_action ); ?>',
			result: '<?php echo esc_js( $settings->result ); ?>',
			showCloseBtn: <?php echo 'show' == $settings->fs_close_button ? 'true' : 'false'; ?>,
		});
	});

})(jQuery);
