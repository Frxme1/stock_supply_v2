<?php
/**
 *  UABB Star Ratting Module front-end CSS php file
 *
 *  @package UABB Star Ratting Module
 */

$rating_title = $settings->rating_title;
$rating       = ! empty( $settings->rating ) ? $settings->rating : 0;
$layout       = 0;
?>
<div class="uabb-rating-content">

<?php
if ( 'bottom' === $settings->star_position ) {
	?>
	<div class="uabb-rating-title"><?php echo wp_kses_post( $rating_title ); ?></div>
	<?php
}
	$rating_scale   = (int) $settings->rating_scale;
	$rating         = (float) $rating > $rating_scale ? $rating_scale : $rating;
	$txt_rating     = $rating . '/' . $rating_scale;
	$floored_rating = (int) $rating;
	$stars_html     = '';
?>
	<div class="uabb-rating" title="<?php echo esc_attr( $txt_rating ); ?>" itemtype="http://schema.org/Rating" itemscope="" itemprop="reviewRating">
	<?php
	$icon = '&#9733;';

	if ( 'outline' === $settings->star_style ) {
		$icon = '&#9734;';
	}
	for ( $stars = 1; $stars <= $settings->rating_scale; $stars++ ) {
		if ( $stars <= $floored_rating ) {
			$stars_html .= '<i class="uabb-star-full">' . $icon . '</i>';
		} elseif ( $floored_rating + 1 === $stars && $rating !== $floored_rating ) {
			$stars_html .= '<i class="uabb-star-' . ( $rating - $floored_rating ) * 10 . '">' . $icon . '</i>';

		} else {
			$stars_html .= '<i class="uabb-star-empty">' . $icon . '</i>';
		}
	}

		echo wp_kses_post( $stars_html );
	?>
	<span itemprop="ratingValue" class="uabb-hidden"><?php echo esc_html( $txt_rating ); ?></span>
	</div>

<?php
if ( 'top' === $settings->star_position ) {
	?>
	<div class="uabb-rating-title"><?php echo wp_kses_post( $rating_title ); ?></div>
	<?php
}
?>
</div>
