<?php

use App\Models\Category;
use App\Models\Database;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private $pdoMock;
    private $statementMock;
    private $categoryModel;

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

        // Створюємо екземпляр Category моделі
        $this->categoryModel = new Category();
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    public function testFindByUserIdAndType(): void
    {
        $userId = 1;
        $type = 'expense';
        $expectedCategories = [['id' => 1, 'name' => 'Test Expense', 'description' => 'Test Desc', 'type' => 'expense']];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetchAll')->willReturn($expectedCategories);

        $categories = $this->categoryModel->findByUserIdAndType($userId, $type);
        $this->assertEquals($expectedCategories, $categories);
    }

    public function testFindAllByUserId(): void
    {
        $userId = 1;
        $expectedCategories = [['id' => 1, 'name' => 'Test', 'description' => 'Test Desc', 'type' => 'income']];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetchAll')->willReturn($expectedCategories);

        $categories = $this->categoryModel->findAllByUserId($userId);
        $this->assertEquals($expectedCategories, $categories);
    }

    public function testFindByIdAndUserId(): void
    {
        $categoryId = 1;
        $userId = 1;
        $expectedCategory = ['id' => 1, 'name' => 'Test', 'type' => 'income', 'description' => 'Test Desc'];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('fetch')->willReturn($expectedCategory);

        $category = $this->categoryModel->findByIdAndUserId($categoryId, $userId);
        $this->assertEquals($expectedCategory, $category);
    }

    public function testCreate(): void
    {
        $categoryData = ['user_id' => 1, 'name' => 'Їжа', 'type' => 'expense', 'description' => 'Покупки їжі'];
        $lastInsertId = '2'; // Очікуємо ID після вставки

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->pdoMock->method('lastInsertId')->willReturn($lastInsertId);

        $categoryId = $this->categoryModel->create($categoryData);
        $this->assertEquals($lastInsertId, $categoryId);
    }

    public function testUpdate(): void
    {
        $categoryId = 1;
        $userId = 1;
        $updateData = ['name' => 'Продукти харчування', 'type' => 'expense', 'description' => 'Оновлений опис'];

        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->statementMock->method('rowCount')->willReturn(1);

        $result = $this->categoryModel->update($categoryId, $userId, $updateData);
        $this->assertTrue($result);
    }

    public function testAddDefaultCategoriesForUser(): void
    {
        $userId = 2; // Інший ID користувача
        $this->statementMock->method('bindParam')->willReturn(true);
        $this->statementMock->method('bindValue')->willReturn(true);
        $this->statementMock->method('execute')->willReturn(true);
        $this->pdoMock->method('lastInsertId')->willReturnOnConsecutiveCalls(3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17); // Мокуємо ID для вставлених категорій

        $result = $this->categoryModel->addDefaultCategoriesForUser($userId);
        $this->assertTrue($result);
    }
}