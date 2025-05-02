// public_html/js/script.js

// Виконуємо код тільки після повного завантаження DOM
document.addEventListener('DOMContentLoaded', () => {

    // --- Знаходимо основні елементи DOM один раз ---
    const modalBackdrop = document.getElementById('modalBackdrop');
    const accountList = document.getElementById('accountList'); // Сайдбар
    const categorySelect = document.getElementById('category_select'); // Селект категорій на сторінці categories
    const categoryDescriptionDisplay = document.getElementById('category_description_display'); // Опис категорії
    const transactionsListBody = document.querySelector('.transactions-list table tbody'); // Тіло таблиці транзакцій

    // Знаходимо всі модальні вікна
    const modals = {
        addAccount: document.getElementById('addAccountModal'),
        editAccount: document.getElementById('editAccountModal'),
        deleteAccount: document.getElementById('deleteAccountModal'),
        editBalance: document.getElementById('editBalanceModal'),
        addCategory: document.getElementById('addCategoryModal'),
        editCategory: document.getElementById('editCategoryModal'),
        deleteCategory: document.getElementById('deleteCategoryModal'),
        addTransaction: document.getElementById('addTransactionModal'),
        deleteTransaction: document.getElementById('deleteTransactionModal'),
        transfer: document.getElementById('transferModal')
    };

    // Знаходимо основні кнопки відкриття модалок
    const buttons = {
        addAccount: document.getElementById('addAccountBtn'),
        addFirstAccount: document.getElementById('addFirstAccountBtn'),
        editBalance: document.getElementById('editBalanceBtn'),
        addCategory: document.getElementById('addCategoryBtn'),
        editCategory: document.getElementById('editCategoryBtn'),
        deleteCategory: document.getElementById('deleteCategoryBtn'),
        addTransaction: document.getElementById('addTransactionBtn'),
        transfer: document.getElementById('transferBtn')
    };

    // --- Глобальні функції для Модальних Вікон ---

    // Відкриття модалки
    window.openModal = function(modalElement) {
        if (!modalElement || !modalBackdrop) return;
        clearModalErrors(modalElement); // Очищуємо помилки перед відкриттям
        modalBackdrop.classList.add('active');
        modalElement.classList.add('active');
        document.body.classList.add('modal-open');
        setTimeout(() => {
            const focusable = modalElement.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]):not(.btn-close-modal):not(.btn-cancel-modal)');
            focusable?.focus();
        }, 50); // Менша затримка
    }

    // Закриття модалки
    window.closeModal = function(modalElement) {
        if (modalElement && modalBackdrop && modalElement.classList.contains('active')) {
            modalBackdrop.classList.remove('active');
            modalElement.classList.remove('active');
            document.body.classList.remove('modal-open');
            clearModalErrors(modalElement); // Очищуємо помилки при закритті
        }
    }

    // Очищення помилок в модалці
    window.clearModalErrors = function(modalElement) {
        if (!modalElement) return;
        modalElement.querySelectorAll('.message.error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        modalElement.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    // Загальні обробники закриття (клік на фон, Escape, кнопки "Х" та "Скасувати")
    modalBackdrop?.addEventListener('click', () => document.querySelectorAll('.modal.active').forEach(closeModal));
    document.addEventListener('keydown', (e) => e.key === 'Escape' && document.querySelectorAll('.modal.active').forEach(closeModal));
    document.querySelectorAll('.modal').forEach(modal => {
        modal?.addEventListener('click', (e) => e.stopPropagation()); // Зупинка спливання
        modal.querySelector('.btn-close-modal')?.addEventListener('click', () => closeModal(modal));
        modal.querySelector('.btn-cancel-modal')?.addEventListener('click', (e) => {
             e.preventDefault(); // Запобігти відправці форми, якщо це кнопка в формі
             closeModal(modal);
        });
    });

    // --- Обробники відкриття конкретних Модалок ---

    // 1. Додати Рахунок
    const openAddAccountModal = () => {
        if (modals.addAccount) {
            modals.addAccount.querySelector('form')?.reset();
            openModal(modals.addAccount);
        }
    };
    buttons.addAccount?.addEventListener('click', openAddAccountModal);
    buttons.addFirstAccount?.addEventListener('click', openAddAccountModal); // Кнопка, якщо рахунків 0

    // 2. Редагувати / Видалити Рахунок (Делегування)
    accountList?.addEventListener('click', (event) => {
        const editButton = event.target.closest('.edit-account-btn');
        const deleteButton = event.target.closest('.delete-account-btn');

        if (editButton && modals.editAccount) {
            event.preventDefault();
            modals.editAccount.querySelector('form')?.reset();
            modals.editAccount.querySelector('#editAccountId').value = editButton.dataset.accountId || '';
            modals.editAccount.querySelector('#edit_account_name').value = editButton.dataset.accountName || '';
            modals.editAccount.querySelector('#edit_account_currency').value = editButton.dataset.accountCurrency || '';
            openModal(modals.editAccount);
        }
        if (deleteButton && modals.deleteAccount) {
            event.preventDefault();
             modals.deleteAccount.querySelector('form')?.reset();
            modals.deleteAccount.querySelector('#deleteAccountId').value = deleteButton.dataset.accountId || '';
            const nameSpan = modals.deleteAccount.querySelector('#deleteAccountName');
            if(nameSpan) nameSpan.textContent = deleteButton.dataset.accountName || 'цей рахунок';
            openModal(modals.deleteAccount);
        }
    });

    // 3. Коригувати Баланс
    buttons.editBalance?.addEventListener('click', () => {
        if (modals.editBalance && buttons.editBalance.dataset.accountId) {
             modals.editBalance.querySelector('form')?.reset();
             modals.editBalance.querySelector('#editBalanceAccountId').value = buttons.editBalance.dataset.accountId;
             openModal(modals.editBalance);
        }
    });

    // 4. Додати Категорію
    buttons.addCategory?.addEventListener('click', () => {
        if (modals.addCategory) {
             modals.addCategory.querySelector('form')?.reset();
            const urlParams = new URLSearchParams(window.location.search);
            const currentType = urlParams.get('type') || 'expense';
            const typeSelect = modals.addCategory.querySelector('#add_category_type');
            if (typeSelect) typeSelect.value = currentType;
            openModal(modals.addCategory);
        }
    });

    // 5. Редагувати / Видалити Категорію
    buttons.editCategory?.addEventListener('click', () => {
        if (!categorySelect || categorySelect.value === "") { alert("Будь ласка, оберіть категорію для редагування."); return; }
        if (modals.editCategory) {
             modals.editCategory.querySelector('form')?.reset();
            const selectedOption = categorySelect.selectedOptions[0];
            modals.editCategory.querySelector('#editCategoryId').value = selectedOption.value;
            modals.editCategory.querySelector('#edit_category_name').value = selectedOption.dataset.name || '';
            modals.editCategory.querySelector('#edit_category_type').value = selectedOption.dataset.type || '';
            modals.editCategory.querySelector('#edit_category_description').value = selectedOption.dataset.description || '';
            openModal(modals.editCategory);
        }
    });
    buttons.deleteCategory?.addEventListener('click', () => {
        if (!categorySelect || categorySelect.value === "") { alert("Будь ласка, оберіть категорію для видалення."); return; }
         if (modals.deleteCategory) {
             modals.deleteCategory.querySelector('form')?.reset();
             const selectedOption = categorySelect.selectedOptions[0];
             modals.deleteCategory.querySelector('#deleteCategoryId').value = selectedOption.value;
             const nameSpan = modals.deleteCategory.querySelector('#deleteCategoryName');
             if(nameSpan) nameSpan.textContent = selectedOption.dataset.name || 'цю категорію';
             openModal(modals.deleteCategory);
         }
    });


    // 6. Додати Транзакцію
    const addTransactionModal = modals.addTransaction; // Зберігаємо для доступу
    const transactionTypeRadios = addTransactionModal?.querySelectorAll('input[name="transaction_type"]');
    const transactionCategorySelect = addTransactionModal?.querySelector('#transaction_category');

    // Функція заповнення категорій тепер використовує глобальну allCategoriesModal
    window.populateCategoriesForModal = function(selectedType = 'expense') {
        // Перевіряємо наявність allCategoriesModal (яка створюється в main.php)
        if (typeof allCategoriesModal === 'undefined' || !transactionCategorySelect) return;
        transactionCategorySelect.innerHTML = '<option value="">-- Оберіть категорію --</option>';
        const categoriesToShow = allCategoriesModal[selectedType] || [];
        categoriesToShow.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            transactionCategorySelect.appendChild(option);
        });
    }

    buttons.addTransaction?.addEventListener('click', () => {
        if (addTransactionModal) {
             addTransactionModal.querySelector('form')?.reset();
             const defaultType = 'expense';
             const defaultRadio = addTransactionModal.querySelector(`input[name="transaction_type"][value="${defaultType}"]`);
             if (defaultRadio) defaultRadio.checked = true;
             populateCategoriesForModal(defaultType);

             const dateTimeInput = addTransactionModal.querySelector('#transaction_datetime');
             if (dateTimeInput) {
                 const now = new Date();
                 const timezoneOffset = now.getTimezoneOffset() * 60000;
                 const localISOTime = (new Date(now.getTime() - timezoneOffset)).toISOString().slice(0, 16);
                 dateTimeInput.value = localISOTime;
             }
            // ID поточного рахунку
            const accountSelector = document.getElementById('account_selector');
            const currentAccountId = accountSelector?.value;
            const hiddenAccIdInput = addTransactionModal.querySelector('input[name="account_id_hidden"]');
             if (hiddenAccIdInput) hiddenAccIdInput.value = currentAccountId || '0';

             openModal(addTransactionModal);
        }
    });
    // Обробник зміни типу транзакції
    transactionTypeRadios?.forEach(radio => {
        radio.addEventListener('change', function() { if (this.checked) populateCategoriesForModal(this.value); });
    });


    // 7. Видалити Транзакцію (Делегування)
    transactionsListBody?.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('.delete-transaction-btn');
        if (deleteButton && modals.deleteTransaction) {
             event.preventDefault();
             modals.deleteTransaction.querySelector('form')?.reset();
             const transactionId = deleteButton.dataset.transactionId;
             const accountSelector = document.getElementById('account_selector');
             const currentAccountId = accountSelector?.value;

             modals.deleteTransaction.querySelector('#deleteTransactionId').value = transactionId || '';
             const hiddenAccIdInput = modals.deleteTransaction.querySelector('#deleteTransactionAccountId');
             if(hiddenAccIdInput) hiddenAccIdInput.value = currentAccountId || '0';
             openModal(modals.deleteTransaction);
        }
    });


    // 8. Переказ між рахунками
    const transferModal = modals.transfer; // Зберігаємо для доступу
    const fromAccountSelect = transferModal?.querySelector('#from_account_id');
    const toAccountSelect = transferModal?.querySelector('#to_account_id');
    const currencyDifferenceDetails = transferModal?.querySelector('#currencyDifferenceDetails');
    const exchangeRateInput = transferModal?.querySelector('#exchange_rate');
    const exchangeRateHint = transferModal?.querySelector('#exchangeRateHint');
    const exchangeRateHintFrom = transferModal?.querySelector('#exchangeRateHintCurrencyFrom');
    const exchangeRateHintTo = transferModal?.querySelector('#exchangeRateHintCurrencyTo');
    const transferAmountCurrencySpan = transferModal?.querySelector('#transferAmountCurrency');

    // Функція оновлення модалки переказу
    function updateTransferModalFields() {
        if (!fromAccountSelect || !toAccountSelect || !currencyDifferenceDetails || !exchangeRateInput || !exchangeRateHint || !exchangeRateHintFrom || !exchangeRateHintTo || !transferAmountCurrencySpan) return;
        const fromAccountId = fromAccountSelect.value;
        const selectedFromOption = fromAccountSelect.options[fromAccountSelect.selectedIndex];
        const fromCurrency = selectedFromOption?.dataset.currency || '';
        const toAccountId = toAccountSelect.value;
        const selectedToOption = toAccountSelect.options[toAccountSelect.selectedIndex];
        const toCurrency = selectedToOption?.dataset.currency || '';

        transferAmountCurrencySpan.textContent = fromCurrency || 'валюта';

        if (fromAccountId && toAccountId && fromCurrency && toCurrency && fromCurrency !== toCurrency) {
            currencyDifferenceDetails.classList.remove('hidden');
            exchangeRateInput.setAttribute('required', 'required');
            exchangeRateHintFrom.textContent = fromCurrency;
            exchangeRateHintTo.textContent = toCurrency;
            exchangeRateHint.textContent = `1 ${fromCurrency} = X ${toCurrency}`;
        } else {
            currencyDifferenceDetails.classList.add('hidden');
            exchangeRateInput.removeAttribute('required');
            if(fromCurrency === toCurrency || !fromAccountId || !toAccountId) {
                 exchangeRateInput.value = '1'; // Скидаємо на 1 тільки якщо валюти однакові або щось не обрано
            }
            exchangeRateHintFrom.textContent = '[З]';
            exchangeRateHintTo.textContent = '[На]';
            exchangeRateHint.textContent = '';
        }

        toAccountSelect.querySelectorAll('option').forEach(option => {
             option.disabled = (option.value !== "" && option.value === fromAccountId);
             option.style.display = (option.value !== "" && option.value === fromAccountId) ? 'none' : '';
        });

        if (toAccountSelect.value === fromAccountId && fromAccountId !== "") {
            toAccountSelect.value = "";
            updateTransferModalFields(); // Оновлюємо стан, якщо скинули toAccount
        }
    }

    // Функція заповнення списків у модалці переказу
    function populateTransferAccountSelects(currentAccountId = null) {
        if (!fromAccountSelect || !toAccountSelect || typeof allAccountsData === 'undefined') return;
        fromAccountSelect.innerHTML = '<option value="">-- Оберіть рахунок --</option>';
        toAccountSelect.innerHTML = '<option value="">-- Оберіть рахунок --</option>';

        // Перевіряємо, чи allAccountsData є масивом
         if (!Array.isArray(allAccountsData)) {
              console.error("allAccountsData is not an array:", allAccountsData);
              return; // Виходимо, якщо дані некоректні
         }


        allAccountsData.forEach(account => {
            const fromOption = document.createElement('option');
            fromOption.value = account.id;
            fromOption.textContent = `${account.name} (${account.currency})`;
            fromOption.dataset.currency = account.currency;
            fromAccountSelect.appendChild(fromOption);
            const toOption = fromOption.cloneNode(true); // Клонуємо опцію
            toAccountSelect.appendChild(toOption);
        });

        if (currentAccountId && fromAccountSelect.querySelector(`option[value="${currentAccountId}"]`)) {
            fromAccountSelect.value = currentAccountId;
        }
        updateTransferModalFields();
    }

    // Відкриття модалки переказу
    buttons.transfer?.addEventListener('click', () => {
        // !!! ОНОВЛЕНО: Додано перевірку allAccountsData перед відкриттям !!!
        if (transferModal) {
            transferModal.querySelector('form')?.reset();
            clearModalErrors(transferModal); // Очищуємо помилки тут

            // Перевірка наявності даних перед заповненням та відкриттям
            if (typeof allAccountsData === 'undefined' || !Array.isArray(allAccountsData)) {
                console.error("Cannot open transfer modal: Global variable 'allAccountsData' is missing or not an array.");
                alert("Помилка: Не вдалося завантажити список рахунків для переказу.");
                return; // Не відкриваємо модалку
            }

            const accountSelector = document.getElementById('account_selector');
            const currentAccountId = accountSelector?.value;
            populateTransferAccountSelects(currentAccountId); // Заповнюємо списки

            const hiddenViewAccId = transferModal.querySelector('input[name="current_view_account_id"]');
            if(hiddenViewAccId) hiddenViewAccId.value = currentAccountId || '0';

            openModal(transferModal);
        } else {
            console.error("Transfer modal element not found!");
        }
    });


    // Обробники зміни рахунків у модалці переказу
    fromAccountSelect?.addEventListener('change', updateTransferModalFields);
    toAccountSelect?.addEventListener('change', updateTransferModalFields);


    // --- Форматування полів вводу сум ---
    function formatAmountInput(event) {
        let value = event.target.value;
        value = value.replace(',', '.');
         // Дозволяємо тільки цифри, крапку та мінус на початку для коригування
         if (event.target.id === 'adjustment_amount') {
             value = value.replace(/[^\d.-]/g, ''); // Дозволяємо мінус
             value = value.replace(/(?!^)-/g, ''); // Забороняємо мінус не на початку
              if (value.indexOf('-') > 0) value = value.replace('-', ''); // Видаляємо мінус не на початку
         } else {
             value = value.replace(/[^\d.]/g, ''); // Тільки цифри та крапка
         }
        const parts = value.split('.');
        if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
        event.target.value = value;
    }
    // Прив'язуємо обробник до всіх полів сум
    const amountInputs = [
         modals.addAccount?.querySelector('#initial_balance'),
         modals.editBalance?.querySelector('#adjustment_amount'),
         modals.addTransaction?.querySelector('#transaction_amount'),
         modals.transfer?.querySelector('#transfer_amount'),
         modals.transfer?.querySelector('#exchange_rate')
    ];
    amountInputs.forEach(input => input?.addEventListener('input', formatAmountInput));


    // --- Обробка помилок PHP після завантаження сторінки ---
    const errorInfoOnLoad = document.getElementById('phpPageLoadError');
    if (errorInfoOnLoad) {
        const targetModalId = errorInfoOnLoad.dataset.targetModal;
        const errorMessage = errorInfoOnLoad.dataset.errorMessage;
        if (targetModalId && errorMessage) {
            const targetModal = document.getElementById(targetModalId);
            if (targetModal) {
                const errorElement = targetModal.querySelector('.message.error-message');
                if (errorElement) {
                    errorElement.textContent = errorMessage;
                    errorElement.style.display = 'block';
                    openModal(targetModal); // Відкриваємо модалку з помилкою

                    // Додаткова логіка відновлення стану для деяких модалок
                    if (targetModalId === 'addTransactionModal' && typeof populateCategoriesForModal === 'function') {
                        const typeInput = targetModal.querySelector('input[name="transaction_type"]:checked');
                        populateCategoriesForModal(typeInput ? typeInput.value : 'expense');
                    }
                    if (targetModalId === 'transferModal' && typeof populateTransferAccountSelects === 'function') {
                        const accountSelector = document.getElementById('account_selector');
                        populateTransferAccountSelects(accountSelector?.value);
                    }
                }
            }
        }
    }

    // --- Оновлення URL при зміні категорії (на сторінці categories) ---
     categorySelect?.addEventListener('change', function() {
         const selectedId = this.value;
         if (selectedId && window.location.pathname.includes('/categories')) { // Перевірка, що ми на сторінці категорій
             const currentUrl = new URL(window.location.href);
             currentUrl.searchParams.set('selected_id', selectedId);
             window.history.replaceState({}, '', currentUrl.toString()); // Оновлення без перезавантаження
             updateCategoryDescription(); // Оновлюємо опис
         }
     });
     // Функція оновлення опису категорії
      window.updateCategoryDescription = function() {
          if (categorySelect && categoryDescriptionDisplay) {
              const selectedOption = categorySelect.options[categorySelect.selectedIndex];
              if (selectedOption && selectedOption.value !== "") {
                  const description = selectedOption.dataset.description ? selectedOption.dataset.description.replace(/\n/g, '<br>') : 'Опис для цієї категорії відсутній.';
                  categoryDescriptionDisplay.innerHTML = description;
                  categoryDescriptionDisplay.style.display = 'block';
              } else {
                  categoryDescriptionDisplay.innerHTML = 'Опис для цієї категорії відсутній.';
                   // Не приховуємо, просто показуємо стандартний текст
                   categoryDescriptionDisplay.style.display = 'block';
              }
          }
      }
      // Викликаємо для початкового відображення опису на сторінці категорій
      if (window.location.pathname.includes('/categories')) {
           updateCategoryDescription();
      }


    // ВИДАЛЕНИЙ РЯДОК: transactionAmountInput

}); // Кінець DOMContentLoaded