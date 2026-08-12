<?php
session_start();
require_once '../modules/db_connection.php';

// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Список таблиц для экспорта
$tables = [
    'byspan' => 'ЛДСП BySpan',
    'mdf' => 'МДФ',
    'countertopsegger' => 'Столешницы Эггер',
    'edge' => 'Кромка',
    'egger' => 'Эггер ДСП',
    'kronospan' => 'Кроноспан',
    'plywood' => 'Фанера',
    'skinali' => 'Скинали',
    'xdf' => 'ХДФ',
    'cedar' => 'Столешницы Кедр'
];

// Определяем тип экспорта
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Функция для экспорта одной таблицы
function exportTableToExcel($connection, $table, $sheetName) {
    $result = $connection->query("SELECT * FROM `$table` ORDER BY id DESC");
    
    $html = '<table border="1">';
    
    // Заголовки
    $html .= '<thead><tr>';
    $html .= '<th>ID</th>';
    $html .= '<th>Наименование</th>';
    $html .= '<th>Номер декора</th>';
    $html .= '<th>Характер брака</th>';
    $html .= '<th>Примечание</th>';
    $html .= '<th>Полезный размер (мм)</th>';
    $html .= '<th>Стоимость реализации с НДС</th>';
    $html .= '<th>Дата добавления</th>';
    $html .= '<th>Кем добавлено</th>';
    $html .= '</tr></thead>';
    
    $html .= '<tbody>';
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $row['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($row['Nomenclature'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['DecorNumber'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['Character'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['Complaint'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['Size'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['Basic'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['Date'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['FromWho'] ?? '') . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="9" style="text-align:center;">Нет данных</td></tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    return $html;
}

// Если экспорт одной таблицы
if ($type !== 'all' && isset($tables[$type])) {
    $sheetName = $tables[$type];
    $html = '<h2>' . $sheetName . '</h2>';
    $html .= exportTableToExcel($connection, $type, $sheetName);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><title>' . $sheetName . '</title></head>';
    echo '<body>';
    echo $html;
    echo '</body></html>';
    exit;
}

// Экспорт всех таблиц
$html = '<h1>Экспорт всех данных ' . date('Y-m-d H:i:s') . '</h1>';

foreach ($tables as $table => $sheetName) {
    $html .= '<br><br>';
    $html .= '<h2>' . $sheetName . '</h2>';
    $html .= exportTableToExcel($connection, $table, $sheetName);
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="export_all_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"><title>Экспорт всех данных</title>';
echo '<style>th{background:#1e293b;color:#fff;padding:8px;}td{padding:8px;border:1px solid #ccc;}</style>';
echo '</head><body>';
echo $html;
echo '</body></html>';
?>