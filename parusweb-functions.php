<?php
/**
 * Plugin Name: ParusWeb Functions
 * Plugin URI: https://parusweb.ru
 * Description: Модульная система для расширения WooCommerce
 * Version: 2.0.0
 * Author: ParusWeb
 * Author URI: https://parusweb.ru
 * Text Domain: parusweb-functions
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * 
 * @package ParusWeb_Functions
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// КОНСТАНТЫ ПЛАГИНА
// ============================================================================

define('PARUSWEB_VERSION', '2.0.0');
define('PARUSWEB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PARUSWEB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PARUSWEB_MODULES_DIR', PARUSWEB_PLUGIN_DIR . 'modules/');
define('PARUSWEB_DEBUG', false); // Отладка (false в продакшене)

// ============================================================================
// ОСНОВНОЙ КЛАСС ПЛАГИНА
// ============================================================================

class ParusWeb_Functions {
    
    private static $instance = null;
    private $active_modules = [];
    private $available_modules = [];
    
    /**
     * Singleton Instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Конструктор
     */
    private function __construct() {
        $this->define_modules();
        $this->load_active_modules();
        $this->init_hooks();
    }
    
    /**
     * Инициализация хуков
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }
    
    /**
     * Определение доступных модулей
     */
    private function define_modules() {
        $this->available_modules = [
            
            // ================================================================
            // КРИТИЧЕСКИЕ МОДУЛИ (Ядро системы)
            // ================================================================
            
            'core-category-helpers' => [
                'name' => '🔧 Ядро: Проверка категорий',
                'description' => 'Базовые функции проверки категорий товаров (КРИТИЧЕСКИЙ)',
                'file' => 'core/category-helpers.php',
                'dependencies' => [],
                'critical' => true,
                'admin_only' => false,
                'group' => 'core'
            ],
            
            'core-product-calculations' => [
                'name' => '🔧 Ядро: Расчеты товаров',
                'description' => 'Расчет площади, цен, множителей (КРИТИЧЕСКИЙ)',
                'file' => 'core/product-calculations.php',
                'dependencies' => ['core-category-helpers'],
                'critical' => true,
                'admin_only' => false,
                'group' => 'core'
            ],
            
            // ================================================================
            // МОДУЛИ ОТОБРАЖЕНИЯ (Frontend Display)
            // ================================================================
            
            'display-price-formatting' => [
                'name' => '💰 Отображение: Форматирование цен',
                'description' => 'Форматирование и вывод цен для разных типов товаров',
                'file' => 'display/price-formatting.php',
                'dependencies' => ['core-product-calculations'],
                'admin_only' => false,
                'group' => 'display'
            ],
            
            'display-calculators' => [
                'name' => '🧮 Отображение: Калькуляторы',
                'description' => 'Вывод калькуляторов на странице товара',
                'file' => 'display/calculators.php',
                'dependencies' => ['core-product-calculations'],
                'admin_only' => false,
                'group' => 'display'
            ],
            
            'display-product-info' => [
                'name' => '📋 Отображение: Информация о товаре',
                'description' => 'Дополнительная информация на странице товара',
                'file' => 'display/product-info.php',
                'dependencies' => ['core-category-helpers'],
                'admin_only' => false,
                'group' => 'display'
            ],
            
            // ================================================================
            // МОДУЛИ КОРЗИНЫ И ЗАКАЗОВ (Cart & Orders)
            // ================================================================
            
            'cart-functionality' => [
                'name' => '🛒 Корзина: Функционал',
                'description' => 'Добавление товаров с расчетами в корзину',
                'file' => 'cart/cart-functionality.php',
                'dependencies' => ['core-product-calculations'],
                'admin_only' => false,
                'group' => 'cart'
            ],
            
            'cart-display' => [
                'name' => '🛒 Корзина: Отображение',
                'description' => 'Вывод данных калькуляторов в корзине',
                'file' => 'cart/cart-display.php',
                'dependencies' => ['cart-functionality'],
                'admin_only' => false,
                'group' => 'cart'
            ],
            
            'order-processing' => [
                'name' => '📦 Заказы: Обработка',
                'description' => 'Создание и обработка заказов',
                'file' => 'orders/order-processing.php',
                'dependencies' => ['cart-functionality'],
                'admin_only' => false,
                'group' => 'orders'
            ],
            
            // ================================================================
            // МОДУЛИ МЕТАДАННЫХ (Admin Meta)
            // ================================================================
            
            'admin-product-meta' => [
                'name' => '⚙️ Админка: Метаполя товаров',
                'description' => 'Кастомные поля товаров (множители, размеры)',
                'file' => 'admin/product-meta.php',
                'dependencies' => [],
                'admin_only' => true,
                'group' => 'admin'
            ],
            
            'admin-category-meta' => [
                'name' => '⚙️ Админка: Метаполя категорий',
                'description' => 'Кастомные поля категорий (множители, фаски)',
                'file' => 'admin/category-meta.php',
                'dependencies' => [],
                'admin_only' => true,
                'group' => 'admin'
            ],
            
            'admin-falsebalk-meta' => [
                'name' => '⚙️ Админка: Фальшбалки',
                'description' => 'Метабокс настройки фальшбалок',
                'file' => 'admin/falsebalk-meta.php',
                'dependencies' => ['core-category-helpers'],
                'admin_only' => true,
                'group' => 'admin'
            ],
            
            'admin-shtaketnik-meta' => [
                'name' => '⚙️ Админка: Штакетник',
                'description' => 'Метаполя для форм верха штакетника',
                'file' => 'admin/shtaketnik-meta.php',
                'dependencies' => [],
                'admin_only' => true,
                'group' => 'admin'
            ],
            
            // ================================================================
            // СПЕЦИАЛИЗИРОВАННЫЕ МОДУЛИ (Specialized)
            // ================================================================
            
            'feature-painting-services' => [
                'name' => '🎨 Услуги покраски',
                'description' => 'ACF поля и логика услуг покраски',
                'file' => 'features/painting-services.php',
                'dependencies' => ['core-category-helpers'],
                'admin_only' => false,
                'group' => 'features'
            ],
            
            'feature-liter-products' => [
                'name' => '🧪 Товары "за литр"',
                'description' => 'Логика товаров с объемом (ЛКМ)',
                'file' => 'features/liter-products.php',
                'dependencies' => ['core-category-helpers'],
                'admin_only' => false,
                'group' => 'features'
            ],
            
            'feature-delivery-calc' => [
                'name' => '🚚 Калькулятор доставки',
                'description' => 'Расчет стоимости доставки по карте',
                'file' => 'features/delivery-calculator.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'features'
            ],
            
            'feature-non-cash-price' => [
                'name' => '💳 Цена по безналу',
                'description' => 'Вывод цены с наценкой 10% для безнала',
                'file' => 'features/non-cash-price.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'features'
            ],
            
            // ================================================================
            // МОДУЛИ ИНТЕГРАЦИЙ (Integrations)
            // ================================================================
            
            'integration-acf' => [
                'name' => '🔌 Интеграция: ACF',
                'description' => 'Настройка полей Advanced Custom Fields',
                'file' => 'integrations/acf-fields.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'integrations'
            ],
            
            'integration-facet-filters' => [
                'name' => '🔌 Интеграция: FacetWP',
                'description' => 'Настройка фильтров FacetWP',
                'file' => 'integrations/facet-filters.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'integrations'
            ],
            
            'integration-mega-menu' => [
                'name' => '🔌 Интеграция: Мега-меню',
                'description' => 'Атрибуты товаров в мега-меню',
                'file' => 'integrations/mega-menu-attributes.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'integrations'
            ],
            
            // ================================================================
            // МОДУЛИ УЧЕТНОЙ ЗАПИСИ (Account)
            // ================================================================
            
            'account-customization' => [
                'name' => '👤 ЛК: Кастомизация',
                'description' => 'Настройка личного кабинета WooCommerce',
                'file' => 'account/account-customization.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'account'
            ],
            
            'account-company-fields' => [
                'name' => '👤 ЛК: Реквизиты компании',
                'description' => 'Поля юридических лиц и ИП',
                'file' => 'account/company-fields.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'account'
            ],
            
            'account-inn-lookup' => [
                'name' => '👤 ЛК: Заполнение по ИНН',
                'description' => 'Автозаполнение реквизитов через DaData',
                'file' => 'account/inn-lookup.php',
                'dependencies' => ['account-company-fields'],
                'admin_only' => false,
                'group' => 'account'
            ],
            
            // ================================================================
            // ВСПОМОГАТЕЛЬНЫЕ МОДУЛИ (Utilities)
            // ================================================================
            
            'utility-ajax-handlers' => [
                'name' => '🔧 Утилиты: AJAX обработчики',
                'description' => 'Обработчики AJAX запросов',
                'file' => 'utilities/ajax-handlers.php',
                'dependencies' => ['core-product-calculations'],
                'admin_only' => false,
                'group' => 'utilities'
            ],
            
            'utility-shortcodes' => [
                'name' => '🔧 Утилиты: Шорткоды',
                'description' => 'Пользовательские шорткоды',
                'file' => 'utilities/shortcodes.php',
                'dependencies' => ['core-category-helpers'],
                'admin_only' => false,
                'group' => 'utilities'
            ],
            
            'utility-misc' => [
                'name' => '🔧 Утилиты: Разное',
                'description' => 'Различные вспомогательные функции',
                'file' => 'utilities/misc-functions.php',
                'dependencies' => [],
                'admin_only' => false,
                'group' => 'utilities'
            ],
            
            // ================================================================
            // JAVASCRIPT МОДУЛИ (Scripts)
            // ================================================================
            
            'script-calculators' => [
                'name' => '📜 JS: Калькуляторы',
                'description' => 'JavaScript логика калькуляторов',
                'file' => 'scripts/calculator-scripts.php',
                'dependencies' => ['display-calculators'],
                'admin_only' => false,
                'group' => 'scripts'
            ],
            
            'script-price-update' => [
                'name' => '📜 JS: Обновление цены',
                'description' => 'Автообновление цены при изменении калькулятора',
                'file' => 'scripts/price-update.php',
                'dependencies' => ['script-calculators'],
                'admin_only' => false,
                'group' => 'scripts'
            ],
        ];
    }
    
    /**
     * Загрузка активных модулей
     */
    private function load_active_modules() {
        $enabled = get_option('parusweb_enabled_modules', array_keys($this->available_modules));
        
        // Сначала загружаем критические модули
        foreach ($this->available_modules as $id => $module) {
            if (!empty($module['critical'])) {
                $this->load_module($id);
            }
        }
        
        // Затем загружаем остальные включенные модули
        foreach ($enabled as $module_id) {
            if (!isset($this->available_modules[$module_id])) continue;
            if (!empty($this->available_modules[$module_id]['critical'])) continue; // Уже загружен
            
            $this->load_module($module_id);
        }
    }
    
    /**
     * Загрузка отдельного модуля
     */
    private function load_module($module_id) {
        if (in_array($module_id, $this->active_modules)) return true;
        if (!isset($this->available_modules[$module_id])) return false;
        
        $module = $this->available_modules[$module_id];
        
        // Проверка зависимостей
        if (!$this->check_dependencies($module_id)) return false;
        
        // Проверка admin_only
        if ($module['admin_only'] && !is_admin()) return false;
        
        // Загрузка файла модуля
        $module_file = PARUSWEB_MODULES_DIR . $module['file'];
        if (file_exists($module_file)) {
            require_once $module_file;
            $this->active_modules[] = $module_id;
            return true;
        }
        
        return false;
    }
    
    /**
     * Проверка зависимостей модуля
     */
    private function check_dependencies($module_id) {
        $module = $this->available_modules[$module_id];
        $enabled = get_option('parusweb_enabled_modules', array_keys($this->available_modules));
        
        foreach ($module['dependencies'] as $dependency) {
            // Критические модули всегда считаются доступными
            if (!empty($this->available_modules[$dependency]['critical'])) continue;
            
            if (!in_array($dependency, $enabled)) return false;
        }
        
        return true;
    }
    
    /**
     * Получение зависимых модулей
     */
    private function get_dependent_modules($module_id) {
        $dependents = [];
        
        foreach ($this->available_modules as $id => $module) {
            if (in_array($module_id, $module['dependencies'])) {
                $dependents[] = $id;
            }
        }
        
        return $dependents;
    }
    
    /**
     * Публичный геттер активных модулей
     */
    public function get_active_modules() {
        return $this->active_modules;
    }
    
    /**
     * Добавление меню в админке
     */
    public function add_admin_menu() {
        add_options_page(
            'ParusWeb Модули',
            'ParusWeb Модули',
            'manage_options',
            'parusweb-modules',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Регистрация настроек
     */
    public function register_settings() {
        register_setting('parusweb_modules', 'parusweb_enabled_modules');
    }
    
    /**
     * Подключение скриптов админки
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_parusweb-modules') return;
        
        wp_enqueue_style('parusweb-admin', PARUSWEB_PLUGIN_URL . 'assets/css/admin.css', [], PARUSWEB_VERSION);
        wp_enqueue_script('parusweb-admin', PARUSWEB_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], PARUSWEB_VERSION, true);
        
        wp_localize_script('parusweb-admin', 'paruswebModules', [
            'dependencies' => $this->get_all_dependencies(),
            'dependents' => $this->get_all_dependents()
        ]);
    }
    
    /**
     * Подключение скриптов фронтенда
     */
    public function enqueue_frontend_scripts() {
        if (!is_product() && !is_cart() && !is_checkout()) return;
        
        wp_enqueue_style('parusweb-frontend', PARUSWEB_PLUGIN_URL . 'assets/css/frontend.css', [], PARUSWEB_VERSION);
        wp_enqueue_script('parusweb-frontend', PARUSWEB_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], PARUSWEB_VERSION, true);
        
        wp_localize_script('parusweb-frontend', 'paruswebData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'decimal_separator' => wc_get_price_decimal_separator(),
            'decimals' => wc_get_price_decimals()
        ]);
    }
    
    /**
     * Получение всех зависимостей
     */
    private function get_all_dependencies() {
        $deps = [];
        foreach ($this->available_modules as $id => $module) {
            $deps[$id] = $module['dependencies'];
        }
        return $deps;
    }
    
    /**
     * Получение всех зависимых модулей
     */
    private function get_all_dependents() {
        $deps = [];
        foreach ($this->available_modules as $id => $module) {
            $deps[$id] = $this->get_dependent_modules($id);
        }
        return $deps;
    }
    
    /**
     * Отрисовка страницы настроек
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        
        // Сохранение настроек
        if (isset($_POST['parusweb_save_modules']) && check_admin_referer('parusweb_modules_save')) {
            $enabled = isset($_POST['parusweb_modules']) ? array_map('sanitize_text_field', $_POST['parusweb_modules']) : [];
            
            // Критические модули всегда включены
            foreach ($this->available_modules as $id => $module) {
                if (!empty($module['critical']) && !in_array($id, $enabled)) {
                    $enabled[] = $id;
                }
            }
            
            update_option('parusweb_enabled_modules', $enabled);
            echo '<div class="notice notice-success"><p>✓ Настройки сохранены! Обновите страницу для применения изменений.</p></div>';
        }
        
        $enabled_modules = get_option('parusweb_enabled_modules', array_keys($this->available_modules));
        $groups = array_unique(array_column($this->available_modules, 'group'));
        
        $group_names = [
            'core' => '🔧 Критические модули (Ядро системы)',
            'display' => '💰 Модули отображения',
            'cart' => '🛒 Корзина и оформление',
            'orders' => '📦 Обработка заказов',
            'admin' => '⚙️ Админ-панель',
            'features' => '⭐ Специализированные функции',
            'integrations' => '🔌 Интеграции с плагинами',
            'account' => '👤 Личный кабинет',
            'utilities' => '🔧 Вспомогательные утилиты',
            'scripts' => '📜 JavaScript модули'
        ];
        
        include PARUSWEB_PLUGIN_DIR . 'templates/admin-page.php';
    }
}

// ============================================================================
// ИНИЦИАЛИЗАЦИЯ ПЛАГИНА
// ============================================================================

function parusweb_functions_init() {
    return ParusWeb_Functions::instance();
}

add_action('plugins_loaded', 'parusweb_functions_init');

// ============================================================================
// ПУБЛИЧНЫЕ API ФУНКЦИИ
// ============================================================================

/**
 * Получить экземпляр плагина
 */
function parusweb() {
    return ParusWeb_Functions::instance();
}

/**
 * Проверка активности модуля
 */
function parusweb_is_module_active($module_id) {
    return in_array($module_id, parusweb()->get_active_modules());
}
