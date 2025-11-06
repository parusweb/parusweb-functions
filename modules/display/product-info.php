<?php

if(1==0) {
/**
 * ============================================================================
 * МОДУЛЬ: ОТОБРАЖЕНИЕ ИНФОРМАЦИИ О ТОВАРЕ
 * ============================================================================
 * 
 * Отображение площади упаковки, бейджей типа товара и единиц измерения.
 * 
 * @package ParusWeb_Functions
 * @subpackage Display
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ИНФОРМАЦИЯ О ПЛОЩАДИ УПАКОВКИ
// ============================================================================

/**
 * Вывод информации о площади упаковки/листа
 */
function parusweb_display_area_info() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) return;
    
    $pack_area = extract_area_with_qty($product->get_name(), $product_id);
    if (!$pack_area) return;
    
    $unit_text = get_unit_type_label($product_id);
    
    echo '<div class="product-area-info" style="margin: 15px 0; padding: 12px; background: #f5f5f5; border-radius: 6px;">';
    echo '<span style="font-size: 14px; color: #666;">Площадь в 1 ' . esc_html($unit_text) . ':</span> ';
    echo '<strong style="font-size: 16px; color: #333;">' . number_format($pack_area, 2, ',', ' ') . ' м²</strong>';
    echo '</div>';
}
add_action('woocommerce_before_add_to_cart_button', 'parusweb_display_area_info', 15);

// ============================================================================
// БЕЙДЖИ ТИПА ТОВАРА
// ============================================================================

/**
 * Отображение бейджей типа товара в каталоге
 */
function parusweb_display_product_badges() {
    global $product;
    $product_id = $product->get_id();
    
    $badges = [];
    
    if (is_partition_slat_category($product_id)) {
        $badges[] = ['text' => 'Реечные перегородки', 'color' => '#8e44ad'];
    }
    
    if (is_running_meter_category($product_id)) {
        $badges[] = ['text' => 'Погонные метры', 'color' => '#2980b9'];
    }
    
    if (is_square_meter_category($product_id)) {
        $badges[] = ['text' => 'Квадратные метры', 'color' => '#27ae60'];
    }
    
    if (is_in_multiplier_categories($product_id)) {
        $badges[] = ['text' => 'С множителем', 'color' => '#e67e22'];
    }
    
    if (is_in_target_categories($product_id)) {
        $pack_area = extract_area_with_qty($product->get_name(), $product_id);
        if ($pack_area) {
            $unit_label = get_unit_type_label($product_id);
            $badges[] = ['text' => number_format($pack_area, 2) . ' м² / ' . $unit_label, 'color' => '#16a085'];
        }
    }
    
    if (!empty($badges)) {
        echo '<div class="product-badges" style="margin: 8px 0;">';
        foreach ($badges as $badge) {
            echo '<span class="product-badge" style="display: inline-block; padding: 4px 10px; margin: 2px 4px 2px 0; background: ' . esc_attr($badge['color']) . '; color: white; font-size: 11px; border-radius: 3px; font-weight: 600;">';
            echo esc_html($badge['text']);
            echo '</span>';
        }
        echo '</div>';
    }
}
add_action('woocommerce_after_shop_loop_item_title', 'parusweb_display_product_badges', 5);

// ============================================================================
// ЕДИНИЦЫ ИЗМЕРЕНИЯ
// ============================================================================

/**
 * Изменение текста кнопки "Добавить в корзину" с учетом единиц измерения
 */
function parusweb_modify_add_to_cart_text($text, $product) {
    if (!$product) return $text;
    
    $product_id = $product->get_id();
    $unit = parusweb_get_product_unit($product_id);
    
    if ($unit && $unit !== 'шт') {
        if (is_product()) {
            return 'Добавить в корзину';
        } else {
            return 'Выбрать';
        }
    }
    
    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'parusweb_modify_add_to_cart_text', 20, 2);
add_filter('woocommerce_product_single_add_to_cart_text', 'parusweb_modify_add_to_cart_text', 20, 2);

/**
 * Отображение единиц измерения в поле количества
 */
function parusweb_display_quantity_unit() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    $unit = parusweb_get_product_unit($product_id);
    
    if ($unit && $unit !== 'шт') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var unit = '<?php echo esc_js($unit); ?>';
            var $qtyInput = $('.quantity input.qty');
            
            if ($qtyInput.length && !$qtyInput.next('.qty-unit').length) {
                $qtyInput.after('<span class="qty-unit" style="margin-left: 8px; color: #666; font-size: 14px;">' + unit + '</span>');
            }
        });
        </script>
        <?php
    }
}
add_action('woocommerce_after_add_to_cart_quantity', 'parusweb_display_quantity_unit');

// ============================================================================
// ИНФОРМАЦИЯ О ЛИСТОВЫХ МАТЕРИАЛАХ
// ============================================================================

/**
 * Дополнительная информация для листовых материалов
 */
function parusweb_display_leaf_material_info() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_leaf_category($product_id)) return;
    
    echo '<div class="leaf-material-notice" style="margin: 15px 0; padding: 12px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">';
    echo '<p style="margin: 0; font-size: 14px; color: #1976d2;">';
    echo '<span class="dashicons dashicons-info" style="font-size: 18px; vertical-align: middle;"></span> ';
    echo '<strong>Листовой материал:</strong> цена указана за 1 лист';
    echo '</p>';
    echo '</div>';
}
add_action('woocommerce_before_add_to_cart_form', 'parusweb_display_leaf_material_info', 25);

// ============================================================================
// ДОПОЛНИТЕЛЬНЫЕ ПОДСКАЗКИ
// ============================================================================

/**
 * Подсказка для товаров с калькулятором площади
 */
function parusweb_display_calculator_hint() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_in_target_categories($product_id)) return;
    
    $pack_area = extract_area_with_qty($product->get_name(), $product_id);
    if (!$pack_area) return;
    
    echo '<div class="calculator-hint" style="margin: 10px 0; padding: 10px; background: #fff3cd; border-radius: 4px;">';
    echo '<p style="margin: 0; font-size: 13px; color: #856404;">';
    echo '<span class="dashicons dashicons-calculator" style="font-size: 16px; vertical-align: middle;"></span> ';
    echo 'Используйте калькулятор ниже для точного расчета нужного количества';
    echo '</p>';
    echo '</div>';
}
add_action('woocommerce_before_add_to_cart_button', 'parusweb_display_calculator_hint', 5);

/**
 * Информация о погонных метрах
 */
function parusweb_display_running_meter_info() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_running_meter_category($product_id)) return;
    
    echo '<div class="running-meter-info" style="margin: 15px 0; padding: 12px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">';
    echo '<p style="margin: 0; font-size: 14px; color: #2e7d32;">';
    echo '<span class="dashicons dashicons-admin-site" style="font-size: 18px; vertical-align: middle;"></span> ';
    echo '<strong>Расчет за погонные метры:</strong> укажите нужную длину';
    echo '</p>';
    echo '</div>';
}
add_action('woocommerce_before_add_to_cart_form', 'parusweb_display_running_meter_info', 25);

/**
 * Информация о квадратных метрах
 */
function parusweb_display_square_meter_info() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    if (!is_square_meter_category($product_id)) return;
    
    echo '<div class="square-meter-info" style="margin: 15px 0; padding: 12px; background: #f3e5f5; border-left: 4px solid #9c27b0; border-radius: 4px;">';
    echo '<p style="margin: 0; font-size: 14px; color: #6a1b9a;">';
    echo '<span class="dashicons dashicons-grid-view" style="font-size: 18px; vertical-align: middle;"></span> ';
    echo '<strong>Расчет за квадратные метры:</strong> укажите площадь';
    echo '</p>';
    echo '</div>';
}
add_action('woocommerce_before_add_to_cart_form', 'parusweb_display_square_meter_info', 25);



}