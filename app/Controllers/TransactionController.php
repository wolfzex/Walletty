<?php
// app/Controllers/TransactionController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\User;
use App\Models\Database;
use PDOException;
use Exception;

class TransactionController extends BaseController
{
    private Transaction $transactionModel;
    private Account $accountModel;
    private Category $categoryModel;
    private User $userModel;

    public function __construct(Request $request, Session $session)
    {
        parent::__construct($request, $session);
        $this->transactionModel = new Transaction();
        $this->accountModel = new Account();
        $this->categoryModel = new Category();
        $this->userModel = new User();
        $this->checkAuthentication();
    }
    /**
     * Відображає сторінку транзакцій для обраного рахунку.
     */
    public function index(): void
    {
        $userId = (int)$this->session->get('user_id');
        $userName = $this->session->get('user_name', 'Користувач');

        $accounts = $this->accountModel->findAllByUserId($userId);
        if (empty($accounts)) {
             $this->session->flash('warning', 'Будь ласка, спочатку створіть рахунок.');
             $this->redirect('/accounts'); // Перенаправляємо на сторінку рахунків
             return;
        }

        $selectedAccountId = null;
        $selectedAccount = null;
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
             if ($requestedAccountId) $this->session->flash('warning', 'Обраний рахунок не знайдено. Показано транзакції для першого.');
        }

        $filters = [
            'category_id' => filter_var($this->request->get('category_id'), FILTER_VALIDATE_INT),
            'start_date' => $this->request->get('start_date'), // Валідація формату Y-m-d бажана
            'end_date' => $this->request->get('end_date'),     // Валідація формату Y-m-d бажана
        ];
        $filters = array_filter($filters, function($value) { return $value !== null && $value !== false && $value !== ''; });

        $transactions = $this->transactionModel->findByAccountId($selectedAccountId, $userId, $filters);

        $allUserCategories = $this->categoryModel->findAllByUserId($userId);

         $jsCategories = [
             'income' => $this->categoryModel->findByUserIdAndType($userId, 'income'),
             'expense' => $this->categoryModel->findByUserIdAndType($userId, 'expense')
         ];

        $data = [
            'pageTitle' => 'Транзакції' . ($selectedAccount ? ' - ' . $selectedAccount['name'] : ''),
            'userName' => $userName,
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'selectedAccount' => $selectedAccount,
            'transactions' => $transactions,
            'allUserCategories' => $allUserCategories,
            'filters' => $filters,
            'allowedCurrencies' => $this->accountModel->getAllowedCurrencies(),
            'allUserAccountsJson' => json_encode($accounts),
            'jsCategoriesModalJson' => json_encode($jsCategories),
            'warning' => $this->session->getFlash('warning'),
            'success' => $this->session->getFlash('success'),
            'phpPageLoadError' => $this->session->getFlash('form_error'),
             'showSidebar' => false,
             'currentAccountIdForTabs' => $selectedAccountId ?? 0,
             'categoryTypeForTabs' => 'expense',
        ];

        $this->render('transactions/index', $data);
    }

    /**
     * Обробляє додавання нової транзакції.
     */
    public function add(): void
    {
         if (!$this->request->isPost()) { $this->redirect('/transactions'); }

         $userId = (int)$this->session->get('user_id');
         $accountId = filter_var($this->request->post('account_id_hidden'), FILTER_VALIDATE_INT);
         $categoryId = filter_var($this->request->post('transaction_category'), FILTER_VALIDATE_INT);
         $amountStr = trim($this->request->post('transaction_amount', '0'));
         $amountStr = str_replace(',', '.', $amountStr);
         $amount = filter_var($amountStr, FILTER_VALIDATE_FLOAT);
         $datetime = trim($this->request->post('transaction_datetime', '')); // Формат 'Y-m-d\TH:i'
         $description = trim($this->request->post('transaction_description', ''));
         $errors = [];

         if (!$accountId) { $errors['account_id'] = "Не вдалося визначити рахунок."; }
         if (!$categoryId) { $errors['category_id'] = "Будь ласка, оберіть категорію."; }
         if ($amount === false || $amount <= 0) { $errors['amount'] = "Сума має бути позитивним числом."; }
         if (empty($datetime)) { $errors['datetime'] = "Будь ласка, оберіть дату та час."; }
         else {
              $d = \DateTime::createFromFormat('Y-m-d\TH:i', $datetime);
              if (!$d) {
                   $errors['datetime'] = "Невірний формат дати/часу.";
              } else {
                   $dbDatetime = $d->format('Y-m-d H:i:s');
              }
         }

         if ($accountId && !$this->accountModel->findByIdAndUserId($accountId, $userId)) {
             $errors['account_id'] = "Обраний рахунок не належить вам.";
         }
          if ($categoryId && !$this->categoryModel->findByIdAndUserId($categoryId, $userId)) {
             $errors['category_id'] = "Обрана категорія не належить вам.";
         }

         if (empty($errors)) {
             $data = [
                 'account_id' => $accountId,
                 'category_id' => $categoryId,
                 'amount' => $amount,
                 'date' => $dbDatetime,
                 'description' => $description
             ];
             $transactionId = $this->transactionModel->create($data);

             if ($transactionId) {
                 $this->session->flash('success', 'Транзакцію успішно додано.');
                 $this->redirect('/transactions?account_id=' . $accountId);
             } else {
                 $this->session->flash('form_error', ['modal' => 'addTransactionModal', 'message' => 'Помилка бази даних при додаванні транзакції.']);
                 $this->redirect('/transactions?account_id=' . $accountId);
             }
         } else {
              $this->session->flash('form_error', ['modal' => 'addTransactionModal', 'message' => implode(' ', $errors)]);
              $this->redirect('/transactions?account_id=' . $accountId);
         }
    }

    /**
     * Обробляє видалення транзакції.
     */
    public function delete(): void
    {
        if (!$this->request->isPost()) { $this->redirect('/transactions'); }

        $userId = (int)$this->session->get('user_id');
        $transactionId = filter_var($this->request->post('transaction_id'), FILTER_VALIDATE_INT);
        $accountId = filter_var($this->request->post('account_id'), FILTER_VALIDATE_INT); // Для редіректу

        if (!$transactionId) {
            $this->session->flash('form_error', ['modal' => 'deleteTransactionModal', 'message' => 'Невірний ID транзакції.']);
        } else {
            $success = $this->transactionModel->delete($transactionId, $userId);
            if ($success) {
                $this->session->flash('success', 'Транзакцію успішно видалено.');
            } else {
                 $this->session->flash('form_error', ['modal' => 'deleteTransactionModal', 'message' => 'Не вдалося видалити транзакцію. Можливо, вона не існує або не належить вам.']);
            }
        }
        $this->redirect('/transactions?account_id=' . $accountId);
    }

    /**
     * Обробляє переказ коштів між рахунками.
     */
     public function transfer(): void
     {
        if (!$this->request->isPost()) { $this->redirect('/transactions'); }

        $userId = (int)$this->session->get('user_id');
        $fromAccountId = filter_var($this->request->post('from_account_id'), FILTER_VALIDATE_INT);
        $toAccountId = filter_var($this->request->post('to_account_id'), FILTER_VALIDATE_INT);
        $amountStr = trim($this->request->post('amount', '0'));
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = filter_var($amountStr, FILTER_VALIDATE_FLOAT);
        $exchangeRateStr = trim($this->request->post('exchange_rate', '1'));
        $exchangeRateStr = str_replace(',', '.', $exchangeRateStr);
        $exchangeRate = filter_var($exchangeRateStr, FILTER_VALIDATE_FLOAT);
        $description = trim($this->request->post('description', ''));
        $currentViewAccountId = filter_var($this->request->post('current_view_account_id'), FILTER_VALIDATE_INT); // Для редіректу
        $errors = [];

        if (!$fromAccountId || !$toAccountId) { $errors['account'] = "Будь ласка, оберіть обидва рахунки."; }
        if ($fromAccountId === $toAccountId) { $errors['account'] = "Неможливо зробити переказ на той самий рахунок."; }
        if ($amount === false || $amount <= 0) { $errors['amount'] = "Сума переказу має бути позитивним числом."; }

        $fromAccount = null;
        $toAccount = null;
        if ($fromAccountId && $toAccountId) {
            $fromAccount = $this->accountModel->findByIdAndUserId($fromAccountId, $userId);
            $toAccount = $this->accountModel->findByIdAndUserId($toAccountId, $userId);
            if (!$fromAccount || !$toAccount) { $errors['account'] = "Один або обидва рахунки не знайдено або не належать вам."; }
        }

        if ($fromAccount && $toAccount && $fromAccount['currency'] !== $toAccount['currency']) {
             if ($exchangeRate === false || $exchangeRate <= 0) {
                 $errors['exchange_rate'] = "Вкажіть коректний курс обміну (> 0) для різних валют.";
             }
        } else {
             $exchangeRate = 1.0;
        }

        if (empty($errors)) {
            $db = Database::getInstance()->getConnection();
            try {
                $db->beginTransaction();

                $transferOutCatId = $this->categoryModel->getOrCreateTransferCategory($userId, 'expense');
                if (!$transferOutCatId) throw new Exception("Не вдалося отримати/створити категорію 'Переказ вихідний'.");

                $descOut = "Переказ на рахунок '" . htmlspecialchars($toAccount['name']) . "'";
                if ($fromAccount['currency'] !== $toAccount['currency']) { $descOut .= " (курс {$exchangeRate})"; }
                if (!empty($description)) { $descOut .= ". Примітка: " . $description; }

                $successOut = $this->transactionModel->create([
                    'account_id' => $fromAccountId,
                    'category_id' => $transferOutCatId,
                    'amount' => $amount,
                    'date' => date('Y-m-d H:i:s'),
                    'description' => $descOut
                ]);
                if (!$successOut) throw new Exception("Не вдалося створити вихідну транзакцію.");

                $transferInCatId = $this->categoryModel->getOrCreateTransferCategory($userId, 'income');
                 if (!$transferInCatId) throw new Exception("Не вдалося отримати/створити категорію 'Переказ вхідний'.");

                $amountIn = $amount * $exchangeRate;
                $descIn = "Переказ з рахунку '" . htmlspecialchars($fromAccount['name']) . "'";
                 if ($fromAccount['currency'] !== $toAccount['currency']) { $descIn .= " (курс {$exchangeRate})"; }
                 if (!empty($description)) { $descIn .= ". Примітка: " . $description; }

                $successIn = $this->transactionModel->create([
                    'account_id' => $toAccountId,
                    'category_id' => $transferInCatId,
                    'amount' => $amountIn,
                    'date' => date('Y-m-d H:i:s'),
                    'description' => $descIn
                ]);
                if (!$successIn) throw new Exception("Не вдалося створити вхідну транзакцію.");

                $db->commit();
                $this->session->flash('success', 'Переказ між рахунками успішно виконано.');
                $this->redirect('/transactions?account_id=' . $currentViewAccountId);

            } catch (PDOException | Exception $e) {
                 if ($db->inTransaction()) { $db->rollBack(); }
                 error_log("Transfer Error: " . $e->getMessage());
                 $this->session->flash('form_error', ['modal' => 'transferModal', 'message' => 'Помилка під час виконання переказу: ' . $e->getMessage()]);
                 $this->redirect('/transactions?account_id=' . $currentViewAccountId);
            }
        } else {
             $this->session->flash('form_error', ['modal' => 'transferModal', 'message' => implode(' ', $errors)]);
             $this->redirect('/transactions?account_id=' . $currentViewAccountId);
        }
     }
}