<?php global $wp_embed; ?>
<?php
$label_tag     = 'a' === $settings->label_tag ? $settings->label_tag . ' href="#" ' : esc_attr( $settings->label_tag );
$tabindex_attr = wp_validate_boolean( $settings->expand_on_tab ) ? 'tabindex="-1"' : ' tabindex="0"';
?>

<div class="fl-accordion fl-accordion-<?php echo sanitize_html_class( $settings->label_size ); ?><?php echo ( $settings->collapse ) ? ' fl-accordion-collapse' : ''; ?>"<?php echo ( ! $settings->collapse ) ? ' multiselectable="true"' : ''; ?>>
	<?php
	if ( 'content' == $settings->source ) {
		for ( $i = 0; $i < count( $settings->items ); $i++ ) {
			if ( ! is_object( $settings->items[ $i ] ) ) {
				continue;
			}

			$label_id            = 'fl-accordion-' . $module->node . '-label-' . $i;
			$icon_id             = 'fl-accordion-' . $module->node . '-icon-' . $i;
			$content_id          = 'fl-accordion-' . $module->node . '-panel-' . $i;
			$item_tab_class      = ( 0 === $i && '1' === $settings->open_first ) ? 'fl-accordion-item-active' : '';
			$item_tab_icon_class = ( 0 === $i && '1' === $settings->open_first ) ? $settings->label_active_icon : $settings->label_icon;
			$expand_text         = ( $i > 0 || ! $settings->open_first ) ? __( 'Expand', 'fl-builder' ) : __( 'Collapse', 'fl-builder' );
			?>
			<div class="fl-accordion-item <?php echo $item_tab_class; ?>"<?php echo ( ! empty( $settings->id ) ) ? ' id="' . sanitize_html_class( $settings->id ) . '-' . $i . '"' : ''; ?>>
				<div class="fl-accordion-button" id="<?php echo 'fl-accordion-' . $module->node . '-tab-' . $i; ?>" aria-controls="<?php echo 'fl-accordion-' . $module->node . '-panel-' . $i; ?>">

					<?php if ( 'left' === $settings->label_icon_position ) : ?>
					<a href="#" id="<?php echo $icon_id; ?>" class="fl-accordion-button-icon fl-accordion-button-icon-left" <?php echo $tabindex_attr; ?>><i class="fl-accordion-button-icon <?php echo $item_tab_icon_class; ?>"><span class="sr-only"><?php echo ( $i > 0 || ! $settings->open_first ) ? 'Expand' : 'Collapse'; ?></span></i></a>
					<?php endif; ?>

					<<?php echo $label_tag; ?> id="<?php echo $label_id; ?>" class="fl-accordion-button-label" tabindex="0" aria-controls="<?php echo $content_id; ?>"><?php echo wp_kses_post( $settings->items[ $i ]->label ); ?></<?php echo esc_attr( $settings->label_tag ); ?>>

					<?php if ( 'right' === $settings->label_icon_position ) : ?>
						<a href="#" id="<?php echo $icon_id; ?>" class="fl-accordion-button-icon fl-accordion-button-icon-right" <?php echo $tabindex_attr; ?>><i class="fl-accordion-button-icon <?php echo $item_tab_icon_class; ?>" title="<?php echo esc_attr( $expand_text ); ?>"><span class="sr-only"><?php echo esc_attr( $expand_text ); ?></span></i></a>
					<?php endif; ?>

				</div>
				<div class="fl-accordion-content fl-clearfix" id="<?php echo $content_id; ?>" aria-labelledby="<?php echo 'fl-accordion-' . $module->node . '-tab-' . $i; ?>" aria-hidden="<?php echo ( $i > 0 || ! $settings->open_first ) ? 'true' : 'false'; ?>">
					<?php
					if ( 'none' === $settings->items[ $i ]->saved_layout ) {
						echo FLBuilderUtils::wpautop( $wp_embed->autoembed( $settings->items[ $i ]->content ), $module );
					} else {
						$post_id = $settings->items[ $i ]->{'saved_' . $settings->items[ $i ]->saved_layout};

						if ( ! empty( $post_id ) ) {
							$module->render_content( $post_id );
						}
					}
					?>
				</div>
			</div>
			<?php
		}
	} elseif ( 'post' == $settings->source ) {
		$settings->exclude_self = 'yes';
		$query                  = FLBuilderLoop::query( $settings );

		if ( $query->have_posts() ) {
			$i = 0;

			while ( $query->have_posts() ) {
				$query->the_post();

				$label_id            = 'fl-accordion-' . $module->node . '-label-' . $i;
				$icon_id             = 'fl-accordion-' . $module->node . '-icon-' . $i;
				$content_id          = 'fl-accordion-' . $module->node . '-panel-' . $i;
				$item_tab_class      = ( 0 === $i && '1' === $settings->open_first ) ? 'fl-accordion-item-active' : '';
				$item_tab_icon_class = ( 0 === $i && '1' === $settings->open_first ) ? $settings->label_active_icon : $settings->label_icon;
				$expand_text         = ( $i > 0 || ! $settings->open_first ) ? __( 'Expand', 'fl-builder' ) : __( 'Collapse', 'fl-builder' );
				?>
				<div class="fl-accordion-item <?php echo $item_tab_class; ?>"<?php echo ( ! empty( $settings->id ) ) ? ' id="' . sanitize_html_class( $settings->id ) . '-' . $i . '"' : ''; ?>>
					<div class="fl-accordion-button" id="<?php echo 'fl-accordion-' . $module->node . '-tab-' . $i; ?>" aria-controls="<?php echo 'fl-accordion-' . $module->node . '-panel-' . $i; ?>">

						<?php if ( 'left' === $settings->label_icon_position ) : ?>
						<a href="#" id="<?php echo $icon_id; ?>" class="fl-accordion-button-icon fl-accordion-button-icon-left" <?php echo $tabindex_attr; ?>><i class="fl-accordion-button-icon <?php echo $item_tab_icon_class; ?>"><span class="sr-only"><?php echo ( $i > 0 || ! $settings->open_first ) ? 'Expand' : 'Collapse'; ?></span></i></a>
						<?php endif; ?>

						<<?php echo $label_tag; ?> id="<?php echo $label_id; ?>" class="fl-accordion-button-label" tabindex="0" aria-controls="<?php echo $content_id; ?>"><?php the_title(); ?></<?php echo esc_attr( $settings->label_tag ); ?>>

						<?php if ( 'right' === $settings->label_icon_position ) : ?>
							<a href="#" id="<?php echo $icon_id; ?>" class="fl-accordion-button-icon fl-accordion-button-icon-right" <?php echo $tabindex_attr; ?> aria-controls="<?php echo $content_id; ?>"><i class="fl-accordion-button-icon <?php echo $item_tab_icon_class; ?>" title="<?php echo esc_attr( $expand_text ); ?>"><span class="sr-only"><?php echo esc_attr( $expand_text ); ?></span></i></a>
						<?php endif; ?>

					</div>
					<div class="fl-accordion-content fl-clearfix" id="<?php echo $content_id; ?>" aria-labelledby="<?php echo 'fl-accordion-' . $module->node . '-tab-' . $i; ?>" aria-hidden="<?php echo ( $i > 0 || ! $settings->open_first ) ? 'true' : 'false'; ?>" role="tabpanel" aria-live="polite">
					<?php

					$post_id = get_the_id();
					if ( ! empty( $settings->content_type ) && 'post_content' === $settings->content_type ) {
						$module->render_content( $post_id );
					} else {
						$module->render_excerpt( $post_id );
						$more_link_text = ( ! empty( $settings->more_link ) && 'show' === $settings->more_link ) ? $settings->more_link_text : '';
						$module->render_more_link( $post_id, $more_link_text );
					}

					?>
					</div>
				</div>
				<?php
				$i++;
			}
			wp_reset_postdata();
		}
	}
	?>
</div>
