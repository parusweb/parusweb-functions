<?php
/**
 * ============================================================================
 * МОДУЛЬ: МНОЖИТЕЛИ ЦЕН
 * ============================================================================
 * 
 * Управление множителями цен для товаров и категорий.
 * Множитель умножает базовую цену для получения финальной цены.
 * 
 * @package ParusWeb_Functions
 * @subpackage Features
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ПОЛУЧЕНИЕ МНОЖИТЕЛЯ
// ============================================================================

/**
 * Получить множитель цены для товара
 * Приоритет: товар → категория → 1.0
 */
function parusweb_get_price_multiplier($product_id) {
    // Проверяем множитель товара
    $product_multiplier = get_post_meta($product_id, '_price_multiplier', true);
    if (!empty($product_multiplier) && is_numeric($product_multiplier)) {
        return floatval($product_multiplier);
    }
    
    // Проверяем категории (от более конкретных к общим)
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'all']);
    if (!is_wp_error($product_categories) && !empty($product_categories)) {
        // Сортируем по глубине вложенности
        usort($product_categories, function($a, $b) {
            $depth_a = count(get_ancestors($a->term_id, 'product_cat'));
            $depth_b = count(get_ancestors($b->term_id, 'product_cat'));
            return $depth_b - $depth_a;
        });
        
        foreach ($product_categories as $category) {
            $cat_multiplier = get_term_meta($category->term_id, 'category_price_multiplier', true);
            if (!empty($cat_multiplier) && is_numeric($cat_multiplier)) {
                return floatval($cat_multiplier);
            }
        }
    }
    
    return 1.0;
}

// Создаем alias для обратной совместимости
if (!function_exists('get_price_multiplier')) {
    function get_price_multiplier($product_id) {
        return parusweb_get_price_multiplier($product_id);
    }
}

// ============================================================================
// МЕТАПОЛЯ ТОВАРА
// ============================================================================

/**
 * Добавление поля множителя в настройки товара
 */
function parusweb_add_product_multiplier_field() {
    global $post;
    
    echo '<div class="options_group">';
    
    woocommerce_wp_text_input([
        'id' => '_price_multiplier',
        'label' => 'Множитель цены',
        'desc_tip' => true,
        'description' => 'Множитель для расчета итоговой цены (например, 1.5). Если не задан, используется множитель категории.',
        'type' => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0',
            'max' => '10'
        ],
        'value' => get_post_meta($post->ID, '_price_multiplier', true)
    ]);
    
    // Показываем текущий множитель категории для справки
    $category_multiplier = 1.0;
    $product_categories = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'all']);
    if (!is_wp_error($product_categories) && !empty($product_categories)) {
        foreach ($product_categories as $category) {
            $cat_mult = get_term_meta($category->term_id, 'category_price_multiplier', true);
            if (!empty($cat_mult)) {
                $category_multiplier = floatval($cat_mult);
                break;
            }
        }
    }
    
    if ($category_multiplier != 1.0) {
        echo '<p class="form-field" style="padding-left: 12px; color: #666; font-style: italic;">';
        echo '💡 Множитель категории: ' . $category_multiplier;
        echo '</p>';
    }
    
    echo '</div>';
}
add_action('woocommerce_product_options_pricing', 'parusweb_add_product_multiplier_field');

/**
 * Сохранение множителя товара
 */
function parusweb_save_product_multiplier($post_id) {
    $multiplier = isset($_POST['_price_multiplier']) ? sanitize_text_field($_POST['_price_multiplier']) : '';
    
    if ($multiplier === '') {
        delete_post_meta($post_id, '_price_multiplier');
    } else {
        update_post_meta($post_id, '_price_multiplier', $multiplier);
    }
}
add_action('woocommerce_process_product_meta', 'parusweb_save_product_multiplier');

// ============================================================================
// МЕТАПОЛЯ КАТЕГОРИИ
// ============================================================================

/**
 * Добавление поля множителя при создании категории
 */
function parusweb_add_category_multiplier_field() {
    ?>
    <div class="form-field">
        <label for="category_price_multiplier">Множитель цены для категории</label>
        <input type="number" name="category_price_multiplier" id="category_price_multiplier" 
               step="0.01" min="0" max="10" value="" style="width: 150px;" />
        <p class="description">Множитель для расчета итоговой цены товаров этой категории (например, 1.5). Применяется к товарам, у которых не задан индивидуальный множитель.</p>
    </div>
    <?php
}
add_action('product_cat_add_form_fields', 'parusweb_add_category_multiplier_field');

/**
 * Добавление поля множителя при редактировании категории
 */
function parusweb_edit_category_multiplier_field($term) {
    $multiplier = get_term_meta($term->term_id, 'category_price_multiplier', true);
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="category_price_multiplier">Множитель цены для категории</label>
        </th>
        <td>
            <input type="number" name="category_price_multiplier" id="category_price_multiplier" 
                   step="0.01" min="0" max="10" value="<?php echo esc_attr($multiplier); ?>" 
                   style="width: 150px;" />
            <p class="description">Множитель для расчета итоговой цены товаров этой категории. Текущее значение: <strong><?php echo $multiplier ?: '1.0 (по умолчанию)'; ?></strong></p>
            
            <?php
            // Показываем количество товаров с этим множителем
            $products = get_posts([
                'post_type' => 'product',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    ]
                ]
            ]);
            
            $count_with_own = 0;
            foreach ($products as $prod_id) {
                if (get_post_meta($prod_id, '_price_multiplier', true)) {
                    $count_with_own++;
                }
            }
            
            if (!empty($products)) {
                echo '<p class="description" style="margin-top: 10px;">';
                echo '📊 Товаров в категории: <strong>' . count($products) . '</strong><br>';
                echo '📌 С индивидуальным множителем: <strong>' . $count_with_own . '</strong><br>';
                echo '🔄 Будут использовать множитель категории: <strong>' . (count($products) - $count_with_own) . '</strong>';
                echo '</p>';
            }
            ?>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'parusweb_edit_category_multiplier_field', 10, 1);

/**
 * Сохранение множителя категории
 */
function parusweb_save_category_multiplier($term_id) {
    if (isset($_POST['category_price_multiplier'])) {
        $multiplier = sanitize_text_field($_POST['category_price_multiplier']);
        
        if ($multiplier === '' || $multiplier === '0') {
            delete_term_meta($term_id, 'category_price_multiplier');
        } else {
            update_term_meta($term_id, 'category_price_multiplier', $multiplier);
        }
    }
}
add_action('created_product_cat', 'parusweb_save_category_multiplier');
add_action('edited_product_cat', 'parusweb_save_category_multiplier');

// ============================================================================
// ПРИМЕНЕНИЕ МНОЖИТЕЛЯ К ЦЕНЕ
// ============================================================================

/**
 * Применить множитель к цене товара (для отображения в каталоге)
 */
function parusweb_apply_multiplier_to_display_price($price, $product) {
    if (!$product) return $price;
    
    $product_id = $product->get_id();
    
    // Только для категорий с множителями
    if (!is_in_multiplier_categories($product_id)) {
        return $price;
    }
    
    $multiplier = parusweb_get_price_multiplier($product_id);
    
    // Если множитель = 1, ничего не меняем
    if ($multiplier == 1.0) {
        return $price;
    }
    
    // Применяем множитель визуально (в калькуляторах применяется отдельно)
    $base_price = floatval($product->get_regular_price() ?: $product->get_price());
    $multiplied_price = $base_price * $multiplier;
    
    // Для страницы товара показываем базовую цену
    if (is_product()) {
        return $price;
    }
    
    // В каталоге показываем с учетом множителя
    return wc_price($multiplied_price);
}
// Раскомментируйте при необходимости:
// add_filter('woocommerce_get_price_html', 'parusweb_apply_multiplier_to_display_price', 10, 2);

// ============================================================================
// ОТОБРАЖЕНИЕ ИНФОРМАЦИИ О МНОЖИТЕЛЕ
// ============================================================================

/**
 * Показать информацию о множителе на странице товара
 */
function parusweb_display_multiplier_info() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_in_multiplier_categories($product_id)) return;
    
    $multiplier = parusweb_get_price_multiplier($product_id);
    
    if ($multiplier == 1.0) return;
    
    ?>
    <div class="multiplier-info" style="margin: 15px 0; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
        <p style="margin: 0; font-size: 13px; color: #856404;">
            <span class="dashicons dashicons-admin-generic" style="font-size: 16px; vertical-align: middle;"></span>
            <strong>Множитель цены:</strong> ×<?php echo number_format($multiplier, 2); ?>
            <span style="display: block; margin-top: 5px; font-size: 12px;">
                Итоговая цена рассчитывается с учетом множителя
            </span>
        </p>
    </div>
    <?php
}
add_action('woocommerce_before_add_to_cart_form', 'parusweb_display_multiplier_info', 15);

// ============================================================================
// ADMIN КОЛОНКА С МНОЖИТЕЛЕМ
// ============================================================================

/**
 * Добавление колонки множителя в список товаров
 */
function parusweb_add_multiplier_column($columns) {
    $new_columns = [];
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'price') {
            $new_columns['price_multiplier'] = 'Множитель';
        }
    }
    
    return $new_columns;
}
add_filter('manage_edit-product_columns', 'parusweb_add_multiplier_column', 20);

/**
 * Заполнение колонки множителя
 */
function parusweb_fill_multiplier_column($column, $post_id) {
    if ($column === 'price_multiplier') {
        $multiplier = parusweb_get_price_multiplier($post_id);
        $product_mult = get_post_meta($post_id, '_price_multiplier', true);
        
        if (!empty($product_mult)) {
            echo '<strong style="color: #2e7d32;">×' . number_format($multiplier, 2) . '</strong>';
            echo '<br><small style="color: #666;">товар</small>';
        } elseif ($multiplier != 1.0) {
            echo '×' . number_format($multiplier, 2);
            echo '<br><small style="color: #999;">категория</small>';
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}
add_action('manage_product_posts_custom_column', 'parusweb_fill_multiplier_column', 10, 2);

/**
 * Сортировка по множителю
 */
function parusweb_multiplier_column_sortable($columns) {
    $columns['price_multiplier'] = 'price_multiplier';
    return $columns;
}
add_filter('manage_edit-product_sortable_columns', 'parusweb_multiplier_column_sortable');

// ============================================================================
// ADMIN КОЛОНКА С МНОЖИТЕЛЕМ В КАТЕГОРИЯХ
// ============================================================================

/**
 * Добавление колонки множителя в список категорий
 */
function parusweb_add_category_multiplier_column($columns) {
    $columns['category_multiplier'] = 'Множитель';
    return $columns;
}
add_filter('manage_edit-product_cat_columns', 'parusweb_add_category_multiplier_column');

/**
 * Заполнение колонки множителя категорий
 */
function parusweb_fill_category_multiplier_column($content, $column_name, $term_id) {
    if ($column_name === 'category_multiplier') {
        $multiplier = get_term_meta($term_id, 'category_price_multiplier', true);
        
        if (!empty($multiplier)) {
            return '<strong style="color: #2e7d32;">×' . number_format(floatval($multiplier), 2) . '</strong>';
        } else {
            return '<span style="color: #999;">—</span>';
        }
    }
    
    return $content;
}
add_filter('manage_product_cat_custom_column', 'parusweb_fill_category_multiplier_column', 10, 3);

// ============================================================================
// МАССОВОЕ ОБНОВЛЕНИЕ
// ============================================================================

/**
 * Добавление массового действия для установки множителя
 */
function parusweb_add_bulk_multiplier_action($bulk_actions) {
    $bulk_actions['set_multiplier'] = 'Установить множитель';
    return $bulk_actions;
}
add_filter('bulk_actions-edit-product', 'parusweb_add_bulk_multiplier_action');

/**
 * Обработка массового действия
 */
function parusweb_handle_bulk_multiplier_action($redirect_to, $action, $post_ids) {
    if ($action !== 'set_multiplier') {
        return $redirect_to;
    }
    
    // В реальном применении здесь должна быть форма для ввода значения
    // Для примера устанавливаем 1.5
    $multiplier = 1.5;
    
    foreach ($post_ids as $post_id) {
        update_post_meta($post_id, '_price_multiplier', $multiplier);
    }
    
    $redirect_to = add_query_arg('bulk_multiplier_updated', count($post_ids), $redirect_to);
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-product', 'parusweb_handle_bulk_multiplier_action', 10, 3);

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Получить список всех товаров с множителями
 */
function parusweb_get_products_with_multipliers() {
    global $wpdb;
    
    $products = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_price_multiplier' 
        AND meta_value != '' 
        AND meta_value != '1'
        AND meta_value != '1.0'
    ");
    
    return $products;
}

/**
 * Получить список всех категорий с множителями
 */
function parusweb_get_categories_with_multipliers() {
    global $wpdb;
    
    $categories = $wpdb->get_results("
        SELECT term_id, meta_value 
        FROM {$wpdb->termmeta} 
        WHERE meta_key = 'category_price_multiplier' 
        AND meta_value != '' 
        AND meta_value != '1'
        AND meta_value != '1.0'
    ");
    
    return $categories;
}
