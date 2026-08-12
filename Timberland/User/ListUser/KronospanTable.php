<?php
require_once '../OperationsUser/KronospanOperations.php';
$connection->set_charset("utf8mb4");

// ===== СЧЁТЧИК ПЕРЕХОДОВ ДЛЯ ПОЛЬЗОВАТЕЛЯ =====
$pageName = 'kronospan';
$connection->query("INSERT INTO page_views (page_name, admin_views, user_views) VALUES ('$pageName', 0, 1) ON DUPLICATE KEY UPDATE user_views = user_views + 1");
// ============================================

// Получение параметров сортировки
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Разрешенные столбцы для сортировки (безопасность)
$allowedColumns = ['id', 'Nomenclature', 'DecorNumber', 'Character', 'Complaint', 'Size', 'Basic'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'id';
}
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}
?>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Кроноспан</title>
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
        
        .sticky-bar input {
            width: 300px;
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
            width: 320px;
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
            }
            .sticky-bar h3 { font-size: 18px; }
            .sticky-bar input { width: 200px; padding: 8px 12px; }
            .table-container {
                height: calc(100vh - 120px);
                padding: 0 12px 12px 12px;
            }
        }
        
        .btn-info {
            background: #e2e8f0;
            border: none;
            color: #1e293b;
            transition: all 0.2s;
        }
        .btn-info:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
        }
        
        .btn-secondary.disabled, .btn-secondary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #94a3b8;
            color: white;
            border: none;
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

        /* Стили для сортировки */
        .sort-link {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .sort-link:hover {
            color: #ffcc00;
            text-decoration: none;
        }
        .sort-arrow {
            font-size: 12px;
        }
    </style>
</head>
<body>
<?php include '../modulesUser/View.php'; ?>

<div class="sticky-bar">
    <h3>🌲 Кроноспан</h3>
    <div style="display: flex; gap: 15px; align-items: center;">
        <input type="text" id="searchInput" placeholder="🔍 Поиск по любой колонке...">
    </div>
</div>

<div class="table-container">
    <table class="table custom-table">
        <thead>
            <tr>
                <th>
                    <a href="?sort=id&order=<?= ($sortColumn == 'id' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        №
                        <?php if ($sortColumn == 'id'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=Nomenclature&order=<?= ($sortColumn == 'Nomenclature' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        Наименование
                        <?php if ($sortColumn == 'Nomenclature'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=DecorNumber&order=<?= ($sortColumn == 'DecorNumber' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        Номер декора
                        <?php if ($sortColumn == 'DecorNumber'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=Character&order=<?= ($sortColumn == 'Character' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        Характер брака
                        <?php if ($sortColumn == 'Character'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=Complaint&order=<?= ($sortColumn == 'Complaint' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        Примечание
                        <?php if ($sortColumn == 'Complaint'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=Size&order=<?= ($sortColumn == 'Size' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        Полезный размер (мм)
                        <?php if ($sortColumn == 'Size'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort=Basic&order=<?= ($sortColumn == 'Basic' && $sortOrder == 'ASC') ? 'DESC' : 'ASC' ?>" class="sort-link">
                        Стоимость реализации с НДС
                        <?php if ($sortColumn == 'Basic'): ?>
                            <span class="sort-arrow"><?= $sortOrder == 'ASC' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Фото</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $connection->query("SELECT * FROM kronospan ORDER BY $sortColumn $sortOrder");
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $imageQuery = $connection->query("SELECT image_path FROM images_kronospan WHERE kronospan_id = $id");
                $imagesData = [];
                while ($img = $imageQuery->fetch_assoc()) {
                    $imagesData[] = '../../Admin/uploads/' . $img['image_path'];
                }
                $imagesJSON = htmlspecialchars(json_encode($imagesData, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                $hasImages = count($imagesData) > 0;
                
                $note = !empty($row['Complaint']) ? htmlspecialchars($row['Complaint']) : '—';
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td style="text-align:left;"><?php echo htmlspecialchars($row['Nomenclature'] ?? ''); ?></td>
                    <td style="text-align:left;"><?php echo htmlspecialchars($row['DecorNumber'] ?? ''); ?></td>
                    <td style="text-align:left;"><?php echo htmlspecialchars($row['Character'] ?? ''); ?></td>
                    <td style="text-align:left; font-size:13px; color:#64748b;"><?php echo $note; ?></td>
                    <td><?php echo htmlspecialchars($row['Size'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['Basic'] ?? ''); ?></td>
                    <td>
                        <?php if ($hasImages): ?>
                            <button class="btn btn-info btn-sm view-images" data-images='<?php echo $imagesJSON; ?>' data-bs-toggle="modal" data-bs-target="#photoModal">📷 Посмотреть фото</button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled style="opacity:0.6; cursor:not-allowed;">❌ Нет фото</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td colspan="8" class="text-center py-5" style="color: #64748b;">📭 Нет данных. Нет доступных записей.</td>
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
                <h5 class="modal-title">📸 Фотографии</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex flex-wrap justify-content-center" id="photoGallery"></div>
        </div>
    </div>
</div>

<script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.umd.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalPhotoGallery = document.getElementById('photoGallery');
    document.querySelectorAll('.view-images').forEach(button => {
        button.addEventListener('click', function () {
            let images = [];
            try { images = JSON.parse(this.getAttribute('data-images')); } catch(e) {}
            modalPhotoGallery.innerHTML = images.length === 0 ? '<p class="text-muted">📭 Фотографии не найдены.</p>' : images.map((src, i) => `<a href="${src}" data-fancybox="gallery"><img src="${src}" class="img-thumbnail m-2" style="max-width:180px; max-height:140px; object-fit:cover;"></a>`).join('');
            Fancybox.bind('[data-fancybox="gallery"]', {});
        });
    });

    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('.custom-table tbody tr').forEach(row => {
            if (row.querySelector('td')) {
                row.style.display = row.innerText.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
            }
        });
    });
});
</script>
</body>
</html>