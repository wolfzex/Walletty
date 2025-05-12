<?php

use App\Models\Transaction;
use App\Models\Database;
use PHPUnit\Framework\TestCase;


class TransactionTest extends TestCase
{
    private $pdoMock;
    private $statementMock;
    private $transactionModel;

    protected function setUp(): void
    {
        // Створюємо мок-об'єкти PDO та PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->statementMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')->willReturn($this->statementMock);

        // Створюємо поведінку getConnection() для Database
        $databaseMock = $this->createMock(Database::class);
        $databaseMock->method('getConnection')->willReturn($this->pdoMock);

        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $databaseMock);

        // Створюємо екземпляр Transaction моделі
        $this->transactionModel = new Transaction();
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    public function testCreate(): void
    {
        $transactionData = [
            'account_id' => 1,
            'category_id' => 2,
            'amount' => 100.50,
            'date' => '2025-05-11 10:00:00',
            'description' => 'Тестова транзакція',
        ];
        $lastInsertId = '1';

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->pdoMock->method('lastInsertId')->willReturn($lastInsertId);

        $transactionId = $this->transactionModel->create($transactionData);
        $this->assertEquals($lastInsertId, $transactionId);
    }

    public function testCreateInvalidAmount(): void
    {
        $transactionData = [
            'account_id' => 1,
            'category_id' => 2,
            'amount' => 0,
            'date' => '2025-05-11 10:00:00',
            'description' => 'Тестова транзакція з невірною сумою',
        ];

        $this->statementMock->expects($this->never())->method('bindParam');
        $this->statementMock->expects($this->never())->method('execute');
        $this->pdoMock->expects($this->never())->method('lastInsertId');

        $transactionId = $this->transactionModel->create($transactionData);
        $this->assertFalse($transactionId);
    }

    public function testFindByIdAndUserId(): void
    {
        $transactionId = 1;
        $userId = 1;
        $expectedTransaction = [
            'id' => 1,
            'account_id' => 1,
            'category_id' => 2,
            'amount' => 100.50,
            'date' => '2025-05-11 10:00:00',
            'description' => 'Тестова транзакція',
        ];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn($expectedTransaction);

        $transaction = $this->transactionModel->findByIdAndUserId($transactionId, $userId);
        $this->assertEquals($expectedTransaction, $transaction);
    }

}