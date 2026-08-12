<?php
// ===== ПРОВЕРКА КОДИРОВКИ CSV =====
$csvFile = 'Timberland/Admin/temp/брак (1).csv'; // ← Поменяй на своё имя

if (!file_exists($csvFile)) {
    die("❌ Файл не найден: $csvFile");
}

$content = file_get_contents($csvFile);
$encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'CP1251', 'ISO-8859-1'], true);

echo "🔍 Кодировка файла: " . ($encoding ? $encoding : 'не определена') . "<br>";

// Показываем первые 500 символов
echo "<br>📄 Первые 500 символов:<br>";
echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";

// Проверяем на BOM
if (substr($content, 0, 3) == "\xEF\xBB\xBF") {
    echo "⚠️ Есть BOM (его нужно убрать)<br>";
} else {
    echo "✅ BOM нет<br>";
}
?>