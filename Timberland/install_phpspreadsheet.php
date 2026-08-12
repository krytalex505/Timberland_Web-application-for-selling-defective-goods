<?php
echo "<pre>";

// Путь к папке Timberland
$dir = '/var/www/Timberland';
chdir($dir);

echo "=== УСТАНОВКА PHPSPREADSHEET ===\n\n";

// 1. Проверяем, есть ли composer.phar
if (!file_exists('composer.phar')) {
    echo "1. Скачиваем Composer...\n";
    $composer = file_get_contents('https://getcomposer.org/installer');
    if ($composer === false) {
        die("❌ Не удалось скачать Composer. Проверьте интернет соединение.\n");
    }
    file_put_contents('composer-setup.php', $composer);
    
    echo "2. Устанавливаем Composer...\n";
    $output = [];
    exec('php composer-setup.php 2>&1', $output);
    echo implode("\n", $output) . "\n";
    
    echo "3. Удаляем установщик...\n";
    unlink('composer-setup.php');
}

// 2. Проверяем, установлен ли PhpSpreadsheet
if (!file_exists('vendor/phpoffice/phpspreadsheet')) {
    echo "4. Устанавливаем PhpSpreadsheet...\n";
    $output = [];
    exec('php composer.phar require phpoffice/phpspreadsheet 2>&1', $output);
    echo implode("\n", $output) . "\n";
} else {
    echo "✅ PhpSpreadsheet уже установлен!\n";
}

// 3. Проверка установки
echo "\n=== ПРОВЕРКА УСТАНОВКИ ===\n";
if (file_exists('vendor/autoload.php')) {
    echo "✅ vendor/autoload.php существует\n";
    require_once 'vendor/autoload.php';
    echo "✅ Библиотека загружена\n";
} else {
    echo "❌ Ошибка: vendor/autoload.php не найден\n";
}

echo "\n=== ГОТОВО! ===\n";
echo "Теперь можно использовать синхронизацию с Excel.\n";
echo "</pre>";
?>