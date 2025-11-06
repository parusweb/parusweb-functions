<?php
/**
 * Painting Services Module
 * 
 * Функционал услуг покраски с трёхуровневой иерархией настроек:
 * 1. Индивидуальные настройки товара (высший приоритет)
 * 2. Настройки категории (средний приоритет)
 * 3. Глобальные настройки (по умолчанию)
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: РЕГИСТРАЦИЯ ACF ПОЛЕЙ
// ============================================================================

/**
 * Регистрация полей ACF для услуг покраски
 * Создаёт поля для товаров, категорий и глобальных настроек
 */
function parusweb_register_painting_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    // -----------------------------------------
    // 1. Поля для категорий товаров
    // -----------------------------------------
    acf_add_local_field_group(array(
        'key' => 'group_painting_services_category',
        'title' => 'Услуги покраски для категории',
        'fields' => array(
            array(
                'key' => 'field_dop_uslugi_category',
                'label' => 'Доступные услуги покраски',
                'name' => 'dop_uslugi',
                'type' => 'repeater',
                'instructions' => 'Настройте доступные виды покраски для этой категории товаров',
                'required' => 0,
                'collapsed' => 'field_name_usluga_category',
                'min' => 0,
                'max' => 0,
                'layout' => 'table',
                'button_label' => 'Добавить услугу покраски',
                'sub_fields' => array(
                    array(
                        'key' => 'field_name_usluga_category',
                        'label' => 'Название услуги',
                        'name' => 'name_usluga',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array('width' => '70'),
                        'placeholder' => 'Например: Покраска натуральным маслом',
                    ),
                    array(
                        'key' => 'field_price_usluga_category',
                        'label' => 'Цена (руб/м²)',
                        'name' => 'price_usluga',
                        'type' => 'number',
                        'required' => 1,
                        'wrapper' => array('width' => '30'),
                        'placeholder' => '650',
                        'append' => 'руб/м²',
                        'min' => 0,
                        'step' => 50,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'product_cat',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ));
    
    // -----------------------------------------
    // 2. Поля для отдельных товаров (переопределение)
    // -----------------------------------------
    acf_add_local_field_group(array(
        'key' => 'group_painting_services_product',
        'title' => 'Индивидуальные услуги покраски',
        'fields' => array(
            array(
                'key' => 'field_use_individual_services',
                'label' => 'Использовать индивидуальные услуги',
                'name' => 'use_individual_services',
                'type' => 'true_false',
                'instructions' => 'Включите, если хотите настроить услуги покраски индивидуально для этого товара, игнорируя настройки категории',
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_dop_uslugi_product',
                'label' => 'Индивидуальные услуги покраски',
                'name' => 'dop_uslugi',
                'type' => 'repeater',
                'instructions' => 'Настройте индивидуальные услуги покраски для этого товара',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_use_individual_services',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
                'collapsed' => 'field_name_usluga_product',
                'min' => 0,
                'max' => 0,
                'layout' => 'table',
                'button_label' => 'Добавить услугу покраски',
                'sub_fields' => array(
                    array(
                        'key' => 'field_name_usluga_product',
                        'label' => 'Название услуги',
                        'name' => 'name_usluga',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array('width' => '70'),
                        'placeholder' => 'Например: Покраска натуральным маслом',
                    ),
                    array(
                        'key' => 'field_price_usluga_product',
                        'label' => 'Цена (руб/м²)',
                        'name' => 'price_usluga',
                        'type' => 'number',
                        'required' => 1,
                        'wrapper' => array('width' => '30'),
                        'placeholder' => '650',
                        'append' => 'руб/м²',
                        'min' => 0,
                        'step' => 50,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'product',
                ),
            ),
        ),
        'menu_order' => 20,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ));
    
    // -----------------------------------------
    // 3. Глобальные настройки (fallback)
    // -----------------------------------------
    acf_add_local_field_group(array(
        'key' => 'group_global_painting_services',
        'title' => 'Глобальные услуги покраски',
        'fields' => array(
            array(
                'key' => 'field_dop_uslugi_global',
                'label' => 'Услуги покраски по умолчанию',
                'name' => 'global_dop_uslugi',
                'type' => 'repeater',
                'instructions' => 'Услуги покраски по умолчанию (используются, если не настроены для категории или товара)',
                'collapsed' => 'field_name_usluga_global',
                'min' => 0,
                'max' => 0,
                'layout' => 'table',
                'button_label' => 'Добавить услугу покраски',
                'sub_fields' => array(
                    array(
                        'key' => 'field_name_usluga_global',
                        'label' => 'Название услуги',
                        'name' => 'name_usluga',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array('width' => '70'),
                        'placeholder' => 'Например: Покраска натуральным маслом',
                    ),
                    array(
                        'key' => 'field_price_usluga_global',
                        'label' => 'Цена (руб/м²)',
                        'name' => 'price_usluga',
                        'type' => 'number',
                        'required' => 1,
                        'wrapper' => array('width' => '30'),
                        'placeholder' => '650',
                        'append' => 'руб/м²',
                        'min' => 0,
                        'step' => 50,
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'theme-general-settings',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ));
}
add_action('acf/init', 'parusweb_register_painting_acf_fields');

// ============================================================================
// БЛОК 2: СТРАНИЦА НАСТРОЕК
// ============================================================================

/**
 * Создание страницы настроек для глобальных услуг покраски
 */
function parusweb_create_painting_options_page() {
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page(array(
            'page_title' => 'Настройки услуг покраски',
            'menu_title' => 'Услуги покраски',
            'menu_slug' => 'theme-general-settings',
            'capability' => 'edit_posts',
            'icon_url' => 'dashicons-art',
            'position' => 30,
        ));
    }
}
add_action('acf/init', 'parusweb_create_painting_options_page');

// ============================================================================
// БЛОК 3: ПОЛУЧЕНИЕ УСЛУГ ПОКРАСКИ
// ============================================================================

/**
 * Получение доступных услуг покраски для товара
 * Использует трёхуровневую иерархию: товар → категория → глобально
 * 
 * @param int $product_id ID товара
 * @return array Массив услуг покраски
 */
function parusweb_get_painting_services($product_id) {
    // Уровень 1: Проверяем индивидуальные настройки товара
    $use_individual = get_field('use_individual_services', $product_id);
    if ($use_individual) {
        $services = get_field('dop_uslugi', $product_id);
        if (!empty($services)) {
            return $services;
        }
    }
    
    // Уровень 2: Получаем услуги из категорий товара (приоритет более конкретным)
    $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'all']);
    if (!is_wp_error($product_categories) && !empty($product_categories)) {
        
        // Сортируем категории по глубине вложенности (от более конкретных к общим)
        usort($product_categories, function($a, $b) {
            $depth_a = count(get_ancestors($a->term_id, 'product_cat'));
            $depth_b = count(get_ancestors($b->term_id, 'product_cat'));
            return $depth_b - $depth_a;
        });
        
        // Ищем услуги, начиная с самых конкретных категорий
        foreach ($product_categories as $category) {
            $services = get_field('dop_uslugi', 'product_cat_' . $category->term_id);
            if (!empty($services)) {
                return $services;
            }
        }
    }
    
    // Уровень 3: Используем глобальные настройки
    $global_services = get_field('global_dop_uslugi', 'option');
    if (!empty($global_services)) {
        return $global_services;
    }
    
    // Возвращаем пустой массив, если ничего не настроено
    return [];
}

/**
 * Получение услуг покраски в формате для калькуляторов
 * Преобразует ACF формат в удобный массив для использования
 * 
 * @param int $product_id ID товара
 * @return array Ассоциативный массив услуг ['key' => ['name' => ..., 'price' => ...]]
 */
function parusweb_get_painting_services_formatted($product_id) {
    $acf_services = parusweb_get_painting_services($product_id);
    $formatted_services = [];
    
    foreach ($acf_services as $service) {
        if (empty($service['name_usluga'])) {
            continue;
        }
        
        $key = 'service_' . sanitize_title($service['name_usluga']);
        $formatted_services[$key] = [
            'name' => $service['name_usluga'],
            'price' => floatval($service['price_usluga'] ?? 0)
        ];
    }
    
    return $formatted_services;
}

/**
 * Алиас для совместимости со старым кодом
 * 
 * @deprecated Используйте parusweb_get_painting_services_formatted()
 */
function get_available_painting_services_by_material($product_id) {
    return parusweb_get_painting_services_formatted($product_id);
}

/**
 * Алиас для совместимости со старым кодом
 * 
 * @deprecated Используйте parusweb_get_painting_services()
 */
function get_acf_painting_services($product_id) {
    return parusweb_get_painting_services($product_id);
}

// ============================================================================
// БЛОК 4: ПРЕДЗАПОЛНЕНИЕ УСЛУГ ПО УМОЛЧАНИЮ
// ============================================================================

/**
 * Функция для предзаполнения услуг покраски по умолчанию
 * Вызывается вручную или при активации плагина
 */
function parusweb_populate_default_painting_services() {
    $default_services = [
        ['name_usluga' => 'Покраска натуральным маслом', 'price_usluga' => 1700],
        ['name_usluga' => 'Покраска Воском', 'price_usluga' => 650],
        ['name_usluga' => 'Покраска Укрывная', 'price_usluga' => 650],
        ['name_usluga' => 'Покраска Гидромаслом', 'price_usluga' => 1050],
        ['name_usluga' => 'Покраска Лаком', 'price_usluga' => 650],
        ['name_usluga' => 'Покраска Лазурью', 'price_usluga' => 650],
        ['name_usluga' => 'Покраска Винтаж', 'price_usluga' => 1050],
        ['name_usluga' => 'Покраска Пропиткой', 'price_usluga' => 650],
    ];
    
    update_field('global_dop_uslugi', $default_services, 'option');
    
    return true;
}

// ============================================================================
// БЛОК 5: ИНТЕГРАЦИЯ С КАЛЬКУЛЯТОРАМИ
// ============================================================================

/**
 * Проверка доступности услуг покраски для товара
 * 
 * @param int $product_id ID товара
 * @return bool true если услуги доступны
 */
function parusweb_has_painting_services($product_id) {
    $services = parusweb_get_painting_services($product_id);
    return !empty($services);
}

/**
 * Получение цены конкретной услуги покраски
 * 
 * @param int $product_id ID товара
 * @param string $service_name Название услуги
 * @return float|null Цена услуги или null если не найдена
 */
function parusweb_get_painting_service_price($product_id, $service_name) {
    $services = parusweb_get_painting_services($product_id);
    
    foreach ($services as $service) {
        if ($service['name_usluga'] === $service_name) {
            return floatval($service['price_usluga'] ?? 0);
        }
    }
    
    return null;
}

/**
 * Получение HTML для выбора услуг покраски (используется в калькуляторах)
 * 
 * @param int $product_id ID товара
 * @return string HTML код селекта
 */
function parusweb_render_painting_services_select($product_id) {
    $services = parusweb_get_painting_services($product_id);
    
    if (empty($services)) {
        return '';
    }
    
    $html = '<div class="painting-services-wrapper">';
    $html .= '<label for="painting_service">Выберите тип покраски:</label>';
    $html .= '<select name="painting_service" id="painting_service" class="painting-service-select">';
    $html .= '<option value="">Без покраски</option>';
    
    foreach ($services as $service) {
        $name = esc_attr($service['name_usluga']);
        $price = floatval($service['price_usluga'] ?? 0);
        $html .= sprintf(
            '<option value="%s" data-price="%s">%s (%s руб/м²)</option>',
            esc_attr($name),
            $price,
            esc_html($name),
            number_format($price, 0, ',', ' ')
        );
    }
    
    $html .= '</select>';
    $html .= '</div>';
    
    return $html;
}
