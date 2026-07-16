<?php
/**
 *  UABB Heading Module front-end file
 *
 *  @package UABB Advanced Accordion
 */

?>

<div class="<?php echo ( FLBuilderModel::is_builder_active() ) ? 'uabb-accordion-edit ' : ''; ?>uabb-module-content uabb-adv-accordion 
						<?php
						if ( 'yes' === $settings->collapse ) {
							echo 'uabb-adv-accordion-collapse';}
						?>
" <?php echo 'data-enable_first="' . esc_attr( $settings->enable_first ) . '"'; ?> role="tablist" >
	<?php
	$count = count( $settings->acc_items );
	for ( $i = 0; $i < $count;
	$i++ ) :
		if ( empty( $settings->acc_items[ $i ] ) ) {
			continue;}
		?>
	<div class="uabb-adv-accordion-item"
		<?php
		if ( ! empty( $settings->id ) ) {
			echo ' id="' . sanitize_html_class( $settings->id ) . '-' . esc_attr( $i ) . '"';}
		?>
	data-index="<?php echo esc_attr( $i ); ?>">
		<div class="uabb-adv-accordion-button uabb-adv-accordion-button<?php echo esc_attr( $id ); ?> uabb-adv-<?php echo esc_attr( $settings->icon_position ); ?>-text" aria-selected="false" role="tab" tabindex="0" aria-expanded="true" aria-controls="expandable">
			<?php echo wp_kses_post( $module->render_icon( 'before' ) ); ?>
			<<?php echo esc_attr( $settings->tag_selection ); ?> class="uabb-adv-accordion-button-label" ><?php echo esc_html( sanitize_text_field( $settings->acc_items[ $i ]->acc_title ) ); ?></<?php echo esc_attr( $settings->tag_selection ); ?>>
			<?php echo wp_kses_post( $module->render_icon( 'after' ) ); ?>
		</div>
		<div class="uabb-adv-accordion-content uabb-adv-accordion-content<?php echo esc_attr( $id ); ?> fl-clearfix <?php echo ( 'content' === $settings->acc_items[ $i ]->content_type ) ? 'uabb-accordion-desc uabb-text-editor' : ''; ?>" aria-expanded="true" >
			<?php
			if ( isset( $settings->acc_items[ $i ]->acc_content ) && 'content' === $settings->acc_items[ $i ]->content_type && '' !== $settings->acc_items[ $i ]->acc_content && '' === $settings->acc_items[ $i ]->ct_content ) {
				global $wp_embed;
				echo wpautop( $wp_embed->autoembed( $settings->acc_items[ $i ]->acc_content ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitizing breaks UI in certain cases.
			} else {
				echo $module->get_accordion_content( $settings->acc_items[ $i ] ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitizing breaks UI in certain cases.
			}
			?>
		</div>
	</div>
	<?php endfor; ?>
</div>
