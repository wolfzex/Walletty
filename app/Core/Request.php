<?php
// app/Core/Request.php

declare(strict_types=1);

namespace App\Core;

/**
 * Клас для обробки HTTP-запиту.
 * Надає доступ до URI, методу запиту та вхідних даних (GET, POST).
 */
class Request
{
    private array $getParams;
    private array $postParams;
    private array $serverParams;

    public function __construct()
    {
        $this->getParams = $_GET;
        $this->postParams = $_POST;
        $this->serverParams = $_SERVER;
    }

    /**
     * Отримує шлях запиту (URI без GET-параметрів).
     *
     * @return string Шлях URI.
     */
    public function getPath(): string
    {
        $path = $this->serverParams['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return rtrim($path, '/'); // Видаляємо слеш в кінці, якщо він є
        }
        // Видаляємо слеш в кінці шляху до знаку питання
        return rtrim(substr($path, 0, $position), '/');
    }

    /**
     * Отримує метод HTTP-запиту (GET, POST тощо).
     *
     * @return string Метод запиту у нижньому регістрі.
     */
    public function getMethod(): string
    {
        return strtolower($this->serverParams['REQUEST_METHOD'] ?? 'get');
    }

    /**
     * Перевіряє, чи є метод запиту GET.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'get';
    }

    /**
     * Перевіряє, чи є метод запиту POST.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'post';
    }

    /**
     * Отримує всі GET-параметри або конкретний параметр за ключем.
     *
     * @param string|null $key Ключ параметра (необов'язково).
     * @param mixed $default Значення за замовчуванням, якщо ключ не знайдено.
     * @return mixed Масив параметрів або значення конкретного параметра.
     */
    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->getParams;
        }
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Отримує всі POST-параметри або конкретний параметр за ключем.
     * Застосовує базову фільтрацію для безпеки.
     *
     * @param string|null $key Ключ параметра (необов'язково).
     * @param mixed $default Значення за замовчуванням, якщо ключ не знайдено.
     * @return mixed Масив параметрів або значення конкретного параметра.
     */
    public function post(string $key = null, mixed $default = null): mixed
    {
        $body = [];
        if ($this->isPost()) {
            foreach ($this->postParams as $postKey => $value) {
                // Проста санітизація (можна розширити)
                $body[$postKey] = filter_input(INPUT_POST, $postKey, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        if ($key === null) {
            return $body;
        }
        return $body[$key] ?? $default;
    }

     /**
      * Отримує параметр із суперглобального масиву $_SERVER.
      *
      * @param string $key Ключ параметра.
      * @param mixed|null $default Значення за замовчуванням.
      * @return mixed
      */
     public function server(string $key, mixed $default = null): mixed
     {
         return $this->serverParams[$key] ?? $default;
     }

    /**
     * Повертає повний URL запиту.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $protocol = (!empty($this->serverParams['HTTPS']) && $this->serverParams['HTTPS'] !== 'off' || $this->serverParams['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $this->serverParams['HTTP_HOST'] ?? 'localhost';
        $uri = $this->serverParams['REQUEST_URI'] ?? '/';
        return $protocol . $host . $uri;
    }
}