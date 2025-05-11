<?php

use App\Models\Account;
use App\Models\Database;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    private $pdoMock;
    private $statementMock;
    private $accountModel;

    protected function setUp(): void
    {
        // Створюємо мок-об'єкт PDO
        $this->pdoMock = $this->createMock(PDO::class);

        // Створюємо мок-об'єкт PDOStatement
        $this->statementMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')->willReturn($this->statementMock);

        // Створюємо мок-об'єкт Database та налаштовуємо getConnection()
        $databaseMock = $this->createMock(Database::class);
        $databaseMock->method('getConnection')->willReturn($this->pdoMock);

        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $databaseMock);

        // Створюємо екземпляр Account моделі
        $this->accountModel = new Account();
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    public function testGetAllowedCurrencies(): void
    {
        $currencies = $this->accountModel->getAllowedCurrencies();
        $this->assertIsArray($currencies);
        $this->assertNotEmpty($currencies);
    }

    public function testFindAllByUserId(): void
    {
        $userId = 1;
        $expectedAccounts = [];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetchAll')->willReturn($expectedAccounts);

        $accounts = $this->accountModel->findAllByUserId($userId);
        $this->assertEquals($expectedAccounts, $accounts);
    }

    public function testFindByIdAndUserId(): void
    {
        $accountId = 1;
        $userId = 1;
        $expectedAccount = [];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn($expectedAccount);

        $account = $this->accountModel->findByIdAndUserId($accountId, $userId);
        $this->assertEquals($expectedAccount, $account);
    }

    public function testCreate(): void
    {
        $accountData = ['user_id' => 1, 'name' => 'My Account', 'currency' => 'USD'];
        $lastInsertId = '123'; // Return the ID as a string

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->pdoMock->method('lastInsertId')->willReturn($lastInsertId);

        $accountId = $this->accountModel->create($accountData);
        $this->assertEquals($lastInsertId, $accountId);
    }

    public function testUpdate(): void
    {
        $accountId = 1;
        $userId = 1;
        $updateData = ['name' => 'Updated', 'currency' => 'USD'];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('rowCount')->willReturn(1);

        $result = $this->accountModel->update($accountId, $userId, $updateData);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $accountId = 1;
        $userId = 1;

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('rowCount')->willReturn(1);

        $result = $this->accountModel->delete($accountId, $userId);
        $this->assertTrue($result);
    }
}
