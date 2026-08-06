<?php
require_once 'wp-load.php';

$audit_page = get_page_by_path('audit');
if ($audit_page) {
    $meta = get_post_meta($audit_page->ID);
    file_put_contents('audit_meta.txt', print_r($meta, true));
}

$qt_page = get_page_by_path('quick-transfer');
if ($qt_page) {
    $meta = get_post_meta($qt_page->ID);
    file_put_contents('qt_meta.txt', print_r($meta, true));
}
echo "Done";
