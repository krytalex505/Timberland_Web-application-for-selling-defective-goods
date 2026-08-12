<?php
// Включаем логирование ошибок
ini_set('log_errors', 1);
ini_set('error_log', '../temp/php_errors.log');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Тест ошибок<br>";

// Намеренно вызываем ошибку
require_once 'NOT_EXISTS_FILE.php';

echo "Если вы это видите - ошибок нет";
?>