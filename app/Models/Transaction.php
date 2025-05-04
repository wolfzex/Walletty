<?php
// app/Models/Transaction.php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Models\Database;
use PDOException;
use DateTime;

/**
 * Модель для роботи з транзакціями (таблиця 'transactions').
 */
class Transaction
{
    /**
     * Створює нову транзакцію.
     *
     * @param array $data ['account_id', 'category_id', 'amount', 'date', 'description']
     * @return int|false ID створеної транзакції або false у разі помилки.
     */
    public function create(array $data): int|false
    {
        if (empty($data['account_id']) || empty($data['category_id']) || !isset($data['amount']) || empty($data['date'])) {
             error_log("Transaction Model (create): Не вистачає даних для створення транзакції.");
             return false;
        }
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
             error_log("Transaction Model (create): Сума транзакції має бути позитивним числом.");
             return false;
        }
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
        if (!$d || $d->format('Y-m-d H:i:s') !== $data['date']) {
             $d = DateTime::createFromFormat('Y-m-d\TH:i', $data['date']);
             if ($d) {
                 $data['date'] = $d->format('Y-m-d H:i:s');
             } else {
                 error_log("Transaction Model (create): Невірний формат дати '{$data['date']}'. Очікується 'Y-m-d H:i:s'.");
                 return false;
             }
        }


        $sql = "INSERT INTO transactions (account_id, category_id, amount, date, description)
                VALUES (:account_id, :category_id, :amount, :date, :description)";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':account_id', $data['account_id'], PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':date', $data['date'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? '', PDO::PARAM_STR);
            if ($stmt->execute()) {
                return (int)$db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Transaction Model (create) Error: " . $e->getMessage());
             if (strpos($e->getMessage(), 'FOREIGN KEY constraint failed') !== false) {
                 error_log("Transaction Model (create): Помилка зовнішнього ключа (account_id або category_id не існує?).");
             }
            return false;
        }
    }

    /**
     * Знаходить транзакцію за її ID та перевіряє належність користувачу через рахунок.
     *
     * @param int $transactionId ID транзакції.
     * @param int $userId ID користувача.
     * @return array|false Дані транзакції або false.
     */
    public function findByIdAndUserId(int $transactionId, int $userId): array|false
    {
        $sql = "SELECT t.* FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                WHERE t.id = :transaction_id AND a.user_id = :user_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Transaction Model (findByIdAndUserId) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Знаходить транзакції для рахунку з урахуванням фільтрів.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача (для перевірки власності).
     * @param array $filters Фільтри ['category_id' => int, 'start_date' => 'Y-m-d', 'end_date' => 'Y-m-d'].
     * @return array Масив транзакцій.
     */
    public function findByAccountId(int $accountId, int $userId, array $filters = []): array
    {
        $sql = "SELECT t.id, t.amount, t.date, t.description as trans_desc,
                       c.name as category_name, c.type as category_type
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                JOIN accounts a ON t.account_id = a.id
                WHERE t.account_id = :account_id AND a.user_id = :user_id";

        $params = [
            ':account_id' => $accountId,
            ':user_id' => $userId
        ];

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = (int)$filters['category_id'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(t.date) >= DATE(:start_date)";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(t.date) <= DATE(:end_date)";
            $params[':end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY t.date DESC, t.id DESC";

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);

            foreach ($params as $key => &$val) {
                $paramType = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindParam($key, $val, $paramType);
            }
            unset($val);

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Transaction Model (findByAccountId) Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Отримує N останніх транзакцій для рахунку.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача.
     * @param int $limit Кількість транзакцій.
     * @return array
     */
    public function findRecentByAccountId(int $accountId, int $userId, int $limit = 5): array
    {
        $sql = "SELECT t.id, t.amount, t.date, t.description as trans_desc,
                       c.name as category_name, c.type as category_type
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                JOIN accounts a ON t.account_id = a.id
                WHERE t.account_id = :account_id AND a.user_id = :user_id
                ORDER BY t.date DESC, t.id DESC
                LIMIT :limit";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Transaction Model (findRecentByAccountId) Error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Видаляє конкретну транзакцію, перевіряючи власника рахунку.
     *
     * @param int $transactionId ID транзакції.
     * @param int $userId ID користувача.
     * @return bool True у разі успіху, false у разі помилки.
     */
    public function delete(int $transactionId, int $userId): bool
    {
        $transaction = $this->findByIdAndUserId($transactionId, $userId);
        if (!$transaction) {
            error_log("Transaction Model (delete): Спроба видалення неіснуючої або чужої транзакції ID {$transactionId} користувачем ID {$userId}.");
            return false;
        }

        $sql = "DELETE FROM transactions WHERE id = :id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Transaction Model (delete) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Видаляє ВСІ транзакції для вказаного рахунку користувача.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача (для перевірки власності).
     * @return bool True у разі успіху (навіть якщо транзакцій не було), false у разі помилки.
     */
    public function deleteByAccountId(int $accountId, int $userId): bool
    {
        $accountModel = new Account(); // Потрібна модель Account
        $account = $accountModel->findByIdAndUserId($accountId, $userId);
        if (!$account) {
            error_log("Transaction Model (deleteByAccountId): Спроба видалення транзакцій для неіснуючого або чужого рахунку ID {$accountId} користувачем ID {$userId}.");
            return false;
        }

        $sql = "DELETE FROM transactions WHERE account_id = :account_id";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Transaction Model (deleteByAccountId) Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Розраховує загальні доходи, витрати та баланс для рахунку.
     *
     * @param int $accountId ID рахунку.
     * @param int $userId ID користувача.
     * @return array ['income' => float, 'expense' => float, 'balance' => float]
     */
    public function getAccountSummary(int $accountId, int $userId): array
    {
        $summary = ['income' => 0.0, 'expense' => 0.0, 'balance' => 0.0];
        $sql = "SELECT c.type as category_type, SUM(t.amount) as total_amount
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                JOIN accounts a ON t.account_id = a.id
                WHERE t.account_id = :account_id AND a.user_id = :user_id
                GROUP BY c.type";
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll();

            foreach ($results as $row) {
                if ($row['category_type'] === 'income') {
                    $summary['income'] = (float)$row['total_amount'];
                } elseif ($row['category_type'] === 'expense') {
                    $summary['expense'] = (float)$row['total_amount'];
                }
            }
            $summary['balance'] = $summary['income'] - $summary['expense'];
            return $summary;
        } catch (PDOException $e) {
            error_log("Transaction Model (getAccountSummary) Error: " . $e->getMessage());
            return $summary;
        }
    }

    /**
      * Отримує дані транзакцій для сторінки статистики.
      *
      * @param int $accountId ID рахунку.
      * @param int $userId ID користувача.
      * @param string $startDate 'Y-m-d'.
      * @param string $endDate 'Y-m-d'.
      * @return array Масив транзакцій ['amount', 'transaction_date', 'category_name', 'category_type'].
      */
     public function getStatisticsData(int $accountId, int $userId, string $startDate, string $endDate): array
     {
         $sql = "SELECT t.amount, DATE(t.date) as transaction_date,
                        c.name as category_name, c.type as category_type
                 FROM transactions t
                 JOIN categories c ON t.category_id = c.id
                 JOIN accounts a ON t.account_id = a.id
                 WHERE t.account_id = :account_id AND a.user_id = :user_id
                 AND DATE(t.date) BETWEEN DATE(:start_date) AND DATE(:end_date)
                 ORDER BY t.date ASC";
         try {
             $db = Database::getInstance()->getConnection();
             $stmt = $db->prepare($sql);
             $stmt->bindParam(':account_id', $accountId, PDO::PARAM_INT);
             $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
             $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
             $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
             $stmt->execute();
             return $stmt->fetchAll();
         } catch (PDOException $e) {
             error_log("Transaction Model (getStatisticsData) Error: " . $e->getMessage());
             return [];
         }
     }

}