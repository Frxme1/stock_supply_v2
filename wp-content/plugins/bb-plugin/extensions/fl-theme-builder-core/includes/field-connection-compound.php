<div class="fl-field-connection fl-field-connection-compound">
	<?php foreach ( $connections as $key => $connection ) : ?>
		<input class="fl-field-connection-value" type="hidden" name="connections[][<?php echo $key; ?>]" value="<?php echo esc_attr( $connection ); ?>" />
	<?php endforeach; ?>
</div>
