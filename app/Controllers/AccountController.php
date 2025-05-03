<?php
// app/Controllers/AccountController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\User;

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
        $this->userModel = new User();
        $this->checkAuthentication();
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
        $contentError = null;

        if (!empty($accounts)) {
            $requestedAccountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
            $selectedAccountId = $requestedAccountId ?: $accounts[0]['id'];

            $found = false;
            foreach ($accounts as $acc) {
                if ($acc['id'] == $selectedAccountId) {
                    $selectedAccount = $acc;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $selectedAccountId = $accounts[0]['id'];
                $selectedAccount = $accounts[0];
                 if ($requestedAccountId) {
                    $this->session->flash('warning', 'Обраний рахунок не знайдено. Показано дані для першого.');
                 }
            }

            if ($selectedAccountId) {
                 $summary = $this->transactionModel->getAccountSummary($selectedAccountId, $userId);
                 $accountBalance = $summary['balance'];
                 $accountIncome = $summary['income'];
                 $accountExpenses = $summary['expense'];
                 $lastTransactions = $this->transactionModel->findRecentByAccountId($selectedAccountId, $userId, 5);
            }
        } else {
             $contentError = "У вас немає створених рахунків.";
        }

        $jsCategories = [
            'income' => $this->categoryModel->findByUserIdAndType($userId, 'income'),
            'expense' => $this->categoryModel->findByUserIdAndType($userId, 'expense')
        ];

        $data = [
            'pageTitle' => $selectedAccount ? $selectedAccount['name'] : 'Рахунки',
            'userName' => $this->session->get('user_name', 'Користувач'),
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'selectedAccount' => $selectedAccount,
            'accountBalance' => $accountBalance,
            'accountIncome' => $accountIncome,
            'accountExpenses' => $accountExpenses,
            'lastTransactions' => $lastTransactions,
            'contentError' => $contentError,
            'allowedCurrencies' => $this->accountModel->getAllowedCurrencies(),
            'allUserAccountsJson' => json_encode($accounts),
            'jsCategoriesModalJson' => json_encode($jsCategories),
            'warning' => $this->session->getFlash('warning'),
            'success' => $this->session->getFlash('success'),
            'phpPageLoadError' => $this->session->getFlash('form_error'),
            'showSidebar' => true,
            'currentAccountIdForTabs' => $selectedAccountId ?? 0,
        ];

        $this->render('accounts/index', $data);
    }

    /**
     * Обробляє додавання нового рахунку.
     */
    public function add(): void
    {
        if (!$this->request->isPost()) { $this->redirect('/accounts'); }

        $userId = (int)$this->session->get('user_id');
        $name = trim($this->request->post('account_name', ''));
        $currency = trim($this->request->post('account_currency', ''));
        $initialBalanceStr = trim($this->request->post('initial_balance', '0'));
        $initialBalanceStr = str_replace(',', '.', $initialBalanceStr);
        $initialBalance = filter_var($initialBalanceStr, FILTER_VALIDATE_FLOAT);
        $errors = [];

        if (empty($name)) { $errors['account_name'] = "Назва рахунку не може бути порожньою."; }
        if (!in_array($currency, $this->accountModel->getAllowedCurrencies())) { $errors['account_currency'] = "Недопустима валюта."; }
        if ($initialBalanceStr !== '' && ($initialBalance === false || $initialBalance < 0)) {
            $errors['initial_balance'] = "Початковий баланс має бути невід'ємним числом.";
        } elseif ($initialBalanceStr !== '' && $initialBalance === false) {
             $errors['initial_balance'] = "Початковий баланс має бути числовим значенням.";
        }
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

            error_log("[AccountController::add] Attempting to create account for user {$userId}. Data: " . json_encode($accountData));
            $accountId = $this->accountModel->create($accountData);
            error_log("[AccountController::add] Account creation result (accountId): " . var_export($accountId, true));

            if ($accountId) {
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
                            error_log("[AccountController::add] Attempting to create initial balance transaction. Data: " . json_encode($transactionData));
                           $transactionCreated = $this->transactionModel->create($transactionData);
                           if (!$transactionCreated) {
                               error_log("[AccountController::add] WARNING: Could not create initial balance transaction for user {$userId}, account {$accountId}");
                               $this->session->flash('warning', 'Рахунок створено, але не вдалося додати початковий баланс.');
                           } else {
                                error_log("[AccountController::add] Initial balance transaction created successfully.");
                           }
                      } else {
                           error_log("[AccountController::add] WARNING: Could not create/get initial balance category for user {$userId}, account {$accountId}");
                            $this->session->flash('warning', 'Рахунок створено, але не вдалося створити категорію для початкового балансу.');
                      }
                 }
                 if (!$this->session->hasFlash('warning')) {
                    $this->session->flash('success', 'Рахунок успішно додано.');
                 }
                 $this->redirect('/accounts?account_id=' . $accountId);

            } else {
                 error_log("[AccountController::add] ERROR: Account creation failed in controller after model returned false/0 for user {$userId}.");
                 $this->session->flash('form_error', ['modal' => 'addAccountModal', 'message' => 'Помилка бази даних при додаванні рахунку. Перевірте логи сервера.']);
                 $this->redirect('/accounts');
            }
        } else {
             error_log("[AccountController::add] Validation failed: " . implode('; ', $errors));
             $this->session->flash('form_error', ['modal' => 'addAccountModal', 'message' => implode(' ', $errors)]);
             $this->redirect('/accounts');
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
                 $this->session->flash('form_error', ['modal' => 'editAccountModal', 'message' => 'Не вдалося оновити рахунок. Можливо, він не існує або сталася помилка.']);
                 $this->redirect('/accounts?account_id=' . $accountId);
            }
        } else {
             $this->session->flash('form_error', ['modal' => 'editAccountModal', 'message' => implode(' ', $errors)]);
             $this->redirect('/accounts?account_id=' . $accountId);
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

        $transactionsDeleted = $this->transactionModel->deleteByAccountId($accountId, $userId);

        if ($transactionsDeleted) {
            $accountDeleted = $this->accountModel->delete($accountId, $userId);

            if ($accountDeleted) {
                 $this->session->flash('success', 'Рахунок та пов\'язані транзакції успішно видалено.');
                 $this->redirect('/accounts');
            } else {
                 $this->session->flash('form_error', ['modal' => 'deleteAccountModal', 'message' => 'Не вдалося видалити рахунок (транзакції видалено). Можливо, рахунок не знайдено або сталася помилка.']);
                 $this->redirect('/accounts?account_id=' . $accountId);
            }
        } else {
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
             $account = $this->accountModel->findByIdAndUserId($accountId, $userId);
             if (!$account) {
                  $this->session->flash('form_error', ['modal' => 'editBalanceModal', 'message' => 'Рахунок не знайдено або він не належить вам.']);
                  $this->redirect('/accounts');
                  return;
             }

             $transactionType = ($adjustmentAmount > 0) ? 'income' : 'expense';
             $amountToRecord = abs($adjustmentAmount);

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
              $this->redirect('/accounts?account_id=' . $accountId);
         }
     }

}