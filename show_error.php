<?php
// Включаем показ всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Пытаемся подключить админку
require_once('Timberland/Admin/List/BySpanTable.php');
?>