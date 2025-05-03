<?php
// app/Models/Database.php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

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
        if (!defined('DB_PATH')) {
            error_log("Константа DB_PATH не визначена в конфігурації.");
             throw new PDOException("Конфігурацію бази даних не знайдено.");
        }

        $dbPath = DB_PATH;

        try {
             if (!file_exists($dbPath)) {
                 error_log("Файл бази даних не знайдено за шляхом: " . $dbPath);
             }

            $this->connection = new PDO('sqlite:' . $dbPath);

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


        } catch (PDOException $e) {
            error_log("Помилка підключення до БД: " . $e->getMessage());
            throw new PDOException("Не вдалося підключитися до бази даних: " . $e->getMessage(), (int)$e->getCode(), $e);
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
            self::$instance = new self();
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
             throw new PDOException("Підключення до бази даних не встановлено.");
        }
        return $this->connection;
    }
}