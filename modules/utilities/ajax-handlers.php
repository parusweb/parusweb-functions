<?php
/**
 * AJAX Handlers Module
 * 
 * Централизованные AJAX обработчики для различных функций плагина:
 * - Расчёты калькуляторов
 * - Получение данных товаров
 * - Обновление цен
 * - Валидация полей
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: AJAX РАСЧЁТЫ КАЛЬКУЛЯТОРОВ
// ============================================================================

/**
 * AJAX: Расчёт цены для калькулятора площади
 */
function parusweb_ajax_calculate_area_price() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $area = floatval($_POST['area'] ?? 0);
    $painting_service = sanitize_text_field($_POST['painting_service'] ?? '');
    
    if (!$product_id || !$area) {
        wp_send_json_error('Недостаточно данных');
    }
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Товар не найден');
    }
    
    // Базовая цена за м²
    $base_price = floatval($product->get_regular_price());
    
    // Множитель
    $multiplier = 1.0;
    if (function_exists('get_price_multiplier')) {
        $multiplier = get_price_multiplier($product_id);
    }
    
    // Расчёт
    $material_cost = $base_price * $area * $multiplier;
    $painting_cost = 0;
    
    // Добавляем покраску если выбрана
    if ($painting_service && function_exists('parusweb_get_painting_service_price')) {
        $painting_price_per_m2 = parusweb_get_painting_service_price($product_id, $painting_service);
        if ($painting_price_per_m2) {
            $painting_cost = $painting_price_per_m2 * $area;
        }
    }
    
    $total = $material_cost + $painting_cost;
    
    wp_send_json_success([
        'material_cost' => round($material_cost, 2),
        'painting_cost' => round($painting_cost, 2),
        'total_cost'    => round($total, 2),
        'area'          => round($area, 3),
        'multiplier'    => $multiplier
    ]);
}
add_action('wp_ajax_calculate_area_price', 'parusweb_ajax_calculate_area_price');
add_action('wp_ajax_nopriv_calculate_area_price', 'parusweb_ajax_calculate_area_price');

/**
 * AJAX: Расчёт цены для калькулятора размеров
 */
function parusweb_ajax_calculate_dimension_price() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $width = floatval($_POST['width'] ?? 0);
    $length = floatval($_POST['length'] ?? 0);
    
    if (!$product_id || !$width || !$length) {
        wp_send_json_error('Недостаточно данных');
    }
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Товар не найден');
    }
    
    // Конвертируем мм в м
    $width_m = $width / 1000;
    $area = $width_m * $length;
    
    // Базовая цена
    $base_price = floatval($product->get_regular_price());
    $multiplier = function_exists('get_price_multiplier') ? get_price_multiplier($product_id) : 1.0;
    
    $total = $base_price * $area * $multiplier;
    
    wp_send_json_success([
        'width'      => $width,
        'length'     => $length,
        'area'       => round($area, 3),
        'price'      => round($total, 2),
        'multiplier' => $multiplier
    ]);
}
add_action('wp_ajax_calculate_dimension_price', 'parusweb_ajax_calculate_dimension_price');
add_action('wp_ajax_nopriv_calculate_dimension_price', 'parusweb_ajax_calculate_dimension_price');

// ============================================================================
// БЛОК 2: AJAX ПОЛУЧЕНИЕ ДАННЫХ ТОВАРОВ
// ============================================================================

/**
 * AJAX: Получение данных товара для калькулятора
 */
function parusweb_ajax_get_product_data() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if (!$product_id) {
        wp_send_json_error('ID товара не указан');
    }
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Товар не найден');
    }
    
    // Собираем данные
    $data = [
        'id'           => $product_id,
        'name'         => $product->get_name(),
        'price'        => floatval($product->get_regular_price()),
        'sale_price'   => floatval($product->get_sale_price()),
        'multiplier'   => function_exists('get_price_multiplier') ? get_price_multiplier($product_id) : 1.0,
    ];
    
    // Настройки калькулятора
    $data['calculator'] = [
        'width_min'  => floatval(get_post_meta($product_id, '_calc_width_min', true)),
        'width_max'  => floatval(get_post_meta($product_id, '_calc_width_max', true)),
        'width_step' => floatval(get_post_meta($product_id, '_calc_width_step', true)) ?: 100,
        'length_min' => floatval(get_post_meta($product_id, '_calc_length_min', true)),
        'length_max' => floatval(get_post_meta($product_id, '_calc_length_max', true)),
        'length_step' => floatval(get_post_meta($product_id, '_calc_length_step', true)) ?: 0.01,
    ];
    
    // Услуги покраски
    if (function_exists('parusweb_get_painting_services_formatted')) {
        $data['painting_services'] = parusweb_get_painting_services_formatted($product_id);
    }
    
    wp_send_json_success($data);
}
add_action('wp_ajax_get_product_data', 'parusweb_ajax_get_product_data');
add_action('wp_ajax_nopriv_get_product_data', 'parusweb_ajax_get_product_data');

/**
 * AJAX: Получение доступных услуг покраски
 */
function parusweb_ajax_get_painting_services() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if (!$product_id) {
        wp_send_json_error('ID товара не указан');
    }
    
    if (!function_exists('parusweb_get_painting_services')) {
        wp_send_json_error('Функция недоступна');
    }
    
    $services = parusweb_get_painting_services($product_id);
    
    wp_send_json_success([
        'services' => $services,
        'has_services' => !empty($services)
    ]);
}
add_action('wp_ajax_get_painting_services', 'parusweb_ajax_get_painting_services');
add_action('wp_ajax_nopriv_get_painting_services', 'parusweb_ajax_get_painting_services');

// ============================================================================
// БЛОК 3: AJAX ВАЛИДАЦИЯ
// ============================================================================

/**
 * AJAX: Валидация размеров товара
 */
function parusweb_ajax_validate_dimensions() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $width = floatval($_POST['width'] ?? 0);
    $length = floatval($_POST['length'] ?? 0);
    
    if (!$product_id) {
        wp_send_json_error('ID товара не указан');
    }
    
    // Получаем ограничения
    $width_min = floatval(get_post_meta($product_id, '_calc_width_min', true));
    $width_max = floatval(get_post_meta($product_id, '_calc_width_max', true));
    $length_min = floatval(get_post_meta($product_id, '_calc_length_min', true));
    $length_max = floatval(get_post_meta($product_id, '_calc_length_max', true));
    
    $errors = [];
    
    // Валидация ширины
    if ($width_min && $width < $width_min) {
        $errors[] = "Минимальная ширина: {$width_min} мм";
    }
    if ($width_max && $width > $width_max) {
        $errors[] = "Максимальная ширина: {$width_max} мм";
    }
    
    // Валидация длины
    if ($length_min && $length < $length_min) {
        $errors[] = "Минимальная длина: {$length_min} м";
    }
    if ($length_max && $length > $length_max) {
        $errors[] = "Максимальная длина: {$length_max} м";
    }
    
    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Параметры вне допустимого диапазона',
            'errors' => $errors
        ]);
    }
    
    wp_send_json_success([
        'message' => 'Размеры валидны',
        'valid' => true
    ]);
}
add_action('wp_ajax_validate_dimensions', 'parusweb_ajax_validate_dimensions');
add_action('wp_ajax_nopriv_validate_dimensions', 'parusweb_ajax_validate_dimensions');

// ============================================================================
// БЛОК 4: AJAX ОБНОВЛЕНИЕ ЦЕНЫ
// ============================================================================

/**
 * AJAX: Получение актуальной цены товара
 */
function parusweb_ajax_get_current_price() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if (!$product_id) {
        wp_send_json_error('ID товара не указан');
    }
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Товар не найден');
    }
    
    wp_send_json_success([
        'price'        => floatval($product->get_price()),
        'regular_price' => floatval($product->get_regular_price()),
        'sale_price'    => floatval($product->get_sale_price()),
        'on_sale'       => $product->is_on_sale(),
        'formatted'     => wc_price($product->get_price())
    ]);
}
add_action('wp_ajax_get_current_price', 'parusweb_ajax_get_current_price');
add_action('wp_ajax_nopriv_get_current_price', 'parusweb_ajax_get_current_price');

// ============================================================================
// БЛОК 5: AJAX УТИЛИТЫ
// ============================================================================

/**
 * AJAX: Конвертация единиц измерения
 */
function parusweb_ajax_convert_units() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $value = floatval($_POST['value'] ?? 0);
    $from = sanitize_text_field($_POST['from'] ?? 'mm');
    $to = sanitize_text_field($_POST['to'] ?? 'm');
    
    $result = $value;
    
    // Конвертация
    if ($from === 'mm' && $to === 'm') {
        $result = $value / 1000;
    } elseif ($from === 'm' && $to === 'mm') {
        $result = $value * 1000;
    } elseif ($from === 'cm' && $to === 'm') {
        $result = $value / 100;
    } elseif ($from === 'm' && $to === 'cm') {
        $result = $value * 100;
    }
    
    wp_send_json_success([
        'original' => $value,
        'converted' => $result,
        'from' => $from,
        'to' => $to
    ]);
}
add_action('wp_ajax_convert_units', 'parusweb_ajax_convert_units');
add_action('wp_ajax_nopriv_convert_units', 'parusweb_ajax_convert_units');

/**
 * AJAX: Форматирование числа
 */
function parusweb_ajax_format_number() {
    check_ajax_referer('parusweb_calculator', 'nonce');
    
    $number = floatval($_POST['number'] ?? 0);
    $decimals = intval($_POST['decimals'] ?? 2);
    
    $formatted = number_format($number, $decimals, ',', ' ');
    
    wp_send_json_success([
        'original' => $number,
        'formatted' => $formatted
    ]);
}
add_action('wp_ajax_format_number', 'parusweb_ajax_format_number');
add_action('wp_ajax_nopriv_format_number', 'parusweb_ajax_format_number');

// ============================================================================
// БЛОК 6: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Генерация nonce для AJAX запросов
 */
function parusweb_get_ajax_nonce() {
    return wp_create_nonce('parusweb_calculator');
}

/**
 * Локализация скриптов с AJAX данными
 */
function parusweb_localize_ajax_scripts() {
    if (!is_product()) {
        return;
    }
    
    wp_localize_script('jquery', 'paruswebAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => parusweb_get_ajax_nonce(),
        'debug'   => defined('WP_DEBUG') && WP_DEBUG
    ]);
}
add_action('wp_enqueue_scripts', 'parusweb_localize_ajax_scripts');

/**
 * Логирование AJAX запросов (для отладки)
 */
function parusweb_log_ajax_request($action) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $data = [
        'action' => $action,
        'time'   => current_time('mysql'),
        'user'   => get_current_user_id(),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'post'   => $_POST
    ];
    
    error_log('[ParusWeb AJAX] ' . wp_json_encode($data));
}

/**
 * Стандартный ответ об ошибке
 */
function parusweb_ajax_error($message, $code = 'error', $data = []) {
    wp_send_json_error([
        'message' => $message,
        'code'    => $code,
        'data'    => $data
    ]);
}

/**
 * Стандартный успешный ответ
 */
function parusweb_ajax_success($data = [], $message = '') {
    $response = is_array($data) ? $data : ['data' => $data];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    wp_send_json_success($response);
}

// ============================================================================
// БЛОК 7: БЕЗОПАСНОСТЬ
// ============================================================================

/**
 * Проверка прав доступа для админ AJAX запросов
 */
function parusweb_check_admin_ajax_permission() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Недостаточно прав');
        wp_die();
    }
}

/**
 * Санитизация AJAX данных
 */
function parusweb_sanitize_ajax_data($data, $type = 'text') {
    switch ($type) {
        case 'int':
            return intval($data);
        case 'float':
            return floatval($data);
        case 'email':
            return sanitize_email($data);
        case 'url':
            return esc_url_raw($data);
        case 'html':
            return wp_kses_post($data);
        case 'text':
        default:
            return sanitize_text_field($data);
    }
}

/**
 * Валидация обязательных параметров
 */
function parusweb_validate_required_params($params = []) {
    foreach ($params as $param => $type) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            wp_send_json_error("Отсутствует обязательный параметр: {$param}");
            wp_die();
        }
    }
    
    return true;
}
