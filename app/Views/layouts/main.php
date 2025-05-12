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

 $pageTitle = $pageTitle ?? 'Walletty';
 $userName = $userName ?? 'Користувач';
 $accounts = $accounts ?? [];
 $jsCategories = $jsCategories ?? ['income' => [], 'expense' => []];
 $selectedAccountId = $selectedAccountId ?? null;
 $allowedCurrencies = $allowedCurrencies ?? ['UAH', 'USD', 'EUR'];
 $phpPageLoadError = $phpPageLoadError ?? null;
 $showSidebar = $showSidebar ?? false;
 $currentAccountIdForTabs = $currentAccountIdForTabs ?? ($selectedAccountId ?: 0);
 $categoryTypeForTabs = $categoryTypeForTabs ?? 'expense';

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
        if ($showSidebar === true):
            try {
                include APP_PATH . '/Views/partials/_sidebar.php';
            } catch (\Throwable $e) {
                echo "<aside class='sidebar'><p class='message error-message'>Помилка завантаження сайдбару: " . htmlspecialchars($e->getMessage()) . "</p></aside>";
                error_log("Sidebar include error: " . $e->getMessage());
            }
        endif;
        ?>

        <?php
        echo $content ?? '<div class="content-area"><p class="message error-message">Помилка: Вміст сторінки не знайдено.</p></div>';
        ?>
    </div>

    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> Walletty. Всі права захищено.</p>
    </footer>

    <?php
    try {
        $modalData = [
             'allowedCurrencies' => $allowedCurrencies,
             'currentAccountIdForTabs' => $currentAccountIdForTabs,
             'all_user_accounts' => $accounts
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

    <script>
        const allAccountsData = <?php echo json_encode($accounts ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
        const allCategoriesModal = <?php echo json_encode($jsCategories ?? ['income' => [], 'expense' => []], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

    </script>

    <script src="/js/script.js"></script>

</body>
</html>