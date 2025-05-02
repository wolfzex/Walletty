<?php
// app/Models/Database.php

declare(strict_types=1);

namespace App\Models;

use PDO; // Імпортуємо клас PDO
use PDOException; // Імпортуємо клас PDOException для обробки помилок

/**
 * Клас для управління підключенням до бази даних SQLite.
 * Використовує шаблон Singleton для забезпечення єдиного екземпляра PDO.
 */
class Database
{
    /**
     * Зберігає єдиний екземпляр класу (Singleton).
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Зберігає об'єкт підключення PDO.
     * @var PDO|null
     */
    private ?PDO $connection = null;

    /**
     * Конструктор закритий (private) для реалізації Singleton.
     * Встановлює підключення до бази даних.
     */
    private function __construct()
    {
        // Перевіряємо, чи визначено шлях до БД у конфігурації
        if (!defined('DB_PATH')) {
            // Замість die() краще викидати виняток або логувати помилку
            error_log("Константа DB_PATH не визначена в конфігурації.");
            // Можна викинути виняток, який буде перехоплено в App::run()
             throw new PDOException("Конфігурацію бази даних не знайдено.");
            // Або просто завершити роботу, якщо це критично
            // die("Помилка конфігурації бази даних.");
        }

        $dbPath = DB_PATH; // Отримуємо шлях з конфігурації

        try {
            // Перевірка існування файлу БД (опціонально, PDO може створити його)
             if (!file_exists($dbPath)) {
                 // Можна викинути виняток, якщо файл БД обов'язково має існувати
                 error_log("Файл бази даних не знайдено за шляхом: " . $dbPath);
                 // throw new PDOException("Файл бази даних не знайдено: " . $dbPath);
                 // Або дозволити PDO створити файл, якщо це допустимо для SQLite
                 // Переконайся, що директорія db/ має права на запис для веб-сервера, якщо файл створюється автоматично.
             }

            // Створення нового підключення PDO до SQLite
            $this->connection = new PDO('sqlite:' . $dbPath);

            // Налаштування PDO для кращої роботи та безпеки:
            // 1. Режим обробки помилок: викидати винятки (рекомендовано)
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // 2. Режим вибірки за замовчуванням: асоціативний масив
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // 3. Вимкнути емуляцію підготовлених запитів (для більшої безпеки з деякими драйверами, для SQLite менш критично)
            // $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // (Опціонально) Увімкнути підтримку зовнішніх ключів для SQLite (якщо використовуєш їх)
            // $this->connection->exec('PRAGMA foreign_keys = ON;');

        } catch (PDOException $e) {
            // Обробка помилок підключення до бази даних
            error_log("Помилка підключення до БД: " . $e->getMessage());
            // Можна викинути виняток далі, щоб його обробив App::run()
            throw new PDOException("Не вдалося підключитися до бази даних: " . $e->getMessage(), (int)$e->getCode(), $e);
            // Або показати повідомлення користувачу (не рекомендовано на продакшені)
            // die("Помилка підключення до бази даних. Спробуйте пізніше.");
        }
    }

    /**
     * Забороняє клонування об'єкта (Singleton).
     */
    private function __clone() {}

    /**
     * Забороняє десеріалізацію об'єкта (Singleton).
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    /**
     * Статичний метод для отримання єдиного екземпляра класу Database.
     *
     * @return Database Екземпляр класу Database.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self(); // Створюємо екземпляр тільки якщо його ще немає
        }
        return self::$instance;
    }

    /**
     * Метод для отримання об'єкта підключення PDO.
     *
     * @return PDO Об'єкт PDO для виконання запитів.
     * @throws PDOException Якщо підключення не було встановлено.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
             // Ця ситуація не повинна виникати, якщо конструктор відпрацював правильно
             throw new PDOException("Підключення до бази даних не встановлено.");
        }
        return $this->connection;
    }
}