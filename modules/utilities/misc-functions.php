<?php
/**
 * Miscellaneous Functions Module
 * 
 * Различные вспомогательные функции, которые не вошли в другие модули:
 * - Функции обратной совместимости
 * - Склонение русских слов
 * - Форматирование данных
 * - Небольшие хелперы
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: СКЛОНЕНИЕ РУССКИХ СЛОВ
// ============================================================================

/**
 * Склонение русских существительных по числительному
 * 
 * @param int $n Число
 * @param array $forms Массив форм [1, 2, 5]: ['товар', 'товара', 'товаров']
 * @return string Правильная форма слова
 * 
 * @example parusweb_plural(1, ['товар', 'товара', 'товаров']) => 'товар'
 * @example parusweb_plural(2, ['товар', 'товара', 'товаров']) => 'товара'
 * @example parusweb_plural(5, ['товар', 'товара', 'товаров']) => 'товаров'
 */
function parusweb_plural($n, $forms) {
    $n = abs($n);
    $n %= 100;
    
    if ($n > 10 && $n < 20) {
        return $forms[2];
    }
    
    $n %= 10;
    
    if ($n === 1) {
        return $forms[0];
    }
    
    if ($n >= 2 && $n <= 4) {
        return $forms[1];
    }
    
    return $forms[2];
}

/**
 * Алиас для совместимости со старым кодом
 * 
 * @deprecated Используйте parusweb_plural()
 */
if (!function_exists('get_russian_plural_for_cart')) {
    function get_russian_plural_for_cart($number, $one, $two, $five) {
        $n = abs($number) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) return $five;
        if ($n1 > 1 && $n1 < 5) return $two;
        if ($n1 == 1) return $one;
        return $five;
    }
}
/**
 * Получить формы склонения для единицы измерения товара
 * 
 * @param int $product_id ID товара
 * @return array Массив форм ['единственное', 'двойственное', 'множественное']
 */
function parusweb_get_unit_forms($product_id) {
    // Проверяем категорию "Листовой материал"
    if (function_exists('is_leaf_category') && is_leaf_category($product_id)) {
        return ['лист', 'листа', 'листов'];
    }
    
    return ['упаковка', 'упаковки', 'упаковок'];
}

/**
 * Алиас для совместимости
 * 
 * @deprecated Используйте parusweb_get_unit_forms()
 */
if (!function_exists('get_unit_forms')) {
    function get_unit_forms($product_id) {
        return parusweb_get_unit_forms($product_id);
    }
}

/**
 * Получить единицу измерения в именительном падеже
 * 
 * @param int $product_id ID товара
 * @return string 'лист' или 'упаковка'
 */
function parusweb_get_unit_text($product_id) {
    $forms = parusweb_get_unit_forms($product_id);
    return $forms[0];
}

/**
 * Алиас для совместимости
 * 
 * @deprecated Используйте parusweb_get_unit_text()
 */
if (!function_exists('get_unit_text')) {
    function get_unit_text($product_id) {
        return parusweb_get_unit_text($product_id);
    }
}

// ============================================================================
// БЛОК 2: ФОРМАТИРОВАНИЕ ДАННЫХ
// ============================================================================

/**
 * Форматирование числа в русском формате (запятая, пробелы)
 * 
 * @param float $number Число для форматирования
 * @param int $decimals Количество знаков после запятой
 * @return string Отформатированное число
 */
if (!function_exists('parusweb_format_number')) {
    function parusweb_format_number($number, $decimals = 2) {
        return number_format($number, $decimals, ',', ' ');
    }
}

/**
 * Форматирование цены с валютой
 * 
 * @param float $price Цена
 * @return string HTML с отформатированной ценой
 */
if (!function_exists('parusweb_format_price')) {
    function parusweb_format_price($price) {
        return wc_price($price);
    }
}


/**
 * Форматирование размеров (мм → м)
 * 
 * @param float $mm Размер в миллиметрах
 * @param int $decimals Знаков после запятой
 * @return string Размер в метрах
 */
function parusweb_mm_to_m($mm, $decimals = 3) {
    return parusweb_format_number($mm / 1000, $decimals);
}

/**
 * Форматирование площади с единицей измерения
 * 
 * @param float $area Площадь в м²
 * @param int $decimals Знаков после запятой
 * @return string Отформатированная площадь с "м²"
 */
function parusweb_format_area($area, $decimals = 3) {
    return parusweb_format_number($area, $decimals) . ' м²';
}

/**
 * Форматирование веса с единицей измерения
 * 
 * @param float $weight Вес в граммах
 * @return string Отформатированный вес (г или кг)
 */
function parusweb_format_weight($weight) {
    if ($weight >= 1000) {
        return parusweb_format_number($weight / 1000, 2) . ' кг';
    }
    return parusweb_format_number($weight, 0) . ' г';
}

// ============================================================================
// БЛОК 3: РАБОТА С URL И ЗАПРОСАМИ
// ============================================================================

/**
 * Безопасное получение GET параметра
 * 
 * @param string $key Ключ параметра
 * @param mixed $default Значение по умолчанию
 * @return mixed Значение параметра или default
 */
function parusweb_get_param($key, $default = '') {
    return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : $default;
}

/**
 * Безопасное получение POST параметра
 * 
 * @param string $key Ключ параметра
 * @param mixed $default Значение по умолчанию
 * @return mixed Значение параметра или default
 */
function parusweb_post_param($key, $default = '') {
    return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default;
}

/**
 * Добавление/изменение параметра в URL
 * 
 * @param string $url URL
 * @param string $key Ключ параметра
 * @param string $value Значение параметра
 * @return string Модифицированный URL
 */
function parusweb_add_query_arg($key, $value, $url = '') {
    if (empty($url)) {
        $url = $_SERVER['REQUEST_URI'];
    }
    
    return add_query_arg($key, $value, $url);
}

// ============================================================================
// БЛОК 4: РАБОТА С МЕТА-ДАННЫМИ
// ============================================================================

/**
 * Безопасное получение post meta с fallback
 * 
 * @param int $post_id ID поста
 * @param string $key Ключ метаданных
 * @param mixed $default Значение по умолчанию
 * @return mixed Значение или default
 */
function parusweb_get_meta($post_id, $key, $default = '') {
    $value = get_post_meta($post_id, $key, true);
    return !empty($value) ? $value : $default;
}

/**
 * Безопасное получение term meta с fallback
 * 
 * @param int $term_id ID термина
 * @param string $key Ключ метаданных
 * @param mixed $default Значение по умолчанию
 * @return mixed Значение или default
 */
function parusweb_get_term_meta($term_id, $key, $default = '') {
    $value = get_term_meta($term_id, $key, true);
    return !empty($value) ? $value : $default;
}

/**
 * Получение числового meta с валидацией
 * 
 * @param int $post_id ID поста
 * @param string $key Ключ метаданных
 * @param float $default Значение по умолчанию
 * @return float Числовое значение
 */
function parusweb_get_numeric_meta($post_id, $key, $default = 0) {
    $value = get_post_meta($post_id, $key, true);
    return is_numeric($value) ? floatval($value) : $default;
}

// ============================================================================
// БЛОК 5: ПРОВЕРКИ И ВАЛИДАЦИЯ
// ============================================================================

/**
 * Проверка является ли запрос AJAX
 * 
 * @return bool
 */
function parusweb_is_ajax() {
    return defined('DOING_AJAX') && DOING_AJAX;
}

/**
 * Проверка является ли пользователь администратором
 * 
 * @return bool
 */
function parusweb_is_admin_user() {
    return current_user_can('manage_options');
}

/**
 * Проверка активности WooCommerce
 * 
 * @return bool
 */
function parusweb_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

/**
 * Проверка что мы на странице товара
 * 
 * @return bool
 */
function parusweb_is_product_page() {
    return is_product();
}

/**
 * Проверка что мы на странице категории
 * 
 * @return bool
 */
function parusweb_is_category_page() {
    return is_product_category();
}

/**
 * Проверка что мы в корзине или оформлении заказа
 * 
 * @return bool
 */
function parusweb_is_cart_or_checkout() {
    return is_cart() || is_checkout();
}

// ============================================================================
// БЛОК 6: ЛОГИРОВАНИЕ И ОТЛАДКА
// ============================================================================

/**
 * Логирование в файл отладки WordPress
 * 
 * @param mixed $message Сообщение для логирования
 * @param string $prefix Префикс сообщения
 */
function parusweb_log($message, $prefix = 'ParusWeb') {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    error_log("[{$prefix}] {$message}");
}

/**
 * Вывод отладочной информации для администраторов
 * 
 * @param mixed $data Данные для вывода
 * @param string $label Метка
 */
function parusweb_debug($data, $label = 'Debug') {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo "\n<!-- {$label}: ";
    
    if (is_array($data) || is_object($data)) {
        print_r($data);
    } else {
        echo $data;
    }
    
    echo " -->\n";
}

// ============================================================================
// БЛОК 7: РАЗНОЕ
// ============================================================================

/**
 * Получение ID текущего пользователя
 * 
 * @return int ID пользователя или 0
 */
function parusweb_get_current_user_id() {
    return get_current_user_id();
}

/**
 * Проверка что пользователь авторизован
 * 
 * @return bool
 */
function parusweb_is_user_logged_in() {
    return is_user_logged_in();
}

/**
 * Безопасное получение объекта товара
 * 
 * @param int|WC_Product $product ID товара или объект
 * @return WC_Product|null Объект товара или null
 */
function parusweb_get_product($product) {
    if ($product instanceof WC_Product) {
        return $product;
    }
    
    if (is_numeric($product)) {
        return wc_get_product($product);
    }
    
    return null;
}

/**
 * Транслитерация русского текста в латиницу
 * 
 * @param string $text Текст для транслитерации
 * @return string Транслитерированный текст
 */
function parusweb_transliterate($text) {
    $map = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
        'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
    );
    
    return strtr($text, $map);
}

/**
 * Генерация случайной строки
 * 
 * @param int $length Длина строки
 * @return string Случайная строка
 */
function parusweb_random_string($length = 10) {
    return wp_generate_password($length, false);
}

/**
 * Очистка строки от HTML тегов и пробелов
 * 
 * @param string $string Строка для очистки
 * @return string Очищенная строка
 */
function parusweb_clean_string($string) {
    $string = strip_tags($string);
    $string = trim($string);
    return $string;
}
