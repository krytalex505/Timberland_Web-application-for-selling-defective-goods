<!DOCTYPE html>
<html lang="en">
<head>
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
            background-color: #343a40; /* Темно-серый фон */
            padding: 1rem;
        }
        .navbar-nav {
            display: flex;
            justify-content: space-between; /* Равномерное распределение */
            align-items: center;
            width: 100%;
        }
        .nav-item.nav-link {
            margin: 0 15px; /* Пространство между ссылками */
            font-size: 1.25rem; /* Чуть крупнее для элегантности */
            color: #ffffff; /* Белый цвет текста */
            transition: color 0.3s ease; /* Плавный переход цвета */
        }
        .nav-item.nav-link:hover {
            color: #ffcc00; /* Золотой оттенок при наведении */
            text-decoration: none; /* Убираем подчеркивание */
        }
        .navbar-nav:hover .nav-item {
            transform: scale(1.05); /* Лёгкое увеличение при наведении */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav">
        <!-- 
            <a class="nav-item nav-link" href="../ListUser/BySpanTable.php">Ивацевичи BySpan</a>
                <a class="nav-item nav-link" href="../ListUser/EggerTable.php">Эггер ДСП</a>
                <a class="nav-item nav-link" href="../ListUser/KronospanTable.php">Кроноспан</a>
                <a class="nav-item nav-link" href="../ListUser/MDFTable.php">МДФ</a>
         -->

                <a class="nav-item nav-link" href="../ListUser/SkinaliTable.php">Скинали, фальшпанели</a>
                <a class="nav-item nav-link" href="../ListUser/CountertopsEggerTable.php">Эггер столешницы</a>
                <a class="nav-item nav-link" href="../ListUser/CedarTable.php">Столешницы Кедр</a>
          <!-- 
                <a class="nav-item nav-link" href="../ListUser/XDFTable.php">ХДФ</a>
                <a class="nav-item nav-link" href="../ListUser/PlywoodTable.php">Фанера</a>
                <a class="nav-item nav-link" href="../ListUser/EdgeTable.php">Кромка, бортики</a>
    			<a class="nav-item nav-link" href="../ListUser/PlasticTable.php">Пластик</a>
                 -->
            </div>
        </div>
    </nav>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.4.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
