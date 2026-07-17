<?php
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['CategoryID'])) {
	global $wpdb;

	$table_devices    = 'Devices';
	$table_history    = 'History_new';
	$table_categories = 'Categories';

	$data = [
		'DeviceID'      => sanitize_text_field($_POST['DeviceID']),
		'CategoryID'    => sanitize_text_field($_POST['CategoryID']),
		'BrandID'       => sanitize_text_field($_POST['BrandID']),
		'Model'         => sanitize_text_field($_POST['Model']),
		'SerialNumber'  => sanitize_text_field($_POST['SerialNumber']),
		'KeywordID'     => sanitize_text_field($_POST['KeywordID']),
		'StatusID'      => sanitize_text_field($_POST['StatusID']),
		'AddDeviceDate' => sanitize_text_field($_POST['AddDeviceDate']),
		'user_email'    => sanitize_email($_POST['user_email'] ?? ''),
		'CreatedAt'     => current_time('mysql'),
		'UpdatedAt'     => current_time('mysql'),
	];

	if (!empty($_POST['edit_id'])) {
		$wpdb->update($table_devices, $data, ['DeviceID' => intval($_POST['edit_id'])]);
		wp_redirect(add_query_arg('updated', '1', wp_get_referer()));
		exit;
	} else {
		// Check if the serial number matches
		if (!empty($data['SerialNumber'])) {
			$existing_serial = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $table_devices WHERE SerialNumber = %s",
				$data['SerialNumber']
			));

			if ($existing_serial > 0) {
				// if the serial number martcheh –> redirect with error
				wp_redirect(add_query_arg('error', 'serial_exists', wp_get_referer()));
				exit;
			}
		}

		
		$wpdb->insert($table_devices, $data);

		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email ?? '';

		$history_data = [
			'DeviceID'    => $data['DeviceID'],
			'Action'      => 'Add Device',
			'Date'        => current_time('mysql'),
			'Description' => "Device ID {$data['DeviceID']} was added to the system",
			'user_email'  => $user_email,
			'CategoryID'  => $data['CategoryID'],
			'Owner'       => '-',
		];
		$wpdb->insert($table_history, $history_data);

		$category_name = $wpdb->get_var($wpdb->prepare(
			"SELECT CategoryName FROM $table_categories WHERE CategoryID = %d",
			intval($data['CategoryID'])
		));

		if ($category_name) {
			$category_slug = sanitize_title($category_name);
			wp_redirect(add_query_arg([
				'success'  => '1',
				'category' => $category_slug
			], wp_get_referer()));
			exit;
		} else {
			wp_redirect(wp_get_referer());
			exit;
		}
	}
}
