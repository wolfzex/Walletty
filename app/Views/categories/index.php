<?php
/**
 * Вид для сторінки управління категоріями.
 *
 * Очікує змінні з CategoryController::index:
 * - $categories (array): Категорії поточного типу.
 * - $selectedCategory (array|null): Дані обраної категорії.
 * - $selectedCategoryId (int|null): ID обраної категорії.
 * - $categoryTypeFilter (string): Поточний фільтр типу ('income'/'expense').
 * - $success (string|null): Flash-повідомлення про успіх.
 * - $warning (string|null): Flash-повідомлення з попередженням.
 * - $phpPageLoadError (array|null): Помилка для модалки.
 * (Також доступні змінні для макету: $pageTitle, $userName, $accounts, $selectedAccountId...)
 */

$categories = $categories ?? [];
$selectedCategory = $selectedCategory ?? null;
$selectedCategoryId = $selectedCategoryId ?? null;
$categoryTypeFilter = $categoryTypeFilter ?? 'expense';
$success = $success ?? $this->session->getFlash('success');
$warning = $warning ?? $this->session->getFlash('warning');
$phpPageLoadError = $phpPageLoadError ?? $this->session->getFlash('form_error'); // Можемо отримати з сесії

// Визначаємо account_id для посилань у табах та кнопках фільтра
$currentAccountIdForTabs = $selectedAccountId ?? ($accounts[0]['id'] ?? 0);

?>
<div class="content-area">
    <nav class="content-tabs">
         <a href="/accounts?account_id=<?php echo $currentAccountIdForTabs; ?>" class="tab-link">Загальна інформація</a>
        <a href="/categories?account_id=<?php echo $currentAccountIdForTabs; ?>&type=<?php echo $categoryTypeFilter; ?><?php echo $selectedCategoryId ? '&selected_id='.$selectedCategoryId : ''; ?>" class="tab-link active">Категорії</a>
        <a href="/statistics?account_id=<?php echo $currentAccountIdForTabs; ?>" class="tab-link">Статистика</a>
        <a href="/transactions?account_id=<?php echo $currentAccountIdForTabs; ?>" class="tab-link">Транзакції</a>
    </nav>

    <div class="content-block category-management">
        <h2>Керування категоріями</h2>

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

        <?php // !!! ЗМІНЕНО ПОСИЛАННЯ ТУТ !!! ?>
        <div class="category-filter">
            <a href="/categories?account_id=<?php echo $currentAccountIdForTabs; ?>&type=income"  <?php // <-- ВИДАЛЕНО selected_id ?>
               class="btn-category-type <?php echo ($categoryTypeFilter === 'income') ? 'active' : ''; ?>">
               Доходи
            </a>
            <span class="type-separator">/</span>
            <a href="/categories?account_id=<?php echo $currentAccountIdForTabs; ?>&type=expense" <?php // <-- ВИДАЛЕНО selected_id ?>
               class="btn-category-type <?php echo ($categoryTypeFilter === 'expense') ? 'active' : ''; ?>">
               Витрати
            </a>
        </div>
        <?php // !!! КІНЕЦЬ ЗМІН У ПОСИЛАННЯХ !!! ?>

        <?php if (!empty($categories)): // Якщо є категорії для поточного типу ?>
            <div class="category-selector-area">
                <select name="category_select" id="category_select">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"
                                data-description="<?php echo htmlspecialchars($cat['description'] ?? ''); ?>"
                                data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                data-type="<?php echo htmlspecialchars($cat['type']); ?>"
                                <?php echo ($cat['id'] == $selectedCategoryId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="category-actions">
                    <button type="button" class="btn-icon" id="editCategoryBtn" title="Редагувати категорію">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn-icon" id="deleteCategoryBtn" title="Видалити категорію">
                         <i class="fas fa-trash"></i>
                    </button>
                     <button type="button" class="btn-text" id="addCategoryBtn">
                        <i class="fas fa-plus"></i> Додати категорію
                    </button>
                </div>
            </div>

            <div class="category-description-display" id="category_description_display">
                <?php echo nl2br(htmlspecialchars($selectedCategory['description'] ?? 'Опис для цієї категорії відсутній.')); ?>
            </div>

        <?php else: // Якщо немає категорій для поточного типу ?>
             <div class="category-selector-area">
                <p>Немає створених категорій типу '<?php echo ($categoryTypeFilter === 'income' ? 'Доходи' : 'Витрати'); ?>'.</p>
                 <button type="button" class="btn-text" id="addCategoryBtn">
                    <i class="fas fa-plus"></i> Додати першу категорію
                </button>
            </div>
            <div class="category-description-display" id="category_description_display" style="display: none;"></div>
        <?php endif; ?>

    </div> <?php // Кінець category-management ?>
</div> <?php // Кінець content-area ?>

<script>
    // Оновлення URL при зміні категорії у випадаючому списку (для збереження стану)
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_select');
        const descriptionDisplay = document.getElementById('category_description_display'); // Визначити тут

        // Функція для оновлення опису
         function updateCategoryDescription() {
             if (categorySelect && descriptionDisplay) {
                 const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                 if (selectedOption && selectedOption.value !== "") {
                     // Використовуємо innerText для безпечного виведення тексту, а потім замінюємо переноси рядків
                     let descText = selectedOption.dataset.description || 'Опис для цієї категорії відсутній.';
                     descriptionDisplay.innerHTML = descText.replace(/\n/g, '<br>'); // Заміна \n на <br> для HTML
                     descriptionDisplay.style.display = 'block';
                 } else {
                     // Якщо категорії немає (наприклад, після видалення останньої), ховаємо блок опису
                     if (categorySelect.options.length === 0) {
                         descriptionDisplay.style.display = 'none';
                         descriptionDisplay.innerHTML = '';
                     } else {
                         descriptionDisplay.innerHTML = 'Опис для цієї категорії відсутній.';
                         descriptionDisplay.style.display = 'block'; // Показуємо навіть якщо немає опису
                     }
                 }
             } else if (descriptionDisplay) {
                 // Якщо немає селекту (напр., немає категорій), ховаємо опис
                 descriptionDisplay.style.display = 'none';
                 descriptionDisplay.innerHTML = '';
             }
         }

        categorySelect?.addEventListener('change', function() {
            const selectedId = this.value;
            if (selectedId) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('selected_id', selectedId);
                // Використовуємо history.replaceState, щоб не додавати новий запис в історію браузера
                window.history.replaceState({}, '', currentUrl.toString());

                // Оновлюємо опис
                updateCategoryDescription();
            }
        });

         // Викликаємо функцію для початкового завантаження опису
         updateCategoryDescription();

    });
</script>