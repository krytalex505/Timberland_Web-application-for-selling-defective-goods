<?php

session_start();
session_unset();
session_destroy(); // уничтожает сессию при загрузке

$config = include_once __DIR__ . '/../config.php';

// Получаем URI, например: /Admin/qwe12345/egger
$uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($uri, PHP_URL_PATH);
$parts = explode('/', trim($uri, '/')); // ['Admin', 'qwe12345', 'egger']

// Проверяем токен
$token = $parts[1] ?? '';
if ($token !== $config['admin_token']) {
    http_response_code(403);
    die("Access Denied");
}

// Устанавливаем флаг
$_SESSION['admin_authenticated'] = true;

// Получаем маршрут
$route = $parts[2] ?? '';

// Подключаем navbar
include_once __DIR__ . '/modules/navbar.php';

// Загружаем нужную страницу
switch ($route) {
    case 'egger':
        include __DIR__ . '/List/EggerTable.php';
        break;
    case 'byspan':
        include __DIR__ . '/List/BySpanTable.php';
        break;
    default:
        http_response_code(404);
        echo "Страница не найдена.";
}
