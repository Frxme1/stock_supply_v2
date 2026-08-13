<?php
$file = 'wp-content/themes/astra-child/functions.php';
$content = file_get_contents($file);

$start_str = "function custom_wp_login_head() {";
$end_str = "add_action('login_footer', 'custom_wp_login_footer');";

$start_pos = strpos($content, $start_str);
$end_pos = strpos($content, $end_str) + strlen($end_str);

if ($start_pos !== false && $end_pos !== false) {
    $new_content = "function custom_wp_login_head() {
    ?>
    <link href=\"https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap\" rel=\"stylesheet\">
    <link rel=\"stylesheet\" href=\"<?php echo get_stylesheet_directory_uri(); ?>/css/custom-login.css?v=<?php echo time(); ?>\">
    <?php
}
add_action('login_head', 'custom_wp_login_head');

function custom_wp_login_footer() {
    ?>
    <script src=\"<?php echo get_stylesheet_directory_uri(); ?>/js/custom-login.js?v=<?php echo time(); ?>\"></script>
    <?php
}
add_action('login_footer', 'custom_wp_login_footer');";
    
    $final = substr($content, 0, $start_pos) . $new_content . substr($content, $end_pos);
    file_put_contents($file, $final);
    echo "Success";
} else {
    echo "Fail";
}
