<?php
/**
 * Real-Time Stock Monitor — AJAX Endpoint
 * 
 * Returns JSON data for the live stock dashboard:
 * - Device counts by status
 * - Device counts by category × status
 * - Low-stock alerts (Available < 3 per category)
 */

if (!defined('ABSPATH')) {
    exit;
}

function stock_realtime_data_handler()
{
    global $wpdb;
    $table = 'DevicesWithNames';

    // 1. Counts by status
    $status_rows = $wpdb->get_results(
        "SELECT Status, COUNT(*) as cnt FROM {$table} GROUP BY Status",
        OBJECT
    );
    $by_status = [];
    foreach ($status_rows as $row) {
        $by_status[$row->Status] = intval($row->cnt);
    }

    // Ensure all known statuses present
    foreach (['Available', 'In Use', 'Maintenance', 'Retired'] as $s) {
        if (!isset($by_status[$s])) {
            $by_status[$s] = 0;
        }
    }

    // 2. Counts by category × status
    $cat_status_rows = $wpdb->get_results(
        "SELECT Category, Status, COUNT(*) as cnt FROM {$table} GROUP BY Category, Status",
        OBJECT
    );
    $by_category_status = [];
    $categories = ['Monitor', 'Laptop', 'Accessories'];
    $statuses = ['Available', 'In Use', 'Maintenance', 'Retired'];

    // Initialize
    foreach ($categories as $cat) {
        $by_category_status[$cat] = [];
        foreach ($statuses as $s) {
            $by_category_status[$cat][$s] = 0;
        }
    }
    foreach ($cat_status_rows as $row) {
        if (isset($by_category_status[$row->Category])) {
            $by_category_status[$row->Category][$row->Status] = intval($row->cnt);
        }
    }

    // 3. Low-stock alerts (Available < 3 per category)
    $threshold = 3;
    $low_stock_alerts = [];
    foreach ($categories as $cat) {
        $avail = $by_category_status[$cat]['Available'];
        if ($avail < $threshold) {
            $total_in_cat = array_sum($by_category_status[$cat]);
            $low_stock_alerts[] = [
                'category'  => $cat,
                'available' => $avail,
                'total'     => $total_in_cat,
                'threshold' => $threshold,
            ];
        }
    }

    // 4. Total
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

    wp_send_json_success([
        'by_status'          => $by_status,
        'by_category_status' => $by_category_status,
        'low_stock_alerts'   => $low_stock_alerts,
        'total'              => intval($total),
        'timestamp'          => current_time('c'),
    ]);
}

add_action('wp_ajax_stock_realtime_data', 'stock_realtime_data_handler');
add_action('wp_ajax_nopriv_stock_realtime_data', 'stock_realtime_data_handler');
