<?php
require_once '../modules/db_connection.php';
$connection->set_charset("utf8mb4");
session_start();
require_once('../modules/auth.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// ===== СЧЁТЧИК ПЕРЕХОДОВ ДЛЯ АДМИНА =====
$pageName = 'egger';

$connection->query("INSERT INTO page_views (page_name, admin_views, user_views) VALUES ('$pageName', 1, 0) ON DUPLICATE KEY UPDATE admin_views = admin_views + 1");

$statsResult = $connection->query("SELECT admin_views, user_views FROM page_views WHERE page_name = '$pageName'");
$adminStats = ['admin_views' => 0, 'user_views' => 0];
if ($statsResult && $statsResult->num_rows > 0) {
    $adminStats = $statsResult->fetch_assoc();
}

$totalRecordsCount = $connection->query("SELECT COUNT(*) as total FROM egger")->fetch_assoc()['total'];
?>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link href="//cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.css" rel="stylesheet">
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
            min-width: 1000px;
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
<div class="alert alert-info alert-dismissible fade show" style="position: fixed; top: 80px; right: 20px; z-index: 9999;">
    <strong>ℹ️ Сообщение:</strong> <?= htmlspecialchars($syncMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($syncError)): ?>
<div class="alert alert-danger alert-dismissible fade show" style="position: fixed; top: 80px; right: 20px; z-index: 9999;">
    <strong>❌ Ошибка!</strong> <?= htmlspecialchars($syncError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="sticky-bar">
    <h3>Эггер ДСП</h3>
    
    <div class="stats-center">
        <div class="stat-badge">
            <span class="label">📋 Всего записей:</span>
            <span class="value"><?php echo $totalRecordsCount; ?></span>
        </div>
        <div class="stat-badge admin">
            <span class="label">👑 Админ:</span>
            <span class="value"><?php echo $adminStats['admin_views']; ?></span>
        </div>
        <div class="stat-badge user">
            <span class="label">👤 Пользователи:</span>
            <span class="value"><?php echo $adminStats['user_views']; ?></span>
        </div>
    </div>
    
    <div style="display: flex; gap: 15px; align-items: center;">
        <input type="text" id="searchInput" placeholder="🔍 Поиск по любой колонке...">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWindow">Создать</button>
    </div>
</div>

<div class="table-container">
    <table class="table custom-table" id="dataTable">
        <thead>
            <tr>
                <th class="sort-header">№ <span class="sort-icon">↕️</span></th>
                <th>Наименование</th>
                <th>Номер декора</th>
                <th>Характер брака</th>
                <th>Примечание</th>
                <th>Полезный размер (мм)</th>
                <th>Стоимость реализации с НДС</th>
                <th>Фото</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $connection->query("SELECT * FROM egger ORDER BY id ASC");
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                
                $imageQuery = $connection->query("SELECT image_path FROM images_egger WHERE egger_id = $id");
                $imagesData = [];
                while ($img = $imageQuery->fetch_assoc()) {
                    $imagesData[] = '../uploads/' . $img['image_path'];
                }
                $imagesJSON = htmlspecialchars(json_encode($imagesData, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                $hasImages = count($imagesData) > 0;
                $note = !empty($row['Complaint']) ? htmlspecialchars($row['Complaint']) : '—';
                
                $photoButton = '';
                if ($hasImages) {
                    $photoButton = '<button class="btn btn-info btn-sm view-images" data-images=\'' . $imagesJSON . '\' data-bs-toggle="modal" data-bs-target="#photoModal">Посмотреть фото</button>';
                } else {
                    $photoButton = '<button class="btn btn-secondary btn-sm" disabled style="opacity:0.6; cursor:not-allowed;">Нет фото</button>';
                }
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
                            <a href="../Edit/editEgger.php?edit=<?= $id ?>" class="btn btn-warning btn-sm">Изменить</a>
                            <a href="../Operations/EggerOperations.php?delete=<?= $id ?>" class="btn btn-danger btn-sm btn-delete">Удалить</a>
                        </div>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td colspan="9" class="text-center py-5" style="color: #64748b;">Нет данных. Создайте первую запись.</span
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно для фото -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Фотографии</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex flex-wrap justify-content-center" id="photoGallery"></div>
        </div>
    </div>
</div>

<!-- Модальное окно создания записи -->
<div class="modal fade" id="createWindow" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="EggerForm" method="POST" action="../Operations/EggerOperations.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Создать запись</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php
                        $fields = [
                            'Nomenclature' => 'Номенклатура',
                            'DecorNumber' => 'Номер декора',
                            'Character' => 'Характер брака',
                            'Complaint' => 'Примечание',
                            'Size' => 'Полезный размер (длина*ширина), мм',
                            'Basic' => 'Стоимость реализации с НДС'
                        ];
                        foreach ($fields as $name => $label) {
                            echo "<div class='col-md-12 mb-3'>
                                    <label class='form-label fw-semibold'>{$label}</label>
                                    <input type='text' name='{$name}' class='form-control' placeholder='Введите {$label}'>
                                </div>";
                        }
                        ?>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">Загрузите изображения</label><br>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('imageInput').click()">Выбрать файлы</button>
                            <input type="file" id="imageInput" multiple style="display: none;">
                            <div id="selectedFilesList" class="mt-2 small"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="submit" name="create" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.umd.js"></script>

<script>
let storedFiles = [];
const imageInput = document.getElementById('imageInput');
const selectedFilesList = document.getElementById('selectedFilesList');

imageInput.addEventListener('change', function(event) {
    for (let file of event.target.files) {
        storedFiles.push(file);
    }
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
        deleteBtn.style.padding = '0 6px';
        deleteBtn.onclick = function() {
            storedFiles.splice(index, 1);
            renderFileList();
        };
        fileRow.appendChild(fileName);
        fileRow.appendChild(deleteBtn);
        selectedFilesList.appendChild(fileRow);
    });
}

document.getElementById('EggerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    for (let el of this.elements) {
        if (el.name && el.type !== 'file') {
            formData.append(el.name, el.value);
        }
    }
    storedFiles.forEach(file => formData.append('ImagePath[]', file));
    
    fetch(this.action, { method: 'POST', body: formData })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text();
            }
        })
        .then(data => { 
            if (data && data.includes('error')) alert("Ошибка: " + data);
            else {
                bootstrap.Modal.getInstance(document.getElementById('createWindow')).hide();
                location.reload();
            }
        })
        .catch(error => { alert("Ошибка при загрузке: " + error); });
});

document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function(e) {
        if (!confirm("Вы уверены, что хотите удалить эту запись?")) {
            e.preventDefault();
        }
    });
});

// СОРТИРОВКА ТОЛЬКО ПО ПЕРВОМУ СТОЛБЦУ
let sortDirection = 'asc';

function sortTable() {
    const tbody = document.querySelector('#dataTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    if (rows.length === 0) return;
    
    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        let aValue = parseInt(a.cells[0]?.textContent) || 0;
        let bValue = parseInt(b.cells[0]?.textContent) || 0;
        return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Назначаем сортировку только на первый заголовок
document.querySelector('.sort-header').addEventListener('click', sortTable);

// Просмотр фото
document.addEventListener("DOMContentLoaded", function() {
    const modalPhotoGallery = document.getElementById('photoGallery');
    document.querySelectorAll('.view-images').forEach(button => {
        button.addEventListener('click', function() {
            let images = [];
            try { images = JSON.parse(this.getAttribute('data-images')); } catch(e) {}
            modalPhotoGallery.innerHTML = images.length === 0 ? '<p class="text-muted">Фотографии не найдены.</p>' : images.map((src, i) => `<a href="${src}" data-fancybox="gallery"><img src="${src}" class="img-thumbnail m-2" style="max-width:180px; max-height:140px; object-fit:cover;"></a>`).join('');
            Fancybox.bind('[data-fancybox="gallery"]', {});
        });
    });
    
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('#dataTable tbody tr').forEach(row => {
            if (row.querySelector('td')) {
                row.style.display = row.innerText.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
            }
        });
    });
});
</script>
</body>
</html>