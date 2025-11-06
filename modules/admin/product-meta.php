<?php
/**
 * ============================================================================
 * МОДУЛЬ: МЕТАПОЛЯ ТОВАРОВ (АДМИНКА)
 * ============================================================================
 * 
 * Настройки калькуляторов для товаров:
 * - Множитель цены
 * - Настройки калькулятора размеров (min/max/step)
 * - Формы фальшбалок
 * - Цены форм верха штакетника
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 1: МНОЖИТЕЛЬ ЦЕНЫ
// ============================================================================

add_action('woocommerce_product_options_pricing', 'parusweb_add_price_multiplier_field');

function parusweb_add_price_multiplier_field() {
    echo '<div class="options_group">';
    
    woocommerce_wp_text_input([
        'id' => '_price_multiplier',
        'label' => 'Множитель цены',
        'desc_tip' => true,
        'description' => 'Множитель для расчета итоговой цены (например, 1.5). Если не задан, используется множитель категории.',
        'type' => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ]
    ]);
    
    echo '</div>';
}

// ============================================================================
// БЛОК 2: НАСТРОЙКИ КАЛЬКУЛЯТОРА РАЗМЕРОВ
// ============================================================================

add_action('woocommerce_product_options_general_product_data', 'parusweb_add_calculator_settings');

function parusweb_add_calculator_settings() {
    global $post;
    
    echo '<div class="options_group show_if_simple show_if_variable">';
    echo '<h4 style="padding-left: 12px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-bottom: 15px;">📐 Настройки калькулятора размеров</h4>';
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_min',
        'label' => 'Ширина мин. (мм)',
        'desc_tip' => true,
        'description' => 'Минимальная ширина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_max',
        'label' => 'Ширина макс. (мм)',
        'desc_tip' => true,
        'description' => 'Максимальная ширина для калькулятора',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_calc_width_step',
        'label' => 'Шаг ширины (мм)',
        'desc_tip' => true,
        'description' => 'Шаг изменения ширины (по умолчанию 100)',
        'placeholder' => '100',
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '1']
    ]);
    
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

// ============================================================================
// БЛОК 3: ФОРМЫ ФАЛЬШБАЛОК
// ============================================================================

add_action('woocommerce_product_options_general_product_data', 'parusweb_add_falsebalk_shapes_fields');

function parusweb_add_falsebalk_shapes_fields() {
    global $post;
    
    if (!has_term(266, 'product_cat', $post->ID)) {
        return;
    }
    
    $shapes_data = get_post_meta($post->ID, '_falsebalk_shapes_data', true);
    if (!is_array($shapes_data)) {
        $shapes_data = [];
    }
    
    $shapes = [
        'g' => 'Г-образная',
        'p' => 'П-образная',
        'o' => 'О-образная'
    ];
    
    ?>
    <div class="options_group">
        <h4 style="padding-left: 12px; color: #d63638; border-bottom: 2px solid #d63638; padding-bottom: 10px; margin-bottom: 15px;">🔨 Настройки фальшбалок</h4>
        
        <div style="padding: 0 12px;">
            <p><strong>Настройте доступные формы сечений и их параметры:</strong></p>
            
            <?php foreach ($shapes as $shape_key => $shape_label): ?>
                <?php
                $shape_info = isset($shapes_data[$shape_key]) ? $shapes_data[$shape_key] : [];
                $enabled = !empty($shape_info['enabled']);
                ?>
                
                <div class="falsebalk-shape-section" style="border: 2px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; background: #f9f9f9;">
                    <h5 style="margin-top: 0;">
                        <label>
                            <input type="checkbox" 
                                   name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][enabled]" 
                                   value="1" 
                                   <?php checked($enabled); ?>
                                   class="falsebalk-shape-toggle"
                                   data-shape="<?php echo $shape_key; ?>">
                            <?php echo esc_html($shape_label); ?>
                        </label>
                    </h5>
                    
                    <div class="falsebalk-shape-fields" data-shape="<?php echo $shape_key; ?>" style="<?php echo !$enabled ? 'display:none;' : ''; ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label><strong>Ширина (мм)</strong></label><br>
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][width_min]" 
                                       placeholder="Мин" value="<?php echo esc_attr($shape_info['width_min'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][width_max]" 
                                       placeholder="Макс" value="<?php echo esc_attr($shape_info['width_max'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][width_step]" 
                                       placeholder="Шаг" value="<?php echo esc_attr($shape_info['width_step'] ?? ''); ?>" 
                                       style="width: 100%;">
                            </div>
                            
                            <div>
                                <label><strong>Высота<?php echo $shape_key === 'p' ? ' 1' : ''; ?> (мм)</strong></label><br>
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][height_min]" 
                                       placeholder="Мин" value="<?php echo esc_attr($shape_info['height_min'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][height_max]" 
                                       placeholder="Макс" value="<?php echo esc_attr($shape_info['height_max'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][height_step]" 
                                       placeholder="Шаг" value="<?php echo esc_attr($shape_info['height_step'] ?? ''); ?>" 
                                       style="width: 100%;">
                            </div>
                            
                            <?php if ($shape_key === 'p'): ?>
                            <div>
                                <label><strong>Высота 2 (мм)</strong></label><br>
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][height2_min]" 
                                       placeholder="Мин" value="<?php echo esc_attr($shape_info['height2_min'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][height2_max]" 
                                       placeholder="Макс" value="<?php echo esc_attr($shape_info['height2_max'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                <input type="number" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][height2_step]" 
                                       placeholder="Шаг" value="<?php echo esc_attr($shape_info['height2_step'] ?? ''); ?>" 
                                       style="width: 100%;">
                            </div>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label><strong>Длина (м)</strong></label><br>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                <input type="number" step="0.01" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][length_min]" 
                                       placeholder="Мин" value="<?php echo esc_attr($shape_info['length_min'] ?? ''); ?>" 
                                       style="width: 100%;">
                                <input type="number" step="0.01" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][length_max]" 
                                       placeholder="Макс" value="<?php echo esc_attr($shape_info['length_max'] ?? ''); ?>" 
                                       style="width: 100%;">
                                <input type="number" step="0.01" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][length_step]" 
                                       placeholder="Шаг" value="<?php echo esc_attr($shape_info['length_step'] ?? ''); ?>" 
                                       style="width: 100%;">
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                            <strong>Старый формат (через запятую):</strong> оставьте для обратной совместимости
                            <div style="margin-top: 10px;">
                                <label>Ширины:</label>
                                <input type="text" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][widths]" 
                                       placeholder="100, 120, 150" value="<?php echo esc_attr($shape_info['widths'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                
                                <label>Высоты:</label>
                                <input type="text" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][heights]" 
                                       placeholder="80, 100, 120" value="<?php echo esc_attr($shape_info['heights'] ?? ''); ?>" 
                                       style="width: 100%; margin-bottom: 5px;">
                                
                                <label>Длины:</label>
                                <input type="text" name="_falsebalk_shapes_data[<?php echo $shape_key; ?>][lengths]" 
                                       placeholder="2.0, 2.5, 3.0" value="<?php echo esc_attr($shape_info['lengths'] ?? ''); ?>" 
                                       style="width: 100%;">
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            <?php endforeach; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.falsebalk-shape-toggle').on('change', function() {
                const shape = $(this).data('shape');
                const fields = $('.falsebalk-shape-fields[data-shape="' + shape + '"]');
                if ($(this).is(':checked')) {
                    fields.slideDown();
                } else {
                    fields.slideUp();
                }
            });
        });
        </script>
    </div>
    <?php
}

// ============================================================================
// БЛОК 4: ЦЕНЫ ФОРМ ВЕРХА ШТАКЕТНИКА
// ============================================================================

add_action('woocommerce_product_options_pricing', 'parusweb_add_shtaketnik_shape_prices');

function parusweb_add_shtaketnik_shape_prices() {
    global $post;
    
    if (!has_term(273, 'product_cat', $post->ID)) {
        return;
    }
    
    echo '<div class="options_group">';
    echo '<h4 style="padding-left: 12px; color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-bottom: 15px;">🔺 Цены за форму верха штакетника</h4>';
    
    woocommerce_wp_text_input([
        'id' => '_shape_price_round',
        'label' => 'Цена "Полукруг" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за полукруглую форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_shape_price_triangle',
        'label' => 'Цена "Треугольник" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за треугольную форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_shape_price_flat',
        'label' => 'Цена "Прямая" (₽)',
        'desc_tip' => true,
        'description' => 'Дополнительная цена за прямую форму верха',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
    
    echo '</div>';
}

// ============================================================================
// БЛОК 5: СОХРАНЕНИЕ МЕТАПОЛЕЙ
// ============================================================================

add_action('woocommerce_process_product_meta', 'parusweb_save_product_meta');

function parusweb_save_product_meta($post_id) {
    
    $multiplier = isset($_POST['_price_multiplier']) ? sanitize_text_field($_POST['_price_multiplier']) : '';
    update_post_meta($post_id, '_price_multiplier', $multiplier);
    
    $calc_fields = [
        '_calc_width_min', '_calc_width_max', '_calc_width_step',
        '_calc_length_min', '_calc_length_max', '_calc_length_step'
    ];
    
    foreach ($calc_fields as $field) {
        $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        update_post_meta($post_id, $field, $value);
    }
    
    if (isset($_POST['_falsebalk_shapes_data'])) {
        $shapes_data = [];
        foreach ($_POST['_falsebalk_shapes_data'] as $shape_key => $shape_info) {
            if (!empty($shape_info['enabled'])) {
                $shapes_data[$shape_key] = [
                    'enabled' => true,
                    'width_min' => !empty($shape_info['width_min']) ? floatval($shape_info['width_min']) : '',
                    'width_max' => !empty($shape_info['width_max']) ? floatval($shape_info['width_max']) : '',
                    'width_step' => !empty($shape_info['width_step']) ? floatval($shape_info['width_step']) : '',
                    'height_min' => !empty($shape_info['height_min']) ? floatval($shape_info['height_min']) : '',
                    'height_max' => !empty($shape_info['height_max']) ? floatval($shape_info['height_max']) : '',
                    'height_step' => !empty($shape_info['height_step']) ? floatval($shape_info['height_step']) : '',
                    'length_min' => !empty($shape_info['length_min']) ? floatval($shape_info['length_min']) : '',
                    'length_max' => !empty($shape_info['length_max']) ? floatval($shape_info['length_max']) : '',
                    'length_step' => !empty($shape_info['length_step']) ? floatval($shape_info['length_step']) : '',
                    'widths' => !empty($shape_info['widths']) ? sanitize_text_field($shape_info['widths']) : '',
                    'heights' => !empty($shape_info['heights']) ? sanitize_text_field($shape_info['heights']) : '',
                    'lengths' => !empty($shape_info['lengths']) ? sanitize_text_field($shape_info['lengths']) : '',
                ];
                
                if ($shape_key === 'p') {
                    $shapes_data[$shape_key]['height2_min'] = !empty($shape_info['height2_min']) ? floatval($shape_info['height2_min']) : '';
                    $shapes_data[$shape_key]['height2_max'] = !empty($shape_info['height2_max']) ? floatval($shape_info['height2_max']) : '';
                    $shapes_data[$shape_key]['height2_step'] = !empty($shape_info['height2_step']) ? floatval($shape_info['height2_step']) : '';
                }
            }
        }
        update_post_meta($post_id, '_falsebalk_shapes_data', $shapes_data);
    }
    
    $shape_prices = [
        '_shape_price_round',
        '_shape_price_triangle',
        '_shape_price_flat'
    ];
    
    foreach ($shape_prices as $field) {
        $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        update_post_meta($post_id, $field, $value);
    }
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
