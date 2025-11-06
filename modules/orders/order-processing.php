<?php
/**
 * ============================================================================
 * МОДУЛЬ: ОБРАБОТКА ЗАКАЗОВ
 * ============================================================================
 * 
 * Сохранение данных калькуляторов в заказ:
 * - Сохранение данных всех калькуляторов
 * - Сохранение услуг покраски
 * - Сохранение схем покраски
 * - Отображение в админке заказа
 * - Форматирование метаданных
 * 
 * @package ParusWeb_Functions
 * @subpackage Orders
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 1: СОХРАНЕНИЕ ДАННЫХ КАЛЬКУЛЯТОРОВ В ЗАКАЗ
// ============================================================================

add_action('woocommerce_checkout_create_order_line_item', 'parusweb_save_calculator_to_order', 10, 4);

function parusweb_save_calculator_to_order($item, $cart_item_key, $values, $order) {
    
    if (isset($values['custom_area_calc'])) {
        $area_calc = $values['custom_area_calc'];
        $is_leaf_category = $area_calc['is_leaf'];
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = ($area_calc['packs'] % 10 === 1 && $area_calc['packs'] % 100 !== 11) ? $unit_forms[0] :
                  (($area_calc['packs'] % 10 >= 2 && $area_calc['packs'] % 10 <= 4 && ($area_calc['packs'] % 100 < 10 || $area_calc['packs'] % 100 >= 20)) ? $unit_forms[1] : $unit_forms[2]);
        
        $display_text = $area_calc['area'] . ' м² (' . $area_calc['packs'] . ' ' . $plural . ')';
        $item->add_meta_data('Выбранная площадь', $display_text, true);
        $item->add_meta_data('_area_calc_data', json_encode($area_calc), true);
    }
    
    if (isset($values['custom_dimensions'])) {
        $dims = $values['custom_dimensions'];
        $area = ($dims['width'] / 1000) * ($dims['length'] / 1000);
        
        $display_text = $dims['width'] . ' мм × ' . $dims['length'] . ' мм (' . round($area, 3) . ' м²)';
        $item->add_meta_data('Размеры', $display_text, true);
        $item->add_meta_data('_dimensions_data', json_encode($dims), true);
    }
    
    if (isset($values['custom_multiplier_calc'])) {
        $mult_calc = $values['custom_multiplier_calc'];
        
        $display_text = 'Ширина: ' . $mult_calc['width'] . ' мм, ';
        $display_text .= 'Длина: ' . $mult_calc['length'] . ' м, ';
        $display_text .= 'Площадь: ' . round($mult_calc['area_per_item'], 3) . ' м²';
        
        $item->add_meta_data('Параметры изделия', $display_text, true);
        $item->add_meta_data('_multiplier_calc_data', json_encode($mult_calc), true);
    }
    
    if (isset($values['custom_running_meter_calc'])) {
        $rm_calc = $values['custom_running_meter_calc'];
        
        $display_text = '';
        
        if (isset($rm_calc['shape_label'])) {
            $display_text .= 'Форма: ' . $rm_calc['shape_label'] . ', ';
            if ($rm_calc['width'] > 0) $display_text .= 'Ширина: ' . $rm_calc['width'] . ' мм, ';
            if (isset($rm_calc['height']) && $rm_calc['height'] > 0) $display_text .= 'Высота: ' . $rm_calc['height'] . ' мм, ';
            if (isset($rm_calc['height2']) && $rm_calc['height2'] > 0) $display_text .= 'Высота 2: ' . $rm_calc['height2'] . ' мм, ';
        } elseif ($rm_calc['width'] > 0) {
            $display_text .= 'Ширина: ' . $rm_calc['width'] . ' мм, ';
        }
        
        $display_text .= 'Длина: ' . $rm_calc['length'] . ' м, ';
        $display_text .= 'Общая длина: ' . $rm_calc['total_length'] . ' пог. м';
        
        $item->add_meta_data('Параметры изделия', $display_text, true);
        $item->add_meta_data('_running_meter_calc_data', json_encode($rm_calc), true);
    }
    
    if (isset($values['custom_square_meter_calc'])) {
        $sq_calc = $values['custom_square_meter_calc'];
        
        $display_text = 'Ширина: ' . $sq_calc['width'] . ' мм, ';
        $display_text .= 'Длина: ' . $sq_calc['length'] . ' м, ';
        $display_text .= 'Площадь: ' . round($sq_calc['area'], 3) . ' м²';
        
        $item->add_meta_data('Параметры изделия', $display_text, true);
        $item->add_meta_data('_square_meter_calc_data', json_encode($sq_calc), true);
    }
    
    if (isset($values['custom_partition_slat_calc'])) {
        $part_calc = $values['custom_partition_slat_calc'];
        
        $display_text = 'Ширина: ' . $part_calc['width'] . ' мм, ';
        $display_text .= 'Длина: ' . $part_calc['length'] . ' м, ';
        $display_text .= 'Толщина: ' . $part_calc['thickness'] . ' мм, ';
        $display_text .= 'Объем: ' . round($part_calc['volume'], 4) . ' м³';
        
        $item->add_meta_data('Параметры изделия', $display_text, true);
        $item->add_meta_data('_partition_slat_calc_data', json_encode($part_calc), true);
    }
    
    if (isset($values['card_pack_purchase'])) {
        $pack_data = $values['card_pack_purchase'];
        
        $display_text = 'Площадь: ' . $pack_data['area'] . ' м², ';
        $display_text .= 'Цена за м²: ' . number_format($pack_data['price_per_m2'], 2, '.', ' ') . ' ₽';
        
        $item->add_meta_data('Покупка из каталога', $display_text, true);
        $item->add_meta_data('_pack_purchase_data', json_encode($pack_data), true);
    }
    
    if (isset($values['standard_pack_purchase'])) {
        $pack_data = $values['standard_pack_purchase'];
        
        $display_text = 'Площадь: ' . $pack_data['area'] . ' м², ';
        $display_text .= 'Цена за м²: ' . number_format($pack_data['price_per_m2'], 2, '.', ' ') . ' ₽';
        
        $item->add_meta_data('Стандартная покупка', $display_text, true);
        $item->add_meta_data('_pack_purchase_data', json_encode($pack_data), true);
    }
}

// ============================================================================
// БЛОК 2: СОХРАНЕНИЕ СХЕМ ПОКРАСКИ И УСЛУГ
// ============================================================================

add_action('woocommerce_checkout_create_order_line_item', 'parusweb_save_painting_to_order', 10, 4);

function parusweb_save_painting_to_order($item, $cart_item_key, $values, $order) {
    
    if (!empty($values['pm_selected_scheme_name'])) {
        $scheme_with_color = $values['pm_selected_scheme_name'];
        
        if (!empty($values['pm_selected_color_filename'])) {
            $scheme_with_color .= ' "' . $values['pm_selected_color_filename'] . '"';
        }
        
        $item->add_meta_data('Схема покраски', $scheme_with_color, true);
    }
    
    if (!empty($values['pm_selected_color_image'])) {
        $item->add_meta_data('_pm_color_image_url', $values['pm_selected_color_image'], true);
    }
    
    if (!empty($values['pm_selected_color_filename'])) {
        $item->add_meta_data('Код цвета', $values['pm_selected_color_filename'], true);
    }
    
    $sources = [
        'custom_area_calc',
        'custom_dimensions',
        'custom_multiplier_calc',
        'custom_running_meter_calc',
        'custom_square_meter_calc',
        'custom_partition_slat_calc',
        'card_pack_purchase',
        'standard_pack_purchase'
    ];
    
    foreach ($sources as $key) {
        if (!empty($values[$key]['painting_service'])) {
            $painting = $values[$key]['painting_service'];
            
            $painting_name = $painting['name_with_color'] ?? ($painting['name'] ?? 'Покраска');
            
            $display_text = $painting_name;
            if (isset($painting['area']) && $painting['area'] > 0) {
                $display_text .= ' (' . round($painting['area'], 2) . ' м²';
                if (isset($painting['price_per_m2']) && $painting['price_per_m2'] > 0) {
                    $display_text .= ' × ' . number_format($painting['price_per_m2'], 2, '.', ' ') . ' ₽/м²';
                }
                $display_text .= ')';
            }
            
            $item->add_meta_data('Услуга покраски', $display_text, true);
            $item->add_meta_data('_painting_service_data', json_encode($painting), true);
            
            break;
        }
    }
}

// ============================================================================
// БЛОК 3: ФОРМАТИРОВАНИЕ ОТОБРАЖЕНИЯ МЕТАДАННЫХ
// ============================================================================

add_filter('woocommerce_order_item_display_meta_key', 'parusweb_format_order_meta_key', 10, 3);

function parusweb_format_order_meta_key($display_key, $meta, $item) {
    
    if ($meta->key === '_pm_color_image_url') {
        return 'Образец цвета';
    }
    
    $hidden_keys = [
        '_area_calc_data',
        '_dimensions_data',
        '_multiplier_calc_data',
        '_running_meter_calc_data',
        '_square_meter_calc_data',
        '_partition_slat_calc_data',
        '_pack_purchase_data',
        '_painting_service_data'
    ];
    
    if (in_array($meta->key, $hidden_keys)) {
        return '';
    }
    
    return $display_key;
}

add_filter('woocommerce_order_item_display_meta_value', 'parusweb_format_order_meta_value', 10, 3);

function parusweb_format_order_meta_value($display_value, $meta, $item) {
    
    if ($meta->key === '_pm_color_image_url') {
        $image_url = $meta->value;
        return '<img src="' . esc_url($image_url) . '" style="width:60px; height:60px; object-fit:cover; border:2px solid #ddd; border-radius:4px; display:block; margin-top:5px;">';
    }
    
    return $display_value;
}

// ============================================================================
// БЛОК 4: ОТОБРАЖЕНИЕ В АДМИНКЕ ЗАКАЗА
// ============================================================================

add_action('woocommerce_admin_order_data_after_order_details', 'parusweb_display_order_calculator_data');

function parusweb_display_order_calculator_data($order) {
    
    $has_calculator_data = false;
    
    foreach ($order->get_items() as $item_id => $item) {
        $area_calc = $item->get_meta('_area_calc_data');
        $dims = $item->get_meta('_dimensions_data');
        $mult_calc = $item->get_meta('_multiplier_calc_data');
        $rm_calc = $item->get_meta('_running_meter_calc_data');
        $sq_calc = $item->get_meta('_square_meter_calc_data');
        $part_calc = $item->get_meta('_partition_slat_calc_data');
        
        if ($area_calc || $dims || $mult_calc || $rm_calc || $sq_calc || $part_calc) {
            $has_calculator_data = true;
            break;
        }
    }
    
    if (!$has_calculator_data) {
        return;
    }
    
    ?>
    <div class="order_data_column" style="clear:both; margin-top: 20px;">
        <h3><?php _e('Данные калькуляторов', 'parusweb-functions'); ?></h3>
        <div class="address">
            <?php
            foreach ($order->get_items() as $item_id => $item) {
                $product_name = $item->get_name();
                
                $area_calc = $item->get_meta('_area_calc_data');
                if ($area_calc) {
                    $data = json_decode($area_calc, true);
                    echo '<p><strong>' . esc_html($product_name) . '</strong></p>';
                    echo '<p>Калькулятор площади:<br>';
                    echo 'Площадь: ' . esc_html($data['area']) . ' м²<br>';
                    echo 'Упаковок: ' . esc_html($data['packs']) . '<br>';
                    echo 'Стоимость: ' . number_format($data['total_price'], 2, '.', ' ') . ' ₽</p>';
                }
                
                $dims = $item->get_meta('_dimensions_data');
                if ($dims) {
                    $data = json_decode($dims, true);
                    echo '<p><strong>' . esc_html($product_name) . '</strong></p>';
                    echo '<p>Калькулятор размеров:<br>';
                    echo 'Ширина: ' . esc_html($data['width']) . ' мм<br>';
                    echo 'Длина: ' . esc_html($data['length']) . ' мм<br>';
                    echo 'Стоимость: ' . number_format($data['price'], 2, '.', ' ') . ' ₽</p>';
                }
                
                $mult_calc = $item->get_meta('_multiplier_calc_data');
                if ($mult_calc) {
                    $data = json_decode($mult_calc, true);
                    echo '<p><strong>' . esc_html($product_name) . '</strong></p>';
                    echo '<p>Калькулятор с множителем:<br>';
                    echo 'Ширина: ' . esc_html($data['width']) . ' мм<br>';
                    echo 'Длина: ' . esc_html($data['length']) . ' м<br>';
                    echo 'Площадь: ' . round($data['area_per_item'], 3) . ' м²<br>';
                    echo 'Множитель: ' . esc_html($data['multiplier']) . '<br>';
                    echo 'Стоимость: ' . number_format($data['price'], 2, '.', ' ') . ' ₽</p>';
                }
                
                $rm_calc = $item->get_meta('_running_meter_calc_data');
                if ($rm_calc) {
                    $data = json_decode($rm_calc, true);
                    echo '<p><strong>' . esc_html($product_name) . '</strong></p>';
                    echo '<p>Калькулятор погонных метров:<br>';
                    
                    if (isset($data['shape_label'])) {
                        echo 'Форма: ' . esc_html($data['shape_label']) . '<br>';
                    }
                    if ($data['width'] > 0) {
                        echo 'Ширина: ' . esc_html($data['width']) . ' мм<br>';
                    }
                    if (isset($data['height']) && $data['height'] > 0) {
                        echo 'Высота: ' . esc_html($data['height']) . ' мм<br>';
                    }
                    if (isset($data['height2']) && $data['height2'] > 0) {
                        echo 'Высота 2: ' . esc_html($data['height2']) . ' мм<br>';
                    }
                    
                    echo 'Длина: ' . esc_html($data['length']) . ' м<br>';
                    echo 'Общая длина: ' . esc_html($data['total_length']) . ' пог. м<br>';
                    echo 'Стоимость: ' . number_format($data['price'], 2, '.', ' ') . ' ₽</p>';
                }
                
                $sq_calc = $item->get_meta('_square_meter_calc_data');
                if ($sq_calc) {
                    $data = json_decode($sq_calc, true);
                    echo '<p><strong>' . esc_html($product_name) . '</strong></p>';
                    echo '<p>Калькулятор квадратных метров:<br>';
                    echo 'Ширина: ' . esc_html($data['width']) . ' мм<br>';
                    echo 'Длина: ' . esc_html($data['length']) . ' м<br>';
                    echo 'Площадь: ' . round($data['area'], 3) . ' м²<br>';
                    echo 'Стоимость: ' . number_format($data['price'], 2, '.', ' ') . ' ₽</p>';
                }
                
                $part_calc = $item->get_meta('_partition_slat_calc_data');
                if ($part_calc) {
                    $data = json_decode($part_calc, true);
                    echo '<p><strong>' . esc_html($product_name) . '</strong></p>';
                    echo '<p>Калькулятор реечных перегородок:<br>';
                    echo 'Ширина: ' . esc_html($data['width']) . ' мм<br>';
                    echo 'Длина: ' . esc_html($data['length']) . ' м<br>';
                    echo 'Толщина: ' . esc_html($data['thickness']) . ' мм<br>';
                    echo 'Объем: ' . round($data['volume'], 4) . ' м³<br>';
                    echo 'Стоимость: ' . number_format($data['price'], 2, '.', ' ') . ' ₽</p>';
                }
                
                $painting = $item->get_meta('_painting_service_data');
                if ($painting) {
                    $data = json_decode($painting, true);
                    echo '<p>Услуга покраски:<br>';
                    echo 'Название: ' . esc_html($data['name_with_color'] ?? $data['name']) . '<br>';
                    if (isset($data['area']) && $data['area'] > 0) {
                        echo 'Площадь: ' . round($data['area'], 2) . ' м²<br>';
                    }
                    if (isset($data['price_per_m2']) && $data['price_per_m2'] > 0) {
                        echo 'Цена за м²: ' . number_format($data['price_per_m2'], 2, '.', ' ') . ' ₽<br>';
                    }
                    if (isset($data['total_cost']) && $data['total_cost'] > 0) {
                        echo 'Стоимость: ' . number_format($data['total_cost'], 2, '.', ' ') . ' ₽';
                    }
                    echo '</p>';
                }
            }
            ?>
        </div>
    </div>
    <?php
}

// ============================================================================
// БЛОК 5: СОХРАНЕНИЕ МЕТАДАННЫХ ЗАКАЗА
// ============================================================================

add_action('woocommerce_checkout_update_order_meta', 'parusweb_save_order_meta');

function parusweb_save_order_meta($order_id) {
    
    $has_calculator = false;
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    foreach ($order->get_items() as $item_id => $item) {
        $area_calc = $item->get_meta('_area_calc_data');
        $dims = $item->get_meta('_dimensions_data');
        $mult_calc = $item->get_meta('_multiplier_calc_data');
        $rm_calc = $item->get_meta('_running_meter_calc_data');
        $sq_calc = $item->get_meta('_square_meter_calc_data');
        $part_calc = $item->get_meta('_partition_slat_calc_data');
        
        if ($area_calc || $dims || $mult_calc || $rm_calc || $sq_calc || $part_calc) {
            $has_calculator = true;
            break;
        }
    }
    
    if ($has_calculator) {
        update_post_meta($order_id, '_has_calculator_data', 'yes');
    }
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
