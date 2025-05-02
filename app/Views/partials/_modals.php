<?php
/**
 * Partial view для всіх модальних вікон додатку.
 *
 * Очікує змінні з файлу, що його підключає (main.php):
 * - $allowedCurrencies (array)
 * - $all_user_accounts (array|null): Масив рахунків для PHP-заповнення списків
 * - $currentAccountIdForTabs (int|null): ID поточного рахунку для форм
 * - (та інші змінні, якщо вони потрібні для модалок)
 */

$allowedCurrencies = $allowedCurrencies ?? ['UAH', 'USD', 'EUR'];
$all_user_accounts = $all_user_accounts ?? [];
$currentAccountIdForTabs = $currentAccountIdForTabs ?? 0;

// Функція для виведення опцій валют
function render_currency_options(array $currencies): void {
    foreach ($currencies as $currency) {
        echo '<option value="' . htmlspecialchars($currency) . '">' . htmlspecialchars($currency) . '</option>';
    }
}

// Функція для виведення опцій рахунків
function render_account_options(array $accounts): void {
    echo '<option value="">-- Оберіть рахунок --</option>';
    foreach ($accounts as $account) {
        echo '<option value="' . htmlspecialchars((string)$account['id']) . '" data-currency="' . htmlspecialchars($account['currency']) . '">'
           . htmlspecialchars($account['name']) . ' (' . htmlspecialchars($account['currency']) . ')</option>';
    }
}

?>

<div class="modal-backdrop" id="modalBackdrop"></div>

<div class="modal" id="addAccountModal">
    <div class="modal-header">
        <h2>Додати новий рахунок</h2>
        <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
    </div>
    <div class="modal-body">
        <form action="/accounts/add" method="POST" id="addAccountForm">
            <div class="form-group">
                <label for="add_account_name">Назва рахунку:</label>
                <input type="text" id="add_account_name" name="account_name" required>
            </div>
            <div class="form-group">
                <label for="add_account_currency">Валюта:</label>
                <select id="add_account_currency" name="account_currency" required>
                    <?php render_currency_options($allowedCurrencies); ?>
                </select>
            </div>
            <div class="form-group">
                <label for="initial_balance">Початковий баланс (необов'язково):</label>
                <input type="text" id="initial_balance" name="initial_balance" inputmode="decimal" placeholder="0.00">
            </div>
            <div id="addAccountModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
                <button type="submit" class="btn btn-primary">Зберегти</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="editAccountModal">
    <div class="modal-header">
       <h2>Редагувати рахунок</h2>
       <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
   </div>
   <div class="modal-body">
       <form action="/accounts/edit" method="POST" id="editAccountForm">
            <input type="hidden" id="editAccountId" name="account_id" value="">
            <div class="form-group">
                <label for="edit_account_name">Нова назва рахунку:</label>
                <input type="text" id="edit_account_name" name="account_name" required>
            </div>
            <div class="form-group">
                <label for="edit_account_currency">Нова валюта:</label>
                <select id="edit_account_currency" name="account_currency" required>
                    <?php render_currency_options($allowedCurrencies); ?>
                </select>
            </div>
             <div id="editAccountModalError" class="message error-message" style="display: none;"></div>
             <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
                <button type="submit" class="btn btn-primary">Зберегти зміни</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="deleteAccountModal">
    <div class="modal-header">
       <h2>Підтвердження видалення</h2>
       <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
   </div>
   <div class="modal-body">
       <form action="/accounts/delete" method="POST" id="deleteAccountForm">
           <input type="hidden" id="deleteAccountId" name="account_id" value="">
           <p>Ви впевнені, що хочете видалити рахунок <strong id="deleteAccountName"></strong>? <br>
              УВАГА: Всі транзакції, пов'язані з цим рахунком, також будуть видалені! <br>
              Ця дія незворотна!</p>
            <div id="deleteAccountModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
               <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
               <button type="submit" class="btn btn-danger">Видалити</button>
           </div>
       </form>
   </div>
</div>

<div class="modal" id="editBalanceModal">
    <div class="modal-header">
       <h2>Редагувати баланс</h2>
       <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
   </div>
   <div class="modal-body">
       <form action="/accounts/adjust_balance" method="POST" id="editBalanceForm">
           <input type="hidden" id="editBalanceAccountId" name="account_id" value="">
           <div class="form-group">
               <label for="adjustment_amount">Сума коригування:</label>
               <input type="text" id="adjustment_amount" name="adjustment_amount" inputmode="decimal" placeholder="±0.00" required>
                <small>Введіть позитивне число, щоб збільшити баланс, або від'ємне (наприклад, -50.00), щоб зменшити.</small>
           </div>
            <div class="form-group">
               <label for="adjustment_description">Примітка (необов'язково):</label>
               <textarea id="adjustment_description" name="adjustment_description" rows="3"></textarea>
           </div>
            <div id="editBalanceModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
               <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
               <button type="submit" class="btn btn-primary">Застосувати</button>
           </div>
       </form>
   </div>
</div>

<div class="modal" id="addCategoryModal">
    <div class="modal-header">
        <h2>Додати нову категорію</h2>
        <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
    </div>
    <div class="modal-body">
        <form action="/categories/add" method="POST" id="addCategoryForm">
            <div class="form-group">
                <label for="add_category_name">Назва категорії:</label>
                <input type="text" id="add_category_name" name="category_name" required>
            </div>
            <div class="form-group">
                <label for="add_category_type">Тип категорії:</label>
                <select id="add_category_type" name="category_type" required>
                    <option value="expense">Витрати</option>
                    <option value="income">Доходи</option>
                </select>
            </div>
             <div class="form-group">
                <label for="add_category_description">Опис (необов'язково):</label>
                <textarea id="add_category_description" name="category_description" rows="3"></textarea>
            </div>
            <div id="addCategoryModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
                <button type="submit" class="btn btn-primary">Зберегти</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="editCategoryModal">
    <div class="modal-header">
       <h2>Редагувати категорію</h2>
       <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
   </div>
   <div class="modal-body">
       <form action="/categories/edit" method="POST" id="editCategoryForm">
           <input type="hidden" id="editCategoryId" name="category_id" value="">
           <div class="form-group">
               <label for="edit_category_name">Назва категорії:</label>
               <input type="text" id="edit_category_name" name="category_name" required>
           </div>
            <div class="form-group">
               <label for="edit_category_type">Тип категорії:</label>
               <select id="edit_category_type" name="category_type" required>
                   <option value="expense">Витрати</option>
                   <option value="income">Доходи</option>
               </select>
           </div>
            <div class="form-group">
               <label for="edit_category_description">Опис (необов'язково):</label>
               <textarea id="edit_category_description" name="category_description" rows="3"></textarea>
           </div>
            <div id="editCategoryModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
               <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
               <button type="submit" class="btn btn-primary">Зберегти зміни</button>
           </div>
       </form>
   </div>
</div>

<div class="modal" id="deleteCategoryModal">
    <div class="modal-header">
       <h2>Підтвердження видалення</h2>
       <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
   </div>
   <div class="modal-body">
        <form action="/categories/delete" method="POST" id="deleteCategoryForm">
           <input type="hidden" id="deleteCategoryId" name="category_id" value="">
           <p>Ви впевнені, що хочете видалити категорію <strong id="deleteCategoryName"></strong>? <br>
              УВАГА: Ви не зможете видалити категорію, якщо вона використовується в транзакціях! <br>
              Ця дія незворотна!</p>
            <div id="deleteCategoryModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
               <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
               <button type="submit" class="btn btn-danger">Видалити</button>
           </div>
       </form>
   </div>
</div>

<div class="modal" id="addTransactionModal">
    <div class="modal-header">
        <h2>Додати транзакцію</h2>
        <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
    </div>
    <div class="modal-body">
        <form action="/transactions/add" method="POST" id="addTransactionForm">
            <input type="hidden" name="account_id_hidden" value="<?php echo $currentAccountIdForTabs; // Початкове значення ?>">
            <div class="form-group transaction-type-group">
                <label>Тип:</label>
                <div>
                    <input type="radio" id="trans_type_expense" name="transaction_type" value="expense" checked>
                    <label for="trans_type_expense">Витрата</label>
                </div>
                <div>
                    <input type="radio" id="trans_type_income" name="transaction_type" value="income">
                    <label for="trans_type_income">Дохід</label>
                </div>
            </div>
            <div class="form-group">
                <label for="transaction_category">Категорія:</label>
                <select id="transaction_category" name="transaction_category" required>
                    <option value="">-- Спочатку оберіть тип --</option>
                </select>
            </div>
            <div class="form-group">
                <label for="transaction_amount">Сума:</label>
                <input type="text" id="transaction_amount" name="transaction_amount" inputmode="decimal" placeholder="0.00" required>
            </div>
            <div class="form-group">
                 <label for="transaction_datetime">Дата та час:</label>
                 <input type="datetime-local" id="transaction_datetime" name="transaction_datetime" required>
            </div>
             <div class="form-group">
                <label for="transaction_description">Опис (необов'язково):</label>
                <textarea id="transaction_description" name="transaction_description" rows="3"></textarea>
            </div>
            <div id="addTransactionModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
                <button type="submit" class="btn btn-primary">Додати</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="deleteTransactionModal">
    <div class="modal-header">
       <h2>Підтвердження видалення</h2>
       <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
   </div>
   <div class="modal-body">
       <form action="/transactions/delete" method="POST" id="deleteTransactionForm">
           <input type="hidden" id="deleteTransactionId" name="transaction_id" value="">
           <input type="hidden" id="deleteTransactionAccountId" name="account_id" value="<?php echo $currentAccountIdForTabs; ?>">
           <p>Ви впевнені, що хочете видалити транзакцію? <br> Ця дія незворотна!</p>
            <div id="deleteTransactionModalError" class="message error-message" style="display: none;"></div>
            <div class="form-actions">
               <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
               <button type="submit" class="btn btn-danger">Видалити</button>
           </div>
       </form>
   </div>
</div>

<div class="modal" id="transferModal">
    <div class="modal-header">
        <h2>Переказ між рахунками</h2>
        <button type="button" class="btn-close-modal" aria-label="Закрити">&times;</button>
    </div>
    <div class="modal-body">
        <form action="/transactions/transfer" method="POST" id="transferForm">
            <input type="hidden" name="current_view_account_id" value="<?php echo $currentAccountIdForTabs; ?>">

            <div class="form-group">
                <label for="from_account_id">З рахунку:</label>
                <select id="from_account_id" name="from_account_id" required>
                    <?php render_account_options($all_user_accounts); ?>
                </select>
            </div>

            <div class="form-group">
                <label for="to_account_id">На рахунок:</label>
                <select id="to_account_id" name="to_account_id" required>
                    <?php render_account_options($all_user_accounts); ?>
                </select>
            </div>

            <div class="form-group">
                <label for="transfer_amount">Сума переказу (<span id="transferAmountCurrency">валюта</span>):</label>
                <input type="text" id="transfer_amount" name="amount" inputmode="decimal" placeholder="0.00" required>
            </div>

            <div id="currencyDifferenceDetails" class="transfer-currency-details hidden">
                <div class="form-group">
                    <label for="exchange_rate">Курс обміну (1 <span id="exchangeRateHintCurrencyFrom">[З]</span> = X <span id="exchangeRateHintCurrencyTo">[На]</span>):</label>
                    <input type="text" id="exchange_rate" name="exchange_rate" inputmode="decimal" placeholder="1.00">
                    <small id="exchangeRateHint"></small>
                 </div>
                <div class="form-group">
                    <label for="transfer_description">Примітка (необов'язково):</label>
                    <textarea id="transfer_description" name="description" rows="3"></textarea>
                </div>
            </div>
             <div id="transferModalError" class="message error-message" style="display: none;"></div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-cancel-modal">Скасувати</button>
                <button type="submit" class="btn btn-primary">Виконати переказ</button>
            </div>
        </form>
    </div>
</div>