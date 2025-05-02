<?php
// app/Controllers/AccountController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\User; // Можливо, знадобиться для отримання імені користувача

class AccountController extends BaseController
{
    private Account $accountModel;
    private Transaction $transactionModel;
    private Category $categoryModel;
    private User $userModel;

    public function __construct(Request $request, Session $session)
    {
        parent::__construct($request, $session);
        $this->accountModel = new Account();
        $this->transactionModel = new Transaction();
        $this->categoryModel = new Category();
        $this->userModel = new User(); // Ініціалізуємо модель User
        $this->checkAuthentication(); // Всі дії цього контролера потребують автентифікації
    }

    /**
     * Відображає головну сторінку рахунків.
     * Показує список рахунків у сайдбарі та деталі обраного рахунку.
     */
    public function index(): void
    {
        $userId = (int)$this->session->get('user_id');
        $accounts = $this->accountModel->findAllByUserId($userId);
        $selectedAccountId = null;
        $selectedAccount = null;
        $accountBalance = 0.0;
        $accountIncome = 0.0;
        $accountExpenses = 0.0;
        $lastTransactions = [];
        $contentError = null; // Локальна помилка контенту

        if (!empty($accounts)) {
            // Визначаємо обраний рахунок (з GET або перший у списку)
            $requestedAccountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
            $selectedAccountId = $requestedAccountId ?: $accounts[0]['id'];

            // Перевіряємо, чи обраний ID належить користувачу
            $found = false;
            foreach ($accounts as $acc) {
                if ($acc['id'] == $selectedAccountId) {
                    $selectedAccount = $acc;
                    $found = true;
                    break;
                }
            }
            // Якщо ID з GET невірний, беремо перший рахунок
            if (!$found) {
                $selectedAccountId = $accounts[0]['id'];
                $selectedAccount = $accounts[0];
                 if ($requestedAccountId) { // Помилка тільки якщо ID був явно вказаний
                    $this->session->flash('warning', 'Обраний рахунок не знайдено. Показано дані для першого.');
                 }
            }

            // Отримуємо фінансову інформацію для обраного рахунку
            if ($selectedAccountId) {
                 $summary = $this->transactionModel->getAccountSummary($selectedAccountId, $userId);
                 $accountBalance = $summary['balance'];
                 $accountIncome = $summary['income'];
                 $accountExpenses = $summary['expense'];
                 $lastTransactions = $this->transactionModel->findRecentByAccountId($selectedAccountId, $userId, 5);
            }
        } else {
             $contentError = "У вас немає створених рахунків.";
             // Не встановлюємо flash, бо це стан, а не результат дії
        }

        // Отримуємо дані для JS (для модалок)
        $jsCategories = [
            'income' => $this->categoryModel->findByUserIdAndType($userId, 'income'),
            'expense' => $this->categoryModel->findByUserIdAndType($userId, 'expense')
        ];

        // Готуємо дані для передачі у вид
        $data = [
            'pageTitle' => $selectedAccount ? $selectedAccount['name'] : 'Рахунки',
            'userName' => $this->session->get('user_name', 'Користувач'), // Беремо ім'я з сесії
            'accounts' => $accounts, // Для сайдбару та модалок
            'selectedAccountId' => $selectedAccountId, // Для сайдбару та видів
            'selectedAccount' => $selectedAccount, // Деталі поточного рахунку для основного контенту
            'accountBalance' => $accountBalance,
            'accountIncome' => $accountIncome,
            'accountExpenses' => $accountExpenses,
            'lastTransactions' => $lastTransactions,
            'contentError' => $contentError, // Передаємо помилку контенту у вид
            'allowedCurrencies' => $this->accountModel->getAllowedCurrencies(), // Отримуємо дозволені валюти
            'allUserAccountsJson' => json_encode($accounts), // JSON для JS
            'jsCategoriesModalJson' => json_encode($jsCategories), // JSON категорій для JS
            'warning' => $this->session->getFlash('warning'), // Попередження (напр., рахунок не знайдено)
            'success' => $this->session->getFlash('success'), // Повідомлення про успіх (після дії)
            'phpPageLoadError' => $this->session->getFlash('form_error'), // Помилки форм для модалок
            'showSidebar' => true, // <-- ДОДАНО ДЛЯ УМОВНОГО САЙДБАРУ
            'currentAccountIdForTabs' => $selectedAccountId ?? 0, // ID для табів
        ];

        $this->render('accounts/index', $data); // Рендеримо вид 'accounts/index' з макетом 'main'
    }

    /**
     * Обробляє додавання нового рахунку.
     */
    public function add(): void
    {
        if (!$this->request->isPost()) { $this->redirect('/accounts'); } // Тільки POST

        $userId = (int)$this->session->get('user_id');
        $name = trim($this->request->post('account_name', ''));
        $currency = trim($this->request->post('account_currency', ''));
        $initialBalanceStr = trim($this->request->post('initial_balance', '0'));
        $initialBalanceStr = str_replace(',', '.', $initialBalanceStr);
        $initialBalance = filter_var($initialBalanceStr, FILTER_VALIDATE_FLOAT);
        $errors = [];

        // --- Валідація ---
        if (empty($name)) { $errors['account_name'] = "Назва рахунку не може бути порожньою."; }
        if (!in_array($currency, $this->accountModel->getAllowedCurrencies())) { $errors['account_currency'] = "Недопустима валюта."; }
        // Валідація початкового балансу: має бути числом або порожнім/нулем, але не від'ємним
        if ($initialBalanceStr !== '' && ($initialBalance === false || $initialBalance < 0)) {
            $errors['initial_balance'] = "Початковий баланс має бути невід'ємним числом.";
        } elseif ($initialBalanceStr !== '' && $initialBalance === false) {
            // Якщо рядок не порожній, але filter_var повернув false, це не число
             $errors['initial_balance'] = "Початковий баланс має бути числовим значенням.";
        }
        // Якщо все пройшло добре, але initialBalance false (напр. порожній рядок), ставимо 0.0
        if ($initialBalance === false) {
            $initialBalance = 0.0;
        }


        if (empty($errors)) {
            // Створення рахунку
            $accountData = [
                'user_id' => $userId,
                'name' => $name,
                'currency' => $currency
            ];
            // === ПОЧАТОК ЛОГУВАННЯ ===
            error_log("[AccountController::add] Attempting to create account for user {$userId}. Data: " . json_encode($accountData));
            // === КІНЕЦЬ ЛОГУВАННЯ ===
            $accountId = $this->accountModel->create($accountData);
            // === ПОЧАТОК ЛОГУВАННЯ ===
            error_log("[AccountController::add] Account creation result (accountId): " . var_export($accountId, true));
            // === КІНЕЦЬ ЛОГУВАННЯ ===

            if ($accountId) {
                 // Якщо є початковий баланс > 0, створюємо транзакцію
                 if ($initialBalance > 0) {
                      $categoryId = $this->categoryModel->getOrCreateInitialBalanceCategory($userId);
                      if ($categoryId) {
                           $transactionData = [
                               'account_id' => $accountId,
                               'category_id' => $categoryId,
                               'amount' => $initialBalance,
                               'date' => date('Y-m-d H:i:s'),
                               'description' => 'Початковий баланс'
                           ];
                           // === ПОЧАТОК ЛОГУВАННЯ ===
                            error_log("[AccountController::add] Attempting to create initial balance transaction. Data: " . json_encode($transactionData));
                           // === КІНЕЦЬ ЛОГУВАННЯ ===
                           $transactionCreated = $this->transactionModel->create($transactionData);
                           if (!$transactionCreated) {
                               // Логуємо помилку, але не зупиняємо процес, рахунок вже створено
                               error_log("[AccountController::add] WARNING: Could not create initial balance transaction for user {$userId}, account {$accountId}");
                               // Можна додати flash-повідомлення про цю конкретну помилку
                               $this->session->flash('warning', 'Рахунок створено, але не вдалося додати початковий баланс.');
                           } else {
                                error_log("[AccountController::add] Initial balance transaction created successfully.");
                           }
                      } else {
                           // Помилка створення категорії - логуємо, але рахунок вже створено
                           error_log("[AccountController::add] WARNING: Could not create/get initial balance category for user {$userId}, account {$accountId}");
                            $this->session->flash('warning', 'Рахунок створено, але не вдалося створити категорію для початкового балансу.');
                      }
                 }
                 // Якщо не було попередження, ставимо повідомлення про успіх
                 if (!$this->session->hasFlash('warning')) {
                    $this->session->flash('success', 'Рахунок успішно додано.');
                 }
                 $this->redirect('/accounts?account_id=' . $accountId);

            } else {
                 // Явно логуємо помилку створення, якщо вона сталася
                 error_log("[AccountController::add] ERROR: Account creation failed in controller after model returned false/0 for user {$userId}.");
                 $this->session->flash('form_error', ['modal' => 'addAccountModal', 'message' => 'Помилка бази даних при додаванні рахунку. Перевірте логи сервера.']);
                 $this->redirect('/accounts'); // Редірект на останній активний рахунок
            }
        } else {
            // Помилки валідації
             // === ПОЧАТОК ЛОГУВАННЯ ===
             error_log("[AccountController::add] Validation failed: " . implode('; ', $errors));
             // === КІНЕЦЬ ЛОГУВАННЯ ===
             $this->session->flash('form_error', ['modal' => 'addAccountModal', 'message' => implode(' ', $errors)]);
             $this->redirect('/accounts'); // Редірект на останній активний рахунок
        }
    }

     /**
      * Обробляє редагування рахунку.
      */
     public function edit(): void
     {
        if (!$this->request->isPost()) { $this->redirect('/accounts'); }

        $userId = (int)$this->session->get('user_id');
        $accountId = filter_var($this->request->post('account_id'), FILTER_VALIDATE_INT);
        $name = trim($this->request->post('account_name', ''));
        $currency = trim($this->request->post('account_currency', ''));
        $errors = [];

        if (!$accountId) { $errors['general'] = "Невірний ID рахунку."; }
        if (empty($name)) { $errors['account_name'] = "Назва рахунку не може бути порожньою."; }
        if (!in_array($currency, $this->accountModel->getAllowedCurrencies())) { $errors['account_currency'] = "Недопустима валюта."; }

        if (empty($errors)) {
            $data = ['name' => $name, 'currency' => $currency];
            $success = $this->accountModel->update($accountId, $userId, $data);

            if ($success) {
                 $this->session->flash('success', 'Рахунок успішно оновлено.');
                 $this->redirect('/accounts?account_id=' . $accountId);
            } else {
                 // Помилка оновлення (можливо, рахунок не належить користувачу або помилка БД)
                 $this->session->flash('form_error', ['modal' => 'editAccountModal', 'message' => 'Не вдалося оновити рахунок. Можливо, він не існує або сталася помилка.']);
                 $this->redirect('/accounts?account_id=' . $accountId); // Редірект на той самий рахунок
            }
        } else {
             $this->session->flash('form_error', ['modal' => 'editAccountModal', 'message' => implode(' ', $errors)]);
             $this->redirect('/accounts?account_id=' . $accountId); // Редірект на той самий рахунок
        }
     }

     /**
      * Обробляє видалення рахунку.
      */
     public function delete(): void
     {
        if (!$this->request->isPost()) { $this->redirect('/accounts'); }

        $userId = (int)$this->session->get('user_id');
        $accountId = filter_var($this->request->post('account_id'), FILTER_VALIDATE_INT);

        if (!$accountId) {
            $this->session->flash('form_error', ['modal' => 'deleteAccountModal', 'message' => 'Невірний ID рахунку.']);
            $this->redirect('/accounts');
            return;
        }

        // Спочатку видаляємо пов'язані транзакції
        $transactionsDeleted = $this->transactionModel->deleteByAccountId($accountId, $userId);

        if ($transactionsDeleted) {
            // Потім видаляємо сам рахунок
            $accountDeleted = $this->accountModel->delete($accountId, $userId);

            if ($accountDeleted) {
                 $this->session->flash('success', 'Рахунок та пов\'язані транзакції успішно видалено.');
                 $this->redirect('/accounts'); // Редірект на сторінку рахунків (покаже перший доступний)
            } else {
                 // Помилка видалення рахунку (після видалення транзакцій - дивно, але можливо)
                 $this->session->flash('form_error', ['modal' => 'deleteAccountModal', 'message' => 'Не вдалося видалити рахунок (транзакції видалено). Можливо, рахунок не знайдено або сталася помилка.']);
                 $this->redirect('/accounts?account_id=' . $accountId);
            }
        } else {
             // Помилка видалення транзакцій (можливо, рахунок не належить користувачу)
             $this->session->flash('form_error', ['modal' => 'deleteAccountModal', 'message' => 'Не вдалося видалити транзакції для цього рахунку. Можливо, рахунок не належить вам або сталася помилка.']);
             $this->redirect('/accounts?account_id=' . $accountId);
        }
     }

    /**
     * Обробляє коригування балансу рахунку.
     */
     public function adjustBalance(): void
     {
         if (!$this->request->isPost()) { $this->redirect('/accounts'); }

         $userId = (int)$this->session->get('user_id');
         $accountId = filter_var($this->request->post('account_id'), FILTER_VALIDATE_INT);
         $adjustmentAmountStr = trim($this->request->post('adjustment_amount', '0'));
         $adjustmentAmountStr = str_replace(',', '.', $adjustmentAmountStr);
         $adjustmentAmount = filter_var($adjustmentAmountStr, FILTER_VALIDATE_FLOAT);
         $description = trim($this->request->post('adjustment_description', ''));
         $errors = [];

         if (!$accountId) { $errors['general'] = "Невірний ID рахунку."; }
         if ($adjustmentAmount === false || $adjustmentAmount == 0) { $errors['adjustment_amount'] = "Сума коригування має бути ненульовим числом."; }

         if (empty($errors)) {
             // Перевірка, чи рахунок належить користувачу
             $account = $this->accountModel->findByIdAndUserId($accountId, $userId);
             if (!$account) {
                  $this->session->flash('form_error', ['modal' => 'editBalanceModal', 'message' => 'Рахунок не знайдено або він не належить вам.']);
                  $this->redirect('/accounts');
                  return;
             }

             $transactionType = ($adjustmentAmount > 0) ? 'income' : 'expense';
             $amountToRecord = abs($adjustmentAmount);

             // Отримуємо або створюємо категорію коригування
             $categoryId = $this->categoryModel->getOrCreateAdjustmentCategory($userId, $transactionType);

             if ($categoryId) {
                 $transactionData = [
                     'account_id' => $accountId,
                     'category_id' => $categoryId,
                     'amount' => $amountToRecord,
                     'date' => date('Y-m-d H:i:s'),
                     'description' => 'Коригування балансу' . (!empty($description) ? ": " . $description : '')
                 ];
                 $transactionId = $this->transactionModel->create($transactionData);

                 if ($transactionId) {
                     $this->session->flash('success', 'Баланс успішно скориговано.');
                     $this->redirect('/accounts?account_id=' . $accountId);
                 } else {
                      $this->session->flash('form_error', ['modal' => 'editBalanceModal', 'message' => 'Не вдалося створити коригувальну транзакцію.']);
                      $this->redirect('/accounts?account_id=' . $accountId);
                 }
             } else {
                 $this->session->flash('form_error', ['modal' => 'editBalanceModal', 'message' => 'Не вдалося створити системну категорію для коригування балансу.']);
                 $this->redirect('/accounts?account_id=' . $accountId);
             }
         } else {
              $this->session->flash('form_error', ['modal' => 'editBalanceModal', 'message' => implode(' ', $errors)]);
              $this->redirect('/accounts?account_id=' . $accountId); // Редірект на той самий рахунок
         }
     }

}