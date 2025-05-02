<?php
/**
 * Основний макет додатку для автентифікованих користувачів.
 *
 * Очікує наступні змінні з контролера (через BaseController::render):
 * - $pageTitle (string): Заголовок сторінки.
 * - $content (string): HTML-вміст основного блоку сторінки.
 * - $userName (string): Ім'я поточного користувача для хедера.
 * - $accounts (array): Масив рахунків користувача (потрібен завжди для модалок).
 * - $selectedAccountId (int|null): ID поточного активного рахунку (потрібен завжди).
 * - $jsCategories (array): Масив категорій ['income'=>[], 'expense'=>[]] (потрібен завжди).
 * - $allowedCurrencies (array): Масив дозволених валют для модальних вікон.
 * - $phpPageLoadError (array|null): Масив ['modal' => ID, 'message' => текст] для відображення помилки в модалці після редіректу.
 * - $showSidebar (bool): Чи показувати сайдбар (true/false).
 * - $currentAccountIdForTabs (int|null): ID рахунку для формування посилань у табах.
 * - $categoryTypeForTabs (string): Тип категорії для посилання на вкладку "Категорії".
 * - (Також доступні всі інші змінні, передані в масиві $data методу render)
 */

 // Встановлюємо значення за замовчуванням, якщо змінні не передані
 $pageTitle = $pageTitle ?? 'Walletty';
 $userName = $userName ?? 'Користувач'; // Ім'я користувача з сесії або моделі
 $accounts = $accounts ?? []; // Список рахунків
 $jsCategories = $jsCategories ?? ['income' => [], 'expense' => []]; // Категорії
 $selectedAccountId = $selectedAccountId ?? null; // ID активного рахунку
 $allowedCurrencies = $allowedCurrencies ?? ['UAH', 'USD', 'EUR']; // Валюти за замовчуванням
 $phpPageLoadError = $phpPageLoadError ?? null; // Помилка для модалки
 $showSidebar = $showSidebar ?? false; // За замовчуванням сайдбар не показуємо
 $currentAccountIdForTabs = $currentAccountIdForTabs ?? ($selectedAccountId ?: 0); // ID для табів
 $categoryTypeForTabs = $categoryTypeForTabs ?? 'expense'; // Тип категорії для табів

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Walletty</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="top-header">
         <div class="logo-container">
             <a href="/">
                 <img src="/pics/WallettyLogoTransperentName.png" alt="Walletty Logo" class="header-logo">
             </a>
         </div>
         <div class="user-menu-top">
             <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
             <i class="fas fa-user-circle user-icon"></i>
             <a href="/logout" class="logout-link" title="Вийти">
                 <i class="fas fa-sign-out-alt"></i>
             </a>
         </div>
    </div>

    <div class="main-layout <?php echo ($showSidebar === true) ? '' : 'no-sidebar'; ?>">
        <?php
        // Перевіряємо, чи потрібно показувати сайдбар для цієї сторінки
        if ($showSidebar === true):
            try {
                // Змінні $accounts та $selectedAccountId вже доступні з контролера
                include APP_PATH . '/Views/partials/_sidebar.php';
            } catch (\Throwable $e) {
                echo "<aside class='sidebar'><p class='message error-message'>Помилка завантаження сайдбару: " . htmlspecialchars($e->getMessage()) . "</p></aside>";
                error_log("Sidebar include error: " . $e->getMessage());
            }
        endif; // Кінець умови if ($showSidebar)
        ?>

        <?php
        // Вставляємо основний контент сторінки ($content)
        echo $content ?? '<div class="content-area"><p class="message error-message">Помилка: Вміст сторінки не знайдено.</p></div>';
        ?>
    </div>

    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> Walletty. Всі права захищено.</p>
    </footer>

    <?php
    // Підключаємо всі модальні вікна з окремого файлу
    try {
        // Передаємо потрібні змінні в область видимості partial
        $modalData = [
             'allowedCurrencies' => $allowedCurrencies,
             // 'allUserAccountsJson' => $allUserAccountsJson, // Більше не потрібно
             // 'jsCategoriesModalJson' => $jsCategoriesModalJson, // Більше не потрібно
             'currentAccountIdForTabs' => $currentAccountIdForTabs, // Для форм всередині модалок
             'all_user_accounts' => $accounts // Використовуємо $accounts, яка завжди передається
        ];
         extract($modalData);
         include APP_PATH . '/Views/partials/_modals.php';
    } catch (\Throwable $e) {
        echo "<div class='message error-message'>Помилка завантаження модальних вікон: " . htmlspecialchars($e->getMessage()) . "</div>";
        error_log("Modals include error: " . $e->getMessage());
    }
    ?>

    <div id="phpPageLoadError"
         data-target-modal="<?php echo htmlspecialchars($phpPageLoadError['modal'] ?? ''); ?>"
         data-error-message="<?php echo htmlspecialchars($phpPageLoadError['message'] ?? ''); ?>"
         style="display: none;">
    </div>

    <?php // !!! ЗМІНА ТУТ: Спосіб ініціалізації глобальних змінних JS !!! ?>
    <script>
        // Передаємо дані для JS (доступні глобально або через селектори)
        // Більш безпечний спосіб ініціалізації через json_encode без JSON.parse
        const allAccountsData = <?php echo json_encode($accounts ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
        const allCategoriesModal = <?php echo json_encode($jsCategories ?? ['income' => [], 'expense' => []], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

        // Глобальна функція populateCategoriesForModal тепер визначається в script.js,
        // але вона буде використовувати змінну allCategoriesModal, визначену вище.
    </script>
    <?php // !!! КІНЕЦЬ ЗМІН !!! ?>

    <script src="/js/script.js"></script>

</body>
</html>