<?php
/**
 * Liter Products Module
 * 
 * Функционал для товаров с выбором объёма тары (ЛКМ: краски, масла, лаки).
 * Особенности:
 * - Выбор объёма тары (литры) на странице товара
 * - Автоматический пересчёт цены = цена_за_литр × объём
 * - Скидка -10% при объёме >= 9 литров
 * - Брендо-специфичные настройки объёмов
 * - Динамическое обновление цены через JavaScript
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ОПРЕДЕЛЕНИЕ КАТЕГОРИЙ И БРЕНДОВ
// ============================================================================

/**
 * Проверка, является ли товар продуктом за литр (ЛКМ)
 * Использует категории 81-86
 * 
 * @param int $product_id ID товара
 * @return bool
 */
function parusweb_is_liter_product($product_id) {
    // Используем функцию из core-category-helpers
    if (function_exists('is_in_liter_categories')) {
        return is_in_liter_categories($product_id);
    }
    
    // Fallback если модуль category-helpers недоступен
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    
    if (is_wp_error($product_categories) || empty($product_categories)) {
        return false;
    }
    
    $target_categories = range(81, 86);
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) {
            return true;
        }
        
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Карта доступных объёмов тары по брендам
 * 
 * @return array Массив [brand_slug => [volumes]]
 */
function parusweb_get_tara_map() {
    return array(
        'remmers'     => [0.75, 2.5, 5, 10, 20],
        'biofa'       => [0.375, 0.75, 2.5, 5, 10],
        'osmo'        => [0.75, 2.5, 10, 25],
        'adler'       => [0.75, 2.5, 5],
        'gori'        => [0.75, 2.5, 5, 10],
        'teknos'      => [0.9, 2.7, 9, 18],
        'sayerlack'   => [1, 5],
        'renner'      => [1, 5],
        'tikkurila'   => [0.9, 2.7, 9, 18],
        'belinka'     => [0.75, 2.5, 5],
        'default'     => [0.5, 1, 2.5, 5, 10, 20],
    );
}

/**
 * Получение бренда товара для определения объёмов тары
 * 
 * @param int $product_id ID товара
 * @return string|null Slug бренда или null
 */
function parusweb_get_product_brand_for_tara($product_id) {
    // Пробуем стандартную таксономию pa_brand (Product Attributes)
    $brand_terms = wp_get_post_terms($product_id, 'pa_brand', array('fields' => 'slugs'));
    
    if (!is_wp_error($brand_terms) && !empty($brand_terms)) {
        return $brand_terms[0];
    }
    
    // Пробуем альтернативную таксономию brand
    $brand_terms = wp_get_post_terms($product_id, 'brand', array('fields' => 'slugs'));
    
    if (!is_wp_error($brand_terms) && !empty($brand_terms)) {
        return $brand_terms[0];
    }
    
    return null;
}

/**
 * Получение доступных объёмов для конкретного товара
 * 
 * @param int $product_id ID товара
 * @return array|null Массив объёмов или null если товар не ЛКМ
 */
function parusweb_get_available_volumes($product_id) {
    if (!parusweb_is_liter_product($product_id)) {
        return null;
    }
    
    $brand_slug = parusweb_get_product_brand_for_tara($product_id);
    $map = parusweb_get_tara_map();
    
    if ($brand_slug && isset($map[$brand_slug])) {
        return $map[$brand_slug];
    }
    
    // Возвращаем default если бренд не найден
    return $map['default'];
}

// ============================================================================
// БЛОК 2: ВЫВОД СЕЛЕКТА ОБЪЁМА НА СТРАНИЦЕ ТОВАРА
// ============================================================================

/**
 * Вывод селекта выбора объёма тары на странице товара
 */
function parusweb_render_tara_select() {
    global $product;
    
    if (!$product || !$product->is_type('simple')) {
        return;
    }
    
    $product_id = $product->get_id();
    
    if (!parusweb_is_liter_product($product_id)) {
        return;
    }
    
    $volumes = parusweb_get_available_volumes($product_id);
    
    if (empty($volumes)) {
        return;
    }
    
    $base_price = wc_get_price_to_display($product);
    $brand_slug = parusweb_get_product_brand_for_tara($product_id);
    
    ?>
    <style>
        #brxe-gkyfue .cart {
            align-items: flex-end;
        }
        .tara-select {
            margin-bottom: 15px;
        }
        .tara-select label {
            display: inline-block;
            margin-right: 10px;
            font-weight: bold;
            white-space: nowrap;
        }
        .tara-select .tinv-wraper {
            padding: 2.5px;
            width: 80px;
            display: inline-block;
        }
    </style>
    
    <div class="tara-select">
        <label for="tara">Объем (л):</label>
        <div class="tinv-wraper">
            <select id="tara" name="tara" data-base-price="<?php echo esc_attr($base_price); ?>">
                <?php foreach ($volumes as $volume): ?>
                    <option value="<?php echo esc_attr($volume); ?>">
                        <?php echo esc_html($volume); ?> л
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <?php if (current_user_can('manage_options')): ?>
        <!-- Debug info for admins -->
        <!-- Product ID: <?php echo $product_id; ?> -->
        <!-- Brand: <?php echo $brand_slug ? $brand_slug : 'not found'; ?> -->
        <!-- Volumes: <?php echo implode(', ', $volumes); ?> -->
    <?php endif;
}
add_action('woocommerce_before_add_to_cart_button', 'parusweb_render_tara_select');

// ============================================================================
// БЛОК 3: ДОБАВЛЕНИЕ ОБЪЁМА В КОРЗИНУ
// ============================================================================

/**
 * Добавление выбранного объёма в данные товара корзины
 * 
 * @param array $cart_item_data Данные товара
 * @param int $product_id ID товара
 * @param int $variation_id ID вариации (если есть)
 * @return array Модифицированные данные товара
 */
function parusweb_add_tara_to_cart($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['tara']) && !empty($_POST['tara'])) {
        $tara = floatval($_POST['tara']);
        
        // Валидация: проверяем что выбранный объём существует для данного товара
        $available_volumes = parusweb_get_available_volumes($product_id);
        
        if ($available_volumes && in_array($tara, $available_volumes)) {
            $cart_item_data['tara'] = $tara;
        }
    }
    
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'parusweb_add_tara_to_cart', 10, 3);

// ============================================================================
// БЛОК 4: ОТОБРАЖЕНИЕ ОБЪЁМА В КОРЗИНЕ
// ============================================================================

/**
 * Отображение выбранного объёма в корзине и оформлении заказа
 * 
 * @param array $item_data Данные для отображения
 * @param array $cart_item Элемент корзины
 * @return array Модифицированные данные для отображения
 */
function parusweb_display_tara_in_cart($item_data, $cart_item) {
    if (!empty($cart_item['tara'])) {
        $item_data[] = array(
            'name'  => 'Объем',
            'value' => $cart_item['tara'] . ' л',
        );
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'parusweb_display_tara_in_cart', 10, 2);

/**
 * Сохранение объёма в метаданных заказа
 * 
 * @param WC_Order_Item_Product $item Элемент заказа
 * @param string $cart_item_key Ключ элемента корзины
 * @param array $values Значения из корзины
 */
function parusweb_save_tara_to_order_items($item, $cart_item_key, $values) {
    if (!empty($values['tara'])) {
        $item->add_meta_data('Объем', $values['tara'] . ' л', true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'parusweb_save_tara_to_order_items', 10, 3);

// ============================================================================
// БЛОК 5: ПЕРЕСЧЁТ ЦЕНЫ В КОРЗИНЕ
// ============================================================================

/**
 * Пересчёт цены товара с учётом объёма и скидки
 * Формула: цена_итоговая = цена_за_литр × объём
 * Скидка: -10% при объёме >= 9 литров
 * 
 * @param WC_Cart $cart Объект корзины
 */
function parusweb_recalculate_tara_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['tara'])) {
            $volume = floatval($cart_item['tara']);
            $price_per_liter = floatval($cart_item['data']->get_regular_price());
            
            // Базовая цена = цена за литр × объём
            $final_price = $price_per_liter * $volume;
            
            // Применяем скидку 10% для объёма >= 9 литров
            if ($volume >= 9) {
                $final_price *= 0.9;
            }
            
            // Устанавливаем новую цену
            $cart_item['data']->set_price($final_price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'parusweb_recalculate_tara_price');

// ============================================================================
// БЛОК 6: JAVASCRIPT ДЛЯ ДИНАМИЧЕСКОГО ОБНОВЛЕНИЯ ЦЕНЫ
// ============================================================================

/**
 * JavaScript для обновления отображаемой цены при изменении объёма
 */
function parusweb_tara_update_script() {
    if (!is_product()) {
        return;
    }
    
    global $product;
    
    if (!$product || !parusweb_is_liter_product($product->get_id())) {
        return;
    }
    
    ?>
    <script>
    (function() {
        'use strict';
        
        document.addEventListener('DOMContentLoaded', function() {
            const select = document.getElementById('tara');
            if (!select) return;
            
            const priceEl = document.querySelector('.woocommerce-Price-amount');
            const basePrice = parseFloat(select.dataset.basePrice);
            
            if (!priceEl || isNaN(basePrice)) return;
            
            function updatePrice() {
                const volume = parseFloat(select.value) || 1;
                let newPrice = basePrice * volume;
                
                // Применяем скидку 10% для объёма >= 9л
                if (volume >= 9) {
                    newPrice *= 0.9;
                }
                
                // Форматируем цену (заменяем точку на запятую и добавляем пробелы)
                const formatted = newPrice.toFixed(2)
                    .replace('.', ',')
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                
                priceEl.innerHTML = formatted + '<span class="woocommerce-Price-currencySymbol">₽</span>';
            }
            
            // Обновляем цену при изменении селекта
            select.addEventListener('change', updatePrice);
            
            // Устанавливаем начальную цену
            updatePrice();
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_tara_update_script');

// ============================================================================
// БЛОК 7: МОДИФИКАЦИЯ ОТОБРАЖЕНИЯ ЦЕНЫ "ЗА ЛИТР"
// ============================================================================

/**
 * Изменение HTML цены для добавления текста "за литр"
 * 
 * @param string $price HTML код цены
 * @param WC_Product $product Объект товара
 * @return string Модифицированный HTML
 */
function parusweb_add_per_liter_text($price, $product) {
    $product_id = $product->get_id();
    
    if (!parusweb_is_liter_product($product_id)) {
        return $price;
    }
    
    // Проверяем, не добавлено ли уже "/литр"
    if (strpos($price, 'за литр') !== false || strpos($price, '/литр') !== false) {
        return $price;
    }
    
    // Если цена содержит HTML теги, добавляем "/литр" внутрь
    if (preg_match('/(.*)<\/span>(.*)$/i', $price, $matches)) {
        $price = $matches[1] . '/литр</span>' . $matches[2];
    } else {
        // Если простой текст
        $price .= ' за литр';
    }
    
    return $price;
}
add_filter('woocommerce_get_price_html', 'parusweb_add_per_liter_text', 10, 2);

// ============================================================================
// БЛОК 8: ДИАГНОСТИКА (ДЛЯ АДМИНИСТРАТОРОВ)
// ============================================================================

/**
 * Диагностическая функция для проверки настройки брендов
 * Выводит информацию в HTML комментарий для администраторов
 * 
 * @param int $product_id ID товара
 */
function parusweb_debug_tara_brand($product_id) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo "\n<!-- TARA DEBUG для товара #{$product_id} -->\n";
    
    // Проверяем таксономию pa_brand
    $pa_brand = wp_get_post_terms($product_id, 'pa_brand', array('fields' => 'all'));
    echo "<!-- pa_brand: ";
    if (!is_wp_error($pa_brand) && !empty($pa_brand)) {
        echo "найден - " . $pa_brand[0]->slug;
    } else {
        echo "не найден";
    }
    echo " -->\n";
    
    // Проверяем таксономию brand
    $brand = wp_get_post_terms($product_id, 'brand', array('fields' => 'all'));
    echo "<!-- brand: ";
    if (!is_wp_error($brand) && !empty($brand)) {
        echo "найден - " . $brand[0]->slug;
    } else {
        echo "не найден";
    }
    echo " -->\n";
    
    // Итоговый бренд
    $final_brand = parusweb_get_product_brand_for_tara($product_id);
    echo "<!-- Итоговый бренд: " . ($final_brand ? $final_brand : 'не определён') . " -->\n";
    
    // Доступные объёмы
    $volumes = parusweb_get_available_volumes($product_id);
    echo "<!-- Доступные объёмы: " . ($volumes ? implode(', ', $volumes) : 'нет') . " -->\n";
    
    echo "<!-- /TARA DEBUG -->\n\n";
}

/**
 * Автоматический вывод диагностики на странице товара для админов
 */
function parusweb_auto_debug_tara() {
    if (!is_product() || !current_user_can('manage_options')) {
        return;
    }
    
    global $product;
    if ($product) {
        parusweb_debug_tara_brand($product->get_id());
    }
}
add_action('wp_footer', 'parusweb_auto_debug_tara', 999);
