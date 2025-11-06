<?php
/**
 * ============================================================================
 * PARUSWEB CHILD THEME - ГЛАВНЫЙ ФАЙЛ FUNCTIONS.PHP
 * ============================================================================
 * 
 * Этот файл служит точкой входа для всех функций темы.
 * Основная логика вынесена в плагин ParusWeb Functions.
 * 
 * @package ParusWeb-Child
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ПОДКЛЮЧЕНИЕ ПЛАГИНА PARUSWEB FUNCTIONS
// ============================================================================

// Загрузка Briks компонентов
include get_stylesheet_directory() . '/inc/briks-loader.php';

// ============================================================================
// БАЗОВАЯ НАСТРОЙКА ТЕМЫ
// ============================================================================

/**
 * Поддержка WebP изображений
 */
add_filter('mime_types', function($mimes) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

/**
 * Удаление префикса "Архивы" из заголовков
 */
add_filter('wpseo_title', function($title) {
    return preg_replace('/^\s*Архивы[:\s\-\—]*/u', '', $title);
}, 10);

/**
 * Замена текста "Subtotal" на "Стоимость"
 */
add_filter('gettext', function($translated, $text, $domain) {
    if ($domain === 'woocommerce') {
        if ($text === 'Subtotal' || $text === 'Подытог') {
            return 'Стоимость';
        }
    }
    return $translated;
}, 10, 3);

// ============================================================================
// ИНТЕГРАЦИЯ С PARUSWEB FUNCTIONS PLUGIN
// ============================================================================

/**
 * Проверка активации плагина ParusWeb Functions
 */
function parusweb_check_plugin() {
    if (!class_exists('ParusWeb_Functions')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>ParusWeb Child Theme:</strong> Требуется активация плагина "ParusWeb Functions" для корректной работы темы.</p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}
add_action('after_setup_theme', 'parusweb_check_plugin');

// ============================================================================
// ПОДКЛЮЧЕНИЕ ДОПОЛНИТЕЛЬНЫХ МОДУЛЕЙ ТЕМЫ
// ============================================================================

// Схемы покраски (связано с ACF и калькуляторами)
require_once get_stylesheet_directory() . '/inc/pm-paint-schemes.php';

// Описания покраски
require_once get_stylesheet_directory() . '/inc/paint-description.php';

// ============================================================================
// СОВМЕСТИМОСТЬ С LEGACY КОДОМ
// ============================================================================

/**
 * Эти функции могут использоваться в шаблонах темы
 * Оставлены для обратной совместимости
 */

if (!function_exists('get_price_multiplier')) {
    function get_price_multiplier($product_id) {
        $product_multiplier = get_post_meta($product_id, '_price_multiplier', true);
        if (!empty($product_multiplier) && is_numeric($product_multiplier)) {
            return floatval($product_multiplier);
        }
        
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
}

if (!function_exists('extract_area_with_qty')) {
    function extract_area_with_qty($title, $product_id = null) {
        $t = mb_strtolower($title, 'UTF-8');
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_replace("\xC2\xA0", ' ', $t);

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

        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $width = $product->get_attribute('pa_shirina') ?: $product->get_attribute('shirina');
                $length = $product->get_attribute('pa_dlina') ?: $product->get_attribute('dlina');
                
                if ($width && $length) {
                    preg_match('/(\d+)/', $width, $width_match);
                    preg_match('/(\d+)/', $length, $length_match);
                    
                    if ($width_match[1] && $length_match[1]) {
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
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================