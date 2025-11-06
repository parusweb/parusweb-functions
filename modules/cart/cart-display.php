<?php
/**
 * ============================================================================
 * МОДУЛЬ: ОТОБРАЖЕНИЕ В КОРЗИНЕ
 * ============================================================================
 * 
 * Отображение данных калькуляторов в корзине:
 * - Вывод информации о расчетах
 * - Форматирование цен
 * - Отображение услуг покраски
 * - Схемы покраски
 * - Мини-корзина
 * 
 * @package ParusWeb_Functions
 * @subpackage Cart
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 1: ОТОБРАЖЕНИЕ ДАННЫХ КАЛЬКУЛЯТОРОВ В КОРЗИНЕ
// ============================================================================

add_filter('woocommerce_get_item_data', 'parusweb_display_calculator_data_in_cart', 10, 2);

function parusweb_display_calculator_data_in_cart($item_data, $cart_item) {
    
    if (!empty($cart_item['pm_selected_scheme_name'])) {
        $item_data[] = [
            'name' => 'Схема покраски',
            'value' => $cart_item['pm_selected_scheme_name']
        ];
    }
    
    if (!empty($cart_item['pm_selected_color'])) {
        $color_display = $cart_item['pm_selected_color'];
        
        if (!empty($cart_item['pm_selected_color_image'])) {
            $image_url = $cart_item['pm_selected_color_image'];
            $filename = !empty($cart_item['pm_selected_color_filename']) ? $cart_item['pm_selected_color_filename'] : '';
            
            $color_display = '<div style="display:flex; align-items:center; gap:10px;">';
            $color_display .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($filename) . '" style="width:40px; height:40px; object-fit:cover; border:2px solid #ddd; border-radius:4px;">';
            $color_display .= '<div>';
            $color_display .= '<div>' . esc_html($cart_item['pm_selected_color']) . '</div>';
            if ($filename) {
                $color_display .= '<div style="font-size:11px; color:#999;">Код: ' . esc_html($filename) . '</div>';
            }
            $color_display .= '</div>';
            $color_display .= '</div>';
        }
        
        $item_data[] = [
            'name' => 'Цвет',
            'value' => $color_display
        ];
    }
    
    if (isset($cart_item['custom_area_calc'])) {
        $area_calc = $cart_item['custom_area_calc'];
        $is_leaf_category = $area_calc['is_leaf'];
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = ($area_calc['packs'] % 10 === 1 && $area_calc['packs'] % 100 !== 11) ? $unit_forms[0] :
                  (($area_calc['packs'] % 10 >= 2 && $area_calc['packs'] % 10 <= 4 && ($area_calc['packs'] % 100 < 10 || $area_calc['packs'] % 100 >= 20)) ? $unit_forms[1] : $unit_forms[2]);
        
        $display_text = $area_calc['area'] . ' м² (' . $area_calc['packs'] . ' ' . $plural . ') — ' . number_format($area_calc['total_price'], 2, '.', ' ') . ' ₽';
        
        if (isset($area_calc['painting_service']) && $area_calc['painting_service']) {
            $painting = $area_calc['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'Выбранная площадь',
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['custom_dimensions'])) {
        $dims = $cart_item['custom_dimensions'];
        $area = ($dims['width'] / 1000) * ($dims['length'] / 1000);
        
        $display_text = $dims['width'] . ' мм × ' . $dims['length'] . ' мм (' . round($area, 3) . ' м²) — ' . number_format($dims['price'], 2, '.', ' ') . ' ₽';
        
        if (isset($dims['painting_service']) && $dims['painting_service']) {
            $painting = $dims['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'Размеры',
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['custom_multiplier_calc'])) {
        $mult_calc = $cart_item['custom_multiplier_calc'];
        
        $display_text = 'Ширина: ' . $mult_calc['width'] . ' мм<br>';
        $display_text .= 'Длина: ' . $mult_calc['length'] . ' м<br>';
        $display_text .= 'Площадь: ' . round($mult_calc['area_per_item'], 3) . ' м²<br>';
        $display_text .= 'Стоимость: ' . number_format($mult_calc['price'], 2, '.', ' ') . ' ₽';
        
        if (isset($mult_calc['painting_service']) && $mult_calc['painting_service']) {
            $painting = $mult_calc['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['custom_running_meter_calc'])) {
        $rm_calc = $cart_item['custom_running_meter_calc'];
        
        $display_text = '';
        
        if (isset($rm_calc['shape_label'])) {
            $display_text .= 'Форма: ' . $rm_calc['shape_label'] . '<br>';
            if ($rm_calc['width'] > 0) $display_text .= 'Ширина: ' . $rm_calc['width'] . ' мм<br>';
            if (isset($rm_calc['height']) && $rm_calc['height'] > 0) $display_text .= 'Высота: ' . $rm_calc['height'] . ' мм<br>';
            if (isset($rm_calc['height2']) && $rm_calc['height2'] > 0) $display_text .= 'Высота 2: ' . $rm_calc['height2'] . ' мм<br>';
        } elseif ($rm_calc['width'] > 0) {
            $display_text .= 'Ширина: ' . $rm_calc['width'] . ' мм<br>';
        }
        
        $display_text .= 'Длина: ' . $rm_calc['length'] . ' м<br>';
        $display_text .= 'Общая длина: ' . $rm_calc['total_length'] . ' пог. м<br>';
        $display_text .= 'Стоимость: ' . number_format($rm_calc['price'], 2, '.', ' ') . ' ₽';
        
        if (isset($rm_calc['painting_service']) && $rm_calc['painting_service']) {
            $painting = $rm_calc['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['custom_square_meter_calc'])) {
        $sq_calc = $cart_item['custom_square_meter_calc'];
        
        $display_text = 'Ширина: ' . $sq_calc['width'] . ' мм<br>';
        $display_text .= 'Длина: ' . $sq_calc['length'] . ' м<br>';
        $display_text .= 'Площадь: ' . round($sq_calc['area'], 3) . ' м²<br>';
        $display_text .= 'Стоимость: ' . number_format($sq_calc['price'], 2, '.', ' ') . ' ₽';
        
        if (isset($sq_calc['painting_service']) && $sq_calc['painting_service']) {
            $painting = $sq_calc['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['custom_partition_slat_calc'])) {
        $part_calc = $cart_item['custom_partition_slat_calc'];
        
        $display_text = 'Ширина: ' . $part_calc['width'] . ' мм<br>';
        $display_text .= 'Длина: ' . $part_calc['length'] . ' м<br>';
        $display_text .= 'Толщина: ' . $part_calc['thickness'] . ' мм<br>';
        $display_text .= 'Объем: ' . round($part_calc['volume'], 4) . ' м³<br>';
        $display_text .= 'Стоимость: ' . number_format($part_calc['price'], 2, '.', ' ') . ' ₽';
        
        if (isset($part_calc['painting_service']) && $part_calc['painting_service']) {
            $painting = $part_calc['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'Параметры',
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['card_pack_purchase'])) {
        $pack_data = $cart_item['card_pack_purchase'];
        
        $display_text = 'Площадь: ' . $pack_data['area'] . ' м²<br>';
        $display_text .= 'Цена за м²: ' . number_format($pack_data['price_per_m2'], 2, '.', ' ') . ' ₽<br>';
        $display_text .= 'Стоимость: ' . number_format($pack_data['total_price'], 2, '.', ' ') . ' ₽';
        
        if (isset($pack_data['painting_service']) && $pack_data['painting_service']) {
            $painting = $pack_data['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'В корзине ' . $pack_data['unit_type'],
            'value' => $display_text
        ];
    }
    
    if (isset($cart_item['standard_pack_purchase'])) {
        $pack_data = $cart_item['standard_pack_purchase'];
        
        $display_text = 'Площадь: ' . $pack_data['area'] . ' м²<br>';
        $display_text .= 'Цена за м²: ' . number_format($pack_data['price_per_m2'], 2, '.', ' ') . ' ₽<br>';
        $display_text .= 'Стоимость: ' . number_format($pack_data['total_price'], 2, '.', ' ') . ' ₽';
        
        if (isset($pack_data['painting_service']) && $pack_data['painting_service']) {
            $painting = $pack_data['painting_service'];
            $painting_name = isset($painting['name_with_color']) ? $painting['name_with_color'] : $painting['name'];
            $display_text .= '<br>+ ' . $painting_name . ' — ' . number_format($painting['total_cost'], 2, '.', ' ') . ' ₽';
        }
        
        $item_data[] = [
            'name' => 'В корзине ' . $pack_data['unit_type'],
            'value' => $display_text
        ];
    }
    
    return $item_data;
}

// ============================================================================
// БЛОК 2: ФОРМАТИРОВАНИЕ ЦЕНЫ В КОРЗИНЕ
// ============================================================================

add_filter('woocommerce_cart_item_price', 'parusweb_format_cart_item_price', 10, 3);

function parusweb_format_cart_item_price($price, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) {
        return $price;
    }
    
    if (isset($cart_item['card_pack_purchase']) || 
        isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        $current_price = floatval($product->get_price());
        $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
        
        $leaf_parent_id = 190;
        $leaf_children = [191, 127, 94];
        $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
        $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
        $unit_text = $is_leaf_category ? 'лист' : 'упаковка';
        
        return wc_price($current_price) . ' за ' . $unit_text . '<br>' .
               '<small style="color: #666;">' . wc_price($base_price_m2) . ' за м²</small>';
    }
    
    return $price;
}

// ============================================================================
// БЛОК 3: ФОРМАТИРОВАНИЕ ИТОГОВОЙ СУММЫ
// ============================================================================

add_filter('woocommerce_cart_item_subtotal', 'parusweb_format_cart_item_subtotal', 10, 3);

function parusweb_format_cart_item_subtotal($subtotal, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) {
        return $subtotal;
    }
    
    if (isset($cart_item['card_pack_purchase']) || 
        isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        $quantity = $cart_item['quantity'];
        $current_price = floatval($product->get_price());
        $total = $current_price * $quantity;
        
        $leaf_parent_id = 190;
        $leaf_children = [191, 127, 94];
        $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
        $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = parusweb_get_russian_plural($quantity, $unit_forms);
        
        return '<strong>' . wc_price($total) . '</strong><br>' .
               '<small style="color: #666;">' . $quantity . ' ' . $plural . '</small>';
    }
    
    return $subtotal;
}

// ============================================================================
// БЛОК 4: МИНИ-КОРЗИНА
// ============================================================================

add_filter('woocommerce_widget_cart_item_quantity', 'parusweb_format_mini_cart_quantity', 10, 3);

function parusweb_format_mini_cart_quantity($quantity, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) {
        return $quantity;
    }
    
    if (isset($cart_item['card_pack_purchase']) || 
        isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        $qty = $cart_item['quantity'];
        $current_price = floatval($product->get_price());
        $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
        
        $leaf_parent_id = 190;
        $leaf_children = [191, 127, 94];
        $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
        $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
        $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
        
        $plural = parusweb_get_russian_plural($qty, $unit_forms);
        
        return '<span class="quantity">' . $qty . ' ' . $plural . ' × ' . wc_price($current_price) . '</span>';
    }
    
    return $quantity;
}

// ============================================================================
// БЛОК 5: УДАЛЕНИЕ ЦЕН ИЗ НАЗВАНИЙ УСЛУГ
// ============================================================================

add_filter('woocommerce_get_item_data', 'parusweb_remove_price_from_service_name', 15, 2);

function parusweb_remove_price_from_service_name($item_data, $cart_item) {
    foreach ($item_data as &$data) {
        if (isset($data['value']) && is_string($data['value'])) {
            $data['value'] = preg_replace('/\s*—\s*[\d\s,.]+₽/', '', $data['value']);
            $data['value'] = preg_replace('/\s*\([\d\s,.]+м²\s*×\s*[\d\s,.]+₽\/м²\)/', '', $data['value']);
        }
    }
    return $item_data;
}

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

function parusweb_get_russian_plural($number, $forms) {
    $number = abs($number) % 100;
    $n1 = $number % 10;
    
    if ($number > 10 && $number < 20) {
        return $forms[2];
    }
    if ($n1 > 1 && $n1 < 5) {
        return $forms[1];
    }
    if ($n1 == 1) {
        return $forms[0];
    }
    return $forms[2];
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
