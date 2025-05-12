<?php

declare(strict_types=1);
define('DB_PATH', ':memory:');

use App\Controllers\CategoryController;
use App\Core\Request;
use App\Core\Session;
use App\Models\Database;
use App\Models\Category;
use App\Models\Account;
use PHPUnit\Framework\TestCase;

class CategoryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                description TEXT
            );
        ");

        $pdo->exec("
            CREATE TABLE accounts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                currency TEXT NOT NULL
            );
        ");

        $pdo->exec("
            INSERT INTO categories (user_id, name, type, description) VALUES
            (1, 'Їжа', 'expense', 'Продукти'),
            (1, 'Зарплата', 'income', 'Щомісячна зарплата');
        ");

        $pdo->exec("
            INSERT INTO accounts (user_id, name, currency) VALUES
            (1, 'Основний рахунок', 'UAH');
        ");

        Database::getInstance()->setTestConnection($pdo);
    }

    public function testIndex_integration(): void
    {
        $request = new Request();
        $session = new Session();

        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Тестовий Користувач';

        $controller = new class($request, $session) extends CategoryController {
            public array $renderedData = [];

            public function render(string $view, array $data = [], string $layout = 'main'): void
            {
                $this->renderedData = $data;
            }

            public function getRenderedData(): array
            {
                return $this->renderedData;
            }
        };

        $controller->index();

        $data = $controller->getRenderedData();

        // Перевірка виведених даних
        $this->assertArrayHasKey('categories', $data);
        $this->assertCount(1, $data['categories']);
        $this->assertEquals('Їжа', $data['categories'][0]['name']);
        $this->assertEquals('Основний рахунок', $data['accounts'][0]['name']);
        $this->assertEquals('Категорії (Витрати)', $data['pageTitle']);
    }
}
