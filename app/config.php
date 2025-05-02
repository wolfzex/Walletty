<?php
// app/config.php

// Визначаємо базовий шлях до проекту.
// dirname(__DIR__) дає шлях до батьківської директорії поточної директорії (тобто корінь проекту).
define('BASE_PATH', dirname(__DIR__));

// Визначаємо шлях до директорії додатку (де лежить вся логіка)
define('APP_PATH', BASE_PATH . '/app');

// Визначаємо шлях до публічної директорії (корінь сайту)
define('PUBLIC_PATH', BASE_PATH . '/public_html');

// Шлях до файлу бази даних SQLite
define('DB_PATH', BASE_PATH . '/db/wallet.db');

// Налаштування відображення помилок (для розробки)
// На продакшені краще встановити в 0 і логувати помилки у файл
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Часовий пояс за замовчуванням
date_default_timezone_set('Europe/Kiev');

// Інші можливі налаштування:
// define('SITE_NAME', 'Walletty');

?>