<?php
echo "1. Начало<br>";

require_once '../modules/db_connection.php';
$connection->set_charset("utf8mb4");
echo "2. БД подключена<br>";

$result = $connection->query("SHOW TABLES");
if ($result) {
    echo "3. Таблицы в БД:<br>";
    while ($row = $result->fetch_array()) {
        echo " - " . $row[0] . "<br>";
    }
} else {
    echo "Ошибка запроса: " . $connection->error . "<br>";
}
?>