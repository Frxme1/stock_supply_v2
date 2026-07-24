<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle CSV Export
add_action('init', 'handle_device_csv_export');

function handle_device_csv_export() {
    if (!isset($_GET['export_csv']) || $_GET['export_csv'] !== 'device') {
        return;
    }

    if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('edit_posts'))) {
        wp_die('Unauthorized access');
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'export_device_csv_action')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $table_device_wn = 'DevicesWithNames';

    // Same filter logic as formDevice.php
    $search = isset($_GET['device_search']) ? stock_supply_parse_search_query($_GET['device_search']) : '';
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    $filter_department = isset($_GET['filter_department']) ? sanitize_text_field($_GET['filter_department']) : '';
    $filter_brand = isset($_GET['filter_brand']) ? sanitize_text_field($_GET['filter_brand']) : '';
    $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

    $search_sql = "WHERE 1=1";
    if (!empty($category_filter)) {
        $search_sql .= $wpdb->prepare(" AND Category = %s", $category_filter);
    }
    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $search_sql .= $wpdb->prepare(" AND (
            DeviceID LIKE %s OR 
            Model LIKE %s OR 
            SerialNumber LIKE %s OR 
            Owner LIKE %s OR
            Nickname LIKE %s
        )", $like, $like, $like, $like, $like);
    }
    if (!empty($filter_status)) {
        $search_sql .= $wpdb->prepare(" AND Status = %s", $filter_status);
    }
    if (!empty($filter_brand)) {
        $search_sql .= $wpdb->prepare(" AND Brand = %s", $filter_brand);
    }
    if (!empty($filter_department) && $filter_status !== 'Available') {
        $search_sql .= $wpdb->prepare(" AND Department = %s", $filter_department);
    }

    $rows = $wpdb->get_results("SELECT * FROM $table_device_wn $search_sql ORDER BY DeviceID DESC");

    // Output CSV
    $filename = 'devices_export_' . date('Y-m-d_H-i') . '.csv';
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Add BOM for UTF-8 to correctly display in Excel
    fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

    // Headers
    fputcsv($output, [
        'DeviceID', 'Brand', 'Model', 'Category', 'Keyword', 
        'SerialNumber', 'Status', 'Owner', 'Nickname', 
        'Department', 'AddDeviceDate', 'ReceiveDate', 'ReturnDate', 'RepairDate'
    ]);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row->DeviceID,
            $row->Brand,
            $row->Model,
            $row->Category,
            $row->Keyword,
            $row->SerialNumber,
            $row->Status,
            $row->Owner,
            $row->Nickname,
            $row->Department,
            $row->AddDeviceDate,
            $row->ReceiveDate,
            $row->ReturnDate,
            $row->RepairDate
        ]);
    }

    fclose($output);
    exit;
}
