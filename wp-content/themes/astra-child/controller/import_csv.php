<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle CSV Import
add_action('admin_post_import_device_csv', 'handle_device_csv_import');

function handle_device_csv_import() {
    if (!isset($_POST['import_csv_nonce']) || !wp_verify_nonce($_POST['import_csv_nonce'], 'import_device_csv_nonce')) {
        wp_die('Invalid nonce specified');
    }

    if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('edit_posts'))) {
        wp_die('Unauthorized access');
    }

    if (empty($_FILES['csv_file']['tmp_name'])) {
        wp_die('No file uploaded');
    }

    $file_type = wp_check_filetype($_FILES['csv_file']['name']);
    if (!in_array($file_type['ext'], ['csv', 'txt'], true)) {
        wp_die('Invalid file type. Only CSV files are permitted.');
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    if (!$handle) {
        wp_die('Could not open file');
    }

    // Read BOM if present
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    global $wpdb;
    $table_devices = 'Devices';
    $table_history = 'History_new';
    
    // Get headers
    $headers = fgetcsv($handle);
    if (!$headers) {
        wp_die('Empty CSV file');
    }
    
    // Remove BOM from first header if still present (fallback)
    $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
    $headers = array_map('trim', $headers);

    // Find indices
    $idx = array_flip($headers);
    
    if (!isset($idx['Brand']) || !isset($idx['Category']) || !isset($idx['Model'])) {
        wp_die('CSV is missing required columns: Brand, Category, Model');
    }

    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email ?? '';

    $success_count = 0;
    $error_count = 0;
    $error_messages = [];

    // Pre-load lookup tables to memory
    $brands = $wpdb->get_results("SELECT BrandID, BrandName FROM Brands");
    $brand_map = [];
    foreach ($brands as $b) {
        $brand_map[strtolower(trim($b->BrandName))] = $b->BrandID;
    }

    $categories = $wpdb->get_results("SELECT CategoryID, CategoryName, Prefix FROM Categories");
    $category_map = [];
    $category_prefix_map = [];
    foreach ($categories as $c) {
        $category_map[strtolower(trim($c->CategoryName))] = $c->CategoryID;
        $category_prefix_map[$c->CategoryID] = $c->Prefix;
    }

    $keywords = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords");
    $keyword_map = [];
    foreach ($keywords as $k) {
        $keyword_map[strtolower(trim($k->KeywordName))] = $k->KeywordID;
    }

    $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
    if (!$available_status_id) $available_status_id = 1;

    // To keep track of the latest device ID sequence locally during import
    $latest_device_numbers = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (empty(array_filter($row))) continue; // skip empty rows

        $brand_str = trim($row[$idx['Brand']] ?? '');
        $category_str = trim($row[$idx['Category']] ?? '');
        $model_str = trim($row[$idx['Model']] ?? '');
        $serial_str = trim($row[$idx['SerialNumber']] ?? '');
        $date_str = trim($row[$idx['AddDeviceDate']] ?? '');
        $keyword_str = trim($row[$idx['Keyword']] ?? '');

        // 1. Validation
        if (empty($brand_str) || empty($category_str) || empty($model_str)) {
            $error_count++;
            $error_messages[] = "Row skipped: Missing Brand, Category, or Model";
            continue;
        }

        $brand_id = $brand_map[strtolower($brand_str)] ?? null;
        $category_id = $category_map[strtolower($category_str)] ?? null;
        
        if (!$brand_id) {
            $error_count++;
            $error_messages[] = "Row skipped: Brand '$brand_str' not found in database.";
            continue;
        }
        if (!$category_id) {
            $error_count++;
            $error_messages[] = "Row skipped: Category '$category_str' not found in database.";
            continue;
        }

        $keyword_id = $keyword_map[strtolower($keyword_str)] ?? null;

        // Date logic
        if (empty($date_str) || $date_str === '0000-00-00') {
            $date_str = date('Y-m-d');
        } else {
            // Check if format is standard (optional: attempt to format)
            $parsed_date = strtotime($date_str);
            if ($parsed_date) {
                $date_str = date('Y-m-d', $parsed_date);
            } else {
                $date_str = date('Y-m-d');
            }
        }

        // Serial Number check
        if (!empty($serial_str)) {
            $existing_serial = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_devices WHERE SerialNumber = %s",
                $serial_str
            ));
            if ($existing_serial > 0) {
                $error_count++;
                $error_messages[] = "Row skipped: Serial Number '$serial_str' already exists.";
                continue;
            }
        }

        // Generate DeviceID
        $prefix = $category_prefix_map[$category_id];
        
        // Find last number for this prefix if not already found in this run
        if (!isset($latest_device_numbers[$category_id])) {
            $last_device = $wpdb->get_var($wpdb->prepare(
                "SELECT DeviceID FROM {$table_devices}
                 WHERE DeviceID LIKE %s
                 ORDER BY CAST(SUBSTRING(DeviceID, LENGTH(%s) + 1) AS UNSIGNED) DESC
                 LIMIT 1",
                $prefix . '%',
                $prefix
            ));
            $last_number = ($last_device) ? intval(substr($last_device, strlen($prefix))) : 0;
            $latest_device_numbers[$category_id] = $last_number;
        }

        // Increment for this new device
        $latest_device_numbers[$category_id]++;
        $new_device_id = $prefix . str_pad($latest_device_numbers[$category_id], 3, '0', STR_PAD_LEFT);

        // Insert Device
        $data = [
            'DeviceID'      => $new_device_id,
            'CategoryID'    => $category_id,
            'BrandID'       => $brand_id,
            'Model'         => $model_str,
            'SerialNumber'  => $serial_str,
            'KeywordID'     => $keyword_id,
            'StatusID'      => $available_status_id,
            'AddDeviceDate' => $date_str,
            'user_email'    => $user_email,
            'CreatedAt'     => current_time('mysql'),
            'UpdatedAt'     => current_time('mysql'),
        ];

        $inserted = $wpdb->insert($table_devices, $data);
        
        if ($inserted) {
            // Insert History
            $history_data = [
                'DeviceID'    => $new_device_id,
                'Action'      => 'Add Device',
                'Date'        => current_time('mysql'),
                'Description' => "Device ID {$new_device_id} was added via CSV Import",
                'user_email'  => $user_email,
                'CategoryID'  => $category_id,
                'Owner'       => '-',
            ];
            $wpdb->insert($table_history, $history_data);
            $success_count++;
        } else {
            $error_count++;
            $error_messages[] = "Database error inserting row for model '$model_str'.";
        }
    }

    fclose($handle);

    // Store results in transient or session to display on next page load
    $result_msg = "Import Complete! Successfully added: $success_count devices. Failed: $error_count.";
    if ($error_count > 0) {
        $result_msg .= " Check the format and ensure Brands/Categories exist.";
    }

    // Redirect back to referring page with query args
    $redirect_url = add_query_arg([
        'import_status' => 'done',
        'import_success' => $success_count,
        'import_error' => $error_count
    ], wp_get_referer());
    
    wp_redirect($redirect_url);
    exit;
}
