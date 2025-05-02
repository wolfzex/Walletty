<?php
// app/Core/Router.php

declare(strict_types=1);

namespace App\Core;

use Exception; // Додаємо для використання винятків

/**
 * Клас маршрутизатора.
 * Реєструє маршрути та знаходить відповідний контролер/дію для запиту.
 */
class Router
{
    /**
     * Масив для зберігання зареєстрованих маршрутів.
     * Структура: ['get' => ['/path' => [Controller::class, 'method']], 'post' => [...]]
     * @var array<string, array<string, array{0: class-string, 1: string}>>
     */
    private array $routes = [];
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        // Ініціалізуємо масив для підтримуваних методів
        $this->routes['get'] = [];
        $this->routes['post'] = [];
        // Можна додати інші методи (PUT, DELETE), якщо потрібно
    }

    /**
     * Реєструє GET-маршрут.
     *
     * @param string $path Шаблон шляху (наприклад, '/users', '/accounts').
     * @param array{0: class-string, 1: string} $callback Масив [КласКонтролера::class, 'метод'].
     * @return $this
     */
    public function get(string $path, array $callback): self
    {
        // Нормалізуємо шлях (видаляємо слеш в кінці, якщо він не є кореневим шляхом)
        $normalizedPath = ($path === '/') ? '/' : rtrim($path, '/');
        $this->routes['get'][$normalizedPath] = $callback;
        return $this; // Дозволяє ланцюжкові виклики ->get(...)->get(...)
    }

    /**
     * Реєструє POST-маршрут.
     *
     * @param string $path Шаблон шляху.
     * @param array{0: class-string, 1: string} $callback Масив [КласКонтролера::class, 'метод'].
     * @return $this
     */
    public function post(string $path, array $callback): self
    {
        $normalizedPath = ($path === '/') ? '/' : rtrim($path, '/');
        $this->routes['post'][$normalizedPath] = $callback;
        return $this;
    }

    /**
     * Знаходить та повертає обробник для поточного запиту.
     *
     * @return array{0: class-string, 1: string}|false Повертає масив [КласКонтролера::class, 'метод'] або false, якщо маршрут не знайдено.
     * @throws Exception Якщо метод HTTP не підтримується роутером.
     */
    public function resolve(): array|false
    {
        $path = $this->request->getPath();
        // Забезпечуємо, що кореневий шлях це '/', а не порожній рядок
        $path = ($path === '') ? '/' : $path;
        $method = $this->request->getMethod();

        // Перевірка, чи підтримується метод запиту
        if (!isset($this->routes[$method])) {
            // Можна викинути виняток або повернути специфічний маркер помилки
            // throw new Exception("Метод запиту '{$method}' не підтримується.");
             return false; // Або просто повертаємо false, якщо метод не підтримується
        }

        // Пошук точного співпадіння маршруту
        $callback = $this->routes[$method][$path] ?? null;

        // Якщо точне співпадіння знайдено
        if ($callback) {
             // Перевірка, чи клас контролера існує
            if (!class_exists($callback[0])) {
                error_log("Клас контролера '{$callback[0]}' не знайдено для маршруту '{$method} {$path}'.");
                return false; // Або викинути виняток
            }
            // Перевірка, чи метод в контролері існує
            if (!method_exists($callback[0], $callback[1])) {
                 error_log("Метод '{$callback[1]}' не знайдено в класі контролера '{$callback[0]}' для маршруту '{$method} {$path}'.");
                return false; // Або викинути виняток
            }
            return $callback; // Повертаємо [КласКонтролера::class, 'метод']
        }

        // Якщо маршрут не знайдено
        // TODO: Додати підтримку маршрутів з параметрами (наприклад, /accounts/{id})
        // Зараз повертаємо false, що буде оброблено як помилка 404

        return false;
    }

    /**
     * Допоміжна функція для генерації URL (можна розширити)
     *
     * @param string $path Відносний шлях (наприклад, '/login')
     * @param array $params GET параметри
     * @return string Повний URL
     */
    public function url(string $path, array $params = []): string
    {
        $url = rtrim($path, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        // Потрібно додати базовий URL сайту, якщо він не в корені домену
        return $url;
    }
}