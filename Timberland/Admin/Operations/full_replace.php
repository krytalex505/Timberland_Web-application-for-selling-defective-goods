<?php
session_start();
require_once '../modules/db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== ФУНКЦИЯ КОНВЕРТАЦИИ КОДИРОВКИ =====
function convertToUtf8($string) {
    if (empty($string)) return $string;
    
    if (substr($string, 0, 3) == "\xEF\xBB\xBF") {
        $string = substr($string, 3);
    }
    
    $encoding = mb_detect_encoding($string, ['UTF-8', 'CP1251', 'Windows-1251', 'ISO-8859-1'], true);
    
    if ($encoding === 'UTF-8') {
        return $string;
    }
    
    if ($encoding) {
        return mb_convert_encoding($string, 'UTF-8', $encoding);
    }
    
    $converted = @mb_convert_encoding($string, 'UTF-8', 'Windows-1251');
    if ($converted !== false && $converted !== '') {
        return $converted;
    }
    
    return $string;
}
// =========================================

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != UPLOAD_ERR_OK) {
    $_SESSION['sync_error'] = 'Ошибка загрузки файла';
    header('Location: ../List/BySpanTable.php');
    exit;
}

$ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

if ($ext != 'csv') {
    $_SESSION['sync_error'] = 'Пожалуйста, используйте CSV файл.';
    header('Location: ../List/BySpanTable.php');
    exit;
}

if (!is_dir('../temp')) {
    mkdir('../temp', 0777, true);
}

$tmpFile = '../temp/' . time() . '_' . basename($_FILES['excel_file']['name']);
move_uploaded_file($_FILES['excel_file']['tmp_name'], $tmpFile);

function getTable($name) {
    $n = mb_strtolower($name, 'UTF-8');
    if (strpos($n, 'byspan') !== false || strpos($n, 'лдсп') !== false) return 'byspan';
    if (strpos($n, 'евродекор') !== false) return 'egger';
    if ((strpos($n, 'столешниц') !== false || strpos($n, 'столешница') !== false) && 
        (strpos($n, '3050') !== false || strpos($n, '4100') !== false)) return 'cedar';
    if ((strpos($n, 'столешниц') !== false || strpos($n, 'столешница') !== false) && 
        (strpos($n, '600') !== false || strpos($n, '920') !== false || strpos($n, '1200') !== false)) return 'countertopsegger';
    if (strpos($n, 'пластик') !== false || strpos($n, 'plastic') !== false) return 'plastic';
    if (strpos($n, 'kronospan') !== false || strpos($n, 'кроноспан') !== false) return 'kronospan';
    if (strpos($n, 'мдф') !== false || strpos($n, 'mdf') !== false) return 'mdf';
    if (strpos($n, 'скинали') !== false || strpos($n, 'skinali') !== false) return 'skinali';
    if (strpos($n, 'хдф') !== false || strpos($n, 'xdf') !== false) return 'xdf';
    if (strpos($n, 'фанера') !== false || strpos($n, 'plywood') !== false) return 'plywood';
    if (strpos($n, 'кромк') !== false || strpos($n, 'edge') !== false || strpos($n, 'кромка') !== false) return 'edge';
    return null;
}

// Читаем файл с конвертацией
$content = file_get_contents($tmpFile);

if (substr($content, 0, 3) == "\xEF\xBB\xBF") {
    $content = substr($content, 3);
}

$detectedEncoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'CP1251', 'ISO-8859-1'], true);
if ($detectedEncoding === false) {
    $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
} elseif ($detectedEncoding != 'UTF-8') {
    $content = mb_convert_encoding($content, 'UTF-8', $detectedEncoding);
}

$lines = explode("\n", $content);
$excelData = [];

for ($i = 1; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    if (empty($line)) continue;
    
    $row = str_getcsv($line);
    if (count($row) < 1) continue;
    
    $name = convertToUtf8(trim($row[0]));
    if (empty($name)) continue;
    
    $table = getTable($name);
    if (!$table) continue;
    
    $excelData[$table][] = [
        'name' => $name,
        'decor' => convertToUtf8(trim($row[1] ?? '')),
        'character' => convertToUtf8(trim($row[2] ?? '')),
        'note' => convertToUtf8(trim($row[3] ?? '')),
        'size' => convertToUtf8(trim($row[4] ?? '')),
        'cost' => convertToUtf8(trim($row[5] ?? ''))
    ];
}

$allTables = ['byspan', 'egger', 'cedar', 'countertopsegger', 'plastic', 'kronospan', 'mdf', 'skinali', 'xdf', 'plywood', 'edge'];
$results = [];

foreach ($allTables as $table) {
    // Очищаем таблицу
    $connection->query("DELETE FROM `$table`");
    $connection->query("DELETE FROM `images_$table`");
    
    $added = 0;
    
    if (isset($excelData[$table])) {
        foreach ($excelData[$table] as $rec) {
            $stmt = $connection->prepare("INSERT INTO `$table` 
                (Nomenclature, DecorNumber, `Character`, Complaint, Size, Basic, `Date`, FromWho) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Excel Sync')");
            $stmt->bind_param("ssssss", 
                $rec['name'], 
                $rec['decor'], 
                $rec['character'], 
                $rec['note'], 
                $rec['size'], 
                $rec['cost']
            );
            if ($stmt->execute()) {
                $added++;
            }
            $stmt->close();
        }
    }
    
    $results[$table] = ['added' => $added, 'deleted' => 'все старые'];
}

unlink($tmpFile);

$_SESSION['sync_result'] = $results;
$_SESSION['sync_message'] = "🔄 ПОЛНАЯ ЗАМЕНА: все таблицы очищены и загружены заново!";

header('Location: ../List/BySpanTable.php');
exit;
?>