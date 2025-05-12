<?php

declare(strict_types=1);
define('DB_PATH', ':memory:');

use App\Controllers\TransactionController;
use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Category;
use App\Models\Database;
use App\Models\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionControllerTest extends TestCase
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
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                description TEXT
            );
        ");

        $pdo->exec("
            CREATE TABLE transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                account_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                category_id INTEGER,
                amount REAL NOT NULL,
                description TEXT,
                transaction_date DATE NOT NULL
            );
        ");

        $pdo->exec("
            INSERT INTO accounts (user_id, name, currency) VALUES
            (1, 'Рахунок 1', 'UAH'),
            (1, 'Рахунок 2', 'USD');
        ");

        $pdo->exec("
            INSERT INTO categories (user_id, name, type, description) VALUES
            (1, 'Їжа', 'expense', 'Продукти'),
            (1, 'Зарплата', 'income', 'ЗП');
        ");

        $pdo->exec("
            INSERT INTO transactions (account_id, user_id, category_id, amount, description, transaction_date) VALUES
            (1, 1, 1, 100.50, 'Купівля продуктів', '2025-05-12'),
            (2, 1, 2, 200.00, 'Отримано зарплату', '2025-05-10');
        ");

        Database::getInstance()->setTestConnection($pdo);
    }

    public function testIndex_withExistingAccounts_rendersTransactionsPageWithData(): void
    {
        $request = new Request();
        $session = new Session();

        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Тестовий Користувач';

        $controllerWithRender = new class($request, $session) extends TransactionController {
            public array $renderedData = [];
            public bool $redirected = false;
            public string $redirectUrl = '';

            public function render(string $view, array $data = [], string $layout = 'main'): void
            {
                $this->renderedData = $data;
            }

            public function redirect(string $url, int $code = 302): void
            {
                $this->redirected = true;
                $this->redirectUrl = $url;
            }

            public function getRenderedData(): array
            {
                return $this->renderedData;
            }

            public function getRedirected(): bool
            {
                return $this->redirected;
            }

            public function getRedirectUrl(): string
            {
                return $this->redirectUrl;
            }
        };

        $controllerWithRender->accountModel = new Account();
        $controllerWithRender->transactionModel = new Transaction();
        $controllerWithRender->categoryModel = new Category();

        $controllerWithRender->index();

        $data = $controllerWithRender->getRenderedData();

        $this->assertFalse($controllerWithRender->getRedirected());

        $this->assertArrayHasKey('pageTitle', $data);
        $this->assertArrayHasKey('transactions', $data);
        $this->assertArrayHasKey('accounts', $data);
        $this->assertArrayHasKey('allUserCategories', $data);

        $this->assertCount(2, $data['accounts']);
        $this->assertCount(2, $data['allUserCategories']);
        $this->assertEquals('Транзакції - Рахунок 1', $data['pageTitle']);
    }
}