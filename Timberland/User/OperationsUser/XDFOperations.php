<?php
require '../modulesUser/db_connection.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Получение всех данных из таблицы
$listGroup = $connection->query('SELECT * FROM `xdf`;') or die("Ошибка получения данных: " . $connection->error);


?>
