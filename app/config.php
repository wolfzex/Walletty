<?php
// app/config.php

// Визначаємо базовий шлях до проекту.
define('BASE_PATH', dirname(__DIR__));

// Визначаємо шлях до директорії додатку
define('APP_PATH', BASE_PATH . '/app');

// Визначаємо шлях до публічної директорії
define('PUBLIC_PATH', BASE_PATH . '/public_html');

// Шлях до файлу бази даних SQLite
define('DB_PATH', BASE_PATH . '/db/wallet.db');

// Налаштування відображення помилок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Часовий пояс за замовчуванням
date_default_timezone_set('Europe/Kiev');

?>