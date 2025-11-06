<?php
/**
 * ============================================================================
 * МОДУЛЬ: ИЗМЕНЕНИЕ ДАННЫХ КОРЗИНЫ
 * ============================================================================
 * 
 * Пересчет цен, обновление количества и модификация данных в корзине.
 * 
 * @package ParusWeb_Functions
 * @subpackage Cart
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ПЕРЕСЧЕТ ЦЕН В КОРЗИНЕ
// ============================================================================

/**
 * Пересчет цены товара в корзине на основе данных калькулятора
 */
function parusweb_recalculate_cart_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        
        // Калькулятор площади
        if (isset($cart_item['custom_area_calc'])) {
            $data = $cart_item['custom_area_calc'];
            $price = floatval($data['price']);
            
            if (isset($data['painting_service']) && !empty($data['painting_service'])) {
                $painting_price = floatval($data['painting_service']['total_price']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        // Калькулятор размеров
        if (isset($cart_item['custom_dimensions'])) {
            $data = $cart_item['custom_dimensions'];
            $price = floatval($data['price']);
            
            if (isset($data['painting_service']) && !empty($data['painting_service'])) {
                $painting_price = floatval($data['painting_service']['total_price']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        // Калькулятор множителя
        if (isset($cart_item['custom_multiplier_calc'])) {
            $data = $cart_item['custom_multiplier_calc'];
            $price = floatval($data['total_price']);
            $product->set_price($price);
        }
        
        // Калькулятор погонных метров
        if (isset($cart_item['custom_running_meter_calc'])) {
            $data = $cart_item['custom_running_meter_calc'];
            $price = floatval($data['grand_total']);
            $product->set_price($price);
        }
        
        // Калькулятор квадратных метров
        if (isset($cart_item['custom_square_meter_calc'])) {
            $data = $cart_item['custom_square_meter_calc'];
            $price = floatval($data['grand_total']);
            $product->set_price($price);
        }
        
        // Калькулятор реечных перегородок
        if (isset($cart_item['custom_partition_slat_calc'])) {
            $data = $cart_item['custom_partition_slat_calc'];
            $price = floatval($data['total_price']);
            $product->set_price($price);
        }
        
        // Покупка из карточки
        if (isset($cart_item['card_pack_purchase'])) {
            $data = $cart_item['card_pack_purchase'];
            $price = floatval($data['total_price']);
            
            if (isset($data['painting_service']) && !empty($data['painting_service'])) {
                $painting_price = floatval($data['painting_service']['total_price']);
                $price += $painting_price;
            }
            
            $product->set_price($price);
        }
        
        // Товары за литр (ЛКМ)
        if (isset($cart_item['tara'])) {
            $base_price = floatval($product->get_regular_price());
            $volume = floatval($cart_item['tara']);
            $price = $base_price * $volume;
            
            // Скидка 10% при объеме >= 9 литров
            if ($volume >= 9) {
                $price *= 0.9;
            }
            
            $product->set_price($price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'parusweb_recalculate_cart_prices', 10, 1);

// ============================================================================
// КОРРЕКТИРОВКА КОЛИЧЕСТВА
// ============================================================================

/**
 * Установка правильного количества при добавлении в корзину
 */
function parusweb_adjust_cart_quantity($quantity, $product_id) {
    // Для товаров с калькулятором количество всегда 1
    if (isset($_POST['custom_area']) || 
        isset($_POST['custom_width']) || 
        isset($_POST['custom_multiplier']) ||
        isset($_POST['custom_rm_length']) ||
        isset($_POST['custom_sq_width'])) {
        return 1;
    }
    
    return $quantity;
}
add_filter('woocommerce_add_to_cart_quantity', 'parusweb_adjust_cart_quantity', 10, 2);

/**
 * Корректировка количества после добавления
 */
function parusweb_correct_cart_quantity($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Если товар добавлен через калькулятор, устанавливаем количество = 1
    if (isset($cart_item_data['custom_area_calc']) || 
        isset($cart_item_data['custom_dimensions']) ||
        isset($cart_item_data['custom_multiplier_calc']) ||
        isset($cart_item_data['custom_running_meter_calc']) ||
        isset($cart_item_data['custom_square_meter_calc']) ||
        isset($cart_item_data['custom_partition_slat_calc'])) {
        
        WC()->cart->set_quantity($cart_item_key, 1, false);
    }
}
add_action('woocommerce_add_to_cart', 'parusweb_correct_cart_quantity', 10, 6);

// ============================================================================
// БЛОКИРОВКА ИЗМЕНЕНИЯ КОЛИЧЕСТВА
// ============================================================================

/**
 * Блокировка поля количества для товаров с калькулятором
 */
function parusweb_lock_calculator_quantity($product_quantity, $cart_item_key, $cart_item) {
    if (isset($cart_item['custom_area_calc']) || 
        isset($cart_item['custom_dimensions']) ||
        isset($cart_item['custom_multiplier_calc']) ||
        isset($cart_item['custom_running_meter_calc']) ||
        isset($cart_item['custom_square_meter_calc']) ||
        isset($cart_item['custom_partition_slat_calc'])) {
        
        return sprintf(
            '<div class="quantity">
                <input type="number" class="input-text qty text" value="1" readonly disabled style="background: #f5f5f5; cursor: not-allowed;" />
                <input type="hidden" name="cart[%s][qty]" value="1" />
            </div>',
            $cart_item_key
        );
    }
    
    return $product_quantity;
}
add_filter('woocommerce_cart_item_quantity', 'parusweb_lock_calculator_quantity', 10, 3);

// ============================================================================
// УДАЛЕНИЕ УСЛУГ ПОКРАСКИ ИЗ НАЗВАНИЯ
// ============================================================================

/**
 * Удаление цен услуг покраски из отображаемых названий
 */
function parusweb_remove_price_from_service_name($item_data, $cart_item) {
    foreach ($item_data as $key => $data) {
        if ($data['key'] === 'Услуга покраски') {
            $value = $data['value'];
            // Удаляем паттерн вида "... (123.45 руб)"
            $value = preg_replace('/\s*\([0-9.,\s]+\s*руб\)\s*$/', '', $value);
            $item_data[$key]['value'] = $value;
        }
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'parusweb_remove_price_from_service_name', 15, 2);

// ============================================================================
// ОБНОВЛЕНИЕ ЦЕНЫ ПРИ ИЗМЕНЕНИИ КОЛИЧЕСТВА
// ============================================================================

/**
 * Обновление цены при изменении количества стандартных товаров
 */
function parusweb_update_price_on_quantity_change($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        // Только для стандартных товаров (без калькуляторов)
        if (isset($cart_item['standard_pack_purchase'])) {
            $data = $cart_item['standard_pack_purchase'];
            $base_price = floatval($data['total_price']);
            
            $cart_item['data']->set_price($base_price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'parusweb_update_price_on_quantity_change', 11, 1);

// ============================================================================
// ОЧИСТКА КОРЗИНЫ ОТ ДУБЛИКАТОВ
// ============================================================================

/**
 * Предотвращение добавления дубликатов товаров с калькулятором
 */
function parusweb_prevent_calculator_duplicates($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Если товар добавлен через калькулятор, не ищем дубликаты
    // Каждый расчет - уникальный товар
}
// Этот хук не используем, так как каждый расчет калькулятора уникален

// ============================================================================
// СОХРАНЕНИЕ ДАННЫХ ПРИ ВОССТАНОВЛЕНИИ КОРЗИНЫ
// ============================================================================

/**
 * Сохранение метаданных калькулятора в сессии
 */
function parusweb_persist_calculator_data($cart_item, $values) {
    if (isset($values['custom_area_calc'])) {
        $cart_item['custom_area_calc'] = $values['custom_area_calc'];
    }
    
    if (isset($values['custom_dimensions'])) {
        $cart_item['custom_dimensions'] = $values['custom_dimensions'];
    }
    
    if (isset($values['custom_multiplier_calc'])) {
        $cart_item['custom_multiplier_calc'] = $values['custom_multiplier_calc'];
    }
    
    if (isset($values['custom_running_meter_calc'])) {
        $cart_item['custom_running_meter_calc'] = $values['custom_running_meter_calc'];
    }
    
    if (isset($values['custom_square_meter_calc'])) {
        $cart_item['custom_square_meter_calc'] = $values['custom_square_meter_calc'];
    }
    
    if (isset($values['custom_partition_slat_calc'])) {
        $cart_item['custom_partition_slat_calc'] = $values['custom_partition_slat_calc'];
    }
    
    if (isset($values['card_pack_purchase'])) {
        $cart_item['card_pack_purchase'] = $values['card_pack_purchase'];
    }
    
    if (isset($values['tara'])) {
        $cart_item['tara'] = $values['tara'];
    }
    
    return $cart_item;
}
add_filter('woocommerce_get_cart_item_from_session', 'parusweb_persist_calculator_data', 10, 2);

// ============================================================================
// ВАЛИДАЦИЯ ДАННЫХ КОРЗИНЫ
// ============================================================================

/**
 * Валидация данных перед добавлением в корзину
 */
function parusweb_validate_cart_data($passed, $product_id, $quantity) {
    // Проверка данных калькулятора площади
    if (isset($_POST['custom_area'])) {
        $area = floatval($_POST['custom_area']);
        if ($area <= 0) {
            wc_add_notice('Площадь должна быть больше нуля', 'error');
            return false;
        }
    }
    
    // Проверка данных калькулятора размеров
    if (isset($_POST['custom_width']) && isset($_POST['custom_length'])) {
        $width = floatval($_POST['custom_width']);
        $length = floatval($_POST['custom_length']);
        
        if ($width <= 0 || $length <= 0) {
            wc_add_notice('Ширина и длина должны быть больше нуля', 'error');
            return false;
        }
    }
    
    // Проверка множителя
    if (isset($_POST['custom_multiplier'])) {
        $multiplier = floatval($_POST['custom_multiplier']);
        if ($multiplier <= 0) {
            wc_add_notice('Множитель должен быть больше нуля', 'error');
            return false;
        }
    }
    
    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'parusweb_validate_cart_data', 10, 3);

// ============================================================================
// ОКРУГЛЕНИЕ ЦЕН
// ============================================================================

/**
 * Округление цен в корзине
 */
function parusweb_round_cart_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    
    foreach ($cart->get_cart() as $cart_item) {
        $price = $cart_item['data']->get_price();
        $rounded_price = round($price, 2);
        $cart_item['data']->set_price($rounded_price);
    }
}
add_action('woocommerce_before_calculate_totals', 'parusweb_round_cart_prices', 99, 1);

// ============================================================================
// МИНИМАЛЬНАЯ СУММА ЗАКАЗА
// ============================================================================

/**
 * Установка минимальной суммы заказа
 */
function parusweb_minimum_order_amount() {
    $minimum = 1000; // Минимальная сумма в рублях
    $current = WC()->cart->subtotal;
    
    if ($current < $minimum) {
        wc_add_notice(
            sprintf(
                'Минимальная сумма заказа — %s. Текущая сумма: %s',
                wc_price($minimum),
                wc_price($current)
            ),
            'error'
        );
        
        return false;
    }
    
    return true;
}
// Раскомментируйте при необходимости:
// add_action('woocommerce_check_cart_items', 'parusweb_minimum_order_amount');

// ============================================================================
// УВЕДОМЛЕНИЯ О СПЕЦИАЛЬНЫХ УСЛОВИЯХ
// ============================================================================

/**
 * Уведомление о скидке на большие объемы ЛКМ
 */
function parusweb_notify_volume_discount() {
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['tara']) && $cart_item['tara'] >= 9) {
            wc_add_notice('🎉 Применена скидка 10% на объем 9+ литров!', 'success');
            break;
        }
    }
}
add_action('woocommerce_before_cart', 'parusweb_notify_volume_discount');
