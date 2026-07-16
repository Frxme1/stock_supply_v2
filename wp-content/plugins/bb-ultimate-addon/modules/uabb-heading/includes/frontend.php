<?php
/**
 *  UABB Heading Module front-end file
 *
 *  @package UABB Heading Module
 */

if ( isset( $settings->link_nofollow ) && UABB_Compatibility::$version_bb_check ) {
	$link_nofollow = $settings->link_nofollow;
} else {
	$link_nofollow = 0;
}
?>

<div class="uabb-module-content uabb-heading-wrapper uabb-heading-align-<?php echo esc_attr( $settings->alignment ); ?> <?php echo esc_attr( ( 'line_text' === $settings->separator_style ) ? $settings->responsive_compatibility : '' ); ?>">
<?php if ( 'yes' === ( $settings->background_text ) ) { ?>
<div class="uabb-background-heading-wrap" data-background-text="<?php echo esc_attr( $settings->bg_heading_text ); ?>">
<?php } ?>
	<?php $module->render_separator( 'top' ); ?>

	<?php if ( 'bottom' === $settings->desc_position ) { ?>

	<<?php echo esc_attr( $settings->tag ); ?> class="uabb-heading">
		<?php if ( ! empty( $settings->link ) ) : ?>
		<a href="<?php echo esc_url( $settings->link ); ?>" title="<?php echo esc_attr( $settings->heading ); ?>" target="<?php echo esc_attr( $settings->link_target ); ?>" <?php BB_Ultimate_Addon_Helper::get_link_rel( $settings->link_target, $link_nofollow, 1 ); ?> aria-label="<?php echo esc_attr__( 'Go to ', 'uabb' ) . esc_url( $settings->link ); ?>">
		<?php endif; ?>
		<span class="uabb-heading-text"><?php echo wp_kses_post( $settings->heading ); ?></span>
		<?php if ( ! empty( $settings->link ) ) : ?>
		</a>
		<?php endif; ?>
	</<?php echo esc_attr( $settings->tag ); ?>>
	<?php } ?>
	<?php if ( 'yes' === $settings->description_option && '' !== $settings->description && 'top' === $settings->desc_position ) { ?>
	<div class="uabb-subheading uabb-text-editor">
			<?php echo wp_kses_post( $settings->description ); ?>
	</div>
	<?php } ?>
	<?php $module->render_separator( 'center' ); ?>
	<?php if ( 'yes' === $settings->description_option && '' !== $settings->description && 'bottom' === $settings->desc_position ) { ?>
	<div class="uabb-subheading uabb-text-editor">
			<?php echo wp_kses_post( $settings->description ); ?>
	</div>
	<?php } ?>
	<?php if ( 'top' === $settings->desc_position ) { ?>

	<<?php echo esc_attr( $settings->tag ); ?> class="uabb-heading">
		<?php if ( ! empty( $settings->link ) ) : ?>
		<a href="<?php echo esc_url( $settings->link ); ?>" title="<?php echo esc_attr( $settings->heading ); ?>" target="<?php echo esc_attr( $settings->link_target ); ?>" <?php BB_Ultimate_Addon_Helper::get_link_rel( $settings->link_target, $link_nofollow, 1 ); ?>>
		<?php endif; ?>
		<span class="uabb-heading-text"><?php echo wp_kses_post( $settings->heading ); ?></span>
		<?php if ( ! empty( $settings->link ) ) : ?>
		</a>
		<?php endif; ?>
	</<?php echo esc_attr( $settings->tag ); ?>>
	<?php } ?>
	<?php $module->render_separator( 'bottom' ); ?>
<?php if ( 'yes' === ( $settings->background_text ) ) { ?>
	</div>
<?php } ?>
</div>
