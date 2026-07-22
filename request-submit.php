<?php
// Separate endpoint for AJAX submission to avoid WordPress redirects
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

define('SHORTINIT', true);
require_once(dirname(__FILE__) . '/wp-load.php');
global $wpdb;

function send_json_response($data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = intval($_POST['OwnerID'] ?? 0);
    $category_id = intval($_POST['CategoryID'] ?? 0);
    $requested_device_id = htmlspecialchars(strip_tags($_POST['RequestedDeviceID'] ?? ''), ENT_QUOTES, 'UTF-8');
    $reason = htmlspecialchars(strip_tags($_POST['Reason'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if ($owner_id && $category_id && !empty($requested_device_id) && !empty($reason)) {
        $inserted = $wpdb->insert(
            'Device_Requests',
            [
                'OwnerID' => $owner_id,
                'CategoryID' => $category_id,
                'Reason' => $reason,
                'Status' => 'Pending',
                'AssignedDeviceID' => $requested_device_id,
                'RequestDate' => date('Y-m-d H:i:s')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted) {
            $request_id = $wpdb->insert_id;
            
            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('RequestSubmitted', $request_id, $owner_id, "Device: $requested_device_id - $reason");
            }

            send_json_response(['success' => true, 'message' => 'Request Submitted Successfully!']);
        } else {
            $err = $wpdb->last_error;
            send_json_response(['success' => false, 'message' => 'Database Error: ' . ($err ?: 'Unable to insert record.')]);
        }
    } else {
        send_json_response(['success' => false, 'message' => 'Missing Information. Please fill in all fields.']);
    }
} else {
    send_json_response(['success' => false, 'message' => 'Invalid Request Method']);
}
