<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['portal_owner_id']);
unset($_SESSION['portal_owner_name']);
unset($_SESSION['portal_owner_email']);

header('Location: portal-login.php');
exit;
