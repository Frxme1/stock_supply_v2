<?php
if (!defined('ABSPATH')) {
    exit;
}

function process_audit_scan_ajax() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'audit_mode_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }
    
    global $wpdb;
    $raw_device_id = sanitize_text_field($_POST['device_id']);
    $device_id = function_exists('stock_supply_parse_search_query') ? stock_supply_parse_search_query($raw_device_id) : $raw_device_id;
    $department_id = intval($_POST['department_id']);
    
    if (empty($device_id) || empty($department_id)) {
        wp_send_json_error(['message' => 'Missing Device ID or Department.']);
        return;
    }

    $table_devices = 'Devices';
    $device = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $device_id));

    if (!$device) {
        wp_send_json_error(['message' => "Device {$device_id} not found."]);
        return;
    }
    
    $status = 'Verified Present';
    $is_wrong_department = false;
    $warning_message = '';
    
    if (empty($device->DepartmentID)) {
        // Device has no department (e.g. Available or Retired)
        $is_wrong_department = true;
        $status = 'Unassigned';
        
        $device_status = $wpdb->get_var($wpdb->prepare("SELECT StatusName FROM Statuses WHERE StatusID = %d", $device->StatusID));
        $warning_message = "Device is not currently in use (Status: {$device_status}).";
    } elseif ($device->DepartmentID != $department_id) {
        $is_wrong_department = true;
        $status = 'Misplaced';
        
        // Fetch department names for the message
        $expected_dept = $wpdb->get_var($wpdb->prepare("SELECT DepartmentName FROM Departments WHERE DepartmentID = %d", $device->DepartmentID));
        $found_dept = $wpdb->get_var($wpdb->prepare("SELECT DepartmentName FROM Departments WHERE DepartmentID = %d", $department_id));
        
        $warning_message = "Device belongs to {$expected_dept}, but found in {$found_dept}.";
    }

    // Update LastAuditDate and LastAuditStatus
    $wpdb->update(
        $table_devices,
        [
            'LastAuditDate' => current_time('mysql'),
            'LastAuditStatus' => $status
        ],
        ['DeviceID' => $device_id]
    );
    
    // Insert history
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email ?? 'unknown@domain.com';
    $owner_nickname = null;
    if (!empty($device->OwnerID)) {
        $owner_nickname = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $device->OwnerID));
    }
    
    $desc = $is_wrong_department ? "Audit: Found in wrong department. {$warning_message}" : "Audit: Verified Present";
    
    $wpdb->insert('History_new', [
        'DeviceID'    => $device_id,
        'Action'      => 'Audit Scan',
        'Date'        => current_time('mysql'),
        'Description' => $desc,
        'user_email'  => $user_email,
        'CategoryID'  => $device->CategoryID,
        'Owner'       => $owner_nickname ?? '-'
    ]);

    // Fetch device category/brand for display
    $category = $wpdb->get_var($wpdb->prepare("SELECT CategoryName FROM Categories WHERE CategoryID = %d", $device->CategoryID));
    
    wp_send_json_success([
        'device_id' => $device->DeviceID,
        'model' => $device->Model,
        'category' => $category,
        'is_wrong_department' => $is_wrong_department,
        'warning' => $warning_message,
        'timestamp' => current_time('Y-m-d H:i:s')
    ]);
}
add_action('wp_ajax_process_audit_scan', 'process_audit_scan_ajax');

function get_audit_summary_ajax() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'audit_mode_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }
    
    global $wpdb;
    $department_id = intval($_POST['department_id']);
    
    if (empty($department_id)) {
        wp_send_json_error(['message' => 'Missing Department.']);
        return;
    }
    
    $table_devices = 'Devices';
    $today = date('Y-m-d', current_time('timestamp'));
    
    // Query missing devices: Devices belonging to this department that haven't been audited today
    $query_missing = $wpdb->prepare("
        SELECT d.DeviceID, d.Model, c.CategoryName, d.LastAuditDate
        FROM $table_devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Statuses s ON d.StatusID = s.StatusID
        WHERE d.DepartmentID = %d 
        AND s.StatusName NOT IN ('Retired', 'Lost')
        AND (DATE(d.LastAuditDate) != %s OR d.LastAuditDate IS NULL)
    ", $department_id, $today);
    
    $missing_devices = $wpdb->get_results($query_missing);
    
    // Query verified devices: Devices audited today for this department
    $query_verified = $wpdb->prepare("
        SELECT d.DeviceID, d.Model, c.CategoryName, d.LastAuditDate, d.LastAuditStatus
        FROM $table_devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        WHERE d.DepartmentID = %d 
        AND DATE(d.LastAuditDate) = %s
    ", $department_id, $today);
    
    $verified_devices = $wpdb->get_results($query_verified);
    
    wp_send_json_success([
        'missing' => $missing_devices,
        'verified' => $verified_devices
    ]);
}
add_action('wp_ajax_get_audit_summary', 'get_audit_summary_ajax');
