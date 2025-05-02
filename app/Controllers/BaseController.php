<?php
// app/Controllers/BaseController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use Exception; // Для винятків

/**
 * Абстрактний базовий контролер.
 * Надає спільні властивості та методи для всіх контролерів додатку.
 */
abstract class BaseController
{
    protected Request $request;
    protected Session $session;

    /**
     * Конструктор базового контролера.
     * Приймає об'єкти Request та Session для використання в контролерах-нащадках.
     *
     * @param Request $request Об'єкт запиту.
     * @param Session $session Об'єкт сесії.
     */
    public function __construct(Request $request, Session $session)
    {
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * Рендерить (відображає) вид разом з макетом.
     *
     * @param string $view Назва файлу виду (наприклад, 'auth/login', 'accounts/index'). Шлях відносно папки app/Views.
     * @param array $data Асоціативний масив даних для передачі у вид (змінні).
     * @param string $layout Назва файлу макету (layout) для використання.
     * @throws Exception Якщо файл виду або макету не знайдено.
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Перетворюємо асоціативний масив даних на окремі змінні
        // Наприклад, ['title' => 'My Page'] стане змінною $title = 'My Page'; у файлі виду
        extract($data);

        // Формуємо повний шлях до файлу виду
        $viewPath = APP_PATH . "/Views/{$view}.php";

        if (!file_exists($viewPath)) {
            throw new Exception("Файл виду '{$viewPath}' не знайдено.");
        }

        // Починаємо буферизацію виводу, щоб захопити вміст виду
        ob_start();
        try {
            include $viewPath; // Підключаємо файл виду, змінні з $data тепер доступні тут
        } catch (\Throwable $e) {
            ob_end_clean(); // Очищаємо буфер у разі помилки у виді
            throw $e; // Перекидаємо виняток далі
        }
        // Отримуємо вміст буфера (згенерований HTML виду) і очищуємо буфер
        $content = ob_get_clean();

        // Формуємо повний шлях до файлу макету
        $layoutPath = APP_PATH . "/Views/layouts/{$layout}.php";

        if (!file_exists($layoutPath)) {
            throw new Exception("Файл макету '{$layoutPath}' не знайдено.");
        }

        // Підключаємо файл макету.
        // Всередині макету буде доступна змінна $content (HTML з файлу виду)
        // та всі змінні з оригінального масиву $data.
        include $layoutPath;
    }

    /**
     * Перенаправляє користувача на інший URL.
     *
     * @param string $url URL для перенаправлення.
     * @param int $statusCode HTTP статус код (за замовчуванням 302 Found).
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        // Переконуємося, що заголовок ще не був відправлений
        if (!headers_sent()) {
            header('Location: ' . $url, true, $statusCode);
        } else {
            // Якщо заголовки вже відправлені, використовуємо JavaScript або HTML редірект
             // (менш надійно, але краще ніж нічого)
             echo "<script>window.location.href='{$url}';</script>";
             echo "<noscript><meta http-equiv='refresh' content='0;url={$url}'></noscript>";
        }
        exit; // Завершуємо виконання скрипта після редіректу
    }

    /**
     * Перевіряє, чи користувач автентифікований.
     * Якщо ні, перенаправляє на сторінку входу.
     * Цей метод можна викликати на початку методів контролерів,
     * які потребують автентифікації.
     */
    protected function checkAuthentication(): void
    {
        if (!$this->session->has('user_id')) {
             $this->session->flash('error', 'Будь ласка, увійдіть для доступу до цієї сторінки.');
             // Замість '/auth/login' краще використовувати метод Router::url() в майбутньому
             $this->redirect('/auth/login');
        }
        // Можна додати перевірку ролей або прав доступу тут
    }

    /**
     * Перевіряє, чи користувач вже автентифікований.
     * Якщо так, перенаправляє на сторінку за замовчуванням (наприклад, рахунки).
     * Цей метод можна викликати на початку методів контролерів,
     * які призначені для неавтентифікованих користувачів (наприклад, сторінка входу/реєстрації).
     */
    protected function redirectIfAuthenticated(): void
    {
        if ($this->session->has('user_id')) {
             // Замість '/' краще використовувати метод Router::url() в майбутньому
             $this->redirect('/');
        }
    }
}