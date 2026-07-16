<?php
/**
 *  UABB Advanced Tabs Module front-end file
 *
 *  @package UABB Advanced Tabs Module
 */

?>
(function($) {

	$(function() {
		new UABBTabs({
			id: '<?php echo esc_attr( $id ); ?>'
		});
	});

})(jQuery);
<?php
$settings->responsive_breakpoint = ( isset( $settings->responsive_breakpoint ) && '' !== $settings->responsive_breakpoint ) ? $settings->responsive_breakpoint : $global_settings->responsive_breakpoint;
if ( 'accordion' === $settings->responsive ) {
	?>
		jQuery(document).ready(function() {
				var breakpoint_val = parseInt( '<?php echo esc_attr( $settings->responsive_breakpoint ); ?>' ),
						$tabsNode = jQuery('.fl-node-<?php echo esc_attr( $id ); ?> .uabb-tabs'),
						prev_width = jQuery(window).width();

				if( prev_width <= breakpoint_val ) {
						<?php if ( 'yes' === $settings->enable_first ) { ?>
						$tabsNode.find('.uabb-content-current .uabb-content').slideUp('normal');
						<?php } ?>
				}

				jQuery(window).on('resize.uabbTabs<?php echo esc_attr( $id ); ?>', function() {
						var new_width = jQuery(window).width();
						if ( prev_width > breakpoint_val && new_width <= breakpoint_val ) {
								<?php if ( 'yes' === $settings->enable_first ) { ?>
								$tabsNode.find('.uabb-content-current .uabb-content').slideUp('normal');
								<?php } ?>
						}
						prev_width = new_width;
				});
		});
		<?php
}
?>
