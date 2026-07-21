<?php
require 'wp-load.php';
global $wpdb;
$requests = $wpdb->get_results("
    SELECT r.*, o.Nickname, o.FirstName, o.LastName, c.CategoryName 
    FROM Device_Requests r
    LEFT JOIN Owners o ON r.OwnerID = o.OwnerID
    LEFT JOIN Categories c ON r.CategoryID = c.CategoryID
    ORDER BY CASE WHEN r.Status = 'Pending' THEN 1 ELSE 2 END, r.RequestDate DESC
");
print_r($requests);
