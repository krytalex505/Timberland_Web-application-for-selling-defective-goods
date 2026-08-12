<?php
// ===== ЗАЩИТА ПАРОЛЕМ =====
$admin_user = '';
$admin_pass = ''; // поменяй на свой пароль

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] != $admin_user || 
    $_SERVER['PHP_AUTH_PW'] != $admin_pass) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '❌ Доступ запрещен!';
    exit;
}
// ===========================
?>
