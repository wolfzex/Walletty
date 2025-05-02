<?php
// app/Core/App.php

declare(strict_types=1);

namespace App\Core;

// Підключаємо класи ядра та контролери, які будемо використовувати для реєстрації маршрутів
use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\CategoryController;
use App\Controllers\TransactionController;
use App\Controllers\StatisticsController;
use Exception; // Для обробки винятків

class App
{
    private Router $router;
    private Request $request;
    private Session $session;

    public function __construct()
    {
        // Створюємо екземпляри основних компонентів
        $this->request = new Request();
        $this->router = new Router($this->request);
        $this->session = new Session(); // Сесія стартує автоматично в конструкторі Session

        // Реєструємо маршрути додатку
        $this->registerRoutes();
    }

    /**
     * Реєструє всі маршрути додатку.
     */
    private function registerRoutes(): void
    {
        // --- Маршрути автентифікації ---
        $this->router->get('/auth/login', [AuthController::class, 'showLogin']);
        $this->router->post('/auth/login', [AuthController::class, 'login']);
        $this->router->get('/auth/register', [AuthController::class, 'showRegister']);
        $this->router->post('/auth/register', [AuthController::class, 'register']);
        $this->router->get('/logout', [AuthController::class, 'logout']);

        // --- Маршрути за замовчуванням (головна сторінка) ---
        // Перенаправляємо корінь сайту на сторінку рахунків
        $this->router->get('/', [AccountController::class, 'index']);

        // --- Маршрути для Рахунків (Accounts) ---
        $this->router->get('/accounts', [AccountController::class, 'index']);
        $this->router->post('/accounts/add', [AccountController::class, 'add']);
        $this->router->post('/accounts/edit', [AccountController::class, 'edit']);
        $this->router->post('/accounts/delete', [AccountController::class, 'delete']);
        $this->router->post('/accounts/adjust_balance', [AccountController::class, 'adjustBalance']);
        // Можливо, знадобиться GET маршрут для показу конкретного рахунку,
        // якщо логіка index буде розділена, або для API:
        // $this->router->get('/accounts/{id}', [AccountController::class, 'show']);

        // --- Маршрути для Категорій (Categories) ---
        $this->router->get('/categories', [CategoryController::class, 'index']); // Буде обробляти GET параметр 'type'
        $this->router->post('/categories/add', [CategoryController::class, 'add']);
        $this->router->post('/categories/edit', [CategoryController::class, 'edit']);
        $this->router->post('/categories/delete', [CategoryController::class, 'delete']);

        // --- Маршрути для Транзакцій (Transactions) ---
        $this->router->get('/transactions', [TransactionController::class, 'index']); // Буде обробляти GET параметр 'account_id' та інші фільтри
        $this->router->post('/transactions/add', [TransactionController::class, 'add']);
        $this->router->post('/transactions/delete', [TransactionController::class, 'delete']);
        $this->router->post('/transactions/transfer', [TransactionController::class, 'transfer']);

        // --- Маршрути для Статистики (Statistics) ---
        $this->router->get('/statistics', [StatisticsController::class, 'index']); // Буде обробляти GET параметри 'account_id', 'start_date', 'end_date'

        // Додайте інші маршрути за потребою
    }

    /**
     * Запускає обробку поточного запиту.
     * Знаходить відповідний маршрут, створює контролер та викликає його метод.
     */
    public function run(): void
    {
        try {
            // Отримуємо обробник (контролер і метод) для поточного запиту
            $callback = $this->router->resolve();

            if ($callback === false) {
                // Маршрут не знайдено - помилка 404
                // TODO: Створити більш інформативну сторінку 404
                http_response_code(404);
                echo "<h1>404 Not Found</h1>";
                echo "Сторінку за адресою '{$this->request->getPath()}' не знайдено.";
                exit;
            }

            // Розпаковуємо масив з іменем класу контролера та методом
            [$controllerClass, $method] = $callback;

            // Перевіряємо ще раз (хоча Router вже мав би перевірити)
            if (!class_exists($controllerClass)) {
                throw new Exception("Клас контролера {$controllerClass} не існує.");
            }

            // Створюємо екземпляр контролера
            // Передаємо йому Request та Session (і, можливо, інші залежності)
            // Це простий варіант ін'єкції залежностей через конструктор
            // У майбутньому можна використовувати DI контейнер
            $controller = new $controllerClass($this->request, $this->session);

            if (!method_exists($controller, $method)) {
                throw new Exception("Метод {$method} не знайдено в контролері {$controllerClass}.");
            }

            // Викликаємо метод контролера
            // У майбутньому можна передавати параметри з URL сюди
            call_user_func([$controller, $method]);

        } catch (\Throwable $e) { // Ловимо будь-які помилки/винятки
            // Базова обробка помилок
            error_log("App Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            // TODO: Створити сторінку помилки 500
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1>";
            echo "Виникла внутрішня помилка сервера.";
            // В режимі розробки можна показувати деталі помилки
            if (ini_get('display_errors')) {
                echo "<hr><pre><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</pre>";
                echo "<pre><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</pre>";
                echo "<pre><strong>Trace:</strong>\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            }
            exit;
        }
    }
}