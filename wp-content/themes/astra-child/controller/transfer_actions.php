<?php
if (!defined('ABSPATH')) {
    exit;
}

// ─── AJAX: Lookup device info ───────────────────────────────────────────────
function quick_transfer_lookup_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'quick_transfer_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }

    global $wpdb;
    $raw = sanitize_text_field($_POST['device_id']);
    $device_id = function_exists('stock_supply_parse_search_query') ? stock_supply_parse_search_query($raw) : $raw;

    if (empty($device_id)) {
        wp_send_json_error(['message' => 'No Device ID provided.']);
    }

    $device = $wpdb->get_row($wpdb->prepare("
        SELECT d.*, b.BrandName, c.CategoryName, s.StatusName, dep.DepartmentName, o.Nickname AS OwnerName
        FROM Devices d
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Statuses s ON d.StatusID = s.StatusID
        LEFT JOIN Departments dep ON d.DepartmentID = dep.DepartmentID
        LEFT JOIN Owners o ON d.OwnerID = o.OwnerID
        WHERE d.DeviceID = %s
    ", $device_id));

    if (!$device) {
        wp_send_json_error(['message' => "Device \"{$device_id}\" not found."]);
    }

    wp_send_json_success([
        'DeviceID'       => $device->DeviceID,
        'Model'          => $device->Model,
        'BrandName'      => $device->BrandName,
        'CategoryName'   => $device->CategoryName,
        'SerialNumber'   => $device->SerialNumber,
        'StatusName'     => $device->StatusName,
        'DepartmentID'   => $device->DepartmentID,
        'DepartmentName' => $device->DepartmentName ?: 'Unassigned',
        'OwnerID'        => $device->OwnerID,
        'OwnerName'      => $device->OwnerName ?: '-',
    ]);
}
add_action('wp_ajax_quick_transfer_lookup', 'quick_transfer_lookup_ajax');

// ─── AJAX: Get owners by department ─────────────────────────────────────────
function quick_transfer_owners_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'quick_transfer_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }

    global $wpdb;
    $dept_id = intval($_POST['department_id']);

    $owners = $wpdb->get_results($wpdb->prepare("
        SELECT o.OwnerID, o.Nickname, d.DepartmentName
        FROM Owners o
        LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
        WHERE o.DepartmentID = %d
        ORDER BY o.Nickname ASC
    ", $dept_id));

    wp_send_json_success($owners);
}
add_action('wp_ajax_quick_transfer_owners', 'quick_transfer_owners_ajax');

// ─── AJAX: Execute transfer ─────────────────────────────────────────────────
function quick_transfer_execute_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'quick_transfer_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }

    global $wpdb;
    $device_id = sanitize_text_field($_POST['device_id']);
    $new_dept_id = intval($_POST['new_department_id']);
    $new_owner_id = intval($_POST['new_owner_id'] ?? 0);

    if (empty($device_id) || empty($new_dept_id)) {
        wp_send_json_error(['message' => 'Missing Device ID or Department.']);
    }

    // Get current device info
    $device = $wpdb->get_row($wpdb->prepare("SELECT * FROM Devices WHERE DeviceID = %s", $device_id));
    if (!$device) {
        wp_send_json_error(['message' => 'Device not found.']);
    }

    // Get department names
    $old_dept = $wpdb->get_var($wpdb->prepare("SELECT DepartmentName FROM Departments WHERE DepartmentID = %d", $device->DepartmentID));
    $new_dept = $wpdb->get_var($wpdb->prepare("SELECT DepartmentName FROM Departments WHERE DepartmentID = %d", $new_dept_id));
    $old_dept = $old_dept ?: 'Unassigned';

    // Get owner info
    $new_owner_name = '-';
    $position_id = null;
    if ($new_owner_id > 0) {
        $owner_info = $wpdb->get_row($wpdb->prepare("SELECT Nickname, PositionID FROM Owners WHERE OwnerID = %d", $new_owner_id));
        $new_owner_name = $owner_info->Nickname ?? '-';
        $position_id = $owner_info->PositionID ?? null;
    }

    // Update device
    $update_data = ['DepartmentID' => $new_dept_id];
    if ($new_owner_id > 0) {
        $update_data['OwnerID'] = $new_owner_id;
        $update_data['PositionID'] = $position_id;
        // Set status to In Use
        $in_use_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");
        if ($in_use_id) {
            $update_data['StatusID'] = $in_use_id;
        }
    }

    $wpdb->update('Devices', $update_data, ['DeviceID' => $device_id]);

    // Insert history
    $current_user = wp_get_current_user();
    $desc = "Transfer: {$old_dept} → {$new_dept}";
    if ($new_owner_id > 0) {
        $old_owner = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $device->OwnerID));
        $desc .= " | Owner: " . ($old_owner ?: '-') . " → {$new_owner_name}";
    }

    $wpdb->insert('History_new', [
        'DeviceID'    => $device_id,
        'Action'      => 'Transfer',
        'Date'        => current_time('mysql'),
        'Description' => $desc,
        'user_email'  => $current_user->user_email ?? 'unknown',
        'CategoryID'  => $device->CategoryID,
        'Owner'       => $new_owner_name,
    ]);

    wp_send_json_success([
        'device_id' => $device_id,
        'model'     => $device->Model,
        'old_dept'  => $old_dept,
        'new_dept'  => $new_dept,
        'new_owner' => $new_owner_name,
        'timestamp' => current_time('Y-m-d H:i:s'),
    ]);
}
add_action('wp_ajax_quick_transfer_execute', 'quick_transfer_execute_ajax');
