<?php
// app/Models/Account.php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Models\Database;
use PDOException;

/**
 * Модель для роботи з рахунками користувачів (таблиця 'accounts').
 */
class Account
{
    private const ALLOWED_CURRENCIES = ['UAH', 'USD', 'EUR', 'GBP', 'PLN', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY'];

    /**
     * Повертає масив дозволених валют.
     * @return array
     */
    public function getAllowedCurrencies(): array
    {
        return self::ALLOWED_CURRENCIES;
    }

    /**
     * Знаходить всі рахунки для вказаного користувача.
     *
     * @param int $userId ID користувача.
     * @return array Масив рахунків, відсортованих за назвою.
     */
    public function findAllByUserId(int $userId): array
    {
        $sql = "SELECT id, name, currency FROM accounts WHERE user_id = :user_id ORDER BY name ASC";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Account Model (findAllByUserId) Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Знаходить рахунок за ID та перевіряє належність користувачу.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача.
     * @return array|false Дані рахунку або false, якщо не знайдено або не належить користувачу.
     */
    public function findByIdAndUserId(int $accountId, int $userId): array|false
    {
        $sql = "SELECT id, name, currency FROM accounts WHERE id = :id AND user_id = :user_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Account Model (findByIdAndUserId) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Створює новий рахунок для користувача.
     *
     * @param array $data ['user_id', 'name', 'currency']
     * @return int|false ID створеного рахунку або false у разі помилки.
     */
    public function create(array $data): int|false
    {
        if (empty($data['user_id']) || empty($data['name']) || empty($data['currency'])) {
             error_log("Account Model (create): Не вистачає даних для створення рахунку.");
             return false;
        }
         if (!in_array($data['currency'], self::ALLOWED_CURRENCIES)) {
             error_log("Account Model (create): Недопустима валюта '{$data['currency']}'.");
             return false;
         }

        $sql = "INSERT INTO accounts (user_id, name, currency) VALUES (:user_id, :name, :currency)";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':currency', $data['currency'], PDO::PARAM_STR);
            if ($stmt->execute()) {
                return (int)$db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Account Model (create) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Оновлює дані існуючого рахунку.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача (для перевірки власності).
     * @param array $data ['name', 'currency']
     * @return bool True у разі успіху, false у разі помилки.
     */
    public function update(int $accountId, int $userId, array $data): bool
    {
         if (empty($data['name']) || empty($data['currency'])) {
             error_log("Account Model (update): Не вистачає даних для оновлення рахунку.");
             return false;
         }
         if (!in_array($data['currency'], self::ALLOWED_CURRENCIES)) {
             error_log("Account Model (update): Недопустима валюта '{$data['currency']}'.");
             return false;
         }

        $sql = "UPDATE accounts SET name = :name, currency = :currency WHERE id = :id AND user_id = :user_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':currency', $data['currency'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Account Model (update) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Видаляє рахунок користувача.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача (для перевірки власності).
     * @return bool True у разі успіху, false у разі помилки.
     */
    public function delete(int $accountId, int $userId): bool
    {
        $sql = "DELETE FROM accounts WHERE id = :id AND user_id = :user_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $accountId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0; // True, якщо запис було знайдено та видалено
        } catch (PDOException $e) {
            error_log("Account Model (delete) Error: " . $e->getMessage());
            return false;
        }
    }
}