<?php
$connection = new mysqli(
    '',
    '',
    '',
    ''
);

if ($connection->connect_error) {
    die("Error connection db: " . $connection->connect_error);
}
// ===== УСТАНАВЛИВАЕМ КОДИРОВКУ =====
$connection->set_charset("utf8mb4");
// ===================================
?>
