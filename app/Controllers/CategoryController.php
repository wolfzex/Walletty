<?php
// app/Controllers/CategoryController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;

class CategoryController extends BaseController
{
    private Category $categoryModel;
    private Account $accountModel;
    private User $userModel;

    public function __construct(Request $request, Session $session)
    {
        parent::__construct($request, $session);
        $this->categoryModel = new Category();
        $this->accountModel = new Account();
        $this->userModel = new User();
        $this->checkAuthentication();
    }

    /**
     * Відображає сторінку управління категоріями.
     */
    public function index(): void
    {
        $userId = (int)$this->session->get('user_id');
        $userName = $this->session->get('user_name', 'Користувач');

        $categoryTypeFilter = $this->request->get('type', 'expense');
        if (!in_array($categoryTypeFilter, ['income', 'expense'])) {
            $categoryTypeFilter = 'expense';
        }

        $categoriesCurrentType = $this->categoryModel->findByUserIdAndType($userId, $categoryTypeFilter);

        $selectedCategoryId = null;
        $selectedCategory = null;
        if (!empty($categoriesCurrentType)) {
             $requestedCategoryId = filter_var($this->request->get('selected_id'), FILTER_VALIDATE_INT);

             $found = false;
             if ($requestedCategoryId) {
                 foreach($categoriesCurrentType as $cat) {
                     if ($cat['id'] == $requestedCategoryId) {
                         $selectedCategory = $cat;
                         $selectedCategoryId = $requestedCategoryId;
                         $found = true;
                         break;
                     }
                 }
             }

             if (!$found) {
                 $selectedCategoryId = $categoriesCurrentType[0]['id'];
                 $selectedCategory = $categoriesCurrentType[0];

                 if ($requestedCategoryId) {
                 }
             }
        }

        $accounts = $this->accountModel->findAllByUserId($userId);
        $currentAccountIdForTabs = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
        if (!$currentAccountIdForTabs && !empty($accounts)) {
            $currentAccountIdForTabs = $accounts[0]['id'];
        }

        $jsCategories = [
            'income' => $this->categoryModel->findByUserIdAndType($userId, 'income'),
            'expense' => $this->categoryModel->findByUserIdAndType($userId, 'expense')
        ];

        $data = [
            'pageTitle' => 'Категорії (' . ($categoryTypeFilter === 'income' ? 'Доходи' : 'Витрати') . ')',
            'userName' => $userName,
            'accounts' => $accounts,
            'selectedAccountId' => $currentAccountIdForTabs,
            'categories' => $categoriesCurrentType,
            'selectedCategory' => $selectedCategory,
            'selectedCategoryId' => $selectedCategoryId,
            'categoryTypeFilter' => $categoryTypeFilter,
            'allowedCurrencies' => $this->accountModel->getAllowedCurrencies(),
            'allUserAccountsJson' => json_encode($accounts),
            'jsCategoriesModalJson' => json_encode($jsCategories),
            'warning' => $this->session->getFlash('warning'),
            'success' => $this->session->getFlash('success'),
            'phpPageLoadError' => $this->session->getFlash('form_error'),
            'showSidebar' => false,
            'currentAccountIdForTabs' => $currentAccountIdForTabs ?? 0,
             'categoryTypeForTabs' => $categoryTypeFilter,

        ];

        $this->render('categories/index', $data);
    }

    /**
     * Обробляє додавання нової категорії.
     */
    public function add(): void
    {
        if (!$this->request->isPost()) { $this->redirect('/categories'); }

        $userId = (int)$this->session->get('user_id');
        $name = trim($this->request->post('category_name', ''));
        $type = trim($this->request->post('category_type', ''));
        $description = trim($this->request->post('category_description', ''));
        $errors = [];

        if (empty($name)) { $errors['category_name'] = "Назва категорії не може бути порожньою."; }
        if (!in_array($type, ['income', 'expense'])) { $errors['category_type'] = "Невірний тип категорії."; }

        if (empty($errors)) {
            $data = [
                'user_id' => $userId,
                'name' => $name,
                'type' => $type,
                'description' => $description
            ];
            $categoryId = $this->categoryModel->create($data);

            if ($categoryId) {
                $this->session->flash('success', 'Категорію успішно додано.');
                $redirectUrl = "/categories?type={$type}&selected_id={$categoryId}";
                 $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
                 if ($accountId) $redirectUrl .= "&account_id={$accountId}";
                $this->redirect($redirectUrl);
            } else {
                 $this->session->flash('form_error', ['modal' => 'addCategoryModal', 'message' => 'Помилка бази даних при додаванні категорії.']);
                 $redirectUrl = "/categories?type={$type}";
                 $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
                 if ($accountId) $redirectUrl .= "&account_id={$accountId}";
                 $this->redirect($redirectUrl);
            }
        } else {
             $this->session->flash('form_error', ['modal' => 'addCategoryModal', 'message' => implode(' ', $errors)]);
             $redirectUrl = "/categories?type={$type}";
             $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
             if ($accountId) $redirectUrl .= "&account_id={$accountId}";
             $this->redirect($redirectUrl);
        }
    }

     /**
      * Обробляє редагування категорії.
      */
     public function edit(): void
     {
         if (!$this->request->isPost()) { $this->redirect('/categories'); }

         $userId = (int)$this->session->get('user_id');
         $categoryId = filter_var($this->request->post('category_id'), FILTER_VALIDATE_INT);
         $name = trim($this->request->post('category_name', ''));
         $type = trim($this->request->post('category_type', ''));
         $description = trim($this->request->post('category_description', ''));
         $errors = [];

         if (!$categoryId) { $errors['general'] = "Невірний ID категорії."; }
         if (empty($name)) { $errors['category_name'] = "Назва категорії не може бути порожньою."; }
         if (!in_array($type, ['income', 'expense'])) { $errors['category_type'] = "Невірний тип категорії."; }

         if (empty($errors)) {
             $data = [
                 'name' => $name,
                 'type' => $type,
                 'description' => $description
             ];
             $success = $this->categoryModel->update($categoryId, $userId, $data);

             if ($success) {
                  $this->session->flash('success', 'Категорію успішно оновлено.');
                  $redirectUrl = "/categories?type={$type}&selected_id={$categoryId}";
                  $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
                  if ($accountId) $redirectUrl .= "&account_id={$accountId}";
                  $this->redirect($redirectUrl);
             } else {
                  $this->session->flash('form_error', ['modal' => 'editCategoryModal', 'message' => 'Не вдалося оновити категорію. Можливо, вона не існує або сталася помилка.']);
                  $redirectUrl = "/categories?type={$type}&selected_id={$categoryId}";
                  $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
                  if ($accountId) $redirectUrl .= "&account_id={$accountId}";
                  $this->redirect($redirectUrl);
             }
         } else {
              $this->session->flash('form_error', ['modal' => 'editCategoryModal', 'message' => implode(' ', $errors)]);
              $originalCategory = $this->categoryModel->findByIdAndUserId($categoryId, $userId);
              $redirectType = $originalCategory ? $originalCategory['type'] : 'expense';
              $redirectUrl = "/categories?type={$redirectType}&selected_id={$categoryId}";
              $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
               if ($accountId) $redirectUrl .= "&account_id={$accountId}";
              $this->redirect($redirectUrl);
         }
     }
    /**
     * Обробляє видалення категорії.
     */
     public function delete(): void
     {
         if (!$this->request->isPost()) { $this->redirect('/categories'); }

         $userId = (int)$this->session->get('user_id');
         $categoryId = filter_var($this->request->post('category_id'), FILTER_VALIDATE_INT);
         $redirectType = 'expense';

         if (!$categoryId) {
              $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Невірний ID категорії.']);
              $this->redirect('/categories');
              return;
         }

         $category = $this->categoryModel->findByIdAndUserId($categoryId, $userId);
         if ($category) {
             $redirectType = $category['type'];
             if ($this->categoryModel->isUsedInTransactions($categoryId)) {
                  $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Неможливо видалити категорію "'.htmlspecialchars($category['name']).'", оскільки вона використовується в транзакціях.']);
             } else {
                 if ($this->categoryModel->delete($categoryId, $userId)) {
                     $this->session->flash('success', 'Категорію "'.htmlspecialchars($category['name']).'" успішно видалено.');
                 } else {
                      $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Не вдалося видалити категорію. Можливо, сталася помилка бази даних.']);
                 }
             }
         } else {
             $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Категорію не знайдено або у вас немає прав на її видалення.']);
         }

         $redirectUrl = "/categories?type={$redirectType}";
         $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
         if ($accountId) $redirectUrl .= "&account_id={$accountId}";
         $this->redirect($redirectUrl);
     }
}