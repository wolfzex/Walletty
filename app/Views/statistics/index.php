<?php
/**
 * Вид для сторінки статистики.
 *
 * Очікує змінні з StatisticsController::index:
 * - $selectedAccount (array|null): Дані обраного рахунку.
 * - $accounts (array): Всі рахунки користувача.
 * - $startDate (string): Дата початку періоду.
 * - $endDate (string): Дата кінця періоду.
 * - $statisticsData (array): Оброблені дані статистики.
 * - $warning (string|null): Flash-повідомлення з попередженням.
 * (Також доступні змінні для макету: $pageTitle, $userName, $selectedAccountId...)
 */

$selectedAccount = $selectedAccount ?? null;
$accounts = $accounts ?? [];
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$statisticsData = $statisticsData ?? [
    'total_income' => 0.0, 'total_expense' => 0.0, 'balance' => 0.0,
    'daily_breakdown' => [], 'category_breakdown_income' => [], 'category_breakdown_expense' => []
];
$warning = $warning ?? $this->session->getFlash('warning');

$currentAccountId = $selectedAccountId ?? ($accounts[0]['id'] ?? 0);
$currentAccountCurrency = $selectedAccount['currency'] ?? '';

?>
<div class="content-area">
    <nav class="content-tabs">
         <a href="/accounts?account_id=<?php echo $currentAccountId; ?>" class="tab-link">Загальна інформація</a>
        <a href="/categories?account_id=<?php echo $currentAccountId; ?>&type=expense" class="tab-link">Категорії</a>
        <a href="/statistics?account_id=<?php echo $currentAccountId; ?>" class="tab-link active">Статистика</a>
        <a href="/transactions?account_id=<?php echo $currentAccountId; ?>" class="tab-link">Транзакції</a>
    </nav>

    <div class="content-block">
        <h2>Статистика</h2>

        <?php if ($warning): ?>
            <p class="message error-message"><?php echo htmlspecialchars($warning); ?></p>
        <?php endif; ?>

        <?php if (!empty($accounts)): ?>
            <div class="statistics-filters-container">
                 <div class="form-group filter-group">
                    <label for="account_selector_stats">Рахунок:</label>
                    <select id="account_selector_stats" name="account_id">
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>" <?php echo ($account['id'] == $currentAccountId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($account['name']) . ' (' . htmlspecialchars($account['currency']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                 </div>
                 <div class="form-group filter-group date-filter">
                    <label for="start_date_stats">Від:</label>
                    <input type="date" id="start_date_stats" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                 </div>
                 <div class="form-group filter-group date-filter">
                    <label for="end_date_stats">До:</label>
                    <input type="date" id="end_date_stats" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                 </div>
                <button type="button" id="apply_statistics_filters_btn" class="btn btn-primary btn-filter-apply">
                     Показати статистику
                 </button>
            </div>

            <?php if ($selectedAccount): ?>
                 <div class="statistics-summary">
                     <h3>
                         Загальна статистика за період
                         (<?php echo date("d.m.Y", strtotime($startDate)); ?> - <?php echo date("d.m.Y", strtotime($endDate)); ?>)
                     </h3>
                      <p>
                          <strong>Доходи:</strong>
                          <span style="color: #28a745;">
                              +<?php echo number_format($statisticsData['total_income'], 2, '.', ' '); ?> <?php echo htmlspecialchars($currentAccountCurrency); ?>
                          </span>
                      </p>
                      <p>
                          <strong>Витрати:</strong>
                          <span style="color: #dc3545;">
                              -<?php echo number_format($statisticsData['total_expense'], 2, '.', ' '); ?> <?php echo htmlspecialchars($currentAccountCurrency); ?>
                          </span>
                      </p>
                       <p>
                          <strong>Баланс за період:</strong>
                          <span>
                              <?php echo number_format($statisticsData['balance'], 2, '.', ' '); ?> <?php echo htmlspecialchars($currentAccountCurrency); ?>
                           </span>
                       </p>
                 </div>

                 <div class="statistics-details">
                     <h4>Розбивка по днях:</h4>
                     <?php if (!empty($statisticsData['daily_breakdown'])): ?>
                         <table>
                              <thead>
                                  <tr>
                                      <th>Дата</th>
                                      <th class="income-col">Дохід</th>
                                      <th class="expense-col">Витрата</th>
                                      <th class="balance-col">Баланс за день</th>
                                  </tr>
                              </thead>
                             <tbody>
                                 <?php foreach ($statisticsData['daily_breakdown'] as $date => $data): ?>
                                     <tr>
                                         <td><?php echo date("d.m.Y", strtotime($date)); ?></td>
                                         <td class="income-col" style="color: #28a745;">+<?php echo number_format($data['income'], 2, '.', ' '); ?></td>
                                         <td class="expense-col" style="color: #dc3545;">-<?php echo number_format($data['expense'], 2, '.', ' '); ?></td>
                                         <td class="balance-col"><?php echo number_format($data['income'] - $data['expense'], 2, '.', ' '); ?></td>
                                     </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                     <?php else: ?>
                         <p>Немає транзакцій за обраний період для розбивки по днях.</p>
                     <?php endif; ?>

                     <h4>Розбивка по категоріях (Доходи):</h4>
                     <?php if (!empty($statisticsData['category_breakdown_income'])): ?>
                         <table>
                             <thead>
                                 <tr>
                                     <th>Категорія</th>
                                     <th class="amount-col">Сума</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($statisticsData['category_breakdown_income'] as $category => $amount): ?>
                                     <tr>
                                         <td><?php echo htmlspecialchars($category); ?></td>
                                         <td class="amount-col">
                                             +<?php echo number_format($amount, 2, '.', ' '); ?> <?php echo htmlspecialchars($currentAccountCurrency); ?>
                                         </td>
                                     </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                     <?php else: ?>
                         <p>Немає доходів за обраний період.</p>
                     <?php endif; ?>

                     <h4>Розбивка по категоріях (Витрати):</h4>
                     <?php if (!empty($statisticsData['category_breakdown_expense'])): ?>
                         <table>
                             <thead>
                                 <tr>
                                     <th>Категорія</th>
                                     <th class="amount-col">Сума</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($statisticsData['category_breakdown_expense'] as $category => $amount): ?>
                                     <tr>
                                         <td><?php echo htmlspecialchars($category); ?></td>
                                         <td class="amount-col">
                                             -<?php echo number_format($amount, 2, '.', ' '); ?> <?php echo htmlspecialchars($currentAccountCurrency); ?>
                                         </td>
                                     </tr>
                                 <?php endforeach; ?>
                             </tbody>
                         </table>
                      <?php else: ?>
                         <p>Немає витрат за обраний період.</p>
                     <?php endif; ?>
                 </div> <?php elseif (empty($accounts)): ?>
                 <p class="message error-message">Немає доступних рахунків для перегляду статистики.</p>
            <?php endif; ?>

        <?php else: // Якщо рахунків немає взагалі ?>
             <p class="message error-message">Немає доступних рахунків. Будь ласка, створіть рахунок на сторінці "Загальна інформація".</p>
        <?php endif; ?>

    </div> </div> <script>
    document.addEventListener('DOMContentLoaded', function() {
        const accountSelector = document.getElementById('account_selector_stats');
        const startDateInput = document.getElementById('start_date_stats');
        const endDateInput = document.getElementById('end_date_stats');
        const applyFiltersBtn = document.getElementById('apply_statistics_filters_btn');

        applyFiltersBtn?.addEventListener('click', () => {
            const accountId = accountSelector?.value;
            const startDate = startDateInput?.value;
            const endDate = endDateInput?.value;

            if (accountId) {
                const params = new URLSearchParams();
                params.set('account_id', accountId);
                if (startDate) params.set('start_date', startDate);
                if (endDate) params.set('end_date', endDate);
                window.location.href = '/statistics?' + params.toString();
            } else {
                 alert("Будь ласка, оберіть рахунок.");
            }
        });
    });
</script>