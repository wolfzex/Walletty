<?php
// app/Controllers/StatisticsController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\User;

class StatisticsController extends BaseController
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
     * Відображає сторінку статистики.
     */
    public function index(): void
    {
        $userId = (int)$this->session->get('user_id');
        $userName = $this->session->get('user_name', 'Користувач');

        $accounts = $this->accountModel->findAllByUserId($userId);

        $selectedAccountId = null;
        $selectedAccount = null;
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
                if ($requestedAccountId) $this->session->flash('warning', 'Обраний рахунок не знайдено. Показано статистику для першого.');
            }
        }

        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-t'));
        $dateError = null;

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            if ($start > $end) {
                $dateError = "Дата початку не може бути пізнішою за дату кінця.";
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
            } else {
                 $startDate = $start->format('Y-m-d');
                 $endDate = $end->format('Y-m-d');
            }
        } catch (\Exception $e) {
            $dateError = "Невірний формат дати.";
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        }
        if ($dateError) {
             $this->session->flash('warning', $dateError);
        }

        $statisticsData = [
            'total_income' => 0.0,
            'total_expense' => 0.0,
            'balance' => 0.0,
            'daily_breakdown' => [],
            'category_breakdown_income' => [],
            'category_breakdown_expense' => []
        ];

        if ($selectedAccountId) {
            $transactions = $this->transactionModel->getStatisticsData($selectedAccountId, $userId, $startDate, $endDate);
            $this->processStatistics($transactions, $statisticsData);
        }

        $jsCategories = [
             'income' => $this->categoryModel->findByUserIdAndType($userId, 'income'),
             'expense' => $this->categoryModel->findByUserIdAndType($userId, 'expense')
         ];


        $data = [
            'pageTitle' => 'Статистика' . ($selectedAccount ? ' - ' . $selectedAccount['name'] : ''),
            'userName' => $userName,
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'selectedAccount' => $selectedAccount,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statisticsData' => $statisticsData,
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

        $this->render('statistics/index', $data);
    }

    /**
     * Обробляє масив транзакцій для розрахунку статистики.
     * Модифікує переданий за посиланням масив $statisticsData.
     *
     * @param array $transactions Масив транзакцій з моделі.
     * @param array &$statisticsData Масив для збереження результатів статистики.
     */
    private function processStatistics(array $transactions, array &$statisticsData): void
    {
        foreach ($transactions as $trans) {
            $amount = (float)$trans['amount'];
            $date = $trans['transaction_date'];
            $categoryName = $trans['category_name'];
            $type = $trans['category_type'];

            if ($type === 'income') {
                $statisticsData['total_income'] += $amount;
            } else {
                $statisticsData['total_expense'] += $amount;
            }

            if (!isset($statisticsData['daily_breakdown'][$date])) {
                $statisticsData['daily_breakdown'][$date] = ['income' => 0.0, 'expense' => 0.0];
            }
            $statisticsData['daily_breakdown'][$date][$type] += $amount;

            if ($type === 'income') {
                if (!isset($statisticsData['category_breakdown_income'][$categoryName])) {
                    $statisticsData['category_breakdown_income'][$categoryName] = 0.0;
                }
                $statisticsData['category_breakdown_income'][$categoryName] += $amount;
            } else {
                if (!isset($statisticsData['category_breakdown_expense'][$categoryName])) {
                    $statisticsData['category_breakdown_expense'][$categoryName] = 0.0;
                }
                $statisticsData['category_breakdown_expense'][$categoryName] += $amount;
            }
        }

        $statisticsData['balance'] = $statisticsData['total_income'] - $statisticsData['total_expense'];

        ksort($statisticsData['daily_breakdown']);
        arsort($statisticsData['category_breakdown_income']);
        arsort($statisticsData['category_breakdown_expense']);
    }
}