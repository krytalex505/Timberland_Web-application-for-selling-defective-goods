<?php
require_once '../modules/db_connection.php';
$connection->set_charset("utf8mb4");
session_start();

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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Экспорт данных</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .card { box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 50px; }
        .btn-export-all { background: #10b981; color: white; padding: 15px; font-size: 18px; }
        .btn-export-all:hover { background: #059669; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">📥 Экспорт данных в Excel</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <a href="../Operations/export_excel.php?type=all" class="btn btn-success btn-export-all w-100">
                        📑 ВЫГРУЗИТЬ ВСЕ ТАБЛИЦЫ СРАЗУ
                    </a>
                </div>
            </div>
            
            <div class="row">
                <?php foreach ($tables as $table => $name): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <a href="../Operations/export_excel.php?type=<?= $table ?>" class="btn btn-outline-primary w-100 text-start">
                        📄 <?= $name ?>
                        <br><small class="text-muted">
                        <?php
                        $result = $connection->query("SELECT COUNT(*) as cnt FROM `$table`");
                        $row = $result->fetch_assoc();
                        echo $row['cnt'] . ' записей';
                        ?>
                        </small>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer">
            <a href="BySpanTable.php" class="btn btn-secondary">← Назад</a>
        </div>
    </div>
</div>
</body>
</html>