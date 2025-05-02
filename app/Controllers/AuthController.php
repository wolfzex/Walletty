<?php
// app/Controllers/AuthController.php

declare(strict_types=1);

namespace App\Controllers;

// Базовий контролер, Модель користувача, Ядро
use App\Core\Request;
use App\Core\Session;
use App\Models\User;

class AuthController extends BaseController
{
    private User $userModel;

    // Конструктор отримує залежності від батьківського BaseController
    public function __construct(Request $request, Session $session)
    {
        parent::__construct($request, $session); // Викликаємо конструктор батька
        $this->userModel = new User(); // Створюємо екземпляр моделі User
    }

    /**
     * Показує сторінку входу.
     */
    public function showLogin(): void
    {
        $this->redirectIfAuthenticated(); // Перенаправляємо, якщо вже залогінений

        // Отримуємо повідомлення з сесії (після редіректу)
        $data = [
            'pageTitle' => 'Вхід',
            'error' => $this->session->getFlash('error'),
            'success' => $this->session->getFlash('success'),
            'old_email' => $this->session->getFlash('old_email') // Для відновлення email у формі
        ];
        // Рендеримо вид 'auth/login' використовуючи макет 'auth'
        $this->render('auth/login', $data, 'auth');
    }

    /**
     * Обробляє POST-запит на вхід.
     */
    public function login(): void
    {
        $this->redirectIfAuthenticated();

        if (!$this->request->isPost()) {
            $this->redirect('/auth/login'); // Дозволяємо тільки POST
        }

        $email = trim($this->request->post('email', ''));
        $password = $this->request->post('password', ''); // Пароль не обрізаємо
        $errors = [];

        // --- Валідація ---
        if (empty($email)) {
            $errors['email'] = 'Будь ласка, введіть вашу пошту.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Будь ласка, введіть коректну адресу пошти.';
        }
        if (empty($password)) {
            $errors['password'] = 'Будь ласка, введіть пароль.';
        }

        // --- Спроба автентифікації ---
        if (empty($errors)) {
            $user = $this->userModel->findByEmail($email);

            if ($user && $this->userModel->verifyPassword($password, $user['password_hash'])) {
                // Успішний вхід
                $this->session->regenerate(true); // Регенеруємо ID сесії
                $this->session->set('user_id', $user['id']); // Зберігаємо ID користувача
                $this->session->set('user_name', $user['first_name']); // Можна зберегти ім'я для швидкого доступу

                $this->redirect('/'); // Перенаправляємо на головну сторінку (рахунки)
            } else {
                // Неправильна пошта або пароль
                // Повертаємо користувача на форму входу з помилкою
                 $this->session->flash('error', 'Неправильна пошта або пароль.');
                 $this->session->flash('old_email', $email); // Зберігаємо email для повторного заповнення
                 $this->redirect('/auth/login');
            }
        } else {
            // Помилки валідації - показуємо форму знову з помилками
             $this->session->flash('error', 'Будь ласка, виправте помилки у формі.');
             $this->session->flash('errors', $errors); // Зберігаємо масив помилок
             $this->session->flash('old_email', $email);
             $this->redirect('/auth/login'); // Редірект для показу flash-повідомлень
            // Або можна рендерити одразу:
            // $data = ['pageTitle' => 'Вхід', 'errors' => $errors, 'old_email' => $email];
            // $this->render('auth/login', $data, 'auth');
            // Але тоді flash-повідомлення можуть не знадобитися для помилок валідації
        }
    }

    /**
     * Показує сторінку реєстрації.
     */
    public function showRegister(): void
    {
        $this->redirectIfAuthenticated();

        $data = [
            'pageTitle' => 'Реєстрація',
            'errors' => $this->session->getFlash('errors') ?: [], // Отримуємо помилки валідації
            'old_input' => $this->session->getFlash('old_input') ?: [] // Отримуємо старі введені дані
        ];
        $this->render('auth/register', $data, 'auth');
    }

    /**
     * Обробляє POST-запит на реєстрацію.
     */
    public function register(): void
    {
        $this->redirectIfAuthenticated();

        if (!$this->request->isPost()) {
            $this->redirect('/auth/register'); // Дозволяємо тільки POST
        }

        // Отримуємо дані з форми
        $firstName = trim($this->request->post('first_name', ''));
        $lastName = trim($this->request->post('last_name', ''));
        $email = trim($this->request->post('email', ''));
        $password = $this->request->post('password', ''); // Не обрізаємо
        $passwordConfirm = $this->request->post('password_confirm', '');
        $errors = [];
        $oldInput = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]; // Зберігаємо введені дані (крім паролів)

        // --- Валідація ---
        if (empty($firstName)) { $errors['first_name'] = "Будь ласка, введіть ваше ім'я."; }
        if (empty($lastName)) { $errors['last_name'] = 'Будь ласка, введіть ваше прізвище.'; }
        if (empty($email)) {
            $errors['email'] = 'Будь ласка, введіть вашу пошту.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Будь ласка, введіть коректну адресу пошти.';
        }
        if (empty($password)) {
            $errors['password'] = 'Будь ласка, введіть пароль.';
        } elseif (mb_strlen($password) < 6) { // Використовуємо mb_strlen для багатобайтних кодувань
            $errors['password'] = 'Пароль має містити щонайменше 6 символів.';
        }
        if (empty($passwordConfirm)) {
            $errors['password_confirm'] = 'Будь ласка, підтвердіть пароль.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Паролі не співпадають.';
            $errors['password'] = 'Паролі не співпадають.'; // Можна позначити обидва поля
        }

        // --- Перевірка існування email та Створення користувача ---
        if (empty($errors)) {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                $errors['email'] = 'Користувач з такою поштою вже зареєстрований.';
            } else {
                // Все добре, реєструємо користувача
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($passwordHash === false) {
                    // Обробка помилки хешування
                    error_log("Password hashing failed for email: {$email}");
                    $this->session->flash('error', 'Сталася помилка під час реєстрації. Спробуйте пізніше.');
                    // Не передаємо старі дані, бо це внутрішня помилка
                    $this->redirect('/auth/register');
                }

                $userData = [
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password_hash' => $passwordHash
                ];

                $userId = $this->userModel->create($userData);

                if ($userId) {
                    // Успішна реєстрація
                    $this->session->flash('success', 'Ви успішно зареєструвалися! Тепер можете увійти.');
                    $this->redirect('/auth/login');
                } else {
                    // Помилка створення користувача в БД
                     $this->session->flash('error', 'Не вдалося зареєструвати користувача через помилку бази даних.');
                     $this->session->flash('old_input', $oldInput); // Повертаємо введені дані
                     $this->redirect('/auth/register');
                }
            }
        }

        // --- Якщо є помилки валідації або email існує ---
        if (!empty($errors)) {
             // Зберігаємо помилки та старі дані у flash-сесії і робимо редірект назад на форму
             $this->session->flash('errors', $errors);
             $this->session->flash('old_input', $oldInput);
             $this->redirect('/auth/register');
             // Альтернатива: рендерити одразу без редіректу
             // $data = ['pageTitle' => 'Реєстрація', 'errors' => $errors, 'old_input' => $oldInput];
             // $this->render('auth/register', $data, 'auth');
        }
    }

    /**
     * Виконує вихід користувача (знищення сесії).
     */
    public function logout(): void
    {
        $this->session->destroy(); // Знищуємо сесію
        $this->redirect('/auth/login'); // Перенаправляємо на сторінку входу
    }
}