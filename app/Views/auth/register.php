<?php
/**
 * Вид для сторінки реєстрації (register).
 *
 * Очікує наступні змінні з AuthController::showRegister (через BaseController::render):
 * - $pageTitle (string): Використовується макетом.
 * - $errors (array): Масив помилок валідації (flash, ключ => повідомлення).
 * - $old_input (array): Асоціативний масив старих введених значень (flash).
 */

// Отримуємо змінні для зручності
$errors = $errors ?? $this->session->getFlash('errors') ?? [];
$old_input = $old_input ?? $this->session->getFlash('old_input') ?? [];

?>

<h2>Реєстрація облікового запису</h2>
<div class="icon-placeholder">
    <i class="fas fa-user-plus"></i>
</div>

<?php // Відображення загального повідомлення про помилки валідації, якщо вони є ?>
<?php if (!empty($errors)): ?>
    <p class="message error-message">Будь ласка, виправте помилки у формі.</p>
<?php endif; ?>

<form action="/auth/register" method="POST" id="registerForm" novalidate>
    <div class="input-group input-half">
        <label for="reg-first-name">Ім'я:</label>
        <input
            type="text"
            id="reg-first-name"
            name="first_name"
            required
            value="<?php echo htmlspecialchars($old_input['first_name'] ?? ''); ?>"
            class="<?php echo !empty($errors['first_name']) ? 'input-error' : ''; ?>"
            aria-describedby="first-name-error"
        >
        <?php if (!empty($errors['first_name'])): ?>
            <p id="first-name-error" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['first_name']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="input-group input-half">
        <label for="reg-last-name">Прізвище:</label>
        <input
            type="text"
            id="reg-last-name"
            name="last_name"
            required
            value="<?php echo htmlspecialchars($old_input['last_name'] ?? ''); ?>"
            class="<?php echo !empty($errors['last_name']) ? 'input-error' : ''; ?>"
            aria-describedby="last-name-error"
        >
        <?php if (!empty($errors['last_name'])): ?>
            <p id="last-name-error" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['last_name']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="input-group input-full">
        <label for="reg-email">Пошта:</label>
        <input
            type="email"
            id="reg-email"
            name="email"
            required
            value="<?php echo htmlspecialchars($old_input['email'] ?? ''); ?>"
            class="<?php echo !empty($errors['email']) ? 'input-error' : ''; ?>"
            aria-describedby="email-error-reg"
        >
        <?php if (!empty($errors['email'])): ?>
            <p id="email-error-reg" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['email']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="input-group input-half">
        <label for="reg-password">Пароль (мін. 6 символів):</label>
        <input
            type="password"
            id="reg-password"
            name="password"
            required
            minlength="6"
            class="<?php echo !empty($errors['password']) ? 'input-error' : ''; ?>"
            aria-describedby="password-error-reg"
        >
        <?php if (!empty($errors['password'])): ?>
            <p id="password-error-reg" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['password']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="input-group input-half">
        <label for="reg-password-confirm">Підтвердіть Пароль:</label>
        <input
            type="password"
            id="reg-password-confirm"
            name="password_confirm"
            required
            minlength="6"
            class="<?php echo !empty($errors['password_confirm']) ? 'input-error' : ''; ?>"
            aria-describedby="password-confirm-error"
        >
         <?php if (!empty($errors['password_confirm'])): ?>
            <p id="password-confirm-error" class="message error-message" style="margin-top: 5px; padding: 5px 10px; font-size: 0.85em;">
                <?php echo htmlspecialchars($errors['password_confirm']); ?>
            </p>
        <?php endif; ?>
    </div>
    <button type="submit" name="register" class="btn btn-primary">Зареєструватись</button>
</form>

<div class="switch-form">
    <p>Вже маєш обліковий запис?</p>
    <a href="/auth/login" class="btn btn-secondary">Вхід</a>
</div>