<?php

use App\Models\User;
use App\Models\Database;
use PHPUnit\Framework\TestCase;


class UserTest extends TestCase
{
    private $pdoMock;
    private $statementMock;
    private $userModel;

    protected function setUp(): void
    {
        // Створюємо мок-об'єкти PDO та PDOStatement
        $this->pdoMock = $this->createMock(PDO::class);
        $this->statementMock = $this->createMock(PDOStatement::class);

        $this->pdoMock->method('prepare')->willReturn($this->statementMock);

        $databaseMock = $this->createMock(Database::class);
        $databaseMock->method('getConnection')->willReturn($this->pdoMock);

        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $databaseMock);

        // Створюємо екземпляр User моделі
        $this->userModel = new User();
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    public function testFindByEmailExisting(): void
    {
        $email = 'test@example.com';
        $expectedUser = ['id' => 1, 'email' => $email, 'first_name' => 'Test', 'last_name' => 'User', 'password_hash' => 'hashed_password'];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn($expectedUser);

        $user = $this->userModel->findByEmail($email);
        $this->assertEquals($expectedUser, $user);
    }

    public function testFindByEmailNotFound(): void
    {
        $email = 'nonexistent@example.com';

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn(false);

        $user = $this->userModel->findByEmail($email);
        $this->assertFalse($user);
    }

    public function testFindByIdExisting(): void
    {
        $userId = 1;
        $expectedUser = ['id' => $userId, 'email' => 'test@example.com', 'first_name' => 'Test', 'last_name' => 'User'];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn($expectedUser);

        $user = $this->userModel->findById($userId);
        $this->assertEquals($expectedUser, $user);
    }

    public function testFindByIdNotFound(): void
    {
        $userId = 99;

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn(false);

        $user = $this->userModel->findById($userId);
        $this->assertFalse($user);
    }

    public function testCreate(): void
    {
        $userData = [
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password_hash' => 'another_hashed_password',
        ];
        $lastInsertId = '2';

        $this->pdoMock->method('beginTransaction')->willReturn(true);
        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->pdoMock->method('lastInsertId')->willReturn($lastInsertId);
        $this->pdoMock->method('commit')->willReturn(true);

        $userId = $this->userModel->create($userData);
        $this->assertEquals($lastInsertId, $userId);
    }

    public function testCreateFailsUserCreation(): void
    {
        $userData = [
            'email' => 'existinguser@example.com',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'password_hash' => 'some_hash',
        ];

        $this->pdoMock->method('beginTransaction')->willReturn(true);
        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(false);
        $this->pdoMock->method('rollBack')->willReturn(true);

        $userId = $this->userModel->create($userData);
        $this->assertFalse($userId);
    }

    public function testVerifyPasswordValid(): void
    {
        $passwordInput = 'secret';
        $passwordHash = password_hash($passwordInput, PASSWORD_DEFAULT);
        $this->assertTrue($this->userModel->verifyPassword($passwordInput, $passwordHash));
    }

    public function testVerifyPasswordInvalid(): void
    {
        $passwordInput = 'wrong_secret';
        $passwordHash = password_hash('secret', PASSWORD_DEFAULT);
        $this->assertFalse($this->userModel->verifyPassword($passwordInput, $passwordHash));
    }
}