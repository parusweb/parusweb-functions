<?php
/**
 * Product Meta Fields
 * 
 * Метаполя для товаров:
 * - Множитель цены товара
 * - Настройки калькулятора (мин/макс/шаг ширины и длины)
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: МНОЖИТЕЛЬ ЦЕНЫ ТОВАРА
// ============================================================================

/**
 * Добавление поля множителя в раздел "Цены" товара
 */
function parusweb_add_product_multiplier_field() {
    global $post;
    
    echo '<div class="options_group">';
    echo '<h4 style="padding-left: 12px; color: #2e7d32; border-bottom: 2px solid #4caf50; padding-bottom: 10px; margin-bottom: 15px;">⚙️ Множитель цены</h4>';
    
    woocommerce_wp_text_input([
        'id' => '_price_multiplier',
        'label' => 'Множитель цены товара',
        'desc_tip' => true,
        'description' => 'Индивидуальный множитель для этого товара (переопределяет множитель категории). 
                         Если не задан, используется множитель категории.',
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

// ============================================================================
// БЛОК 2: НАСТРОЙКИ КАЛЬКУЛЯТОРА
// ============================================================================

/**
 * Добавление полей настройки калькулятора
 */
function parusweb_add_calculator_settings_fields() {
    echo '<div class="options_group">';
    echo '<h4 style="padding-left: 12px; color: #1976d2; border-bottom: 2px solid #2196f3; padding-bottom: 10px; margin-bottom: 15px;">🧮 Настройки калькулятора</h4>';
    
    // ШИРИНА
    woocommerce_wp_text_input([
        'id' => '_calc_width_min',
        'label' => 'Ширина мин. (мм)',
        'desc_tip' => true,
        'description' => 'Минимальная ширина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '1']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_max',
        'label' => 'Ширина макс. (мм)',
        'desc_tip' => true,
        'description' => 'Максимальная ширина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '1']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_step',
        'label' => 'Шаг ширины (мм)',
        'desc_tip' => true,
        'description' => 'Шаг изменения ширины (по умолчанию 1)',
        'placeholder' => '1',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '1']
    ]);
    
    // ДЛИНА
    woocommerce_wp_text_input([
        'id' => '_calc_length_min',
        'label' => 'Длина мин. (м)',
        'desc_tip' => true,
        'description' => 'Минимальная длина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0.01']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_length_max',
        'label' => 'Длина макс. (м)',
        'desc_tip' => true,
        'description' => 'Максимальная длина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0.01']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_length_step',
        'label' => 'Шаг длины (м)',
        'desc_tip' => true,
        'description' => 'Шаг изменения длины (по умолчанию 0.01)',
        'placeholder' => '0.01',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0.01']
    ]);
    
    echo '</div>';
}
add_action('woocommerce_product_options_general_product_data', 'parusweb_add_calculator_settings_fields');

// ============================================================================
// БЛОК 3: СОХРАНЕНИЕ МЕТАПОЛЕЙ
// ============================================================================

/**
 * Сохранение всех метаполей товара
 */
function parusweb_save_product_meta($post_id) {
    
    // Сохранение множителя
    $multiplier = isset($_POST['_price_multiplier']) ? sanitize_text_field($_POST['_price_multiplier']) : '';
    
    if ($multiplier === '') {
        delete_post_meta($post_id, '_price_multiplier');
    } else {
        update_post_meta($post_id, '_price_multiplier', $multiplier);
    }
    
    // Сохранение настроек калькулятора
    $calc_fields = [
        '_calc_width_min',
        '_calc_width_max',
        '_calc_width_step',
        '_calc_length_min',
        '_calc_length_max',
        '_calc_length_step'
    ];
    
    foreach ($calc_fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            
            if ($value === '') {
                delete_post_meta($post_id, $field);
            } else {
                update_post_meta($post_id, $field, $value);
            }
        }
    }
}
add_action('woocommerce_process_product_meta', 'parusweb_save_product_meta');

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
