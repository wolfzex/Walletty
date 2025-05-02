<?php
/**
 * Вид для сторінки входу (login).
 *
 * Очікує наступні змінні з AuthController::showLogin (через BaseController::render):
 * - $pageTitle (string): Використовується макетом.
 * - $error (string|null): Загальне повідомлення про помилку (flash).
 * - $success (string|null): Повідомлення про успіх (flash).
 * - $errors (array): Масив помилок валідації (flash, ключ => повідомлення).
 * - $old_email (string|null): Старе значення поля email (flash).
 */

// Робимо змінні доступними для зручності (хоча вони вже доступні завдяки extract у BaseController)
$error = $error ?? $this->session->getFlash('error'); // Альтернативний доступ через сесію, якщо передача через $data не використовується
$success = $success ?? $this->session->getFlash('success');
$errors = $errors ?? $this->session->getFlash('errors') ?? []; // Масив помилок валідації
$old_email = $old_email ?? $this->session->getFlash('old_email') ?? '';

?>

<h2>Вхід в обліковий запис</h2>
<div class="icon-placeholder">
    <i class="fas fa-user"></i>
</div>

<?php // Відображення повідомлень про помилку або успіх ?>
<?php if (!empty($success)): ?>
    <p class="message success-message"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <p class="message error-message"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php // Додаткове повідомлення про помилки валідації, якщо вони є
if (!empty($errors) && empty($error)) { // Показуємо тільки якщо немає загальної помилки
    echo '<p class="message error-message">Будь ласка, виправте помилки у формі.</p>';
}
?>

<form action="/auth/login" method="POST" id="loginForm" novalidate>
    <div class="input-group">
        <label for="login-email">Пошта:</label>
        <input
            type="email"
            id="login-email"
            name="email"
            required
            value="<?php echo htmlspecialchars($old_email); ?>"
            class="<?php echo !empty($errors['email']) ? 'input-error' : ''; ?>"
            aria-describedby="email-error"
        >
        <?php if (!empty($errors['email'])): ?>
            <p id="email-error" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['email']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="input-group">
        <label for="login-password">Пароль:</label>
        <input
            type="password"
            id="login-password"
            name="password"
            required
            class="<?php echo !empty($errors['password']) ? 'input-error' : ''; ?>"
            aria-describedby="password-error"
        >
         <?php if (!empty($errors['password'])): ?>
            <p id="password-error" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['password']); ?>
            </p>
        <?php endif; ?>
    </div>
    <button type="submit" name="login" class="btn btn-primary">Вхід</button>
</form>

<div class="switch-form">
    <p>Ще не маєш свого облікового запису?</p>
    <a href="/auth/register" class="btn btn-secondary">Зареєструватись</a>
</div>