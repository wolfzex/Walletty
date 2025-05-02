<?php
// app/Models/User.php

declare(strict_types=1);

namespace App\Models;

use PDO; // Імпортуємо PDO
use App\Models\Database; // Імпортуємо наш клас Database
use App\Models\Category; // Імпортуємо модель Category для додавання стандартних категорій

/**
 * Модель для роботи з даними користувачів (таблиця 'users').
 */
class User
{
    // Ми не зберігаємо $db у властивості, а отримуємо з'єднання в кожному методі,
    // що є нормальним для моделей, які не обов'язково представляють один запис.
    // Або можна додати конструктор і зберігати $db, якщо зручніше.

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
            // fetch() поверне false, якщо запис не знайдено
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("User Model (findByEmail) Error: " . $e->getMessage());
            return false; // Повертаємо false у разі помилки БД
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
        // Перевірка наявності необхідних ключів
        if (empty($data['email']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['password_hash'])) {
            error_log("User Model (create): Не вистачає даних для створення користувача.");
            return false;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO users (email, first_name, last_name, password_hash)
                VALUES (:email, :first_name, :last_name, :password_hash)";

        try {
            $db->beginTransaction(); // Починаємо транзакцію

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $data['first_name'], PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $data['last_name'], PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $data['password_hash'], PDO::PARAM_STR);

            $success = $stmt->execute();

            if ($success) {
                $userId = (int)$db->lastInsertId(); // Отримуємо ID створеного користувача

                // Створюємо стандартні категорії для нового користувача
                // Ми припускаємо, що модель Category матиме статичний метод addDefaultCategories
                // який ми створимо пізніше.
                $categoryModel = new Category(); // Створюємо екземпляр моделі Category
                $categoriesAdded = $categoryModel->addDefaultCategoriesForUser($userId);

                if ($categoriesAdded) {
                    $db->commit(); // Завершуємо транзакцію успішно
                    return $userId;
                } else {
                     error_log("User Model (create): Не вдалося додати стандартні категорії для user ID: {$userId}. Відкат транзакції.");
                     $db->rollBack(); // Відкат, якщо категорії не додалися
                     return false;
                }
            } else {
                $db->rollBack(); // Відкат, якщо користувача не вдалося створити
                error_log("User Model (create): Не вдалося виконати запит на створення користувача.");
                return false;
            }
        } catch (\PDOException $e) {
            // Відкат транзакції у разі помилки БД
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("User Model (create) Error: " . $e->getMessage());
            // Можна перевірити помилку дублікату email
             if (strpos($e->getMessage(), 'UNIQUE constraint failed: users.email') !== false) {
                 // Можна повернути спеціальне значення або викинути виняток
                 // Наприклад, return -1; або throw new DuplicateEmailException(...);
                 // Поки що просто повертаємо false
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