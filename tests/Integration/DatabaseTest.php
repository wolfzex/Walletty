<?php
// tests/Integration/DatabaseConnectionTest.php

declare(strict_types=1);

// Підключення конфігурації
require_once __DIR__ . '/../../app/config.php';

use PHPUnit\Framework\TestCase;
use App\Models\Database;

final class DatabaseTest extends TestCase
{
    public function testConnectionToDatabase(): void
    {
        // Отримуємо екземпляр підключення
        $db = Database::getInstance()->getConnection();

        // Перевіряємо, що це екземпляр PDO
        $this->assertInstanceOf(PDO::class, $db, 'Підключення не є обʼєктом PDO');

        // Пробуємо виконати простий SQL-запит
        $stmt = $db->query('SELECT 1');
        $result = $stmt->fetchColumn();

        // Очікуємо, що результат SELECT 1 — це 1
        $this->assertEquals(1, (int)$result, 'Результат SELECT 1 не дорівнює 1');
    }
}
