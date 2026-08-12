#!/bin/bash
cd /var/www/Timberland

# Проверяем, установлен ли Composer
if [ ! -f "composer.phar" ]; then
    echo "📦 Composer не найден. Скачиваю..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    echo "✅ Composer установлен."
fi

# Устанавливаем PhpSpreadsheet
echo "📚 Устанавливаю PhpSpreadsheet..."
php composer.phar require phpoffice/phpspreadsheet

# Проверяем результат
if [ -d "vendor/phpoffice/phpspreadsheet" ]; then
    echo "✅✅✅ БИБЛИОТЕКА УСПЕШНО УСТАНОВЛЕНА! ✅✅✅"
    echo "Теперь синхронизация с Excel будет работать."
else
    echo "❌ Ошибка при установке."
fi