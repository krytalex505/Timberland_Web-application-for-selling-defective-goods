<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../modules/db_connection.php');
$connection->set_charset("utf8mb4");
session_start();
require_once('../modules/auth.php');
// Обработка результатов синхронизации
if (isset($_SESSION['sync_result'])) {
    $syncResult = $_SESSION['sync_result'];
    unset($_SESSION['sync_result']);
}
if (isset($_SESSION['sync_error'])) {
    $syncError = $_SESSION['sync_error'];
    unset($_SESSION['sync_error']);
}
if (isset($_SESSION['sync_message'])) {
    $syncMessage = $_SESSION['sync_message'];
    unset($_SESSION['sync_message']);
}

// Получение статистики
if (isset($_GET['action']) && $_GET['action'] == 'getStats') {
    header('Content-Type: application/json');
    
    $totalResult = $connection->query("SELECT COUNT(*) as total FROM byspan");
    $total = $totalResult->fetch_assoc()['total'];
    
    $activeResult = $connection->query("SELECT COUNT(*) as active FROM byspan WHERE Nomenclature IS NOT NULL AND Nomenclature != ''");
    $active = $activeResult->fetch_assoc()['active'];
    
    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'active' => (int)$active
    ]);
    exit;
}

// Обработка AJAX-запроса для получения данных с пагинацией
if (isset($_GET['action']) && $_GET['action'] == 'getData') {
    header('Content-Type: application/json');
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
    
    $offset = ($page - 1) * $limit;
    
    $where = "";
    if (!empty($search)) {
        $where = " WHERE Nomenclature LIKE '%$search%' 
                   OR DecorNumber LIKE '%$search%' 
                   OR Character LIKE '%$search%' 
                   OR Complaint LIKE '%$search%'
                   OR Size LIKE '%$search%'
                   OR Basic LIKE '%$search%'";
    }
    
    $countResult = $connection->query("SELECT COUNT(*) as total FROM byspan $where");
    $total = $countResult->fetch_assoc()['total'];
    
    $query = "SELECT * FROM byspan $where ORDER BY id ASC LIMIT $offset, $limit";
    $result = $connection->query($query);
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $imageQuery = $connection->query("SELECT image_path FROM images_byspan WHERE byspan_id = $id");
        $images = [];
        while ($img = $imageQuery->fetch_assoc()) {
            $images[] = '../uploads/' . $img['image_path'];
        }
        $row['images'] = $images;
        $data[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}

// ======= ФУНКЦИИ =======
function handleFlexibleDateInput($input) {
    $input = trim($input);
    if ($input === '') {
        return '';
    }
    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $input)) {
        list($day, $month, $year) = explode('.', $input);
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            die("Ошибка: введена несуществующая дата ($input).");
        }
        $converted = "$year-$month-$day";
        $minDate = date('Y') . '-01-01';
        $maxDate = date('Y-m-d');
        if ($converted < $minDate || $converted > $maxDate) {
            die("Дата должна быть от " . date('d.m.Y', strtotime($minDate)) . " до " . date('d.m.Y') . ".");
        }
        return $converted;
    }
    if (preg_match('/\d{6,}/', $input)) {
        die("Неверный формат даты. Введите дату в формате ДД.ММ.ГГГГ или текст.");
    }
    return $input;
}

function validateDate($dateInput) {
    if (empty($dateInput)) {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
        $convertedDate = $dateInput;
    } elseif (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dateInput)) {
        list($day, $month, $year) = explode('.', $dateInput);
        $convertedDate = "$year-$month-$day";
    } else {
        die("Ошибка: неверный формат даты. Ожидается ДД.ММ.ГГГГ или ГГГГ-ММ-ДД.");
    }
    return $convertedDate;
}

// ======= СОЗДАНИЕ ЗАПИСИ =======
if (isset($_POST['create'])) {
    $Nomenclature = $_POST['Nomenclature'] ?? '';
    $DecorNumber = $_POST['DecorNumber'] ?? '';
    $Character = $_POST['Character'] ?? '';
    $Complaint = $_POST['Complaint'] ?? '';
    $Size = $_POST['Size'] ?? '';
    $Basic = $_POST['Basic'] ?? '';

    $Group = '';
    $Unit = '';
    $accounting = '';
    $Sheets = '';
    $Cost = '';
    $Date = null;
    $FromWho = '';
    $DateComplaint = '';
    $Percent = '';
    $Compensation = '';
    $Sum = '';

    $imagePaths = [];

    if (isset($_FILES['ImagePath']) && is_array($_FILES['ImagePath']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['ImagePath']['name'] as $key => $name) {
            if ($_FILES['ImagePath']['error'][$key] === UPLOAD_ERR_OK) {
                $imagePath = uniqid('img_', true) . '_' . basename($name);
                $uploadFile = $uploadDir . $imagePath;
                if (move_uploaded_file($_FILES['ImagePath']['tmp_name'][$key], $uploadFile)) {
                    $imagePaths[] = $imagePath;
                } else {
                    die("Ошибка загрузки изображения: " . $name);
                }
            }
        }
    }

    $stmt = $connection->prepare("INSERT INTO `byspan` 
        (Nomenclature, DecorNumber, `Character`, `Complaint`, `Size`, `Basic`,
         `Group`, `Unit`, `accounting`, `Sheets`, `Cost`, `Date`, `FromWho`, `DateComplaint`, `Percent`, `Compensation`, `Sum`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssssssssssss", 
        $Nomenclature, $DecorNumber, $Character, $Complaint, $Size, $Basic,
        $Group, $Unit, $accounting, $Sheets, $Cost, $Date, $FromWho, $DateComplaint, $Percent, $Compensation, $Sum);

    if ($stmt->execute()) {
        $byspan_id = $stmt->insert_id;

        if (!empty($imagePaths)) {
            $imgStmt = $connection->prepare("INSERT INTO images_byspan (byspan_id, image_path) VALUES (?, ?)"); 
            foreach ($imagePaths as $path) {
                $imgStmt->bind_param("is", $byspan_id, $path);
                $imgStmt->execute();
            }
            $imgStmt->close();
        }

        header("Location: BySpanTable.php");
        exit();
    } else {
        die("Ошибка создания записи: " . $stmt->error);
    }
}

// ======= УДАЛЕНИЕ =======
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $imageQuery = $connection->query("SELECT image_path FROM images_byspan WHERE byspan_id = $id");
    if ($imageQuery) {
        while ($img = $imageQuery->fetch_assoc()) {
            $imagePath = '../uploads/' . $img['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
    
    $connection->query("DELETE FROM images_byspan WHERE byspan_id = $id");
    $connection->query("DELETE FROM byspan WHERE id = $id");

    header("Location: BySpanTable.php");
    exit;
}

// ======= РЕДАКТИРОВАНИЕ =======
if (isset($_POST['edit'])) {
    if (!isset($_POST['Id']) || !is_numeric($_POST['Id'])) {
        die("Ошибка: Некорректный ID.");
    }

    $id = intval($_POST['Id']);
    
    $Nomenclature = $_POST['Nomenclature'] ?? '';
    $DecorNumber = $_POST['DecorNumber'] ?? '';
    $Character = $_POST['Character'] ?? '';
    $Complaint = $_POST['Complaint'] ?? '';
    $Size = $_POST['Size'] ?? '';
    $Basic = $_POST['Basic'] ?? '';

    $query = "UPDATE `byspan` SET 
        Nomenclature = ?, 
        DecorNumber = ?, 
        `Character` = ?, 
        `Complaint` = ?, 
        `Size` = ?, 
        `Basic` = ? 
        WHERE Id = ?";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssssssi", $Nomenclature, $DecorNumber, $Character, $Complaint, $Size, $Basic, $id);

    if (isset($_FILES['ImagePath']) && is_array($_FILES['ImagePath']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['ImagePath']['name'] as $key => $name) {
            if ($_FILES['ImagePath']['error'][$key] === UPLOAD_ERR_OK) {
                $imagePath = time() . '_' . basename($name);
                $uploadFile = $uploadDir . $imagePath;
                if (move_uploaded_file($_FILES['ImagePath']['tmp_name'][$key], $uploadFile)) {
                    $imgStmt = $connection->prepare("INSERT INTO images_byspan (byspan_id, image_path) VALUES (?, ?)");     
                    $imgStmt->bind_param("is", $id, $imagePath);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }
    }

    if ($stmt->execute()) {
        header("Location: BySpanTable.php");
        exit();
    } else {
        die("Ошибка обновления: " . $stmt->error);
    }
}

// ===== СЧЁТЧИК ПЕРЕХОДОВ =====
$pageName = 'byspan';
$connection->query("INSERT INTO page_views (page_name, admin_views, user_views) VALUES ('$pageName', 1, 0) ON DUPLICATE KEY UPDATE admin_views = admin_views + 1");

$statsResult = $connection->query("SELECT admin_views, user_views FROM page_views WHERE page_name = '$pageName'");
$adminStats = ['admin_views' => 0, 'user_views' => 0];
if ($statsResult && $statsResult->num_rows > 0) {
    $adminStats = $statsResult->fetch_assoc();
}

$totalRecordsCount = $connection->query("SELECT COUNT(*) as total FROM byspan")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ивацевичи BySpan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #212529 !important;
            margin-bottom: 0;
        }
        
        .sticky-bar {
            position: sticky;
            top: 56px;
            background: white;
            z-index: 1020;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            border-bottom: 2px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .sticky-bar h3 { 
            margin: 0; 
            font-size: 24px;
            font-weight: 600; 
            color: #1e293b;
            letter-spacing: -0.3px;
        }
        
        .stats-center {
            display: flex;
            gap: 30px;
            align-items: center;
            background: #f8fafc;
            padding: 8px 20px;
            border-radius: 40px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.03);
        }
        
        .stat-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #1e293b;
        }
        
        .stat-badge .label {
            font-weight: 500;
            color: #64748b;
        }
        
        .stat-badge .value {
            font-weight: 700;
            color: #1e293b;
            background: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 15px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .stat-badge.admin .value {
            background: #1e293b;
            color: white;
        }
        
        .stat-badge.user .value {
            background: #10b981;
            color: white;
        }
        
        .sticky-bar input {
            width: 280px;
            padding: 10px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 40px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .sticky-bar input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
            width: 300px;
        }
        
        .sticky-bar .btn-primary { 
            padding: 10px 24px;
            background: #1e293b;
            border: none;
            border-radius: 40px;
            font-weight: 500;
            font-size: 14px;
        }
        .sticky-bar .btn-primary:hover { 
            background: #0f172a;
            transform: translateY(-1px);
        }
        
        .sticky-bar .btn-success { 
            padding: 10px 24px;
            background: #10b981;
            border: none;
            border-radius: 40px;
            font-weight: 500;
            font-size: 14px;
            color: white;
        }
        .sticky-bar .btn-success:hover { 
            background: #059669;
            transform: translateY(-1px);
        }
        
        .sticky-bar .btn-danger { 
            padding: 10px 24px;
            background: #ef4444;
            border: none;
            border-radius: 40px;
            font-weight: 500;
            font-size: 14px;
            color: white;
        }
        .sticky-bar .btn-danger:hover { 
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .table-container {
            height: calc(100vh - 130px);
            overflow-y: auto;
            overflow-x: auto;
            margin: 0;
            padding: 0 20px 20px 20px;
        }
        
        .custom-table {
            border-collapse: collapse;
            font-size: 15px;
            text-align: center;
            width: 100%;
            min-width: 1200px;
        }
        .custom-table th, .custom-table td {
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
        }
        .custom-table th {
            background-color: #1e293b;
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .custom-table thead th {
            position: sticky;
            top: 0;
            background-color: #1e293b;
            z-index: 1010;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .custom-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .custom-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        @media (max-width: 768px) {
            .custom-table { font-size: 13px; }
            .custom-table th, .custom-table td { padding: 10px 12px; }
            .sticky-bar { 
                padding: 12px 16px;
                top: 56px;
                flex-direction: column;
            }
            .stats-center { 
                order: 1;
                width: 100%;
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }
            .sticky-bar h3 { order: 0; font-size: 18px; }
            .sticky-bar > div:last-child { order: 2; width: 100%; justify-content: center; }
            .sticky-bar input { width: 100%; }
            .table-container {
                height: calc(100vh - 200px);
                padding: 0 12px 12px 12px;
            }
        }
        
        .btn-group {
            display: flex;
            gap: 6px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-sm { 
            padding: 5px 12px; 
            font-size: 12px;
            border-radius: 20px;
        }
        .btn-warning {
            background: #f59e0b;
            border: none;
            color: white;
        }
        .btn-warning:hover {
            background: #d97706;
        }
        .btn-danger {
            background: #ef4444;
            border: none;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-info {
            background: #e2e8f0;
            border: none;
            color: #1e293b;
        }
        .btn-info:hover {
            background: #cbd5e1;
        }
        
        .btn-secondary.disabled, .btn-secondary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        body {
            overflow: hidden;
        }
        
        .modal {
            z-index: 1050 !important;
        }
        
        .modal-backdrop {
            z-index: 1040 !important;
        }
        
        .alert-fixed {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .sort-header {
            cursor: pointer;
            user-select: none;
        }
        .sort-header:hover {
            background: #2d3a4e;
        }
        .sort-icon {
            font-size: 11px;
            opacity: 0.6;
            margin-left: 5px;
        }
        .sort-header:hover .sort-icon {
            opacity: 1;
        }
    </style>
</head>
<body>
<?php include '../modules/navbar.php'; ?>

<?php if (isset($syncMessage)): ?>
<div class="alert alert-info alert-dismissible fade show alert-fixed">
    <strong>ℹ️ Сообщение:</strong> <?= htmlspecialchars($syncMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($syncError)): ?>
<div class="alert alert-danger alert-dismissible fade show alert-fixed">
    <strong>❌ Ошибка!</strong> <?= htmlspecialchars($syncError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="sticky-bar">
    <h3>Ивацевичи BySpan</h3>
    <div class="stats-center">
        <div class="stat-badge">📋 Всего: <span class="value"><?= $totalRecordsCount ?></span></div>
        <div class="stat-badge admin">👑 Админ: <span class="value"><?= $adminStats['admin_views'] ?></span></div>
        <div class="stat-badge user">👤 Пользователи: <span class="value"><?= $adminStats['user_views'] ?></span></div>
    </div>
    <div style="display: flex; gap: 15px; align-items: center;">
        <input type="text" id="searchInput" placeholder="🔍 Поиск...">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#syncExcelModal">📂 Синхронизировать</button>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#fullReplaceModal">⚠️ Полная замена</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWindow">➕ Создать</button>
    </div>
</div>

<div class="table-container">
    <table class="custom-table" id="dataTable">
        <thead>
            <tr>
                <th class="sort-header">№ <span class="sort-icon">↕️</span></th>
                <th>Наименование</th>
                <th>Номер декора</th>
                <th>Характер брака</th>
                <th>Примечание</th>
                <th>Размер (мм)</th>
                <th>Стоимость</th>
                <th>Фото</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $connection->query("SELECT * FROM byspan ORDER BY id ASC");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $imageQuery = $connection->query("SELECT image_path FROM images_byspan WHERE byspan_id = $id");
                $imagesData = [];
                while ($img = $imageQuery->fetch_assoc()) $imagesData[] = '../uploads/' . $img['image_path'];
                $imagesJSON = htmlspecialchars(json_encode($imagesData, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                $hasImages = !empty($imagesData);
                $note = !empty($row['Complaint']) ? htmlspecialchars($row['Complaint']) : '—';
                $photoButton = $hasImages ? '<button class="btn btn-info btn-sm view-images" data-images=\'' . $imagesJSON . '\' data-bs-toggle="modal" data-bs-target="#photoModal">Посмотреть фото</button>' : '<button class="btn btn-secondary btn-sm" disabled>Нет фото</button>';
                ?>
                <tr data-id="<?= $id ?>">
                    <td class="row-number"><?= $id ?></td>
                    <td style="text-align:left;"><?= htmlspecialchars($row['Nomenclature'] ?? '') ?></td>
                    <td style="text-align:left;"><?= htmlspecialchars($row['DecorNumber'] ?? '') ?></td>
                    <td style="text-align:left;"><?= htmlspecialchars($row['Character'] ?? '') ?></td>
                    <td style="text-align:left; font-size:13px; color:#64748b;"><?= $note ?></td>
                    <td><?= htmlspecialchars($row['Size'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Basic'] ?? '') ?></td>
                    <td><?= $photoButton ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="../Edit/editBySpan.php?edit=<?= $id ?>" class="btn btn-warning btn-sm">Изменить</a>
                            <a href="../Operations/BySpanOperations.php?delete=<?= $id ?>" class="btn btn-danger btn-sm btn-delete">Удалить</a>
                        </div>
                    </td>
                </tr>
            <?php }
        } else { ?>
            <tr><td colspan="9" class="text-center py-5">Нет данных. Создайте первую запись.</td></tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно для фото -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Фотографии</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body d-flex flex-wrap justify-content-center" id="photoGallery"></div>
        </div>
    </div>
</div>

<!-- Модальное окно создания записи -->
<div class="modal fade" id="createWindow" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bySpanForm" method="POST" action="../Operations/BySpanOperations.php" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Создать запись</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row">
                        <?php $fields = ['Nomenclature'=>'Номенклатура','DecorNumber'=>'Номер декора','Character'=>'Характер брака','Complaint'=>'Примечание','Size'=>'Полезный размер (длина*ширина), мм','Basic'=>'Стоимость реализации с НДС'];
                        foreach ($fields as $name => $label) echo "<div class='col-md-12 mb-3'><label class='form-label fw-semibold'>{$label}</label><input type='text' name='{$name}' class='form-control' placeholder='{$label}'></div>"; ?>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Загрузите изображения</label><br>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('imageInput').click()">Выбрать файлы</button>
                            <input type="file" id="imageInput" multiple style="display: none;">
                            <div id="selectedFilesList" class="mt-2 small"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button><button type="submit" name="create" class="btn btn-primary">Создать</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для СИНХРОНИЗАЦИИ -->
<div class="modal fade" id="syncExcelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="../Operations/upload_sync_handler.php" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white"><h5 class="modal-title">📂 Синхронизация с Excel</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="alert alert-info">Выберите CSV файл (сохраните Excel как CSV)<br><small>Будут добавлены новые записи и удалены отсутствующие</small></div><input type="file" name="excel_file" class="form-control" accept=".csv" required></div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button><button type="submit" class="btn btn-success">Загрузить</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для ПОЛНОЙ ЗАМЕНЫ -->
<div class="modal fade" id="fullReplaceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="../Operations/full_replace.php" enctype="multipart/form-data">
                <div class="modal-header bg-danger text-white"><h5 class="modal-title">⚠️ ПОЛНАЯ ЗАМЕНА ДАННЫХ</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="alert alert-danger"><strong>ВНИМАНИЕ!</strong><br>Все текущие данные в таблицах будут УДАЛЕНЫ и заменены данными из Excel.<br>Это действие НЕЛЬЗЯ отменить!</div><input type="file" name="excel_file" class="form-control" accept=".csv" required></div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button><button type="submit" class="btn btn-danger">✅ Подтверждаю полную замену</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.umd.js"></script>

<script>
let storedFiles = [];
const imageInput = document.getElementById('imageInput');
const selectedFilesList = document.getElementById('selectedFilesList');

imageInput.addEventListener('change', function(event) {
    for (let file of event.target.files) storedFiles.push(file);
    renderFileList();
    imageInput.value = '';
});

function renderFileList() {
    selectedFilesList.innerHTML = '';
    storedFiles.forEach((file, index) => {
        const fileRow = document.createElement('div');
        fileRow.classList.add('d-flex', 'align-items-center', 'justify-content-between', 'mb-1', 'p-1', 'bg-light', 'rounded', 'px-2');
        const fileName = document.createElement('span');
        fileName.textContent = `${index + 1}. ${file.name}`;
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-outline-danger';
        deleteBtn.textContent = '✖';
        deleteBtn.onclick = function() { storedFiles.splice(index, 1); renderFileList(); };
        fileRow.appendChild(fileName);
        fileRow.appendChild(deleteBtn);
        selectedFilesList.appendChild(fileRow);
    });
}

document.getElementById('bySpanForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    for (let el of this.elements) if (el.name && el.type !== 'file') formData.append(el.name, el.value);
    storedFiles.forEach(file => formData.append('ImagePath[]', file));
    fetch(this.action, { method: 'POST', body: formData }).then(response => { if (response.redirected) window.location.href = response.url; else return response.text(); }).then(data => { if (data && data.includes('error')) alert("Ошибка: " + data); else { bootstrap.Modal.getInstance(document.getElementById('createWindow')).hide(); location.reload(); } }).catch(error => { alert("Ошибка при загрузке: " + error); });
});

document.querySelectorAll('.btn-delete').forEach(button => { button.addEventListener('click', function(e) { if (!confirm("Вы уверены, что хотите удалить эту запись?")) e.preventDefault(); }); });

let sortDirection = 'asc';
function sortTable() {
    const tbody = document.querySelector('#dataTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    if (rows.length === 0) return;
    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    rows.sort((a, b) => { let aValue = parseInt(a.cells[0]?.textContent) || 0; let bValue = parseInt(b.cells[0]?.textContent) || 0; return sortDirection === 'asc' ? aValue - bValue : bValue - aValue; });
    rows.forEach(row => tbody.appendChild(row));
}
document.querySelector('.sort-header')?.addEventListener('click', sortTable);

document.addEventListener("DOMContentLoaded", function() {
    const modalPhotoGallery = document.getElementById('photoGallery');
    document.querySelectorAll('.view-images').forEach(button => { button.addEventListener('click', function() { let images = []; try { images = JSON.parse(this.getAttribute('data-images')); } catch(e) {} modalPhotoGallery.innerHTML = images.length === 0 ? '<p class="text-muted">Фотографии не найдены.</p>' : images.map((src, i) => `<a href="${src}" data-fancybox="gallery"><img src="${src}" class="img-thumbnail m-2" style="max-width:180px; max-height:140px; object-fit:cover;"></a>`).join(''); Fancybox.bind('[data-fancybox="gallery"]', {}); }); });
    document.getElementById('searchInput')?.addEventListener('keyup', function() { let filter = this.value.toLowerCase(); document.querySelectorAll('#dataTable tbody tr').forEach(row => { if (row.querySelector('td')) row.style.display = row.innerText.toLowerCase().indexOf(filter) > -1 ? '' : 'none'; }); });
    setTimeout(() => { $('.alert-fixed').fadeOut(500); }, 5000);
});
</script>
</body>
</html>