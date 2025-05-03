<?php
// app/Controllers/AuthController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\User;

class AuthController extends BaseController
{
    private User $userModel;

    public function __construct(Request $request, Session $session)
    {
        parent::__construct($request, $session);
        $this->userModel = new User();
    }

    /**
     * Показує сторінку входу.
     */
    public function showLogin(): void
    {
        $this->redirectIfAuthenticated();

        $data = [
            'pageTitle' => 'Вхід',
            'error' => $this->session->getFlash('error'),
            'success' => $this->session->getFlash('success'),
            'old_email' => $this->session->getFlash('old_email')
        ];
        $this->render('auth/login', $data, 'auth');
    }

    /**
     * Обробляє POST-запит на вхід.
     */
    public function login(): void
    {
        $this->redirectIfAuthenticated();

        if (!$this->request->isPost()) {
            $this->redirect('/auth/login');
        }

        $email = trim($this->request->post('email', ''));
        $password = $this->request->post('password', '');
        $errors = [];

        if (empty($email)) {
            $errors['email'] = 'Будь ласка, введіть вашу пошту.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Будь ласка, введіть коректну адресу пошти.';
        }
        if (empty($password)) {
            $errors['password'] = 'Будь ласка, введіть пароль.';
        }

        if (empty($errors)) {
            $user = $this->userModel->findByEmail($email);

            if ($user && $this->userModel->verifyPassword($password, $user['password_hash'])) {
                $this->session->regenerate(true);
                $this->session->set('user_id', $user['id']);
                $this->session->set('user_name', $user['first_name']);

                $this->redirect('/');
            } else {
                 $this->session->flash('error', 'Неправильна пошта або пароль.');
                 $this->session->flash('old_email', $email);
                 $this->redirect('/auth/login');
            }
        } else {
             $this->session->flash('error', 'Будь ласка, виправте помилки у формі.');
             $this->session->flash('errors', $errors);
             $this->session->flash('old_email', $email);
             $this->redirect('/auth/login');
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
            'errors' => $this->session->getFlash('errors') ?: [],
            'old_input' => $this->session->getFlash('old_input') ?: []
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
            $this->redirect('/auth/register');
        }

        $firstName = trim($this->request->post('first_name', ''));
        $lastName = trim($this->request->post('last_name', ''));
        $email = trim($this->request->post('email', ''));
        $password = $this->request->post('password', '');
        $passwordConfirm = $this->request->post('password_confirm', '');
        $errors = [];
        $oldInput = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ];

        if (empty($firstName)) { $errors['first_name'] = "Будь ласка, введіть ваше ім'я."; }
        if (empty($lastName)) { $errors['last_name'] = 'Будь ласка, введіть ваше прізвище.'; }
        if (empty($email)) {
            $errors['email'] = 'Будь ласка, введіть вашу пошту.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Будь ласка, введіть коректну адресу пошти.';
        }
        if (empty($password)) {
            $errors['password'] = 'Будь ласка, введіть пароль.';
        } elseif (mb_strlen($password) < 6) {
            $errors['password'] = 'Пароль має містити щонайменше 6 символів.';
        }
        if (empty($passwordConfirm)) {
            $errors['password_confirm'] = 'Будь ласка, підтвердіть пароль.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Паролі не співпадають.';
            $errors['password'] = 'Паролі не співпадають.';
        }

        if (empty($errors)) {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                $errors['email'] = 'Користувач з такою поштою вже зареєстрований.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($passwordHash === false) {
                    error_log("Password hashing failed for email: {$email}");
                    $this->session->flash('error', 'Сталася помилка під час реєстрації. Спробуйте пізніше.');
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

                    $this->session->flash('success', 'Ви успішно зареєструвалися! Тепер можете увійти.');
                    $this->redirect('/auth/login');
                } else {
                    // Помилка створення користувача в БД
                     $this->session->flash('error', 'Не вдалося зареєструвати користувача через помилку бази даних.');
                     $this->session->flash('old_input', $oldInput);
                     $this->redirect('/auth/register');
                }
            }
        }

        if (!empty($errors)) {
             $this->session->flash('errors', $errors);
             $this->session->flash('old_input', $oldInput);
             $this->redirect('/auth/register');
        }
    }

    /**
     * Виконує вихід користувача (знищення сесії).
     */
    public function logout(): void
    {
        $this->session->destroy();
        $this->redirect('/auth/login');
    }
}