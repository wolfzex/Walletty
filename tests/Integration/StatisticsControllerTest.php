<?php

declare(strict_types=1);
define('DB_PATH', ':memory:');

use App\Controllers\StatisticsController;
use App\Core\Request;
use App\Core\Session;
use App\Models\Database;
use PHPUnit\Framework\TestCase;

class StatisticsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Мінімальні таблиці
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

        // Мінімальні дані
        $pdo->exec("
            INSERT INTO categories (user_id, name, type, description) VALUES
            (1, 'Їжа', 'expense', 'Продукти'),
            (1, 'Зарплата', 'income', 'ЗП');
        ");

        $pdo->exec("
            INSERT INTO accounts (user_id, name, currency) VALUES
            (1, 'Основний рахунок', 'UAH');
        ");

        Database::getInstance()->setTestConnection($pdo);
    }

    public function testIndex_showsStatisticsPage(): void
    {
        $_GET['start_date'] = '2024-05-01';
        $_GET['end_date'] = '2024-05-31';

        $request = new Request();
        $session = new Session();

        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Тест';

        $controller = new class($request, $session) extends StatisticsController {
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

        // Мінімальні перевірки
        $this->assertArrayHasKey('pageTitle', $data);
        $this->assertArrayHasKey('statisticsData', $data);
        $this->assertArrayHasKey('accounts', $data);
        $this->assertEquals('Статистика - Основний рахунок', $data['pageTitle']);
    }
}
