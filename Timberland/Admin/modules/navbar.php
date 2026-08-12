<!DOCTYPE html>
<html lang="en">
<head>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($config)) {
    $config = include_once __DIR__ . '/../../config.php';
}
$token = $config['admin_token'] ?? '';
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Навигационная панель</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background-color: #343a40;
            padding: 1rem;
        }
        .navbar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .nav-item.nav-link {
            margin: 0 15px;
            font-size: 1.25rem;
            color: #ffffff;
            transition: color 0.3s ease;
        }
        .nav-item.nav-link:hover {
            color: #ffcc00;
            text-decoration: none;
        }
        .navbar-nav:hover .nav-item {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav">
             	<a class="nav-item nav-link" href="../List/BySpanTable.php">Ивацевичи BySpan</a> > 
                <a class="nav-item nav-link" href="../List/EggerTable.php">Эггер ДСП</a>
                <a class="nav-item nav-link" href="../List/KronospanTable.php">Кроноспан</a>
                <a class="nav-item nav-link" href="../List/MDFTable.php">МДФ</a>
                <a class="nav-item nav-link" href="../List/SkinaliTable.php">Скинали, фальшпанели</a>
                <a class="nav-item nav-link" href="../List/CountertopsEggerTable.php">Эггер столешницы</a>
                <a class="nav-item nav-link" href="../List/CedarTable.php">Столешницы Кедр</a>
                <a class="nav-item nav-link" href="../List/XDFTable.php">ХДФ</a>
                <a class="nav-item nav-link" href="../List/PlywoodTable.php">Фанера</a>
                <a class="nav-item nav-link" href="../List/EdgeTable.php">Кромка, бортики</a>
                <a class="nav-item nav-link" href="../List/PlasticTable.php">Пластик</a>
            </div>
        </div>
    </nav>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.4.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>