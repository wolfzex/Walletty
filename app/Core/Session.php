<?php
// app/Core/Session.php

declare(strict_types=1);

namespace App\Core;

/**
 * Клас-обгортка для роботи з PHP-сесіями.
 * Забезпечує методи для старту, встановлення/отримання даних,
 * видалення, знищення сесії та роботи з flash-повідомленнями.
 */
class Session
{
    protected const FLASH_KEY = '_flash';

    public function __construct()
    {
        // Автоматично стартуємо сесію при створенні об'єкта, якщо вона ще не активна
        $this->start();
    }

    /**
     * Стартує сесію, якщо вона ще не активна.
     * Можна додати додаткові налаштування безпеки.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Налаштування безпеки перед стартом (рекомендовано)
            // ini_set('session.use_only_cookies', 1); // Використовувати тільки куки
            // ini_set('session.use_strict_mode', 1); // Строгий режим ID сесії
            // ini_set('session.cookie_httponly', 1); // Доступ до куки тільки через HTTP
            // ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Надсилати куку тільки по HTTPS
            // ini_set('session.cookie_samesite', 'Lax'); // Захист від CSRF

            session_start();
        }
    }

    /**
     * Встановлює значення в сесію за ключем.
     *
     * @param string $key Ключ.
     * @param mixed $value Значення.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Отримує значення з сесії за ключем.
     *
     * @param string $key Ключ.
     * @param mixed|null $default Значення за замовчуванням, якщо ключ не знайдено.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Перевіряє, чи існує ключ у сесії.
     *
     * @param string $key Ключ.
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Видаляє ключ із сесії.
     *
     * @param string $key Ключ.
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Встановлює flash-повідомлення (доступне тільки для наступного запиту).
     *
     * @param string $key Ключ повідомлення (наприклад, 'success', 'error').
     * @param mixed $message Повідомлення.
     */
    public function flash(string $key, mixed $message): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $message;
    }

    /**
     * Перевіряє, чи існує flash-повідомлення за ключем.
     *
     * @param string $key Ключ.
     * @return bool
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }

    /**
     * Отримує flash-повідомлення за ключем (і видаляє його).
     *
     * @param string $key Ключ.
     * @param mixed|null $default Значення за замовчуванням.
     * @return mixed
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $message = $_SESSION[self::FLASH_KEY][$key] ?? $default;
        if (isset($_SESSION[self::FLASH_KEY][$key])) {
            unset($_SESSION[self::FLASH_KEY][$key]);
            // Очистити масив _flash, якщо він порожній
            if (empty($_SESSION[self::FLASH_KEY])) {
                 unset($_SESSION[self::FLASH_KEY]);
            }
        }
        return $message;
    }

    /**
     * Знищує поточну сесію.
     * Видаляє всі дані сесії та сесійну куку.
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // 1. Очистити масив $_SESSION
            $_SESSION = [];

            // 2. Видалити сесійну куку
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // 3. Знищити сесію на сервері
            session_destroy();
        }
    }

    /**
     * Регенерує ID поточної сесії.
     * Важливо для безпеки, щоб запобігти фіксації сесії.
     *
     * @param bool $deleteOldSession Чи видаляти старий файл сесії.
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Повертає ID поточної сесії.
     *
     * @return string|false
     */
    public function id(): string|false
    {
        return session_id();
    }
}