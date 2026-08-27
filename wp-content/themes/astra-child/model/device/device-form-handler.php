<?php
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['CategoryID'])) {
	if (!is_user_logged_in() || !isset($_POST['_add_device_nonce']) || !wp_verify_nonce($_POST['_add_device_nonce'], 'add_device_nonce')) {
		wp_die('Security check failed. Please refresh the page and try again.');
	}

	global $wpdb;

	$table_devices    = 'Devices';
	$table_history    = 'History_new';
	$table_categories = 'Categories';

	$raw_brand_id   = trim($_POST['BrandID'] ?? '');
	$brand_name_in  = trim($_POST['BrandName'] ?? '');
	$new_brand_name = trim($_POST['new_brand_name'] ?? '');

	$brand_string = !empty($new_brand_name) ? $new_brand_name : (!empty($brand_name_in) ? $brand_name_in : $raw_brand_id);

	if (is_numeric($brand_string) && intval($brand_string) > 0) {
		$brand_id = intval($brand_string);
	} elseif (!empty($brand_string)) {
		$existing_brand_id = $wpdb->get_var($wpdb->prepare("SELECT BrandID FROM Brands WHERE LOWER(BrandName) = LOWER(%s)", $brand_string));
		if ($existing_brand_id) {
			$brand_id = intval($existing_brand_id);
		} else {
			// Insert new brand into database
			$inserted = $wpdb->insert('Brands', ['BrandName' => ucfirst($brand_string)]);
			if (!$inserted) {
				$next_brand_id = intval($wpdb->get_var("SELECT MAX(BrandID) FROM Brands")) + 1;
				$wpdb->query($wpdb->prepare("INSERT INTO Brands (BrandID, BrandName) VALUES (%d, %s)", $next_brand_id, ucfirst($brand_string)));
			}
			$brand_id = intval($wpdb->get_var($wpdb->prepare("SELECT BrandID FROM Brands WHERE LOWER(BrandName) = LOWER(%s)", $brand_string)));
		}
	} else {
		$brand_id = intval($raw_brand_id);
	}

	$current_user = wp_get_current_user();
	$user_email   = $current_user->user_email ?? '';

	$data = [
		'DeviceID'      => sanitize_text_field($_POST['DeviceID']),
		'CategoryID'    => intval($_POST['CategoryID']),
		'BrandID'       => $brand_id,
		'Model'         => sanitize_text_field($_POST['Model']),
		'SerialNumber'  => sanitize_text_field($_POST['SerialNumber']),
		'KeywordID'     => !empty($_POST['KeywordID']) ? intval($_POST['KeywordID']) : null,
		'StatusID'      => !empty($_POST['StatusID']) ? intval($_POST['StatusID']) : 1,
		'AddDeviceDate' => sanitize_text_field($_POST['AddDeviceDate']),
		'user_email'    => $user_email,
		'CreatedAt'     => current_time('mysql'),
		'UpdatedAt'     => current_time('mysql'),
	];

	if (!empty($_POST['edit_id'])) {
		$edit_id = sanitize_text_field($_POST['edit_id']);
		$wpdb->update($table_devices, $data, ['DeviceID' => $edit_id]);

		// Log history for edit
		$wpdb->insert($table_history, [
			'DeviceID'    => $data['DeviceID'],
			'Action'      => 'Update Device',
			'Date'        => current_time('mysql'),
			'Description' => "Device ID {$data['DeviceID']} information updated",
			'user_email'  => $user_email,
			'CategoryID'  => $data['CategoryID'],
			'Owner'       => '-',
		]);

		wp_redirect(add_query_arg('updated', '1', wp_get_referer() ?: home_url('/home/')));
		exit;
	} else {
		// Check if the serial number matches
		if (!empty($data['SerialNumber'])) {
			$existing_serial = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $table_devices WHERE SerialNumber = %s",
				$data['SerialNumber']
			));

			if ($existing_serial > 0) {
				// if the serial number matches –> redirect with error
				wp_redirect(add_query_arg('error', 'serial_exists', wp_get_referer() ?: home_url('/add-device/')));
				exit;
			}
		}

		$wpdb->insert($table_devices, $data);

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
			], wp_get_referer() ?: home_url('/' . $category_slug . '/')));
			exit;
		} else {
			wp_redirect(wp_get_referer() ?: home_url('/home/'));
			exit;
		}
	}
}
