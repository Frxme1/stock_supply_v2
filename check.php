<?php
require_once('wp-load.php');
global $wpdb;

echo "--- History_new Schema ---\n";
$schema = $wpdb->get_results("DESCRIBE History_new");
print_r($schema);

echo "\n--- Last 5 History_new records ---\n";
$records = $wpdb->get_results("SELECT * FROM History_new ORDER BY HistoryID DESC LIMIT 5");
print_r($records);
