<?php
// app/Core/Router.php

declare(strict_types=1);

namespace App\Core;

use Exception;

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
        $this->routes['get'] = [];
        $this->routes['post'] = [];
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
        $normalizedPath = ($path === '/') ? '/' : rtrim($path, '/');
        $this->routes['get'][$normalizedPath] = $callback;
        return $this;
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
        $path = ($path === '') ? '/' : $path;
        $method = $this->request->getMethod();

        if (!isset($this->routes[$method])) {
             return false;
        }

        $callback = $this->routes[$method][$path] ?? null;

        if ($callback) {
            if (!class_exists($callback[0])) {
                error_log("Клас контролера '{$callback[0]}' не знайдено для маршруту '{$method} {$path}'.");
                return false; // Або викинути виняток
            }
            if (!method_exists($callback[0], $callback[1])) {
                 error_log("Метод '{$callback[1]}' не знайдено в класі контролера '{$callback[0]}' для маршруту '{$method} {$path}'.");
                return false;
            }
            return $callback;
        }

        return false;
    }

    /**
     * Допоміжна функція для генерації URL
     *
     * @param string $path Відносний шлях
     * @param array $params GET параметри
     * @return string Повний URL
     */
    public function url(string $path, array $params = []): string
    {
        $url = rtrim($path, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}