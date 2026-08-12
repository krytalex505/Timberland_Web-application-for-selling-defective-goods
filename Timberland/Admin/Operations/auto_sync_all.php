<?php
/**
 * auto_sync_all.php - Универсальный АВТОМАТИЧЕСКИЙ парсер Excel
 * Раскладывает данные по всем таблицам автоматически
 */

require_once '../modules/db_connection.php';

// ===== ДОБАВЛЯЕМ КОНВЕРТАЦИЮ КОДИРОВКИ =====
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

function convertRowToUtf8($row) {
    if (!is_array($row)) return $row;
    foreach ($row as $key => $value) {
        if (is_string($value)) {
            $row[$key] = convertToUtf8($value);
        }
    }
    return $row;
}
// =============================================

class AutoExcelSync {
    private $connection;
    private $lastSyncFile;
    private $syncInterval = 300; // 5 минут (300 секунд)
    private $logFile;
    
    // Маппинг ключевых слов из наименования в таблицы БД
    private $tableMapping = [
        'byspan' => ['byspan', 'лдсп byspan'],
        'cedar' => ['кедр', 'cedar'],
        'countertopsegger' => ['столешниц', 'столешница', 'countertop'],
        'edge' => ['кромк', 'edge', 'кромка'],
        'egger' => ['egger', 'эггер', 'еггер'],
        'kronospan' => ['kronospan', 'кроноспан'],
        'mdf' => ['mdf', 'мдф'],
        'plywood' => ['plywood', 'фанера'],
        'skinali' => ['skinali', 'скинали'],
        'xdf' => ['xdf', 'хдф']
    ];
    
    public function __construct($connection) {
        $this->connection = $connection;
        $this->lastSyncFile = '../temp/last_full_sync.txt';
        $this->logFile = '../logs/auto_sync.log';
        
        // Создаем папки если нет
        if (!is_dir('../temp')) mkdir('../temp', 0777, true);
        if (!is_dir('../logs')) mkdir('../logs', 0777, true);
    }
    
    /**
     * Проверяет, нужна ли синхронизация (не чаще 1 раза в 5 минут)
     */
    public function needSync() {
        if (!file_exists($this->lastSyncFile)) {
            return true;
        }
        $lastSync = (int)file_get_contents($this->lastSyncFile);
        return (time() - $lastSync) > $this->syncInterval;
    }
    
    /**
     * Определяет таблицу по наименованию
     */
    private function getTableByNomenclature($nomenclature) {
        $nomenclatureLower = mb_strtolower($nomenclature, 'UTF-8');
        
        foreach ($this->tableMapping as $table => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($nomenclatureLower, $keyword, 0, 'UTF-8') !== false) {
                    return $table;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Извлекает размер из наименования
     */
    private function extractSize($text) {
        // Ищем паттерны: 2800x2070x16, 2800*2070*16, 2800х2070х16
        if (preg_match('/(\d+)\s*[xх*]\s*(\d+)(?:\s*[xх*]\s*(\d+))?/ui', $text, $matches)) {
            $width = $matches[1];
            $height = $matches[2];
            $thickness = $matches[3] ?? '';
            
            if ($thickness) {
                return $width . 'x' . $height . 'x' . $thickness;
            }
            return $width . 'x' . $height;
        }
        return '';
    }
    
    /**
     * Извлекает номер декора из наименования
     */
    private function extractDecorNumber($text) {
        // Ищем паттерны: "601 SM", "175 SWO", "463 SWN", "H3704 ST15" и т.д.
        if (preg_match('/(\d{3,})(?:\s+([A-Z]{2,4}))?/i', $text, $matches)) {
            $number = $matches[1];
            $suffix = $matches[2] ?? '';
            return $suffix ? $number . ' ' . $suffix : $number;
        }
        return '';
    }
    
    /**
     * Нормализует стоимость (убирает пробелы, заменяет запятую на точку)
     */
    private function normalizeCost($cost) {
        if (empty($cost)) return '';
        $cost = preg_replace('/\s+/', '', $cost);
        $cost = str_replace(',', '.', $cost);
        return $cost;
    }
    
    /**
     * Очищает размер от лишних символов
     */
    private function cleanSize($size) {
        if (empty($size)) return '';
        // Убираем скобки и лишний текст
        $size = preg_replace('/\s*\([^)]*\)\s*/', '', $size);
        $size = str_replace(['*', 'х'], 'x', $size);
        return trim($size);
    }
    
    /**
     * Записывает лог
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Главная функция синхронизации
     */
    public function sync() {
        $this->log("=== НАЧАЛО СИНХРОНИЗАЦИИ ===");
        
        // Пути к Excel файлу (проверяем несколько вариантов)
        $excelPaths = [
            '\\\\vfs01.office.tland.by\\Interchange\\Пыргарь\\Сайт\\брак.xlsx',
            '/mnt/Interchange/Пыргарь/Сайт/брак.xlsx',
            '../брак.xlsx'
        ];
        
        $excelPath = null;
        foreach ($excelPaths as $path) {
            if (file_exists($path)) {
                $excelPath = $path;
                break;
            }
        }
        
        if (!$excelPath) {
            $this->log("❌ Excel файл не найден ни по одному из путей");
            return ['success' => false, 'message' => 'Excel файл не найден'];
        }
        
        $this->log("📁 Найден Excel файл: $excelPath");
        
        // Проверяем наличие библиотеки
        if (!file_exists('../vendor/autoload.php')) {
            $this->log("❌ Библиотека PhpSpreadsheet не установлена!");
            return ['success' => false, 'message' => 'PhpSpreadsheet не установлен'];
        }
        
        require_once '../vendor/autoload.php';
        use PhpOffice\PhpSpreadsheet\IOFactory;
        
        try {
            // Загружаем Excel
            $spreadsheet = IOFactory::load($excelPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $this->log("📊 Всего строк в Excel: " . count($rows));
            
            // Собираем данные из Excel по таблицам
            $excelData = [];
            $allTables = array_keys($this->tableMapping);
            foreach ($allTables as $table) {
                $excelData[$table] = [];
            }
            
            $processedRows = 0;
            $skippedRows = 0;
            
            // Проходим по строкам Excel (начиная со 2-й, так как 1-я - заголовки)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // ===== КОНВЕРТИРУЕМ ВСЮ СТРОКУ В UTF-8 =====
                $row = convertRowToUtf8($row);
                // ===========================================
                
                // Проверяем, что строка не пустая
                $nomenclature = trim($row[0] ?? '');
                if (empty($nomenclature)) {
                    continue;
                }
                
                // Определяем таблицу
                $table = $this->getTableByNomenclature($nomenclature);
                
                if (!$table) {
                    $this->log("⚠️ Не удалось определить таблицу для: " . mb_substr($nomenclature, 0, 50));
                    $skippedRows++;
                    continue;
                }
                
                // Извлекаем данные
                $decorNumber = trim($row[1] ?? '');
                if (empty($decorNumber)) {
                    $decorNumber = $this->extractDecorNumber($nomenclature);
                }
                
                $size = $this->cleanSize(trim($row[4] ?? ''));
                if (empty($size)) {
                    $size = $this->extractSize($nomenclature);
                }
                
                $record = [
                    'nomenclature' => $nomenclature,
                    'decor_number' => $decorNumber,
                    'character' => trim($row[2] ?? ''),
                    'note' => trim($row[3] ?? ''),
                    'size' => $size,
                    'cost' => $this->normalizeCost(trim($row[5] ?? ''))
                ];
                
                // Уникальный ключ для сравнения
                $key = md5($nomenclature . $decorNumber);
                $excelData[$table][$key] = $record;
                $processedRows++;
            }
            
            $this->log("✅ Обработано строк: $processedRows, пропущено: $skippedRows");
            
            $results = [];
            $totalAdded = 0;
            $totalDeleted = 0;
            $totalSkipped = 0;
            
            // Синхронизируем каждую таблицу
            foreach ($allTables as $table) {
                $records = $excelData[$table] ?? [];
                
                // Получаем существующие записи из БД
                $existingRecords = [];
                $query = "SELECT id, Nomenclature, DecorNumber, FromWho FROM `$table`";
                $result = $this->connection->query($query);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Конвертируем данные из БД для сравнения
                        $nomenclature = convertToUtf8($row['Nomenclature'] ?? '');
                        $decorNumber = convertToUtf8($row['DecorNumber'] ?? '');
                        $key = md5($nomenclature . $decorNumber);
                        $existingRecords[$key] = $row;
                    }
                }
                
                $added = 0;
                $deleted = 0;
                $skipped = 0;
                
                // ДОБАВЛЯЕМ новые записи из Excel
                foreach ($records as $key => $record) {
                    if (!isset($existingRecords[$key])) {
                        $stmt = $this->connection->prepare("
                            INSERT INTO `$table` 
                            (Nomenclature, DecorNumber, `Character`, Complaint, Size, Basic, `Date`, FromWho) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Excel Sync')
                        ");
                        $stmt->bind_param(
                            'ssssss',
                            $record['nomenclature'],
                            $record['decor_number'],
                            $record['character'],
                            $record['note'],
                            $record['size'],
                            $record['cost']
                        );
                        
                        if ($stmt->execute()) {
                            $added++;
                            $this->log("➕ Добавлено в $table: " . mb_substr($record['nomenclature'], 0, 60));
                        } else {
                            $this->log("❌ Ошибка добавления в $table: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
                
                // УДАЛЯЕМ записи, которых нет в Excel (только созданные синхронизацией)
                foreach ($existingRecords as $key => $record) {
                    if (!isset($records[$key])) {
                        // Проверяем, была ли запись создана синхронизацией
                        $fromWho = convertToUtf8($record['FromWho'] ?? '');
                        if (strpos($fromWho, 'Excel Sync') !== false) {
                            $id = $record['id'];
                            
                            // Удаляем связанные изображения
                            $imgTable = "images_$table";
                            $imgQuery = $this->connection->query("SELECT image_path FROM `$imgTable` WHERE {$table}_id = $id");
                            if ($imgQuery && $imgQuery->num_rows > 0) {
                                while ($img = $imgQuery->fetch_assoc()) {
                                    $filePath = '../uploads/' . $img['image_path'];
                                    if (file_exists($filePath)) {
                                        unlink($filePath);
                                        $this->log("🗑️ Удалено фото: $filePath");
                                    }
                                }
                                $this->connection->query("DELETE FROM `$imgTable` WHERE {$table}_id = $id");
                            }
                            
                            // Удаляем запись
                            $this->connection->query("DELETE FROM `$table` WHERE id = $id");
                            $deleted++;
                            $this->log("❌ Удалено из $table (ID: $id): " . mb_substr($record['Nomenclature'], 0, 60));
                        } else {
                            $skipped++;
                            $this->log("⏭️ Пропущено удаление (ручная запись) из $table: " . mb_substr($record['Nomenclature'], 0, 60));
                        }
                    }
                }
                
                $results[$table] = [
                    'added' => $added,
                    'deleted' => $deleted,
                    'skipped' => $skipped,
                    'total_excel' => count($records),
                    'total_db' => count($existingRecords)
                ];
                
                $totalAdded += $added;
                $totalDeleted += $deleted;
                $totalSkipped += $skipped;
                
                if ($added > 0 || $deleted > 0) {
                    $this->log("📊 Таблица $table: +$added, -$deleted, пропущено: $skipped");
                }
            }
            
            // Сохраняем время синхронизации
            file_put_contents($this->lastSyncFile, time());
            
            $this->log("=== СИНХРОНИЗАЦИЯ ЗАВЕРШЕНА ===");
            $this->log("📈 ИТОГО: Добавлено: $totalAdded, Удалено: $totalDeleted, Пропущено: $totalSkipped");
            
            return [
                'success' => true,
                'message' => "Синхронизация завершена",
                'results' => $results,
                'time' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->log("❌ КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }
}

// АВТОМАТИЧЕСКИЙ ЗАПУСК ПРИ ПОДКЛЮЧЕНИИ ФАЙЛА
$autoSync = new AutoExcelSync($connection);

// Проверяем, нужно ли выполнять синхронизацию
if ($autoSync->needSync()) {
    $syncResult = $autoSync->sync();
    
    // Можно добавить email уведомление при ошибке (опционально)
    if (!$syncResult['success']) {
        // error_log("AutoSync Error: " . $syncResult['message']);
    }
}

// Экспортируем объект для использования в других файлах (если нужно)
return $autoSync;
?>