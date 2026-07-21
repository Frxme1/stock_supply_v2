<?php
// Separate endpoint for AJAX submission to avoid WordPress redirects
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('SHORTINIT', true);
require_once( dirname(__FILE__) . '/wp-load.php' );
global $wpdb;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = intval($_POST['OwnerID'] ?? 0);
    $device_id = htmlspecialchars(strip_tags($_POST['DeviceID'] ?? ''), ENT_QUOTES, 'UTF-8');
    $reason = htmlspecialchars(strip_tags($_POST['Reason'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    if ($owner_id && !empty($device_id) && !empty($reason)) {
        
        // Ensure device actually exists and belongs to this owner
        $device_check = $wpdb->get_row($wpdb->prepare("SELECT StatusID FROM Devices WHERE DeviceID = %s AND OwnerID = %d", $device_id, $owner_id));
        
        if (!$device_check) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid device or you do not own this device.']);
            exit;
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
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Repair Request Submitted Successfully! It is now Pending Approval.']);
            exit;
        } else {
            $err = $wpdb->last_error;
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $err]);
            exit;
        }
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Missing Information. Please fill in all fields.']);
        exit;
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}
