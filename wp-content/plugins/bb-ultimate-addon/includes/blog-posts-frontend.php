<?php
/**
 * Blog Posts frontend content
 *
 * @package Blog Post
 */

?>
<div class="uabb-blog-post-content">
	<?php
	FLPostGridModule::schema_meta();
	$content = apply_filters( 'uabb_custom_post_layout_html', $settings->uabb_custom_post_layout->html );
	echo do_shortcode( FLThemeBuilderFieldConnections::parse_shortcodes( $content ) );
	?>
</div>
