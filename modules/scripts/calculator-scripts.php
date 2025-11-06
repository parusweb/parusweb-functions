<?php
/**
 * Calculator Scripts Module
 * 
 * Управление подключением JavaScript для калькуляторов:
 * - Подключение основных скриптов калькуляторов
 * - Локализация данных для JavaScript
 * - Передача настроек товара в frontend
 * - Условное подключение скриптов
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: РЕГИСТРАЦИЯ СКРИПТОВ
// ============================================================================

/**
 * Регистрация JavaScript файлов калькуляторов
 */
function parusweb_register_calculator_scripts() {
    // Основной скрипт калькулятора (если есть отдельный файл)
    wp_register_script(
        'parusweb-calculator',
        PARUSWEB_PLUGIN_URL . 'assets/js/calculator.js',
        ['jquery'],
        PARUSWEB_VERSION,
        true
    );
    
    // Скрипт обновления цен
    wp_register_script(
        'parusweb-price-update',
        PARUSWEB_PLUGIN_URL . 'assets/js/price-update.js',
        ['jquery', 'parusweb-calculator'],
        PARUSWEB_VERSION,
        true
    );
    
    // Скрипт калькулятора доставки
    wp_register_script(
        'parusweb-delivery-calc',
        get_stylesheet_directory_uri() . '/js/delivery-calc.js',
        ['jquery'],
        '1.0',
        true
    );
    
    // Общие утилиты
    wp_register_script(
        'parusweb-utils',
        PARUSWEB_PLUGIN_URL . 'assets/js/utils.js',
        ['jquery'],
        PARUSWEB_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'parusweb_register_calculator_scripts', 5);

// ============================================================================
// БЛОК 2: ПОДКЛЮЧЕНИЕ СКРИПТОВ НА СТРАНИЦЕ ТОВАРА
// ============================================================================

/**
 * Подключение скриптов на странице товара
 */
function parusweb_enqueue_product_calculator_scripts() {
    // Только на странице товара
    if (!is_product()) {
        return;
    }
    
global $product;

// Попытка получить объект через WC
if (!$product || !($product instanceof WC_Product)) {
    if (is_product() && is_numeric(get_the_ID())) {
        $product = wc_get_product(get_the_ID());
    } else {
        return; // Не страница товара или невалидный объект
    }
}

$product_id = $product->get_id();
    
    // Определяем нужен ли калькулятор
    $calculator_type = 'none';
    
    if (function_exists('get_calculator_type')) {
        $calculator_type = get_calculator_type($product_id);
    }
    
    // Если калькулятор не нужен, не подключаем скрипты
    if ($calculator_type === 'none') {
        return;
    }
    
    // Подключаем основные скрипты
    wp_enqueue_script('parusweb-calculator');
    wp_enqueue_script('parusweb-utils');
    
    // Локализуем данные для JavaScript
    parusweb_localize_calculator_data($product_id, $calculator_type);
}
add_action('wp_enqueue_scripts', 'parusweb_enqueue_product_calculator_scripts', 20);

// ============================================================================
// БЛОК 3: ЛОКАЛИЗАЦИЯ ДАННЫХ
// ============================================================================

/**
 * Локализация данных калькулятора для JavaScript
 * 
 * @param int $product_id ID товара
 * @param string $calculator_type Тип калькулятора
 */
function parusweb_localize_calculator_data($product_id, $calculator_type) {
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return;
    }
    
    // Базовые данные товара
    $data = [
        'productId'       => $product_id,
        'calculatorType'  => $calculator_type,
        'basePrice'       => floatval($product->get_regular_price()),
        'salePrice'       => floatval($product->get_sale_price()),
        'currentPrice'    => floatval($product->get_price()),
        'priceMultiplier' => function_exists('get_price_multiplier') ? get_price_multiplier($product_id) : 1.0,
        'currency'        => get_woocommerce_currency_symbol(),
    ];
    
    // Настройки калькулятора (мин/макс/шаг)
    $data['settings'] = [
        'widthMin'   => floatval(get_post_meta($product_id, '_calc_width_min', true)) ?: 70,
        'widthMax'   => floatval(get_post_meta($product_id, '_calc_width_max', true)) ?: 3000,
        'widthStep'  => floatval(get_post_meta($product_id, '_calc_width_step', true)) ?: 100,
        'lengthMin'  => floatval(get_post_meta($product_id, '_calc_length_min', true)) ?: 1,
        'lengthMax'  => floatval(get_post_meta($product_id, '_calc_length_max', true)) ?: 6,
        'lengthStep' => floatval(get_post_meta($product_id, '_calc_length_step', true)) ?: 0.01,
    ];
    
    // Услуги покраски
    if (function_exists('parusweb_get_painting_services_formatted')) {
        $data['paintingServices'] = parusweb_get_painting_services_formatted($product_id);
    } else {
        $data['paintingServices'] = [];
    }
    
    // Извлекаем площадь из названия
    if (function_exists('extract_area_with_qty')) {
        $data['packArea'] = extract_area_with_qty($product->get_name(), $product_id);
    }
    
    // Извлекаем размеры из названия
    if (function_exists('extract_dimensions_from_title')) {
        $dims = extract_dimensions_from_title($product->get_name());
        $data['dimensions'] = [
            'width'  => $dims['width'] ?? 0,
            'length' => $dims['length'] ?? 0,
            'height' => $dims['height'] ?? 0,
        ];
    }
    
    // Категории товара
    $data['categories'] = [
        'isArea'           => function_exists('is_area_category') ? is_area_category($product_id) : false,
        'isMultiplier'     => function_exists('is_multiplier_category') ? is_multiplier_category($product_id) : false,
        'isFalsebalk'      => function_exists('is_falsebalk_category') ? is_falsebalk_category($product_id) : false,
        'isSquareMeter'    => function_exists('is_square_meter_category') ? is_square_meter_category($product_id) : false,
        'isRunningMeter'   => function_exists('is_running_meter_category') ? is_running_meter_category($product_id) : false,
        'isPartitionSlat'  => function_exists('is_partition_slat_category') ? is_partition_slat_category($product_id) : false,
        'isLiter'          => function_exists('is_in_liter_categories') ? is_in_liter_categories($product_id) : false,
        'isShtaketnik'     => function_exists('is_shtaketnik_category') ? is_shtaketnik_category($product_id) : false,
    ];
    
    // AJAX
    $data['ajax'] = [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('parusweb_calculator'),
    ];
    
    // Отладка
    $data['debug'] = defined('WP_DEBUG') && WP_DEBUG;
    
    // Локализуем для JavaScript
    wp_localize_script('parusweb-calculator', 'paruswebCalculator', $data);
}

// ============================================================================
// БЛОК 4: INLINE СКРИПТЫ
// ============================================================================

/**
 * Добавление inline JavaScript для инициализации калькулятора
 */
function parusweb_add_calculator_inline_script() {
    if (!is_product()) {
        return;
    }
    
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $calculator_type = function_exists('get_calculator_type') ? get_calculator_type($product_id) : 'none';
    
    if ($calculator_type === 'none') {
        return;
    }
    
    ?>
    <script>
    // Автоматическая инициализация калькулятора при загрузке
    jQuery(document).ready(function($) {
        if (typeof paruswebCalculator !== 'undefined') {
            console.log('[ParusWeb] Калькулятор готов:', paruswebCalculator.calculatorType);
            
            // Можно добавить дополнительную логику инициализации
            $(document).trigger('parusweb_calculator_ready', [paruswebCalculator]);
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_add_calculator_inline_script', 10);

// ============================================================================
// БЛОК 5: УСЛОВНОЕ ПОДКЛЮЧЕНИЕ СКРИПТОВ
// ============================================================================

/**
 * Подключение скриптов в корзине/оформлении заказа
 */
function parusweb_enqueue_cart_scripts() {
    if (!is_cart() && !is_checkout()) {
        return;
    }
    
    wp_enqueue_script('parusweb-utils');
    
    // Данные для корзины
    wp_localize_script('parusweb-utils', 'paruswebCart', [
        'ajax' => [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parusweb_cart'),
        ],
        'currency' => get_woocommerce_currency_symbol(),
    ]);
}
add_action('wp_enqueue_scripts', 'parusweb_enqueue_cart_scripts', 20);

/**
 * Подключение скриптов на страницах каталога
 */
function parusweb_enqueue_catalog_scripts() {
    if (!is_shop() && !is_product_category() && !is_product_tag()) {
        return;
    }
    
    wp_enqueue_script('parusweb-utils');
    
    wp_localize_script('parusweb-utils', 'paruswebCatalog', [
        'ajax' => [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('parusweb_catalog'),
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'parusweb_enqueue_catalog_scripts', 20);

// ============================================================================
// БЛОК 6: СТИЛИ ДЛЯ КАЛЬКУЛЯТОРОВ
// ============================================================================

/**
 * Подключение CSS для калькуляторов
 */
function parusweb_enqueue_calculator_styles() {
    if (!is_product()) {
        return;
    }
    
    // Если есть отдельный CSS файл для калькуляторов
    wp_enqueue_style(
        'parusweb-calculator',
        PARUSWEB_PLUGIN_URL . 'assets/css/calculator.css',
        [],
        PARUSWEB_VERSION
    );
}
add_action('wp_enqueue_scripts', 'parusweb_enqueue_calculator_styles');

// ============================================================================
// БЛОК 7: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Проверка нужен ли калькулятор для товара
 * 
 * @param int $product_id ID товара
 * @return bool
 */
function parusweb_product_needs_calculator($product_id) {
    if (!function_exists('get_calculator_type')) {
        return false;
    }
    
    $type = get_calculator_type($product_id);
    
    return $type !== 'none';
}

/**
 * Получение списка скриптов калькулятора
 * 
 * @return array Массив зарегистрированных скриптов
 */
function parusweb_get_calculator_scripts() {
    return [
        'parusweb-calculator',
        'parusweb-price-update',
        'parusweb-delivery-calc',
        'parusweb-utils',
    ];
}

/**
 * Принудительное подключение скриптов калькулятора
 * Полезно для кастомных шаблонов
 * 
 * @param int $product_id ID товара
 */
function parusweb_force_enqueue_calculator($product_id) {
    if (!parusweb_product_needs_calculator($product_id)) {
        return;
    }
    
    $calculator_type = get_calculator_type($product_id);
    
    wp_enqueue_script('parusweb-calculator');
    wp_enqueue_script('parusweb-utils');
    wp_enqueue_style('parusweb-calculator');
    
    parusweb_localize_calculator_data($product_id, $calculator_type);
}

/**
 * Деактивация скриптов калькулятора
 * Полезно для оптимизации на страницах где они не нужны
 */
function parusweb_dequeue_calculator_scripts() {
    $scripts = parusweb_get_calculator_scripts();
    
    foreach ($scripts as $handle) {
        wp_dequeue_script($handle);
    }
    
    wp_dequeue_style('parusweb-calculator');
}

// ============================================================================
// БЛОК 8: ОТЛАДКА
// ============================================================================

/**
 * Вывод отладочной информации о подключенных скриптах (для админов)
 */
function parusweb_debug_calculator_scripts() {
    if (!current_user_can('manage_options') || !is_product()) {
        return;
    }
    
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $calculator_type = function_exists('get_calculator_type') ? get_calculator_type($product_id) : 'none';
    
    echo "\n<!-- ParusWeb Calculator Debug -->\n";
    echo "<!-- Product ID: {$product_id} -->\n";
    echo "<!-- Calculator Type: {$calculator_type} -->\n";
    
    $scripts = parusweb_get_calculator_scripts();
    echo "<!-- Registered Scripts: " . implode(', ', $scripts) . " -->\n";
    
    global $wp_scripts;
    $enqueued = [];
    foreach ($scripts as $handle) {
        if (isset($wp_scripts->registered[$handle])) {
            $enqueued[] = $handle . ' (' . ($wp_scripts->registered[$handle]->src ?? 'inline') . ')';
        }
    }
    
    echo "<!-- Enqueued Scripts: " . implode(', ', $enqueued) . " -->\n";
    echo "<!-- /ParusWeb Calculator Debug -->\n\n";
}
add_action('wp_footer', 'parusweb_debug_calculator_scripts', 999);

// ============================================================================
// БЛОК 9: ОПТИМИЗАЦИЯ
// ============================================================================

/**
 * Удаление скриптов калькулятора на ненужных страницах
 */
function parusweb_optimize_calculator_scripts() {
    // На административных страницах не нужны
    if (is_admin()) {
        return;
    }
    
    // На страницах без калькулятора удаляем
    if (!is_product() && !is_cart() && !is_checkout()) {
        parusweb_dequeue_calculator_scripts();
    }
}
add_action('wp_enqueue_scripts', 'parusweb_optimize_calculator_scripts', 999);

/**
 * Добавление атрибута defer к скриптам для ускорения загрузки
 * 
 * @param string $tag HTML тег скрипта
 * @param string $handle Handle скрипта
 * @return string Модифицированный тег
 */
function parusweb_add_defer_to_calculator_scripts($tag, $handle) {
    $defer_scripts = parusweb_get_calculator_scripts();
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'parusweb_add_defer_to_calculator_scripts', 10, 2);
