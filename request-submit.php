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

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Request Submitted Successfully!']);
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
