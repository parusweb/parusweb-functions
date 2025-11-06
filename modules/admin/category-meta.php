<?php
/**
 * Category Meta Fields
 * 
 * Метаполя для категорий товаров:
 * - Множитель цены категории
 * - Типы фаски с изображениями
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: МНОЖИТЕЛЬ ЦЕНЫ КАТЕГОРИИ
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
        <p class="description">Множитель для расчета итоговой цены товаров этой категории (например, 1.5 или 2.0)</p>
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
            <p class="description">Множитель для расчета итоговой цены товаров этой категории</p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'parusweb_edit_category_multiplier_field');

/**
 * Сохранение множителя при создании категории
 */
function parusweb_save_category_multiplier_create($term_id) {
    if (isset($_POST['category_price_multiplier'])) {
        $value = sanitize_text_field($_POST['category_price_multiplier']);
        if ($value !== '') {
            update_term_meta($term_id, 'category_price_multiplier', $value);
        }
    }
}
add_action('created_product_cat', 'parusweb_save_category_multiplier_create');

/**
 * Сохранение множителя при редактировании категории
 */
function parusweb_save_category_multiplier_edit($term_id) {
    if (isset($_POST['category_price_multiplier'])) {
        $value = sanitize_text_field($_POST['category_price_multiplier']);
        if ($value === '') {
            delete_term_meta($term_id, 'category_price_multiplier');
        } else {
            update_term_meta($term_id, 'category_price_multiplier', $value);
        }
    }
}
add_action('edited_product_cat', 'parusweb_save_category_multiplier_edit');

// ============================================================================
// БЛОК 2: ТИПЫ ФАСКИ С ИЗОБРАЖЕНИЯМИ
// ============================================================================

/**
 * Добавление полей типов фаски при редактировании категории
 */
function parusweb_add_category_faska_fields($term) {
    $faska_types = get_term_meta($term->term_id, 'faska_types', true);
    if (!is_array($faska_types)) {
        $faska_types = [];
    }
    ?>
    <tr class="form-field">
        <th scope="row">
            <label>Типы фаски</label>
        </th>
        <td>
            <div id="faska-types-container">
                <?php if (!empty($faska_types)): ?>
                    <?php foreach ($faska_types as $index => $faska): ?>
                        <div class="faska-type-row" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                            <p>
                                <label>Название типа фаски:</label><br>
                                <input type="text" name="faska_types[<?php echo $index; ?>][name]" 
                                       value="<?php echo esc_attr($faska['name'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 10px;" />
                            </p>
                            <p>
                                <label>URL изображения:</label><br>
                                <input type="text" name="faska_types[<?php echo $index; ?>][image]" 
                                       class="faska-image-url" 
                                       value="<?php echo esc_url($faska['image'] ?? ''); ?>" 
                                       style="width: 70%;" />
                                <button type="button" class="button upload-faska-image">Выбрать изображение</button>
                            </p>
                            <?php if (!empty($faska['image'])): ?>
                                <p>
                                    <img src="<?php echo esc_url($faska['image']); ?>" 
                                         style="max-width: 150px; max-height: 150px; display: block; margin-top: 10px;" />
                                </p>
                            <?php endif; ?>
                            <button type="button" class="button remove-faska-type" style="color: #b32d2e;">Удалить</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add-faska-type" class="button">Добавить тип фаски</button>
            <p class="description">Настройте доступные типы фаски с изображениями для товаров этой категории</p>
        </td>
    </tr>
    
    <script>
    jQuery(document).ready(function($) {
        let faskaIndex = <?php echo count($faska_types); ?>;
        
        // Добавление нового типа фаски
        $('#add-faska-type').on('click', function() {
            const html = `
                <div class="faska-type-row" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                    <p>
                        <label>Название типа фаски:</label><br>
                        <input type="text" name="faska_types[${faskaIndex}][name]" 
                               style="width: 100%; margin-bottom: 10px;" />
                    </p>
                    <p>
                        <label>URL изображения:</label><br>
                        <input type="text" name="faska_types[${faskaIndex}][image]" 
                               class="faska-image-url" 
                               style="width: 70%;" />
                        <button type="button" class="button upload-faska-image">Выбрать изображение</button>
                    </p>
                    <button type="button" class="button remove-faska-type" style="color: #b32d2e;">Удалить</button>
                </div>
            `;
            $('#faska-types-container').append(html);
            faskaIndex++;
        });
        
        // Удаление типа фаски
        $(document).on('click', '.remove-faska-type', function() {
            $(this).closest('.faska-type-row').remove();
        });
        
        // Загрузчик изображений
        $(document).on('click', '.upload-faska-image', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const input = button.prev('.faska-image-url');
            
            const frame = wp.media({
                title: 'Выберите изображение фаски',
                button: { text: 'Использовать это изображение' },
                multiple: false
            });
            
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
                
                // Показываем превью
                let preview = button.closest('p').next('p');
                if (preview.length && preview.find('img').length) {
                    preview.find('img').attr('src', attachment.url);
                } else {
                    button.closest('p').after(
                        '<p><img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px; display: block; margin-top: 10px;" /></p>'
                    );
                }
            });
            
            frame.open();
        });
    });
    </script>
    <?php
}
add_action('product_cat_edit_form_fields', 'parusweb_add_category_faska_fields', 20);

/**
 * Сохранение полей фаски при сохранении категории
 */
function parusweb_save_category_faska_fields($term_id) {
    if (isset($_POST['faska_types'])) {
        $faska_types = [];
        
        foreach ($_POST['faska_types'] as $faska) {
            if (!empty($faska['name']) || !empty($faska['image'])) {
                $faska_types[] = [
                    'name' => sanitize_text_field($faska['name']),
                    'image' => esc_url_raw($faska['image'])
                ];
            }
        }
        
        if (!empty($faska_types)) {
            update_term_meta($term_id, 'faska_types', $faska_types);
        } else {
            delete_term_meta($term_id, 'faska_types');
        }
    }
}
add_action('edited_product_cat', 'parusweb_save_category_faska_fields', 20);

// ============================================================================
// БЛОК 3: ИНТЕГРАЦИЯ ФАСКИ С КОРЗИНОЙ И ЗАКАЗАМИ
// ============================================================================

/**
 * Добавление выбранной фаски в данные корзины
 */
function parusweb_add_faska_to_cart($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['selected_faska_type'])) {
        $cart_item_data['selected_faska'] = sanitize_text_field($_POST['selected_faska_type']);
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'parusweb_add_faska_to_cart', 10, 3);

/**
 * Отображение фаски в корзине
 */
function parusweb_display_faska_in_cart($item_data, $cart_item) {
    if (isset($cart_item['selected_faska'])) {
        $item_data[] = [
            'key' => 'Тип фаски',
            'value' => $cart_item['selected_faska']
        ];
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'parusweb_display_faska_in_cart', 10, 2);

/**
 * Сохранение фаски в метаданные заказа
 */
function parusweb_add_faska_to_order_items($item, $cart_item_key, $values, $order) {
    if (isset($values['selected_faska'])) {
        $item->add_meta_data('Тип фаски', $values['selected_faska'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'parusweb_add_faska_to_order_items', 10, 4);

/**
 * Форматирование ключа метаданных фаски в заказе
 */
function parusweb_format_faska_meta_key($display_key, $meta, $item) {
    if ($meta->key === 'Тип фаски') {
        return 'Тип фаски';
    }
    return $display_key;
}
add_filter('woocommerce_order_item_display_meta_key', 'parusweb_format_faska_meta_key', 10, 3);

/**
 * Форматирование значения метаданных фаски в заказе
 */
function parusweb_format_faska_meta_value($display_value, $meta, $item) {
    if ($meta->key === 'Тип фаски') {
        return '<strong>' . esc_html($display_value) . '</strong>';
    }
    return $display_value;
}
add_filter('woocommerce_order_item_display_meta_value', 'parusweb_format_faska_meta_value', 10, 3);

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
