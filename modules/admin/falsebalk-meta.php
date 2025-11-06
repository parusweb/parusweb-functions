<?php
/**
 * Falsebalk Meta Fields
 * 
 * Метаполя для фальшбалок (категория 266):
 * - Настройка форм сечения (Г, П, О-образные)
 * - Доступные размеры (ширина, высота, длина) для каждой формы
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: МЕТАПОЛЯ ФОРМ ФАЛЬШБАЛОК
// ============================================================================

/**
 * Добавление метабокса для настройки форм фальшбалок
 */
function parusweb_add_falsebalk_shapes_fields() {
    global $post;
    
    // Проверяем, что товар относится к категории фальшбалок (266)
    if (!has_term(266, 'product_cat', $post->ID)) {
        return;
    }
    
    // Получаем сохраненные данные
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
                
                <div style="padding: 15px; margin: 12px 0; border: 2px solid <?php echo $enabled ? '#4caf50' : '#e0e0e0'; ?>; border-radius: 8px; background: <?php echo $enabled ? '#f1f8f4' : '#f9f9f9'; ?>;">
                    <h4 style="margin: 0 0 15px 0; color: #333; font-size: 15px;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" 
                                   name="_shape_<?php echo $shape_key; ?>_enabled" 
                                   value="1" 
                                   <?php checked($enabled); ?>
                                   class="falsebalk-shape-toggle"
                                   data-shape="<?php echo $shape_key; ?>"
                                   style="width: 20px; height: 20px;">
                            <span style="font-weight: 600;"><?php echo $shape_label; ?></span>
                        </label>
                    </h4>
                    
                    <div class="falsebalk-shape-fields" 
                         data-shape="<?php echo $shape_key; ?>" 
                         style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        
                        <!-- ШИРИНА -->
                        <div style="margin-bottom: 15px; padding: 12px; background: #fff; border-radius: 4px;">
                            <h5 style="margin: 0 0 10px 0; color: #1976d2;">Ширина (мм)</h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                <label>
                                    Минимум:<br>
                                    <input type="number" name="_shape_<?php echo $shape_key; ?>_width_min" 
                                           value="<?php echo esc_attr($shape_info['width_min'] ?? ''); ?>" 
                                           placeholder="70" style="width: 100%;" step="1" min="1">
                                </label>
                                <label>
                                    Максимум:<br>
                                    <input type="number" name="_shape_<?php echo $shape_key; ?>_width_max" 
                                           value="<?php echo esc_attr($shape_info['width_max'] ?? ''); ?>" 
                                           placeholder="300" style="width: 100%;" step="1" min="1">
                                </label>
                                <label>
                                    Шаг:<br>
                                    <input type="number" name="_shape_<?php echo $shape_key; ?>_width_step" 
                                           value="<?php echo esc_attr($shape_info['width_step'] ?? ''); ?>" 
                                           placeholder="10" style="width: 100%;" step="1" min="1">
                                </label>
                            </div>
                        </div>
                        
                        <!-- ВЫСОТА (для П-образной два поля) -->
                        <?php if ($shape_key === 'p'): ?>
                            <div style="margin-bottom: 15px; padding: 12px; background: #fff; border-radius: 4px;">
                                <h5 style="margin: 0 0 10px 0; color: #1976d2;">Высота 1 (мм)</h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                    <label>
                                        Минимум:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height1_min" 
                                               value="<?php echo esc_attr($shape_info['height1_min'] ?? ''); ?>" 
                                               placeholder="100" style="width: 100%;" step="1" min="1">
                                    </label>
                                    <label>
                                        Максимум:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height1_max" 
                                               value="<?php echo esc_attr($shape_info['height1_max'] ?? ''); ?>" 
                                               placeholder="300" style="width: 100%;" step="1" min="1">
                                    </label>
                                    <label>
                                        Шаг:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height1_step" 
                                               value="<?php echo esc_attr($shape_info['height1_step'] ?? ''); ?>" 
                                               placeholder="50" style="width: 100%;" step="1" min="1">
                                    </label>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px; padding: 12px; background: #fff; border-radius: 4px;">
                                <h5 style="margin: 0 0 10px 0; color: #1976d2;">Высота 2 (мм)</h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                    <label>
                                        Минимум:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height2_min" 
                                               value="<?php echo esc_attr($shape_info['height2_min'] ?? ''); ?>" 
                                               placeholder="100" style="width: 100%;" step="1" min="1">
                                    </label>
                                    <label>
                                        Максимум:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height2_max" 
                                               value="<?php echo esc_attr($shape_info['height2_max'] ?? ''); ?>" 
                                               placeholder="300" style="width: 100%;" step="1" min="1">
                                    </label>
                                    <label>
                                        Шаг:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height2_step" 
                                               value="<?php echo esc_attr($shape_info['height2_step'] ?? ''); ?>" 
                                               placeholder="50" style="width: 100%;" step="1" min="1">
                                    </label>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="margin-bottom: 15px; padding: 12px; background: #fff; border-radius: 4px;">
                                <h5 style="margin: 0 0 10px 0; color: #1976d2;">Высота (мм)</h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                    <label>
                                        Минимум:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height_min" 
                                               value="<?php echo esc_attr($shape_info['height_min'] ?? ''); ?>" 
                                               placeholder="100" style="width: 100%;" step="1" min="1">
                                    </label>
                                    <label>
                                        Максимум:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height_max" 
                                               value="<?php echo esc_attr($shape_info['height_max'] ?? ''); ?>" 
                                               placeholder="300" style="width: 100%;" step="1" min="1">
                                    </label>
                                    <label>
                                        Шаг:<br>
                                        <input type="number" name="_shape_<?php echo $shape_key; ?>_height_step" 
                                               value="<?php echo esc_attr($shape_info['height_step'] ?? ''); ?>" 
                                               placeholder="50" style="width: 100%;" step="1" min="1">
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- ДЛИНА -->
                        <div style="margin-bottom: 15px; padding: 12px; background: #fff; border-radius: 4px;">
                            <h5 style="margin: 0 0 10px 0; color: #1976d2;">Длина (м)</h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                <label>
                                    Минимум:<br>
                                    <input type="number" name="_shape_<?php echo $shape_key; ?>_length_min" 
                                           value="<?php echo esc_attr($shape_info['length_min'] ?? ''); ?>" 
                                           placeholder="0.5" style="width: 100%;" step="0.01" min="0.01">
                                </label>
                                <label>
                                    Максимум:<br>
                                    <input type="number" name="_shape_<?php echo $shape_key; ?>_length_max" 
                                           value="<?php echo esc_attr($shape_info['length_max'] ?? ''); ?>" 
                                           placeholder="6" style="width: 100%;" step="0.01" min="0.01">
                                </label>
                                <label>
                                    Шаг:<br>
                                    <input type="number" name="_shape_<?php echo $shape_key; ?>_length_step" 
                                           value="<?php echo esc_attr($shape_info['length_step'] ?? ''); ?>" 
                                           placeholder="0.1" style="width: 100%;" step="0.01" min="0.01">
                                </label>
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
                const container = $(this).closest('div[style*="padding: 15px"]');
                
                if ($(this).is(':checked')) {
                    fields.slideDown();
                    container.css({
                        'border-color': '#4caf50',
                        'background': '#f1f8f4'
                    });
                } else {
                    fields.slideUp();
                    container.css({
                        'border-color': '#e0e0e0',
                        'background': '#f9f9f9'
                    });
                }
            });
        });
        </script>
    </div>
    <?php
}
add_action('woocommerce_product_options_general_product_data', 'parusweb_add_falsebalk_shapes_fields');

// ============================================================================
// БЛОК 2: СОХРАНЕНИЕ ДАННЫХ ФАЛЬШБАЛОК
// ============================================================================

/**
 * Сохранение настроек форм фальшбалок
 */
function parusweb_save_falsebalk_shapes($post_id) {
    // Проверяем категорию
    if (!has_term(266, 'product_cat', $post_id)) {
        return;
    }
    
    $shapes_data = [];
    $shapes = ['g', 'p', 'o'];
    
    foreach ($shapes as $shape_key) {
        $enabled = isset($_POST['_shape_' . $shape_key . '_enabled']);
        
        if (!$enabled) {
            continue;
        }
        
        $shape_data = ['enabled' => true];
        
        // Ширина
        $shape_data['width_min'] = isset($_POST['_shape_' . $shape_key . '_width_min']) ? 
            floatval($_POST['_shape_' . $shape_key . '_width_min']) : 0;
        $shape_data['width_max'] = isset($_POST['_shape_' . $shape_key . '_width_max']) ? 
            floatval($_POST['_shape_' . $shape_key . '_width_max']) : 0;
        $shape_data['width_step'] = isset($_POST['_shape_' . $shape_key . '_width_step']) ? 
            floatval($_POST['_shape_' . $shape_key . '_width_step']) : 10;
        
        // Длина
        $shape_data['length_min'] = isset($_POST['_shape_' . $shape_key . '_length_min']) ? 
            floatval($_POST['_shape_' . $shape_key . '_length_min']) : 0;
        $shape_data['length_max'] = isset($_POST['_shape_' . $shape_key . '_length_max']) ? 
            floatval($_POST['_shape_' . $shape_key . '_length_max']) : 0;
        $shape_data['length_step'] = isset($_POST['_shape_' . $shape_key . '_length_step']) ? 
            floatval($_POST['_shape_' . $shape_key . '_length_step']) : 0.1;
        
        // Высота (для П-образной - две высоты)
        if ($shape_key === 'p') {
            $shape_data['height1_min'] = isset($_POST['_shape_' . $shape_key . '_height1_min']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height1_min']) : 0;
            $shape_data['height1_max'] = isset($_POST['_shape_' . $shape_key . '_height1_max']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height1_max']) : 0;
            $shape_data['height1_step'] = isset($_POST['_shape_' . $shape_key . '_height1_step']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height1_step']) : 50;
            
            $shape_data['height2_min'] = isset($_POST['_shape_' . $shape_key . '_height2_min']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height2_min']) : 0;
            $shape_data['height2_max'] = isset($_POST['_shape_' . $shape_key . '_height2_max']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height2_max']) : 0;
            $shape_data['height2_step'] = isset($_POST['_shape_' . $shape_key . '_height2_step']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height2_step']) : 50;
        } else {
            $shape_data['height_min'] = isset($_POST['_shape_' . $shape_key . '_height_min']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height_min']) : 0;
            $shape_data['height_max'] = isset($_POST['_shape_' . $shape_key . '_height_max']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height_max']) : 0;
            $shape_data['height_step'] = isset($_POST['_shape_' . $shape_key . '_height_step']) ? 
                floatval($_POST['_shape_' . $shape_key . '_height_step']) : 50;
        }
        
        $shapes_data[$shape_key] = $shape_data;
    }
    
    if (!empty($shapes_data)) {
        update_post_meta($post_id, '_falsebalk_shapes_data', $shapes_data);
    } else {
        delete_post_meta($post_id, '_falsebalk_shapes_data');
    }
}
add_action('woocommerce_process_product_meta', 'parusweb_save_falsebalk_shapes');

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
