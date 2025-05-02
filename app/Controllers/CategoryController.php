<?php
// app/Controllers/CategoryController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Models\Account; // Потрібна для отримання списку рахунків для сайдбару
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
        $this->checkAuthentication(); // Всі дії вимагають входу
    }

    /**
     * Відображає сторінку управління категоріями.
     */
    public function index(): void
    {
        $userId = (int)$this->session->get('user_id');
        $userName = $this->session->get('user_name', 'Користувач');

        // Визначаємо тип категорій для фільтрації (з GET або за замовчуванням 'expense')
        $categoryTypeFilter = $this->request->get('type', 'expense');
        if (!in_array($categoryTypeFilter, ['income', 'expense'])) {
            $categoryTypeFilter = 'expense'; // Значення за замовчуванням, якщо передано невірне
        }

        // Отримуємо категорії поточного типу
        $categoriesCurrentType = $this->categoryModel->findByUserIdAndType($userId, $categoryTypeFilter);

        // Отримуємо ID обраної категорії (з GET або перша зі списку)
        $selectedCategoryId = null;
        $selectedCategory = null;
        if (!empty($categoriesCurrentType)) {
             $requestedCategoryId = filter_var($this->request->get('selected_id'), FILTER_VALIDATE_INT);
             // $selectedCategoryId = $requestedCategoryId ?: $categoriesCurrentType[0]['id']; // --- ПОПЕРЕДНЯ ЛОГІКА ---

             // --- НОВА ЛОГІКА ---
             // Спочатку шукаємо requestedCategoryId серед поточних категорій
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

             // Якщо ID не було передано, або переданий ID не знайдено СЕРЕД КАТЕГОРІЙ ПОТОЧНОГО ТИПУ,
             // беремо першу категорію поточного типу
             if (!$found) {
                 $selectedCategoryId = $categoriesCurrentType[0]['id'];
                 $selectedCategory = $categoriesCurrentType[0];
                 // Показуємо попередження тільки якщо ID БУВ переданий, але не знайдений
                 if ($requestedCategoryId) {
                      // $this->session->flash('warning', 'Обрану категорію не знайдено.'); // -- Попередження прибрано згідно побажань
                 }
             }
             // --- КІНЕЦЬ НОВОЇ ЛОГІКИ ---
        }


        // Дані для сайдбару (потрібні для модалок та заголовка)
        $accounts = $this->accountModel->findAllByUserId($userId);
        // Визначаємо account_id для формування посилань у табах
        $currentAccountIdForTabs = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
        if (!$currentAccountIdForTabs && !empty($accounts)) {
            $currentAccountIdForTabs = $accounts[0]['id']; // Беремо ID першого рахунку, якщо не передано
        }


        // Дані для JS (модалки)
        $jsCategories = [
            'income' => $this->categoryModel->findByUserIdAndType($userId, 'income'),
            'expense' => $this->categoryModel->findByUserIdAndType($userId, 'expense')
        ];


        $data = [
            'pageTitle' => 'Категорії (' . ($categoryTypeFilter === 'income' ? 'Доходи' : 'Витрати') . ')',
            'userName' => $userName,
            'accounts' => $accounts, // Для модалок
            'selectedAccountId' => $currentAccountIdForTabs, // ID рахунку для модалок та табів
            'categories' => $categoriesCurrentType, // Категорії поточного типу для select
            'selectedCategory' => $selectedCategory, // Обрана категорія для показу опису
            'selectedCategoryId' => $selectedCategoryId, // ID обраної категорії
            'categoryTypeFilter' => $categoryTypeFilter, // Поточний фільтр типу
            'allowedCurrencies' => $this->accountModel->getAllowedCurrencies(),
            'allUserAccountsJson' => json_encode($accounts), // JSON рахунків для модалок
            'jsCategoriesModalJson' => json_encode($jsCategories), // JSON категорій для модалок
            'warning' => $this->session->getFlash('warning'),
            'success' => $this->session->getFlash('success'),
            'phpPageLoadError' => $this->session->getFlash('form_error'),
            'showSidebar' => false, // <-- ДОДАНО ДЛЯ УМОВНОГО САЙДБАРУ
            'currentAccountIdForTabs' => $currentAccountIdForTabs ?? 0,
             'categoryTypeForTabs' => $categoryTypeFilter, // Для посилання на цю ж вкладку з ін. сторінок

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
                // Редірект на сторінку категорій з новим типом та обраною категорією
                $redirectUrl = "/categories?type={$type}&selected_id={$categoryId}";
                 // Збережемо account_id, якщо він був у запиті (для навігації табів)
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
              // Спробуємо визначити тип категорії для редіректу
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
         $redirectType = 'expense'; // Тип за замовчуванням для редіректу

         if (!$categoryId) {
              $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Невірний ID категорії.']);
              $this->redirect('/categories');
              return;
         }

         // Зберігаємо тип категорії перед видаленням для коректного редіректу
         $category = $this->categoryModel->findByIdAndUserId($categoryId, $userId);
         if ($category) {
             $redirectType = $category['type'];
             // Перевіряємо, чи використовується категорія
             if ($this->categoryModel->isUsedInTransactions($categoryId)) {
                  $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Неможливо видалити категорію "'.htmlspecialchars($category['name']).'", оскільки вона використовується в транзакціях.']);
             } else {
                 // Видаляємо категорію
                 if ($this->categoryModel->delete($categoryId, $userId)) {
                     $this->session->flash('success', 'Категорію "'.htmlspecialchars($category['name']).'" успішно видалено.');
                 } else {
                      $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Не вдалося видалити категорію. Можливо, сталася помилка бази даних.']);
                 }
             }
         } else {
             // Категорія не знайдена або не належить користувачу
             $this->session->flash('form_error', ['modal' => 'deleteCategoryModal', 'message' => 'Категорію не знайдено або у вас немає прав на її видалення.']);
         }

         // Редірект на сторінку категорій відповідного типу
         $redirectUrl = "/categories?type={$redirectType}";
         $accountId = filter_var($this->request->get('account_id'), FILTER_VALIDATE_INT);
         if ($accountId) $redirectUrl .= "&account_id={$accountId}";
         $this->redirect($redirectUrl);
     }
}