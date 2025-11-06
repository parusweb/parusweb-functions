<?php
/**
 * ============================================================================
 * МОДУЛЬ: ПРОВЕРКА КАТЕГОРИЙ (КРИТИЧЕСКИЙ)
 * ============================================================================
 * 
 * Базовые функции для определения типа товара по категориям.
 * Этот модуль является ядром системы и не может быть отключен.
 * 
 * @package ParusWeb_Functions
 * @subpackage Core
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ПРОВЕРКА КАТЕГОРИЙ ТОВАРОВ
// ============================================================================

/**
 * Проверка принадлежности к целевым категориям (пиломатериалы + листовые)
 */
function is_in_target_categories($product_id) {
    return is_in_painting_categories($product_id);
}

/**
 * Проверка категорий для покраски
 */
function is_in_painting_categories($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = array_merge(
        range(87, 93),      // Пиломатериалы
        [190, 191, 127, 94], // Листовые
        range(265, 271)      // Столярные изделия
    );
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Проверка категорий столярки (с множителем)
 */
function is_in_multiplier_categories($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = [265, 266, 267, 268, 270, 271, 273];
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Проверка категорий за квадратные метры
 */
function is_square_meter_category($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = [266, 270, 268];
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Проверка категорий за погонные метры
 */
function is_running_meter_category($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = [267];
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Проверка категории реечных перегородок
 */
function is_partition_slat_category($product_id) {
    return has_term(271, 'product_cat', $product_id);
}

/**
 * Проверка категорий ЛКМ (за литр)
 */
function is_in_liter_categories($product_id) {
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    if (is_wp_error($product_categories) || empty($product_categories)) return false;
    
    $target_categories = range(81, 86);
    
    foreach ($product_categories as $cat_id) {
        if (in_array($cat_id, $target_categories)) return true;
        foreach ($target_categories as $target_cat_id) {
            if (cat_is_ancestor_of($target_cat_id, $cat_id)) return true;
        }
    }
    return false;
}

/**
 * Универсальная функция проверки принадлежности к категории (с учетом иерархии)
 */
function product_in_category($product_id, $category_id) {
    $terms = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    if (is_wp_error($terms) || empty($terms)) return false;
    
    if (in_array($category_id, $terms)) return true;
    
    foreach ($terms as $term_id) {
        if (term_is_ancestor_of($category_id, $term_id, 'product_cat')) {
            return true;
        }
    }
    
    return false;
}

// ============================================================================
// ОПРЕДЕЛЕНИЕ ТИПОВ ТОВАРОВ
// ============================================================================

/**
 * Определить тип товара по категориям
 * 
 * @return string|false Тип товара или false
 */
function parusweb_get_product_type($product_id) {
    if (is_partition_slat_category($product_id)) return 'partition_slat';
    if (is_running_meter_category($product_id)) return 'running_meter';
    if (is_square_meter_category($product_id)) return 'square_meter';
    if (is_in_multiplier_categories($product_id)) return 'multiplier';
    if (is_in_liter_categories($product_id)) return 'liter';
    if (is_in_target_categories($product_id)) return 'target';
    return false;
}

/**
 * Получить название единицы измерения товара
 */
function parusweb_get_product_unit($product_id) {
    $type = parusweb_get_product_type($product_id);
    
    $units = [
        'partition_slat' => 'шт',
        'running_meter' => 'пог. м',
        'square_meter' => 'м²',
        'multiplier' => 'шт',
        'liter' => 'л',
        'target' => 'упак'
    ];
    
    return $units[$type] ?? 'шт';
}

/**
 * Проверка - является ли товар листовым материалом
 */
function is_leaf_category($product_id) {
    $leaf_parent_id = 190;
    $leaf_children = [191, 127, 94];
    $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
    
    return has_term($leaf_ids, 'product_cat', $product_id);
}

/**
 * Получить тип единицы (лист/упаковка) для отображения
 */
function get_unit_type_label($product_id) {
    return is_leaf_category($product_id) ? 'лист' : 'упаковка';
}

/**
 * Получить формы склонения единицы
 */
function get_unit_forms($product_id) {
    return is_leaf_category($product_id) 
        ? ['лист', 'листа', 'листов'] 
        : ['упаковка', 'упаковки', 'упаковок'];
}

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Склонение русских существительных
 */
function get_russian_plural_for_cart($n, $forms) {
    $n = abs($n);
    $n %= 100;
    if ($n > 10 && $n < 20) return $forms[2];
    $n %= 10;
    if ($n === 1) return $forms[0];
    if ($n >= 2 && $n <= 4) return $forms[1];
    return $forms[2];
}
