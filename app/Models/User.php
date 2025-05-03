<?php
// app/Models/User.php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Models\Database;
use App\Models\Category;

/**
 * Модель для роботи з даними користувачів (таблиця 'users').
 */
class User
{
    /**
     * Знаходить користувача за адресою електронної пошти.
     *
     * @param string $email Електронна пошта користувача.
     * @return array|false Асоціативний масив з даними користувача (включаючи 'id', 'password_hash') або false, якщо не знайдено.
     */
    public function findByEmail(string $email): array|false
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, email, first_name, last_name, password_hash FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("User Model (findByEmail) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Знаходить користувача за його ID.
     *
     * @param int $userId ID користувача.
     * @return array|false Асоціативний масив з даними користувача або false, якщо не знайдено.
     */
    public function findById(int $userId): array|false
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("User Model (findById) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Створює нового користувача в базі даних.
     * Також ініціює створення стандартних категорій для цього користувача.
     *
     * @param array $data Асоціативний масив з даними: ['email' => ..., 'first_name' => ..., 'last_name' => ..., 'password_hash' => ...]
     * @return int|false ID створеного користувача або false у разі помилки.
     */
    public function create(array $data): int|false
    {
        if (empty($data['email']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['password_hash'])) {
            error_log("User Model (create): Не вистачає даних для створення користувача.");
            return false;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO users (email, first_name, last_name, password_hash)
                VALUES (:email, :first_name, :last_name, :password_hash)";

        try {
            $db->beginTransaction();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $data['first_name'], PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $data['last_name'], PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $data['password_hash'], PDO::PARAM_STR);

            $success = $stmt->execute();

            if ($success) {
                $userId = (int)$db->lastInsertId();

                $categoryModel = new Category();
                $categoriesAdded = $categoryModel->addDefaultCategoriesForUser($userId);

                if ($categoriesAdded) {
                    $db->commit();
                    return $userId;
                } else {
                     error_log("User Model (create): Не вдалося додати стандартні категорії для user ID: {$userId}. Відкат транзакції.");
                     $db->rollBack();
                     return false;
                }
            } else {
                $db->rollBack();
                error_log("User Model (create): Не вдалося виконати запит на створення користувача.");
                return false;
            }
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("User Model (create) Error: " . $e->getMessage());
             if (strpos($e->getMessage(), 'UNIQUE constraint failed: users.email') !== false) {
                 return false;
             }
            return false;
        }
    }

    /**
     * Перевіряє наданий пароль відносно збереженого хешу.
     *
     * @param string $passwordInput Пароль, введений користувачем.
     * @param string $passwordHash Хеш пароля з бази даних.
     * @return bool True, якщо пароль вірний, інакше false.
     */
    public function verifyPassword(string $passwordInput, string $passwordHash): bool
    {
        return password_verify($passwordInput, $passwordHash);
    }
}