<?php
/**
 *  UABB Woo - Mini Cart Module front-end file
 *
 *  @package UABBWooMiniCartModule
 */

if ( null === WC()->cart ) {
	return;
}

$style          = $settings->cart_style;
$floating_class = ( ( 'floating' === $settings->display_position && 'dropdown' !== $style ) ? 'uabb-mini-cart-style-floating uabb-mini-cart-floating-' . $settings->floating_align : '' );
$offcanvas_pos  = ( 'off-canvas' === $settings->cart_style && isset( $settings->offcanvas_position ) ) ? 'uabb-offcanvas-position-at-' . $settings->offcanvas_position : '';
?>
	<?php
	if ( 'floating' === $settings->display_position && FLBuilderModel::is_builder_active() && 'dropdown' !== $style ) {
		?>
	<div class="uabb-builder-msg" style="text-align: center;">
		<h5><?php esc_html_e( 'UABB Woo - Mini Cart - ', 'uabb' ); ?><?php echo esc_html( $id ); ?></h5>
		<p><?php esc_html_e( 'Click here to edit the "Floating Woo - Mini Cart Button" settings. This text will not be visible on frontend.', 'uabb' ); ?></p>
	</div>
		<?php
	}
	?>
<div class="uabb-woo-mini-cart <?php echo esc_attr( $floating_class ); ?>">
	<div class="uabb-mini-cart-btn">
		<a class="uabb-cart-btn-contents" aria-label="<?php esc_attr_e( 'View your cart items', 'uabb' ); ?>" tabindex="0">
			<span class="uabb-cart-button-wrap uabb-badge-style-<?php echo esc_attr( $settings->badge_position ); ?>">
			<?php if ( 'icon' === $settings->btn_style || 'icon-text' === $settings->btn_style ) { ?>

				<i class="<?php echo esc_attr( $settings->cart_icon ); ?> uabb-cart-btn-icon"></i>

				<?php
			}
			if ( 'text' === $settings->btn_style || 'icon-text' === $settings->btn_style ) {
				?>

				<span class="uabb-mini-cart-text"><?php echo esc_html( $settings->cart_text ); ?></span>
				<?php if ( 'yes' === $settings->show_subtotal ) { ?>
					<span class="uabb-mc__btn-subtotal">
						<?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?>
					</span>
				<?php } ?>
				<?php
			}
			if ( 'yes' === $settings->show_badge ) {
				?>
				<span class="uabb-cart-btn-badge">
					<?php echo wp_kses_post( WC()->cart->get_cart_contents_count() ); ?>
				</span>
			<?php } ?>
			</span>
		</a>
	</div>

	<?php if ( 'modal' === $settings->cart_style || 'off-canvas' === $settings->cart_style ) { ?>
		<div class="uabb-cart-<?php echo esc_attr( $style ); ?>-wrap uabb-cart-<?php echo esc_attr( $style ); ?>-wrap-close">
	<?php } ?>
			<div class="uabb-mini-cart-content uabb-cart-style-<?php echo esc_attr( $style ); ?> uabb-cart-<?php echo esc_attr( $style ); ?>-close <?php echo esc_attr( $offcanvas_pos ); ?>">
				<?php if ( 'modal' === $settings->cart_style || 'off-canvas' === $settings->cart_style ) { ?>
					<div class="uabb-cart-<?php echo esc_attr( $style ); ?>__close-btn"><i class="fa fa-times"></i></div>
				<?php } ?>
				<div class="uabb-mini-cart-title">
					<p><?php echo wp_kses_post( $settings->cart_title ); ?></p>
				</div>
				<div class="uabb-mini-cart-header">
					<div class="uabb-mini-cart-icon-wrap">
						<?php if ( 'text' === $settings->btn_style && isset( $settings->in_cart_icon ) ) { ?>
							<i class="<?php echo esc_attr( $settings->in_cart_icon ); ?> uabb-mini-cart-header-icon"></i>
						<?php } else { ?>
							<i class="<?php echo esc_attr( $settings->cart_icon ); ?> uabb-mini-cart-header-icon"></i>
						<?php } ?>
						<span class="uabb-mini-cart-header-badge">
							<?php echo wp_kses_post( WC()->cart->get_cart_contents_count() ); ?>
						</span>
					</div>
					<span class="uabb-mini-cart-header-text">
						Sub-Total: <?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?>
					</span>
				</div>
				<div class="uabb-mini-cart-items"><?php woocommerce_mini_cart(); ?></div>
				<div class="uabb-mini-cart-message"><?php echo esc_html( wp_strip_all_tags( $settings->cart_msg ) ); ?></div>
			</div>
			<div class="uabb-overlay"></div>
	<?php if ( 'modal' === $settings->cart_style || 'off-canvas' === $settings->cart_style ) { ?>

		</div>
	<?php } ?>
</div>
