<?php
/**
 * Rollback Version Form Settings
 *
 * @package Rollback Version
 */

?>
<div id="fl-uabb-rollback-version-form" class="fl-settings-form uabb-rollback-version-fl-settings-form">

	<h3 class="fl-settings-form-header"><?php esc_html_e( 'Rollback Version', 'uabb' ); ?></h3>

	<form id="uabb-rollback-version-form" action="<?php UABBBuilderAdminSettings::render_form_action( 'uabb-rollback-version' ); ?>" method="post">
		<div class="fl-settings-form-content">
			<div class="uabb-form-setting">
				<?php
				$product_id = 'uabb';
				bsf_get_version_rollback_form( $product_id );
				?>
			</div>
		</div>

	</form>
</div>
