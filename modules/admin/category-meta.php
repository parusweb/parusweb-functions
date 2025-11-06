<?php
/**
 * ============================================================================
 * МОДУЛЬ: МЕТАПОЛЯ КАТЕГОРИЙ (АДМИНКА)
 * ============================================================================
 * 
 * Настройки для категорий товаров:
 * - Множитель цены категории
 * - Типы фасок для категории
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 1: МНОЖИТЕЛЬ ЦЕНЫ ДЛЯ КАТЕГОРИИ
// ============================================================================

add_action('product_cat_add_form_fields', 'parusweb_add_category_multiplier_field');

function parusweb_add_category_multiplier_field() {
    ?>
    <div class="form-field">
        <label for="category_price_multiplier">Множитель цены для категории</label>
        <input type="number" 
               name="category_price_multiplier" 
               id="category_price_multiplier" 
               step="0.01" 
               min="0" 
               value="">
        <p class="description">Множитель для расчета итоговой цены товаров этой категории (например, 1.5). Если у товара задан свой множитель, он имеет приоритет.</p>
    </div>
    <?php
}

add_action('product_cat_edit_form_fields', 'parusweb_edit_category_multiplier_field', 10, 2);

function parusweb_edit_category_multiplier_field($term) {
    $multiplier = get_term_meta($term->term_id, 'category_price_multiplier', true);
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="category_price_multiplier">Множитель цены для категории</label>
        </th>
        <td>
            <input type="number" 
                   name="category_price_multiplier" 
                   id="category_price_multiplier" 
                   step="0.01" 
                   min="0" 
                   value="<?php echo esc_attr($multiplier); ?>">
            <p class="description">Множитель для расчета итоговой цены товаров этой категории (например, 1.5)</p>
        </td>
    </tr>
    <?php
}

// ============================================================================
// БЛОК 2: ТИПЫ ФАСОК ДЛЯ КАТЕГОРИИ
// ============================================================================

add_action('product_cat_edit_form_fields', 'parusweb_add_faska_fields', 10, 2);

function parusweb_add_faska_fields($term) {
    $faska_types = get_term_meta($term->term_id, 'faska_types', true);
    if (!is_array($faska_types)) {
        $faska_types = [];
    }
    ?>
    <tr class="form-field">
        <th scope="row">
            <label>Типы фасок</label>
        </th>
        <td>
            <div id="faska_types_container">
                <p class="description" style="margin-bottom: 15px;">Настройте доступные типы фасок для этой категории товаров.</p>
                
                <?php if (!empty($faska_types)): ?>
                    <?php foreach ($faska_types as $index => $faska): ?>
                        <div class="faska-type-row" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div style="flex: 1;">
                                    <label><strong>Название фаски:</strong></label><br>
                                    <input type="text" 
                                           name="faska_types[<?php echo $index; ?>][name]" 
                                           value="<?php echo esc_attr($faska['name'] ?? ''); ?>" 
                                           placeholder="Например: Фаска 2×2 мм"
                                           style="width: 100%;">
                                </div>
                                <div style="flex: 1;">
                                    <label><strong>URL изображения:</strong></label><br>
                                    <input type="text" 
                                           name="faska_types[<?php echo $index; ?>][image]" 
                                           value="<?php echo esc_url($faska['image'] ?? ''); ?>" 
                                           class="faska-image-url"
                                           placeholder="https://..."
                                           style="width: 100%;">
                                </div>
                                <div style="width: 80px;">
                                    <?php if (!empty($faska['image'])): ?>
                                        <img src="<?php echo esc_url($faska['image']); ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #ddd; border-radius: 4px;">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button type="button" class="button remove-faska-type" style="background: #d63638; color: white;">Удалить</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="button" id="add_faska_type" class="button button-secondary" style="margin-top: 10px;">+ Добавить тип фаски</button>
            
            <script>
            jQuery(document).ready(function($) {
                let faskaIndex = <?php echo count($faska_types); ?>;
                
                $('#add_faska_type').on('click', function() {
                    const row = $('<div class="faska-type-row" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">' +
                        '<div style="display: flex; gap: 15px; align-items: center;">' +
                            '<div style="flex: 1;">' +
                                '<label><strong>Название фаски:</strong></label><br>' +
                                '<input type="text" name="faska_types[' + faskaIndex + '][name]" placeholder="Например: Фаска 2×2 мм" style="width: 100%;">' +
                            '</div>' +
                            '<div style="flex: 1;">' +
                                '<label><strong>URL изображения:</strong></label><br>' +
                                '<input type="text" name="faska_types[' + faskaIndex + '][image]" class="faska-image-url" placeholder="https://..." style="width: 100%;">' +
                            '</div>' +
                            '<div style="width: 80px;"></div>' +
                            '<div>' +
                                '<button type="button" class="button remove-faska-type" style="background: #d63638; color: white;">Удалить</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>');
                    
                    $('#faska_types_container').append(row);
                    faskaIndex++;
                });
                
                $(document).on('click', '.remove-faska-type', function() {
                    $(this).closest('.faska-type-row').remove();
                });
            });
            </script>
        </td>
    </tr>
    <?php
}

// ============================================================================
// БЛОК 3: СОХРАНЕНИЕ МЕТАПОЛЕЙ КАТЕГОРИИ
// ============================================================================

add_action('created_product_cat', 'parusweb_save_category_meta');
add_action('edited_product_cat', 'parusweb_save_category_meta');

function parusweb_save_category_meta($term_id) {
    
    if (isset($_POST['category_price_multiplier'])) {
        $multiplier = sanitize_text_field($_POST['category_price_multiplier']);
        update_term_meta($term_id, 'category_price_multiplier', $multiplier);
    }
    
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
        update_term_meta($term_id, 'faska_types', $faska_types);
    }
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
