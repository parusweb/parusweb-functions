<?php
/**
 * Delivery Calculator Module
 * 
 * Калькулятор стоимости доставки с интеграцией Яндекс.Карт.
 * Функциональность:
 * - Интерактивная карта с выбором адреса доставки
 * - Автокомплит адресов через Яндекс API
 * - Расчёт расстояния от склада до точки доставки
 * - Дифференцированные тарифы (легкие/тяжелые грузы)
 * - Минимальные суммы доставки
 * - Интеграция с корзиной и оформлением заказа WooCommerce
 * - Сохранение данных доставки в заказе
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ПОДКЛЮЧЕНИЕ СКРИПТОВ И СТИЛЕЙ
// ============================================================================

/**
 * Подключение скриптов Яндекс.Карт и калькулятора доставки
 */
function parusweb_enqueue_delivery_scripts() {
    if (!is_checkout() && !is_cart()) {
        return;
    }
    
    // API ключ Яндекс.Карт
    $api_key = '81c72bf5-a635-4fb5-8939-e6b31aa52ffe';
    
    // Подключаем Яндекс.Карты
    wp_enqueue_script(
        'yandex-maps',
        "https://api-maps.yandex.ru/2.1/?apikey={$api_key}&lang=ru_RU",
        [],
        null,
        true
    );
    
    // Подключаем скрипт калькулятора (должен быть в assets/js/)
    wp_enqueue_script(
        'delivery-calc',
        get_stylesheet_directory_uri() . '/js/delivery-calc.js',
        ['jquery', 'yandex-maps'],
        '1.3',
        true
    );
    
    // Получаем вес корзины
    $cart_weight = WC()->cart ? WC()->cart->get_cart_contents_weight() : 0;
    
    // Передаём настройки в JavaScript
    wp_localize_script('delivery-calc', 'deliveryVars', [
        'ajaxurl'     => admin_url('admin-ajax.php'),
        'basePoint'   => 'г. Санкт-Петербург, Выборгское шоссе 369к6',
        'rateLight'   => 200,  // руб/км для легких грузов (до 1500г)
        'rateHeavy'   => 250,  // руб/км для тяжелых грузов (свыше 1500г)
        'minLight'    => 6000, // минимальная стоимость для легких грузов
        'minHeavy'    => 7500, // минимальная стоимость для тяжелых грузов
        'minDistance' => 30,   // минимальное расстояние для применения минималки (км)
        'cartWeight'  => $cart_weight,
        'apiKey'      => $api_key
    ]);
}
add_action('wp_enqueue_scripts', 'parusweb_enqueue_delivery_scripts');

// ============================================================================
// БЛОК 2: ВЫВОД ИНТЕРФЕЙСА КАЛЬКУЛЯТОРА
// ============================================================================

/**
 * Вывод интерфейса калькулятора доставки на странице оформления заказа
 */
function parusweb_render_delivery_calculator() {
    ?>
    <style>
    .woocommerce-delivery-calc {
        background: #f8f9fa;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .woocommerce-delivery-calc h3 {
        margin: 0 0 15px 0;
        color: #495057;
        font-size: 18px;
    }
    #delivery-map {
        width: 100%;
        height: 400px;
        margin-bottom: 15px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    #ymaps-address {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        box-sizing: border-box;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }
    #ymaps-address:focus {
        outline: none;
        border-color: #0066cc;
        box-shadow: 0 0 0 2px rgba(0,102,204,0.2);
    }
    
    /* Стили для автокомплита */
    .ymaps-suggest-container {
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        width: 100%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 4px;
        margin-top: 1px;
    }
    
    .ymaps-suggest-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }
    
    .ymaps-suggest-item:last-child {
        border-bottom: none;
    }
    
    .ymaps-suggest-item:hover,
    .ymaps-suggest-item.active {
        background-color: #f5f5f5;
    }
    
    .ymaps-suggest-item.active {
        background-color: #007bff !important;
        color: white !important;
    }
    
    @media(max-width:768px) {
        #delivery-map { height: 300px; }
        .woocommerce-delivery-calc { padding: 15px; margin-bottom: 15px; }
    }
    
    #delivery-result {
        font-weight: normal;
        margin-top: 10px;
    }
    
    .delivery-instructions {
        background: #e7f3ff;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
        color: #0066cc;
    }
    </style>

    <div class="woocommerce-delivery-calc">
        <h3>📍 Расчет стоимости доставки</h3>
        <div class="delivery-instructions">
            💡 <strong>Как рассчитать доставку:</strong><br>
            1️⃣ Введите адрес в поле ниже и выберите из подсказок<br>
            2️⃣ Или просто кликните по нужной точке на карте<br>
            3️⃣ Стоимость рассчитается автоматически
        </div>
        <p>
            <label for="ymaps-address">
                <strong>🏠 Адрес доставки:</strong>
                <input 
                    type="text" 
                    id="ymaps-address" 
                    placeholder="Введите адрес доставки (например: Невский проспект, 1)"
                >
            </label>
        </p>
        <div id="delivery-map"></div>
        <div id="delivery-result"></div>
    </div>
    <?php
}
add_action('woocommerce_before_checkout_billing_form', 'parusweb_render_delivery_calculator');

// ============================================================================
// БЛОК 3: AJAX ОБРАБОТЧИКИ
// ============================================================================

/**
 * AJAX: Сохранение стоимости доставки в сессию
 */
function parusweb_set_delivery_cost() {
    if (!isset($_POST['cost'])) {
        wp_send_json_error('Не указана стоимость');
        wp_die();
    }
    
    $cost = round(floatval($_POST['cost'])); // округляем до целых
    WC()->session->set('custom_delivery_cost', $cost);
    
    // Сохраняем расстояние, если передано
    if (!empty($_POST['distance'])) {
        WC()->session->set('delivery_distance', floatval($_POST['distance']));
    }
    
    // Очищаем уведомления
    if (function_exists('wc_clear_notices')) {
        wc_clear_notices();
    }
    
    // Очищаем кэши WooCommerce
    wp_cache_flush();
    WC_Cache_Helper::get_transient_version('shipping', true);
    delete_transient('wc_shipping_method_count');
    
    // Сбрасываем пользовательский кэш доставки
    $packages_hash = 'wc_ship_' . md5( 
        json_encode(WC()->cart->get_cart_for_session()) . 
        WC()->customer->get_shipping_country() . 
        WC()->customer->get_shipping_state() . 
        WC()->customer->get_shipping_postcode() . 
        WC()->customer->get_shipping_city()
    );
    wp_cache_delete($packages_hash, 'shipping_zones');
    
    // Пересчет корзины
    if (WC()->cart) {
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
    }
    
    wp_send_json_success([
        'cost'    => $cost,
        'message' => 'Стоимость доставки обновлена'
    ]);
    
    wp_die();
}
add_action('wp_ajax_set_delivery_cost', 'parusweb_set_delivery_cost');
add_action('wp_ajax_nopriv_set_delivery_cost', 'parusweb_set_delivery_cost');

/**
 * AJAX: Очистка стоимости доставки из сессии
 */
function parusweb_clear_delivery_cost() {
    WC()->session->__unset('custom_delivery_cost');
    WC()->session->__unset('delivery_distance');
    
    // Очищаем кэши
    WC_Cache_Helper::get_transient_version('shipping', true);
    delete_transient('wc_shipping_method_count');
    
    if (WC()->cart) {
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
    }
    
    wp_send_json_success(['message' => 'Стоимость доставки очищена']);
    wp_die();
}
add_action('wp_ajax_clear_delivery_cost', 'parusweb_clear_delivery_cost');
add_action('wp_ajax_nopriv_clear_delivery_cost', 'parusweb_clear_delivery_cost');

// ============================================================================
// БЛОК 4: КАСТОМНЫЙ МЕТОД ДОСТАВКИ WOOCOMMERCE
// ============================================================================

/**
 * Инициализация кастомного метода доставки
 */
function parusweb_init_delivery_method() {
    if (class_exists('WC_Custom_Delivery_Method')) {
        return;
    }
    
    /**
     * Кастомный метод доставки для отображения рассчитанной стоимости
     */
    class WC_Custom_Delivery_Method extends WC_Shipping_Method {
        
        /**
         * Конструктор
         */
        public function __construct($instance_id = 0) {
            $this->id = 'custom_delivery';
            $this->instance_id = absint($instance_id);
            $this->method_title = __('Доставка по карте', 'parusweb-functions');
            $this->method_description = __('Расчет доставки по карте', 'parusweb-functions');
            $this->supports = array(
                'shipping-zones',
                'instance-settings',
            );
            $this->enabled = 'yes';
            $this->title = 'Доставка по карте';
            $this->init();
        }
        
        /**
         * Инициализация настроек
         */
        public function init() {
            $this->init_form_fields();
            $this->init_settings();
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }
        
        /**
         * Поля настроек в админке
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Включить/Отключить', 'parusweb-functions'),
                    'type'        => 'checkbox',
                    'description' => __('Включить этот метод доставки.', 'parusweb-functions'),
                    'default'     => 'yes'
                ),
                'title' => array(
                    'title'       => __('Название', 'parusweb-functions'),
                    'type'        => 'text',
                    'description' => __('Название метода доставки.', 'parusweb-functions'),
                    'default'     => __('Доставка по карте', 'parusweb-functions'),
                    'desc_tip'    => true,
                )
            );
        }
        
        /**
         * Расчёт стоимости доставки
         */
        public function calculate_shipping($package = array()) {
            $delivery_cost = WC()->session->get('custom_delivery_cost');
            
            if ($delivery_cost && $delivery_cost > 0) {
                $rate = array(
                    'id'       => $this->id . ':' . $this->instance_id,
                    'label'    => $this->title,
                    'cost'     => $delivery_cost,
                    'calc_tax' => 'per_item'
                );
                
                $this->add_rate($rate);
            }
        }
    }
}
add_action('woocommerce_shipping_init', 'parusweb_init_delivery_method');

/**
 * Добавление метода доставки в список доступных
 */
function parusweb_add_delivery_method($methods) {
    $methods['custom_delivery'] = 'WC_Custom_Delivery_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'parusweb_add_delivery_method');

/**
 * Принудительное обновление методов доставки при изменении стоимости
 */
function parusweb_force_shipping_update($post_data) {
    if (WC()->session->get('custom_delivery_cost')) {
        WC_Cache_Helper::get_transient_version('shipping', true);
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
    }
}
add_action('woocommerce_checkout_update_order_review', 'parusweb_force_shipping_update');

// ============================================================================
// БЛОК 5: СОХРАНЕНИЕ ДАННЫХ ДОСТАВКИ В ЗАКАЗЕ
// ============================================================================

/**
 * Сохранение информации о доставке в метаданных заказа
 */
function parusweb_save_delivery_info($order_id) {
    $delivery_cost = WC()->session->get('custom_delivery_cost');
    $delivery_distance = WC()->session->get('delivery_distance');
    
    if ($delivery_cost) {
        update_post_meta($order_id, '_delivery_cost', $delivery_cost);
    }
    
    if ($delivery_distance) {
        update_post_meta($order_id, '_delivery_distance', $delivery_distance);
    }
    
    // Очищаем сессию после сохранения заказа
    WC()->session->__unset('custom_delivery_cost');
    WC()->session->__unset('delivery_distance');
}
add_action('woocommerce_checkout_update_order_meta', 'parusweb_save_delivery_info');

/**
 * Отображение информации о доставке в админке заказов
 */
function parusweb_display_delivery_info($order) {
    $delivery_cost = get_post_meta($order->get_id(), '_delivery_cost', true);
    $delivery_distance = get_post_meta($order->get_id(), '_delivery_distance', true);
    
    if (!$delivery_cost && !$delivery_distance) {
        return;
    }
    
    echo '<h3>Информация о доставке</h3>';
    
    if ($delivery_distance) {
        echo '<p><strong>Расстояние:</strong> ' . number_format($delivery_distance, 1) . ' км</p>';
    }
    
    if ($delivery_cost) {
        echo '<p><strong>Стоимость доставки:</strong> ' . number_format($delivery_cost, 0) . ' ₽</p>';
    }
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'parusweb_display_delivery_info');

// ============================================================================
// БЛОК 6: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Получение настроек тарифов доставки
 * 
 * @return array Массив с настройками
 */
function parusweb_get_delivery_rates() {
    return array(
        'rate_light'   => 200,  // руб/км для легких грузов (до 1500г)
        'rate_heavy'   => 250,  // руб/км для тяжелых грузов (свыше 1500г)
        'min_light'    => 6000, // минимальная стоимость для легких грузов
        'min_heavy'    => 7500, // минимальная стоимость для тяжелых грузов
        'min_distance' => 30,   // минимальное расстояние для применения минималки (км)
        'base_point'   => 'г. Санкт-Петербург, Выборгское шоссе 369к6',
    );
}

/**
 * Расчёт стоимости доставки на основе расстояния и веса
 * 
 * @param float $distance Расстояние в км
 * @param float $weight Вес в граммах
 * @return float Стоимость доставки
 */
function parusweb_calculate_delivery_cost($distance, $weight = 0) {
    $rates = parusweb_get_delivery_rates();
    
    // Определяем тариф в зависимости от веса
    $is_heavy = $weight > 1500;
    $rate = $is_heavy ? $rates['rate_heavy'] : $rates['rate_light'];
    $min_cost = $is_heavy ? $rates['min_heavy'] : $rates['min_light'];
    
    // Базовый расчёт
    $calculated_cost = $distance * $rate;
    
    // Применяем минимальную стоимость для коротких расстояний
    if ($distance <= $rates['min_distance']) {
        $calculated_cost = max($calculated_cost, $min_cost);
    }
    
    return round($calculated_cost);
}
