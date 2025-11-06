<?php
/**
 * ============================================================================
 * МОДУЛЬ: ГЛОБАЛЬНЫЕ НАСТРОЙКИ
 * ============================================================================
 * 
 * Страница глобальных настроек плагина в админ-панели WordPress.
 * 
 * @package ParusWeb_Functions
 * @subpackage Admin
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// РЕГИСТРАЦИЯ СТРАНИЦЫ НАСТРОЕК
// ============================================================================

/**
 * Добавление страницы настроек в меню админки
 */
function parusweb_add_global_settings_page() {
    add_options_page(
        'Настройки ParusWeb Functions',
        'ParusWeb Настройки',
        'manage_options',
        'parusweb-settings',
        'parusweb_render_global_settings_page'
    );
}
add_action('admin_menu', 'parusweb_add_global_settings_page');

/**
 * Регистрация настроек
 */
function parusweb_register_global_settings() {
    // Общие настройки
    register_setting('parusweb_settings_general', 'parusweb_enable_calculators');
    register_setting('parusweb_settings_general', 'parusweb_enable_painting_services');
    register_setting('parusweb_settings_general', 'parusweb_enable_delivery_calc');
    register_setting('parusweb_settings_general', 'parusweb_enable_non_cash_price');
    register_setting('parusweb_settings_general', 'parusweb_non_cash_percentage');
    
    // Настройки калькуляторов
    register_setting('parusweb_settings_calculators', 'parusweb_calc_default_width_min');
    register_setting('parusweb_settings_calculators', 'parusweb_calc_default_width_max');
    register_setting('parusweb_settings_calculators', 'parusweb_calc_default_width_step');
    register_setting('parusweb_settings_calculators', 'parusweb_calc_default_length_min');
    register_setting('parusweb_settings_calculators', 'parusweb_calc_default_length_max');
    register_setting('parusweb_settings_calculators', 'parusweb_calc_default_length_step');
    
    // Настройки доставки
    register_setting('parusweb_settings_delivery', 'parusweb_delivery_base_point');
    register_setting('parusweb_settings_delivery', 'parusweb_delivery_base_price');
    register_setting('parusweb_settings_delivery', 'parusweb_delivery_price_per_km');
    register_setting('parusweb_settings_delivery', 'parusweb_yandex_maps_api_key');
    
    // Настройки API
    register_setting('parusweb_settings_api', 'parusweb_dadata_api_key');
    register_setting('parusweb_settings_api', 'parusweb_dadata_secret_key');
    
    // Настройки отображения
    register_setting('parusweb_settings_display', 'parusweb_show_area_in_title');
    register_setting('parusweb_settings_display', 'parusweb_show_calculator_hints');
    register_setting('parusweb_settings_display', 'parusweb_show_product_badges');
    register_setting('parusweb_settings_display', 'parusweb_primary_color');
}
add_action('admin_init', 'parusweb_register_global_settings');

// ============================================================================
// РЕНДЕР СТРАНИЦЫ НАСТРОЕК
// ============================================================================

/**
 * Отрисовка страницы настроек
 */
function parusweb_render_global_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Сохранение настроек
    if (isset($_POST['parusweb_save_settings']) && check_admin_referer('parusweb_settings_save')) {
        parusweb_save_all_settings();
        echo '<div class="notice notice-success"><p>✓ Настройки успешно сохранены!</p></div>';
    }
    
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    ?>
    
    <div class="wrap parusweb-settings-page">
        <h1>⚙️ Настройки ParusWeb Functions</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=parusweb-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                Общие
            </a>
            <a href="?page=parusweb-settings&tab=calculators" class="nav-tab <?php echo $active_tab === 'calculators' ? 'nav-tab-active' : ''; ?>">
                Калькуляторы
            </a>
            <a href="?page=parusweb-settings&tab=delivery" class="nav-tab <?php echo $active_tab === 'delivery' ? 'nav-tab-active' : ''; ?>">
                Доставка
            </a>
            <a href="?page=parusweb-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
                API ключи
            </a>
            <a href="?page=parusweb-settings&tab=display" class="nav-tab <?php echo $active_tab === 'display' ? 'nav-tab-active' : ''; ?>">
                Отображение
            </a>
        </h2>
        
        <form method="post">
            <?php wp_nonce_field('parusweb_settings_save'); ?>
            
            <?php if ($active_tab === 'general'): ?>
                <?php parusweb_render_general_settings(); ?>
            <?php elseif ($active_tab === 'calculators'): ?>
                <?php parusweb_render_calculators_settings(); ?>
            <?php elseif ($active_tab === 'delivery'): ?>
                <?php parusweb_render_delivery_settings(); ?>
            <?php elseif ($active_tab === 'api'): ?>
                <?php parusweb_render_api_settings(); ?>
            <?php elseif ($active_tab === 'display'): ?>
                <?php parusweb_render_display_settings(); ?>
            <?php endif; ?>
            
            <p class="submit">
                <input type="submit" name="parusweb_save_settings" class="button button-primary button-large" value="💾 Сохранить настройки">
            </p>
        </form>
    </div>
    
    <style>
    .parusweb-settings-page .form-table th {
        width: 250px;
        font-weight: 600;
    }
    .parusweb-settings-page .description {
        color: #666;
        font-style: italic;
        margin-top: 5px;
    }
    .parusweb-settings-section {
        background: #fff;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .parusweb-settings-section h3 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #3aa655;
        color: #3aa655;
    }
    </style>
    <?php
}

// ============================================================================
// ВКЛАДКА: ОБЩИЕ НАСТРОЙКИ
// ============================================================================

function parusweb_render_general_settings() {
    ?>
    <div class="parusweb-settings-section">
        <h3>🔧 Основные функции</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Включить калькуляторы</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_enable_calculators" value="1" 
                               <?php checked(get_option('parusweb_enable_calculators', 1)); ?> />
                        Показывать калькуляторы на страницах товаров
                    </label>
                    <p class="description">Отключите, если хотите временно убрать все калькуляторы</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Услуги покраски</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_enable_painting_services" value="1" 
                               <?php checked(get_option('parusweb_enable_painting_services', 1)); ?> />
                        Включить услуги покраски с выбором цвета
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Расчет доставки</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_enable_delivery_calc" value="1" 
                               <?php checked(get_option('parusweb_enable_delivery_calc', 1)); ?> />
                        Показывать калькулятор доставки на checkout
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Безналичный расчет</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_enable_non_cash_price" value="1" 
                               <?php checked(get_option('parusweb_enable_non_cash_price', 1)); ?> />
                        Показывать цену с надбавкой для безнала
                    </label>
                    <p class="description">Процент надбавки настраивается ниже</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Процент надбавки безнал (%)</th>
                <td>
                    <input type="number" name="parusweb_non_cash_percentage" 
                           value="<?php echo esc_attr(get_option('parusweb_non_cash_percentage', 10)); ?>" 
                           min="0" max="50" step="1" style="width: 100px;" />
                    <p class="description">По умолчанию: 10%</p>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

// ============================================================================
// ВКЛАДКА: КАЛЬКУЛЯТОРЫ
// ============================================================================

function parusweb_render_calculators_settings() {
    ?>
    <div class="parusweb-settings-section">
        <h3>🧮 Настройки по умолчанию для калькуляторов</h3>
        <p>Эти значения используются, если не заданы индивидуальные настройки для товара</p>
        
        <table class="form-table">
            <tr>
                <th scope="row">Ширина минимальная (мм)</th>
                <td>
                    <input type="number" name="parusweb_calc_default_width_min" 
                           value="<?php echo esc_attr(get_option('parusweb_calc_default_width_min', 100)); ?>" 
                           min="10" step="10" style="width: 150px;" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">Ширина максимальная (мм)</th>
                <td>
                    <input type="number" name="parusweb_calc_default_width_max" 
                           value="<?php echo esc_attr(get_option('parusweb_calc_default_width_max', 3000)); ?>" 
                           min="100" step="100" style="width: 150px;" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">Шаг ширины (мм)</th>
                <td>
                    <input type="number" name="parusweb_calc_default_width_step" 
                           value="<?php echo esc_attr(get_option('parusweb_calc_default_width_step', 10)); ?>" 
                           min="1" step="1" style="width: 150px;" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">Длина минимальная (м)</th>
                <td>
                    <input type="number" name="parusweb_calc_default_length_min" 
                           value="<?php echo esc_attr(get_option('parusweb_calc_default_length_min', 0.5)); ?>" 
                           min="0.1" step="0.1" style="width: 150px;" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">Длина максимальная (м)</th>
                <td>
                    <input type="number" name="parusweb_calc_default_length_max" 
                           value="<?php echo esc_attr(get_option('parusweb_calc_default_length_max', 6)); ?>" 
                           min="1" step="0.5" style="width: 150px;" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">Шаг длины (м)</th>
                <td>
                    <input type="number" name="parusweb_calc_default_length_step" 
                           value="<?php echo esc_attr(get_option('parusweb_calc_default_length_step', 0.01)); ?>" 
                           min="0.01" step="0.01" style="width: 150px;" />
                </td>
            </tr>
        </table>
    </div>
    <?php
}

// ============================================================================
// ВКЛАДКА: ДОСТАВКА
// ============================================================================

function parusweb_render_delivery_settings() {
    ?>
    <div class="parusweb-settings-section">
        <h3>🚚 Настройки расчета доставки</h3>
        
        <table class="form-table">
            <tr>
                <th scope="row">Базовая точка (адрес)</th>
                <td>
                    <input type="text" name="parusweb_delivery_base_point" 
                           value="<?php echo esc_attr(get_option('parusweb_delivery_base_point', 'г. Москва, ул. Примерная, д. 1')); ?>" 
                           class="regular-text" />
                    <p class="description">Адрес вашего склада или магазина</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Базовая цена доставки (₽)</th>
                <td>
                    <input type="number" name="parusweb_delivery_base_price" 
                           value="<?php echo esc_attr(get_option('parusweb_delivery_base_price', 1500)); ?>" 
                           min="0" step="100" style="width: 150px;" />
                    <p class="description">Стоимость доставки в пределах МКАД</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Цена за км (₽)</th>
                <td>
                    <input type="number" name="parusweb_delivery_price_per_km" 
                           value="<?php echo esc_attr(get_option('parusweb_delivery_price_per_km', 50)); ?>" 
                           min="0" step="10" style="width: 150px;" />
                    <p class="description">Надбавка за каждый километр за МКАД</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">API ключ Яндекс.Карты</th>
                <td>
                    <input type="text" name="parusweb_yandex_maps_api_key" 
                           value="<?php echo esc_attr(get_option('parusweb_yandex_maps_api_key', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Получите ключ на <a href="https://developer.tech.yandex.ru/" target="_blank">developer.tech.yandex.ru</a></p>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

// ============================================================================
// ВКЛАДКА: API КЛЮЧИ
// ============================================================================

function parusweb_render_api_settings() {
    ?>
    <div class="parusweb-settings-section">
        <h3>🔑 API ключи сервисов</h3>
        
        <table class="form-table">
            <tr>
                <th scope="row">DaData API ключ</th>
                <td>
                    <input type="text" name="parusweb_dadata_api_key" 
                           value="<?php echo esc_attr(get_option('parusweb_dadata_api_key', '903f6c9ee3c3fabd7b9ae599e3735b164f9f71d9')); ?>" 
                           class="regular-text" />
                    <p class="description">Токен авторизации от <a href="https://dadata.ru" target="_blank">DaData.ru</a></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">DaData Secret ключ</th>
                <td>
                    <input type="text" name="parusweb_dadata_secret_key" 
                           value="<?php echo esc_attr(get_option('parusweb_dadata_secret_key', 'ea0595f2a66c84887976a56b8e57ec0aa329a9f7')); ?>" 
                           class="regular-text" />
                    <p class="description">Секретный ключ от DaData для автозаполнения по ИНН</p>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 5px;">
            <h4 style="margin-top: 0;">💡 Тестирование API</h4>
            <p>Для тестирования ключей DaData используйте страницу: <a href="<?php echo admin_url('options-general.php?page=inn-api-settings'); ?>">Настройки ИНН API</a></p>
        </div>
    </div>
    <?php
}

// ============================================================================
// ВКЛАДКА: ОТОБРАЖЕНИЕ
// ============================================================================

function parusweb_render_display_settings() {
    ?>
    <div class="parusweb-settings-section">
        <h3>🎨 Настройки отображения</h3>
        
        <table class="form-table">
            <tr>
                <th scope="row">Показывать площадь в названии</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_show_area_in_title" value="1" 
                               <?php checked(get_option('parusweb_show_area_in_title', 1)); ?> />
                        Отображать площадь упаковки рядом с названием товара
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Подсказки калькуляторов</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_show_calculator_hints" value="1" 
                               <?php checked(get_option('parusweb_show_calculator_hints', 1)); ?> />
                        Показывать информационные подсказки под калькуляторами
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Бейджи товаров</th>
                <td>
                    <label>
                        <input type="checkbox" name="parusweb_show_product_badges" value="1" 
                               <?php checked(get_option('parusweb_show_product_badges', 1)); ?> />
                        Показывать цветные бейджи типов товаров в каталоге
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Основной цвет</th>
                <td>
                    <input type="color" name="parusweb_primary_color" 
                           value="<?php echo esc_attr(get_option('parusweb_primary_color', '#3aa655')); ?>" 
                           style="width: 100px; height: 40px;" />
                    <p class="description">Цвет для кнопок, акцентов и активных элементов (по умолчанию: #3aa655)</p>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

// ============================================================================
// СОХРАНЕНИЕ НАСТРОЕК
// ============================================================================

function parusweb_save_all_settings() {
    // Общие настройки
    update_option('parusweb_enable_calculators', isset($_POST['parusweb_enable_calculators']) ? 1 : 0);
    update_option('parusweb_enable_painting_services', isset($_POST['parusweb_enable_painting_services']) ? 1 : 0);
    update_option('parusweb_enable_delivery_calc', isset($_POST['parusweb_enable_delivery_calc']) ? 1 : 0);
    update_option('parusweb_enable_non_cash_price', isset($_POST['parusweb_enable_non_cash_price']) ? 1 : 0);
    
    if (isset($_POST['parusweb_non_cash_percentage'])) {
        update_option('parusweb_non_cash_percentage', intval($_POST['parusweb_non_cash_percentage']));
    }
    
    // Настройки калькуляторов
    $calc_fields = [
        'parusweb_calc_default_width_min',
        'parusweb_calc_default_width_max',
        'parusweb_calc_default_width_step',
        'parusweb_calc_default_length_min',
        'parusweb_calc_default_length_max',
        'parusweb_calc_default_length_step'
    ];
    
    foreach ($calc_fields as $field) {
        if (isset($_POST[$field])) {
            update_option($field, floatval($_POST[$field]));
        }
    }
    
    // Настройки доставки
    if (isset($_POST['parusweb_delivery_base_point'])) {
        update_option('parusweb_delivery_base_point', sanitize_text_field($_POST['parusweb_delivery_base_point']));
    }
    
    if (isset($_POST['parusweb_delivery_base_price'])) {
        update_option('parusweb_delivery_base_price', floatval($_POST['parusweb_delivery_base_price']));
    }
    
    if (isset($_POST['parusweb_delivery_price_per_km'])) {
        update_option('parusweb_delivery_price_per_km', floatval($_POST['parusweb_delivery_price_per_km']));
    }
    
    if (isset($_POST['parusweb_yandex_maps_api_key'])) {
        update_option('parusweb_yandex_maps_api_key', sanitize_text_field($_POST['parusweb_yandex_maps_api_key']));
    }
    
    // API ключи
    if (isset($_POST['parusweb_dadata_api_key'])) {
        update_option('parusweb_dadata_api_key', sanitize_text_field($_POST['parusweb_dadata_api_key']));
    }
    
    if (isset($_POST['parusweb_dadata_secret_key'])) {
        update_option('parusweb_dadata_secret_key', sanitize_text_field($_POST['parusweb_dadata_secret_key']));
    }
    
    // Настройки отображения
    update_option('parusweb_show_area_in_title', isset($_POST['parusweb_show_area_in_title']) ? 1 : 0);
    update_option('parusweb_show_calculator_hints', isset($_POST['parusweb_show_calculator_hints']) ? 1 : 0);
    update_option('parusweb_show_product_badges', isset($_POST['parusweb_show_product_badges']) ? 1 : 0);
    
    if (isset($_POST['parusweb_primary_color'])) {
        update_option('parusweb_primary_color', sanitize_hex_color($_POST['parusweb_primary_color']));
    }
}

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Получение значения настройки с значением по умолчанию
 */
function parusweb_get_setting($key, $default = '') {
    return get_option($key, $default);
}

/**
 * Проверка включена ли функция
 */
function parusweb_is_feature_enabled($feature) {
    $option_map = [
        'calculators' => 'parusweb_enable_calculators',
        'painting' => 'parusweb_enable_painting_services',
        'delivery' => 'parusweb_enable_delivery_calc',
        'non_cash' => 'parusweb_enable_non_cash_price'
    ];
    
    if (isset($option_map[$feature])) {
        return get_option($option_map[$feature], 1) == 1;
    }
    
    return false;
}
