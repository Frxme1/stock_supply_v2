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
    $device_id = htmlspecialchars(strip_tags($_POST['DeviceID'] ?? ''), ENT_QUOTES, 'UTF-8');
    $reason = htmlspecialchars(strip_tags($_POST['Reason'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if ($owner_id && !empty($device_id) && !empty($reason)) {
        
        // Ensure device actually exists and belongs to this owner
        $device_check = $wpdb->get_row($wpdb->prepare("SELECT StatusID FROM Devices WHERE DeviceID = %s AND (OwnerID = %d OR OwnerID = %s)", $device_id, $owner_id, (string)$owner_id));
        
        if (!$device_check) {
            send_json_response(['success' => false, 'message' => 'Invalid device or you do not own this device.']);
        }

        // Insert as 'Pending' in Repair_Requests table
        $inserted = $wpdb->insert(
            'Repair_Requests',
            [
                'OwnerID' => $owner_id,
                'DeviceID' => $device_id,
                'Reason' => $reason,
                'Status' => 'Pending',
                'RequestDate' => date('Y-m-d H:i:s')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted) {
            send_json_response(['success' => true, 'message' => 'Repair Request Submitted Successfully! It is now Pending Approval.']);
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
