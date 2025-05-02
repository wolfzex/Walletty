<?php
/**
 * Макет для сторінок автентифікації (вхід, реєстрація).
 *
 * Очікує наступні змінні з контролера (через BaseController::render):
 * - $pageTitle (string): Заголовок сторінки.
 * - $content (string): HTML-вміст основного блоку сторінки (згенерований видом 'auth/login' або 'auth/register').
 * - (Також доступні всі інші змінні, передані в масиві $data методу render)
 */
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Автентифікація - Walletty'); ?> - Walletty</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-container">
            <header class="auth-header">
                 <img src="/pics/WallettyLogoTransperent.png" alt="Walletty Logo" class="header-logo">
            </header>

            <div class="form-box">
                <?php
                // Вставляємо основний контент сторінки (форма входу або реєстрації),
                // який був згенерований відповідним видом (auth/login.php або auth/register.php)
                // і переданий сюди через змінну $content методом BaseController::render()
                echo $content ?? '';
                ?>
            </div>
        </div>
    </div>

    <script src="/js/script.js"></script>
    </body>
</html>