<?php
// app/Models/Category.php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Models\Database;
use PDOException;

/**
 * Модель для роботи з категоріями доходів та витрат (таблиця 'categories').
 */
class Category
{
    // Масив стандартних категорій
    private const DEFAULT_CATEGORIES = [
        ['name' => 'Продукти', 'type' => 'expense', 'description' => 'Витрати на покупки продуктів харчування та інших необхідних товарів для дому.'],
        ['name' => 'Транспорт', 'type' => 'expense', 'description' => 'Витрати на громадський транспорт, таксі, пальне, обслуговування авто.'],
        ['name' => 'Житло', 'type' => 'expense', 'description' => 'Витрати на оренду або іпотеку, комунальні послуги, ремонт, меблі.'],
        ['name' => 'Одяг та взуття', 'type' => 'expense', 'description' => 'Покупка одягу, взуття та аксесуарів.'],
        ['name' => 'Здоров\'я', 'type' => 'expense', 'description' => 'Витрати на медичні послуги, ліки, страхування, спорт.'],
        ['name' => 'Розваги', 'type' => 'expense', 'description' => 'Витрати на кіно, театри, концерти, кафе, ресторани, хобі, подорожі.'],
        ['name' => 'Освіта', 'type' => 'expense', 'description' => 'Витрати на навчання, курси, книги, освітні матеріали.'],
        ['name' => 'Подарунки', 'type' => 'expense', 'description' => 'Витрати на подарунки іншим людям.'],
        ['name' => 'Зв\'язок та інтернет', 'type' => 'expense', 'description' => 'Оплата мобільного зв\'язку, інтернету, телебачення.'],
        ['name' => 'Інші витрати', 'type' => 'expense', 'description' => 'Різні некласифіковані витрати.'],
        ['name' => 'Зарплата', 'type' => 'income', 'description' => 'Основний дохід від роботи.'],
        ['name' => 'Підробіток', 'type' => 'income', 'description' => 'Додатковий дохід, фріланс.'],
        ['name' => 'Подарунки', 'type' => 'income', 'description' => 'Отримані грошові подарунки.'],
        ['name' => 'Відсотки', 'type' => 'income', 'description' => 'Відсотки за вкладами, кешбек.'],
        ['name' => 'Інші доходи', 'type' => 'income', 'description' => 'Різні некласифіковані доходи.'],
        // Системні категорії будуть створюватися за потребою методами getOrCreate...
    ];

    /**
     * Отримує всі категорії певного типу для вказаного користувача.
     *
     * @param int $userId ID користувача.
     * @param string $type Тип категорії ('income' або 'expense').
     * @return array Масив категорій.
     */
    public function findByUserIdAndType(int $userId, string $type): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            // Виключаємо системні категорії, якщо вони мають специфічні імена
            $sql = "SELECT id, name, description, type FROM categories
                    WHERE user_id = :user_id AND type = :type
                    ORDER BY name ASC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Category Model (findByUserIdAndType) Error: " . $e->getMessage());
            return []; // Повертаємо порожній масив у разі помилки
        }
    }

    /**
     * Отримує всі категорії для вказаного користувача.
     *
     * @param int $userId ID користувача.
     * @return array Масив усіх категорій користувача.
     */
    public function findAllByUserId(int $userId): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT id, name, description, type FROM categories WHERE user_id = :user_id ORDER BY type, name ASC";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Category Model (findAllByUserId) Error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Знаходить категорію за її ID та ID користувача.
     * Перевіряє належність категорії користувачу.
     *
     * @param int $categoryId ID категорії.
     * @param int $userId ID користувача.
     * @return array|false Дані категорії або false, якщо не знайдено або не належить користувачу.
     */
    public function findByIdAndUserId(int $categoryId, int $userId): array|false
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, name, type, description FROM categories WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Category Model (findByIdAndUserId) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Створює нову категорію для користувача.
     *
     * @param array $data ['user_id', 'name', 'type', 'description']
     * @return int|false ID створеної категорії або false у разі помилки.
     */
    public function create(array $data): int|false
    {
        if (empty($data['user_id']) || empty($data['name']) || empty($data['type'])) {
             error_log("Category Model (create): Не вистачає даних для створення категорії.");
             return false;
        }
        // Валідація типу
        if (!in_array($data['type'], ['income', 'expense'])) {
             error_log("Category Model (create): Невірний тип категорії '{$data['type']}'.");
             return false;
        }

        $sql = "INSERT INTO categories (user_id, name, type, description) VALUES (:user_id, :name, :type, :description)";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
            // Використовуємо null coalescing operator для опису
            $stmt->bindValue(':description', $data['description'] ?? '', PDO::PARAM_STR);
            if ($stmt->execute()) {
                return (int)$db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Category Model (create) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Оновлює існуючу категорію користувача.
     *
     * @param int $categoryId ID категорії для оновлення.
     * @param int $userId ID користувача (для перевірки власності).
     * @param array $data ['name', 'type', 'description']
     * @return bool True у разі успіху, false у разі помилки або якщо категорія не належить користувачу.
     */
    public function update(int $categoryId, int $userId, array $data): bool
    {
        if (empty($data['name']) || empty($data['type'])) {
            error_log("Category Model (update): Не вистачає даних для оновлення категорії.");
            return false;
        }
         if (!in_array($data['type'], ['income', 'expense'])) {
             error_log("Category Model (update): Невірний тип категорії '{$data['type']}'.");
             return false;
         }

        $sql = "UPDATE categories SET name = :name, type = :type, description = :description
                WHERE id = :id AND user_id = :user_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? '', PDO::PARAM_STR);
            $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            // execute() поверне true у разі успіху, але rowCount() надійніше перевірить, чи був запис змінено/знайдено
            $stmt->execute();
            return $stmt->rowCount() > 0; // Повертаємо true, якщо хоча б один рядок був оновлений
        } catch (PDOException $e) {
            error_log("Category Model (update) Error: " . $e->getMessage());
            return false;
        }
    }

     /**
      * Перевіряє, чи використовується категорія в транзакціях.
      *
      * @param int $categoryId ID категорії.
      * @return bool True, якщо використовується, інакше false.
      */
     public function isUsedInTransactions(int $categoryId): bool
     {
         $sql = "SELECT 1 FROM transactions WHERE category_id = :category_id LIMIT 1";
         try {
             $db = Database::getInstance()->getConnection();
             $stmt = $db->prepare($sql);
             $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
             $stmt->execute();
             return $stmt->fetchColumn() !== false; // Якщо знайдено хоч один запис, поверне '1' (true)
         } catch (PDOException $e) {
             error_log("Category Model (isUsedInTransactions) Error: " . $e->getMessage());
             // Вважаємо, що сталася помилка, і краще не видаляти категорію
             return true;
         }
     }

    /**
     * Видаляє категорію користувача.
     * Видалення можливе тільки якщо категорія не використовується в транзакціях.
     *
     * @param int $categoryId ID категорії.
     * @param int $userId ID користувача.
     * @return bool True у разі успішного видалення, false у разі помилки,
     * якщо категорія не належить користувачу, або якщо вона використовується.
     */
    public function delete(int $categoryId, int $userId): bool
    {
        // 1. Перевірити, чи не використовується категорія
        if ($this->isUsedInTransactions($categoryId)) {
            error_log("Category Model (delete): Спроба видалення категорії ID {$categoryId}, яка використовується в транзакціях.");
            return false; // Не можна видаляти використану категорію
        }

        // 2. Видалити категорію, перевіряючи власника
        $sql = "DELETE FROM categories WHERE id = :id AND user_id = :user_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            // Перевіряємо, чи був запис видалено (rowCount > 0)
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Category Model (delete) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Додає стандартний набір категорій для нового користувача.
     *
     * @param int $userId ID користувача.
     * @return bool True, якщо всі категорії успішно додані, інакше false.
     */
    public function addDefaultCategoriesForUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $sql = "INSERT INTO categories (user_id, name, type, description) VALUES (:user_id, :name, :type, :description)";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            $allAdded = true;
            foreach (self::DEFAULT_CATEGORIES as $category) {
                $stmt->bindValue(':name', $category['name']);
                $stmt->bindValue(':type', $category['type']);
                $stmt->bindValue(':description', $category['description']);
                if (!$stmt->execute()) {
                    // Логуємо помилку, але продовжуємо додавати інші категорії
                    error_log("Category Model (addDefault): Failed to insert category '{$category['name']}' for user ID {$userId}.");
                    $allAdded = false; // Позначаємо, що була помилка
                }
            }
             return $allAdded; // Повертаємо true тільки якщо ВСІ додалися без помилок

        } catch (PDOException $e) {
            error_log("Category Model (addDefault) Error for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Отримує або створює системну категорію (наприклад, для переказів).
     *
     * @param int $userId ID користувача.
     * @param string $name Назва системної категорії.
     * @param string $type Тип ('income' або 'expense').
     * @param string $description Опис категорії.
     * @return int|false ID категорії або false у разі помилки.
     */
    private function getOrCreateSystemCategory(int $userId, string $name, string $type, string $description): int|false
    {
        try {
            $db = Database::getInstance()->getConnection();
            // 1. Спробувати знайти існуючу
            $stmtSelect = $db->prepare("SELECT id FROM categories WHERE user_id = :user_id AND name = :name AND type = :type");
            $stmtSelect->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtSelect->bindParam(':name', $name, PDO::PARAM_STR);
            $stmtSelect->bindParam(':type', $type, PDO::PARAM_STR);
            $stmtSelect->execute();
            $existing = $stmtSelect->fetchColumn();

            if ($existing !== false) {
                return (int)$existing; // Знайдено, повертаємо ID
            }

            // 2. Якщо не знайдено, створити нову
            $stmtInsert = $db->prepare("INSERT INTO categories (user_id, name, type, description) VALUES (:user_id, :name, :type, :description)");
            $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtInsert->bindParam(':name', $name, PDO::PARAM_STR);
            $stmtInsert->bindParam(':type', $type, PDO::PARAM_STR);
            $stmtInsert->bindParam(':description', $description, PDO::PARAM_STR);

            if ($stmtInsert->execute()) {
                return (int)$db->lastInsertId(); // Створено, повертаємо новий ID
            }

            error_log("Category Model (getOrCreateSystem): Failed INSERT for '{$name}', user ID {$userId}");
            return false;

        } catch (PDOException $e) {
            error_log("Category Model (getOrCreateSystem) Error for '{$name}', user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    // Публічні методи для отримання/створення конкретних системних категорій

    public function getOrCreateTransferCategory(int $userId, string $type): int|false
    {
        if ($type === 'expense') {
            return $this->getOrCreateSystemCategory($userId, 'Переказ вихідний', 'expense', 'Переказ коштів на інший рахунок.');
        } elseif ($type === 'income') {
            return $this->getOrCreateSystemCategory($userId, 'Переказ вхідний', 'income', 'Отримання коштів з іншого рахунку.');
        }
        return false; // Невірний тип
    }

    public function getOrCreateInitialBalanceCategory(int $userId): int|false
    {
        return $this->getOrCreateSystemCategory($userId, 'Початковий баланс', 'income', 'Початковий баланс рахунку при його створенні.');
    }

    public function getOrCreateAdjustmentCategory(int $userId, string $type): int|false
    {
         if (!in_array($type, ['income', 'expense'])) return false;
         // Можна використовувати одну категорію "Коригування балансу" (наприклад, expense)
         // або дві різні, залежно від знаку коригування. Створимо дві для ясності.
        return $this->getOrCreateSystemCategory($userId, 'Коригування балансу', $type, 'Коригувальна транзакція для зміни балансу рахунку.');
    }
}