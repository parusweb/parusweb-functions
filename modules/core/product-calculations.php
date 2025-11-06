<?php
/**
 * ============================================================================
 * МОДУЛЬ: РАСЧЕТЫ ТОВАРОВ (КРИТИЧЕСКИЙ)
 * ============================================================================
 * 
 * Функции расчета площади, цен, множителей для товаров.
 * 
 * @package ParusWeb_Functions
 * @subpackage Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ИЗВЛЕЧЕНИЕ ПЛОЩАДИ ИЗ НАЗВАНИЯ ТОВАРА
// ============================================================================

/**
 * Извлечь площадь упаковки/листа из названия товара
 */
function extract_area_with_qty($title, $product_id = null) {
    $t = mb_strtolower($title, 'UTF-8');
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = str_replace("\xC2\xA0", ' ', $t);

    // Паттерны для извлечения площади
    $patterns = [
        '/\(?\s*(\d+(?:[.,-]\d+)?)\s*[мm](?:2|²)\b/u',
        '/\((\d+(?:[.,-]\d+)?)\s*[мm](?:2|²)\s*\/\s*\d+\s*(?:лист|упак|шт)\)/u',
        '/(\d+(?:[.,-]\d+)?)\s*[мm](?:2|²)\s*\/\s*(?:упак|лист|шт)\b/u',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $t, $m)) {
            $num = str_replace([',','-'], '.', $m[1]);
            return (float) $num;
        }
    }

    // Размеры ширина*длина*высота с упаковкой
    if (preg_match('/(\d+)\*(\d+)\*(\d+).*?(\d+)\s*штуп/u', $t, $m)) {
        $width_mm = intval($m[1]);
        $length_mm = intval($m[2]);
        $height_mm = intval($m[3]);
        $qty = intval($m[4]);
        
        $sizes = [$width_mm, $length_mm, $height_mm];
        rsort($sizes);
        $width = $sizes[0];
        $length = $sizes[1];
        
        if ($width > 0 && $length > 0) {
            $area_m2 = ($width / 1000) * ($length / 1000) * $qty;
            return round($area_m2, 3);
        }
    }

    if (preg_match('/(\d+)\s*шт\s*\/\s*уп|(\d+)\s*штуп/u', $t, $m)) {
        $qty = !empty($m[1]) ? intval($m[1]) : intval($m[2] ?? 1);
        if (preg_match_all('/(\d{2,4})[xх\/](\d{2,4})[xх\/](\d{2,4})/u', $t, $rows)) {
            $nums = array_map('intval', [$rows[1][0], $rows[2][0], $rows[3][0]]);
            rsort($nums);
            $width_mm  = $nums[0];
            $length_mm = $nums[1];
            if ($width_mm > 0 && $length_mm > 0) {
                $area_m2 = ($width_mm / 1000) * ($length_mm / 1000) * $qty;
                return round($area_m2, 3);
            }
        }
    }

    if (preg_match_all('/(\d{2,4})[xх\/](\d{2,4})[xх\/](\d{2,4})/u', $t, $rows)) {
        $nums = array_map('intval', [$rows[1][0], $rows[2][0], $rows[3][0]]);
        rsort($nums);
        $width_mm  = $nums[0];
        $length_mm = $nums[1];
        if ($width_mm > 0 && $length_mm > 0) {
            $area_m2 = ($width_mm / 1000) * ($length_mm / 1000);
            return round($area_m2, 3);
        }
    }

    // Попытка получить из атрибутов товара
    if ($product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $width = $product->get_attribute('pa_shirina') ?: $product->get_attribute('shirina');
            $length = $product->get_attribute('pa_dlina') ?: $product->get_attribute('dlina');
            
            if ($width && $length) {
                preg_match('/(\d+)/', $width, $width_match);
                preg_match('/(\d+)/', $length, $length_match);
                
                if (!empty($width_match[1]) && !empty($length_match[1])) {
                    $width_mm = intval($width_match[1]);
                    $length_mm = intval($length_match[1]);
                    $area_m2 = ($width_mm / 1000) * ($length_mm / 1000);
                    return round($area_m2, 3);
                }
            }
        }
    }

    return null;
}

// ============================================================================
// МНОЖИТЕЛИ ЦЕН
// ============================================================================

/**
 * Получить множитель цены для товара или категории
 */
function get_price_multiplier($product_id) {
    // Проверяем множитель товара
    $product_multiplier = get_post_meta($product_id, '_price_multiplier', true);
    if (!empty($product_multiplier) && is_numeric($product_multiplier)) {
        return floatval($product_multiplier);
    }
    
    // Проверяем категории
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (!is_wp_error($product_categories) && !empty($product_categories)) {
        foreach ($product_categories as $cat_id) {
            $cat_multiplier = get_term_meta($cat_id, 'category_price_multiplier', true);
            if (!empty($cat_multiplier) && is_numeric($cat_multiplier)) {
                return floatval($cat_multiplier);
            }
        }
    }
    
    return 1.0;
}

// ============================================================================
// ИЗВЛЕЧЕНИЕ РАЗМЕРОВ ИЗ НАЗВАНИЯ
// ============================================================================

/**
 * Извлечь размеры из названия товара (для старых калькуляторов)
 */
function extract_dimensions_from_title($title) {
    if (preg_match('/\d+\/(\d+)(?:\((\d+)\))?\/(\d+)-(\d+)/u', $title, $m)) {
        $widths = [$m[1]];
        if (!empty($m[2])) $widths[] = $m[2];
        $length_min = (int)$m[3];
        $length_max = (int)$m[4];
        return [
            'widths' => $widths, 
            'length_min' => $length_min, 
            'length_max' => $length_max
        ];
    }
    return null;
}

// ============================================================================
// РАСЧЕТ МИНИМАЛЬНОЙ ЦЕНЫ ДЛЯ ОТОБРАЖЕНИЯ
// ============================================================================

/**
 * Рассчитать минимальную цену товара для превью
 */
function calculate_min_price($product_id, $base_price) {
    $type = parusweb_get_product_type($product_id);
    
    switch ($type) {
        case 'partition_slat':
            return calculate_min_price_partition_slat($product_id, $base_price);
            
        case 'running_meter':
            return calculate_min_price_running_meter($product_id, $base_price);
            
        case 'square_meter':
            return calculate_min_price_square_meter($product_id, $base_price);
            
        case 'multiplier':
            return calculate_min_price_multiplier($product_id, $base_price);
            
        default:
            return $base_price;
    }
}

/**
 * Минимальная цена для реечных перегородок
 */
function calculate_min_price_partition_slat($product_id, $base_price_per_m2) {
    $min_width = 30; // мм
    $min_length = 3; // м
    $multiplier = get_price_multiplier($product_id);
    
    $min_area = ($min_width / 1000) * $min_length;
    return $base_price_per_m2 * $min_area * $multiplier;
}

/**
 * Минимальная цена для погонных метров
 */
function calculate_min_price_running_meter($product_id, $base_price_per_m) {
    $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true)) ?: 1;
    $multiplier = get_price_multiplier($product_id);
    
    return $base_price_per_m * $min_length * $multiplier;
}

/**
 * Минимальная цена для квадратных метров
 */
function calculate_min_price_square_meter($product_id, $base_price_per_m2) {
    $is_falsebalk = product_in_category($product_id, 266);
    $multiplier = get_price_multiplier($product_id);
    
    if ($is_falsebalk) {
        $min_width = floatval(get_post_meta($product_id, '_calc_width_min', true)) ?: 70;
        $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true)) ?: 1;
        $min_area = 2 * ($min_width / 1000) * $min_length;
    } else {
        $min_width = floatval(get_post_meta($product_id, '_calc_width_min', true)) ?: 100;
        $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true)) ?: 0.01;
        $min_area = ($min_width / 1000) * $min_length;
    }
    
    return $base_price_per_m2 * $min_area * $multiplier;
}

/**
 * Минимальная цена для товаров с множителем
 */
function calculate_min_price_multiplier($product_id, $base_price_per_m2) {
    $min_width = floatval(get_post_meta($product_id, '_calc_width_min', true));
    $min_length = floatval(get_post_meta($product_id, '_calc_length_min', true));
    $multiplier = get_price_multiplier($product_id);
    
    // Для штакетника фиксированная ширина
    if (has_term(273, 'product_cat', $product_id)) {
        $min_width = 95;
    }
    
    if (!$min_width || $min_width <= 0) $min_width = 100;
    if (!$min_length || $min_length <= 0) $min_length = 0.01;
    
    $min_area = ($min_width / 1000) * $min_length;
    $min_price = $base_price_per_m2 * $min_area * $multiplier;
    
    // Добавляем цену формы для штакетника
    if (has_term(273, 'product_cat', $product_id)) {
        $flat_shape_price = floatval(get_post_meta($product_id, '_shape_price_flat', true)) ?: 0;
        $min_price += $flat_shape_price;
    }
    
    return $min_price;
}

// ============================================================================
// ФОРМАТИРОВАНИЕ ЦЕН
// ============================================================================

/**
 * Форматировать цену с валютой
 */
function parusweb_format_price($price) {
    return wc_price($price);
}

/**
 * Форматировать число
 */
function parusweb_format_number($number, $decimals = 2) {
    return number_format($number, $decimals, ',', ' ');
}
