<?php
// ===== ТЕСТОВЫЙ ИМПОРТ =====
require_once('Timberland/Admin/modules/db_connection.php');

// Устанавливаем кодировку
$connection->set_charset("utf8mb4");

$csvFile = 'Timberland/Admin/temp/брак (1).csv';

if (!file_exists($csvFile)) {
    die("❌ Файл не найден");
}

$content = file_get_contents($csvFile);
$lines = explode("\n", $content);

echo "=== ТЕСТОВЫЙ ИМПОРТ ===<br>";
echo "Всего строк: " . count($lines) . "<br><br>";

$count = 0;
for ($i = 1; $i < min(10, count($lines)); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $row = str_getcsv($line);
    if (count($row) < 1) continue;
    
    $name = trim($row[0]);
    if (empty($name)) continue;
    
    echo "Строка $i: " . htmlspecialchars($name) . "<br>";
    
    // Пробуем вставить в БД
    $stmt = $connection->prepare("INSERT INTO byspan (Nomenclature, FromWho) VALUES (?, 'Test')");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo "✅ Вставлено: " . htmlspecialchars($name) . "<br>";
        $count++;
    } else {
        echo "❌ Ошибка: " . $stmt->error . "<br>";
    }
    $stmt->close();
    
    if ($count >= 3) break;
}

echo "<br>✅ Тест завершён";
?>