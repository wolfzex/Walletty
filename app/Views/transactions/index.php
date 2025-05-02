<?php
/**
 * Вид для сторінки транзакцій.
 *
 * Очікує змінні з TransactionController::index:
 * - $selectedAccount (array|null): Дані обраного рахунку.
 * - $transactions (array): Масив транзакцій для відображення.
 * - $accounts (array): Всі рахунки користувача (для селектора та сайдбару).
 * - $allUserCategories (array): Всі категорії користувача (для фільтра).
 * - $filters (array): Активні фільтри ['category_id', 'start_date', 'end_date'].
 * - $success (string|null): Flash-повідомлення про успіх.
 * - $warning (string|null): Flash-повідомлення з попередженням.
 * - $phpPageLoadError (array|null): Помилка для модалки.
 * (Також доступні змінні для макету: $pageTitle, $userName, $selectedAccountId...)
 */

$selectedAccount = $selectedAccount ?? null;
$transactions = $transactions ?? [];
$accounts = $accounts ?? [];
$allUserCategories = $allUserCategories ?? [];
$filters = $filters ?? [];
$success = $success ?? $this->session->getFlash('success');
$warning = $warning ?? $this->session->getFlash('warning');
$phpPageLoadError = $phpPageLoadError ?? $this->session->getFlash('form_error');

$currentAccountId = $selectedAccountId ?? ($accounts[0]['id'] ?? 0);
$currentAccountCurrency = $selectedAccount['currency'] ?? '';

// Визначаємо, чи активний будь-який фільтр
$isAnyFilterActive = !empty($filters);

?>
<div class="content-area">
    <nav class="content-tabs">
         <a href="/accounts?account_id=<?php echo $currentAccountId; ?>" class="tab-link">Загальна інформація</a>
        <a href="/categories?account_id=<?php echo $currentAccountId; ?>&type=expense" class="tab-link">Категорії</a>
        <a href="/statistics?account_id=<?php echo $currentAccountId; ?>" class="tab-link">Статистика</a>
        <a href="/transactions?account_id=<?php echo $currentAccountId; ?>" class="tab-link active">Транзакції</a>
    </nav>

    <div class="content-block">

        <?php // Відображення повідомлень ?>
        <?php if ($success): ?>
            <p class="message success-message"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($warning): ?>
            <p class="message error-message"><?php echo htmlspecialchars($warning); ?></p>
        <?php endif; ?>
         <?php // Помилка з модалки (якщо була передана, а не оброблена JS)
         if ($phpPageLoadError && is_array($phpPageLoadError)): ?>
              <p class="message error-message"><?php echo htmlspecialchars($phpPageLoadError['message']); ?></p>
         <?php endif; ?>

        <?php if (!empty($accounts)): ?>
            <div class="account-selector-container">
                 <label for="account_selector">Рахунок:</label>
                 <select id="account_selector" name="account_selector">
                     <?php foreach ($accounts as $account): ?>
                         <option value="<?php echo $account['id']; ?>" <?php echo ($account['id'] == $currentAccountId) ? 'selected' : ''; ?>>
                             <?php echo htmlspecialchars($account['name']) . ' (' . htmlspecialchars($account['currency']) . ')'; ?>
                         </option>
                     <?php endforeach; ?>
                 </select>
            </div>

             <div class="transaction-header">
                <h2>Транзакції</h2>
                <div class="transaction-actions-buttons">
                    <button type="button" class="btn btn-secondary btn-transfer" id="transferBtn">
                          <i class="fas fa-exchange-alt"></i> Переказ
                     </button>
                    <button type="button" class="btn btn-primary btn-add-transaction" id="addTransactionBtn">
                        <i class="fas fa-plus"></i> Додати транзакцію
                    </button>
                </div>
            </div>


             <div class="transaction-filters-container">
                 <div class="form-group filter-group">
                     <label for="category_filter">Категорія:</label>
                     <select id="category_filter" name="category_filter">
                         <option value="all">Всі категорії</option>
                         <?php foreach ($allUserCategories as $category): ?>
                             <option value="<?php echo $category['id']; ?>"
                                     <?php echo (($filters['category_id'] ?? null) == $category['id']) ? 'selected' : ''; ?>>
                                 <?php echo htmlspecialchars($category['name']) . ' (' . ($category['type'] === 'income' ? 'Дохід' : 'Витрата') . ')'; ?>
                             </option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                 <div class="form-group filter-group date-filter">
                    <label for="start_date">Від:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                 </div>
                 <div class="form-group filter-group date-filter">
                    <label for="end_date">До:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                 </div>
                 <button type="button" id="apply_filters_btn" class="btn btn-primary btn-filter-apply">
                     Застосувати фільтри
                 </button>
                 <button type="button" id="reset_filters_btn" class="btn btn-secondary btn-filter-reset <?php echo $isAnyFilterActive ? '' : 'hidden'; ?>">
                      Скинути фільтри
                 </button>
             </div>

             <div class="transactions-list">
                <?php if (!empty($transactions)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Категорія</th>
                                <th>Опис</th>
                                <th class="amount-col">Сума</th>
                                <th class="actions-col">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr class="transaction-<?php echo htmlspecialchars($t['category_type']); ?>">
                                    <td><?php echo date("d.m.Y H:i", strtotime($t['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($t['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['trans_desc']); ?></td>
                                    <td class="amount-col">
                                         <?php
                                             $amount_display = number_format((float)$t['amount'], 2, '.', ' ');
                                             $display_sign = ($t['category_type'] === 'income' ? '+' : '-');
                                             echo $display_sign . $amount_display;
                                             echo ' ' . htmlspecialchars($currentAccountCurrency);
                                         ?>
                                    </td>
                                    <td class="actions-col">
                                        <button type="button" class="btn-icon delete-transaction-btn"
                                                data-transaction-id="<?php echo $t['id']; ?>"
                                                title="Видалити транзакцію">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                 <?php else: // Якщо транзакцій немає ?>
                    <p style="margin-top: 20px;">
                        <?php echo $isAnyFilterActive
                            ? 'Немає транзакцій за обраними фільтрами.'
                            : 'На цьому рахунку ще немає транзакцій.';
                        ?>
                    </p>
                 <?php endif; ?>
            </div>

        <?php else: // Якщо у користувача взагалі немає рахунків ?>
             <p class="message error-message">Немає доступних рахунків. Будь ласка, створіть рахунок на сторінці "Загальна інформація".</p>
        <?php endif; ?>

    </div> </div> <script>
    // JS для обробки зміни рахунку та застосування/скидання фільтрів
    document.addEventListener('DOMContentLoaded', function() {
        const accountSelector = document.getElementById('account_selector');
        const categoryFilter = document.getElementById('category_filter');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const applyFiltersBtn = document.getElementById('apply_filters_btn');
        const resetFiltersBtn = document.getElementById('reset_filters_btn');

        // Зміна рахунку
        accountSelector?.addEventListener('change', function() {
            const selectedAccountId = this.value;
            if (selectedAccountId) {
                 // Перенаправляємо на сторінку транзакцій для нового рахунку,
                 // скидаючи інші фільтри (або можна їх зберігати за бажанням)
                 window.location.href = '/transactions?account_id=' + selectedAccountId;
            }
        });

        // Застосування фільтрів
        applyFiltersBtn?.addEventListener('click', () => {
             const accountId = accountSelector?.value;
             if (!accountId) return;

             const params = new URLSearchParams();
             params.set('account_id', accountId);

             if (categoryFilter && categoryFilter.value !== 'all') {
                 params.set('category_id', categoryFilter.value);
             }
             if (startDateInput && startDateInput.value) {
                 params.set('start_date', startDateInput.value);
             }
              if (endDateInput && endDateInput.value) {
                 params.set('end_date', endDateInput.value);
             }
             window.location.href = '/transactions?' + params.toString();
        });

        // Скидання фільтрів
        resetFiltersBtn?.addEventListener('click', () => {
            const accountId = accountSelector?.value;
            if (accountId) {
                 window.location.href = '/transactions?account_id=' + accountId; // Перехід тільки з account_id
            } else {
                 window.location.href = '/transactions'; // Якщо рахунок не вибрано (малоймовірно)
            }
        });
    });
</script>