<?php

declare(strict_types=1);
define('DB_PATH', ':memory:');

use App\Controllers\AccountController;
use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Database;
use PHPUnit\Framework\TestCase;

class AccountControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
        CREATE TABLE accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            currency TEXT NOT NULL
        );
    ");

        $pdo->exec("
        INSERT INTO accounts (user_id, name, currency) VALUES
        (1, 'Рахунок 1', 'UAH'),
        (1, 'Рахунок 2', 'USD');
    ");

        Database::getInstance()->setTestConnection($pdo);
    }

    public function testIndex_integration(): void
    {
        $request = new Request();
        $session = new Session();

        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Тестовий Користувач';

        $controller = new AccountController($request, $session);

        $controllerWithRender = new class($request, $session) extends AccountController {
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

        $controllerWithRender->accountModel = new Account();

        $controllerWithRender->index();
        
        $data = $controllerWithRender->getRenderedData();
        $this->assertArrayHasKey('accounts', $data);
        $this->assertCount(2, $data['accounts']);
        $this->assertEquals('Рахунок 1', $data['accounts'][0]['name']);
    }
}
