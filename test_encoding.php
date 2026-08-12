<?php
require_once('Timberland/Admin/modules/db_connection.php');

$testName = 'Тестовая запись с русским текстом';

$stmt = $connection->prepare("INSERT INTO byspan (Nomenclature, FromWho) VALUES (?, 'Test')");
$stmt->bind_param("s", $testName);
$stmt->execute();

echo "✅ Вставлено: " . $testName . "<br>";

$result = $connection->query("SELECT * FROM byspan ORDER BY id DESC LIMIT 1");
$row = $result->fetch_assoc();
echo "✅ Из БД: " . $row['Nomenclature'];
?>