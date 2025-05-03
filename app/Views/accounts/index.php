<?php
/**
 * Вид для сторінки рахунків (головна інформація).
 *
 * Очікує змінні з AccountController::index:
 * - $selectedAccount (array|null): Дані обраного рахунку ['id', 'name', 'currency'].
 * - $accountBalance (float): Поточний баланс.
 * - $accountIncome (float): Загальний дохід.
 * - $accountExpenses (float): Загальні витрати.
 * - $lastTransactions (array): Масив останніх транзакцій.
 * - $contentError (string|null): Повідомлення про помилку (напр., немає рахунків).
 * - $success (string|null): Flash-повідомлення про успіх.
 * - $warning (string|null): Flash-повідомлення з попередженням.
 * (Також доступні змінні для макету: $pageTitle, $userName, $accounts, $selectedAccountId ...)
 */

$selectedAccount = $selectedAccount ?? null;
$accountBalance = $accountBalance ?? 0.0;
$accountIncome = $accountIncome ?? 0.0;
$accountExpenses = $accountExpenses ?? 0.0;
$lastTransactions = $lastTransactions ?? [];
$contentError = $contentError ?? null;
$success = $success ?? $this->session->getFlash('success');
$warning = $warning ?? $this->session->getFlash('warning');

?>
<div class="content-area">
    <nav class="content-tabs">
        <a href="/accounts?account_id=<?php echo $selectedAccountId ?? 0; ?>" class="tab-link active">Загальна інформація</a>
        <a href="/categories?account_id=<?php echo $selectedAccountId ?? 0; ?>&type=expense" class="tab-link">Категорії</a>
        <a href="/statistics?account_id=<?php echo $selectedAccountId ?? 0; ?>" class="tab-link">Статистика</a>
        <a href="/transactions?account_id=<?php echo $selectedAccountId ?? 0; ?>" class="tab-link">Транзакції</a>
    </nav>

    <div class="content-block <?php echo empty($accounts) && !$contentError ? 'no-accounts' : ''; ?>">

         <?php if ($success): ?>
             <p class="message success-message"><?php echo htmlspecialchars($success); ?></p>
         <?php endif; ?>
         <?php if ($warning): ?>
             <p class="message error-message"><?php echo htmlspecialchars($warning); ?></p>
         <?php endif; ?>
          <?php if ($contentError): ?>
             <p class="message error-message"><?php echo htmlspecialchars($contentError); ?></p>
         <?php endif; ?>


         <?php if ($selectedAccount): ?>
            <div class="account-details">
                <h2><?php echo htmlspecialchars($selectedAccount['name']); ?></h2>

                <div class="account-summary">
                    <div class="summary-item">
                        <span class="value">
                            <?php echo number_format($accountBalance, 2, '.', ' '); ?>
                            <?php echo htmlspecialchars($selectedAccount['currency']); ?>
                        </span>
                        <span class="label">Поточний баланс</span>
                        <button type="button" class="btn-icon" id="editBalanceBtn" data-account-id="<?php echo $selectedAccount['id']; ?>" title="Коригувати баланс">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                    </div>
                    <div class="summary-item">
                         <span class="value" style="color: #28a745;">
                            +<?php echo number_format($accountIncome, 2, '.', ' '); ?>
                            <?php echo htmlspecialchars($selectedAccount['currency']); ?>
                         </span>
                        <span class="label">Доходи (за весь час)</span>
                    </div>
                     <div class="summary-item">
                        <span class="value" style="color: #dc3545;">
                            -<?php echo number_format($accountExpenses, 2, '.', ' '); ?>
                            <?php echo htmlspecialchars($selectedAccount['currency']); ?>
                        </span>
                        <span class="label">Витрати (за весь час)</span>
                    </div>
                </div>

                <div class="recent-transactions">
                     <h3>Останні транзакції (макс. 5)</h3>
                     <?php if (!empty($lastTransactions)): ?>
                         <div class="transactions-list">
                             <table>
                                 <thead>
                                     <tr>
                                         <th>Дата</th>
                                         <th>Категорія</th>
                                         <th>Опис</th>
                                         <th class="amount-col">Сума</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php foreach ($lastTransactions as $t): ?>
                                         <tr class="transaction-<?php echo htmlspecialchars($t['category_type']); ?>">
                                             <td><?php echo date("d.m.Y H:i", strtotime($t['date'])); ?></td>
                                             <td><?php echo htmlspecialchars($t['category_name']); ?></td>
                                             <td><?php echo htmlspecialchars($t['trans_desc']); ?></td>
                                             <td class="amount-col">
                                                  <?php
                                                      $amount_display = number_format($t['amount'], 2, '.', ' ');
                                                      $sign = ($t['category_type'] === 'income') ? '+' : '-';
                                                      echo $sign . $amount_display;
                                                      echo ' ' . htmlspecialchars($selectedAccount['currency']);
                                                  ?>
                                             </td>
                                         </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>
                         <div style="margin-top: 15px; text-align: right;">
                             <a href="/transactions?account_id=<?php echo $selectedAccount['id']; ?>" class="btn btn-secondary">Переглянути всі транзакції</a>
                         </div>
                     <?php else: ?>
                         <p>На цьому рахунку ще немає транзакцій.</p>
                     <?php endif; ?>
                </div>

            </div>
        <?php elseif (empty($accounts) && !$contentError): ?>
             <div class="no-accounts-message">
                 <p>У вас ще немає жодного рахунку.</p>
                 <button type="button" class="btn btn-primary btn-lg" id="addFirstAccountBtn">
                     <i class="fas fa-plus"></i> Створити перший рахунок
                 </button>
             </div>
         <?php elseif (!$contentError): ?>
              <p class="message error-message">Не вдалося завантажити дані рахунку.</p>
         <?php endif; ?>
    </div> <?php ?>
</div> <?php  ?>
<?php ?>