<?php
/**
 * Partial view для сайдбару зі списком рахунків.
 *
 * Очікує наступні змінні з файлу, що його підключає (main.php):
 * - $accounts (array): Масив рахунків користувача. Кожен елемент - асоц. масив ['id', 'name', 'currency'].
 * - $selectedAccountId (int|null): ID поточного активного рахунку.
 */

// Встановлюємо значення за замовчуванням, якщо змінні не були передані
$accounts = $accounts ?? [];
$selectedAccountId = $selectedAccountId ?? null;
?>
<aside class="sidebar">
    <h2 class="sidebar-title">Мої рахунки</h2>

    <button class="btn btn-add-account" id="addAccountBtn">
        <i class="fas fa-plus"></i> Додати рахунок
    </button>

    <ul class="account-list" id="accountList">
        <?php if (!empty($accounts)): // Якщо є рахунки ?>
            <?php foreach ($accounts as $account): ?>
                <?php
                    // Визначаємо, чи є поточний рахунок активним
                    $isActive = ($account['id'] == $selectedAccountId);
                    $activeClass = $isActive ? 'active' : '';
                ?>
                <li class="account-item <?php echo $activeClass; ?>">
                    <a href="/accounts?account_id=<?php echo $account['id']; ?>" class="account-link">
                        <?php echo htmlspecialchars($account['name']); ?>
                        </a>
                    <div class="account-actions">
                        <button type="button" class="btn-icon edit-account-btn"
                           data-account-id="<?php echo $account['id']; ?>"
                           data-account-name="<?php echo htmlspecialchars($account['name']); ?>"
                           data-account-currency="<?php echo htmlspecialchars($account['currency']); ?>"
                           title="Редагувати">
                           <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn-icon delete-account-btn"
                           data-account-id="<?php echo $account['id']; ?>"
                           data-account-name="<?php echo htmlspecialchars($account['name']); ?>"
                           title="Видалити">
                           <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: // Якщо рахунків немає ?>
            <li style="padding: 10px 15px; color: #777;">
                Немає створених рахунків.
            </li>
        <?php endif; ?>
    </ul>
</aside>