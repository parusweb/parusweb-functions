<?php
/**
 * ============================================================================
 * МОДУЛЬ: КАСТОМИЗАЦИЯ ЛИЧНОГО КАБИНЕТА
 * ============================================================================
 * 
 * Настройка меню, дашборда и интерфейса личного кабинета WooCommerce.
 * 
 * @package ParusWeb_Functions
 * @subpackage Account
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// КАСТОМИЗАЦИЯ МЕНЮ ЛИЧНОГО КАБИНЕТА
// ============================================================================

/**
 * Изменение структуры меню личного кабинета
 */
function parusweb_customize_account_menu($items) {
    return [
        'dashboard'       => 'Панель управления',
        'orders'          => 'Заказы',
        'edit-account'    => 'Мои данные',
        'edit-address'    => 'Адрес доставки',
    ];
}
add_filter('woocommerce_account_menu_items', 'parusweb_customize_account_menu', 20);

/**
 * Удаление пункта "Корзина" из меню
 */
function parusweb_remove_cart_from_menu($items) {
    unset($items['cart']);
    return $items;
}
add_filter('woocommerce_account_menu_items', 'parusweb_remove_cart_from_menu', 999);

/**
 * Удаление ссылок на корзину из навигационных меню
 */
function parusweb_remove_cart_from_nav($items, $args) {
    $items = preg_replace('/<li[^>]*><a[^>]*href="[^"]*cart[^"]*"[^>]*>.*?<\/a><\/li>/i', '', $items);
    return $items;
}
add_filter('wp_nav_menu_items', 'parusweb_remove_cart_from_nav', 10, 2);

// ============================================================================
// ДАШБОРД ЛИЧНОГО КАБИНЕТА
// ============================================================================

/**
 * Плитки на дашборде личного кабинета
 */
function parusweb_account_dashboard_tiles() {
    $orders_url = esc_url(wc_get_account_endpoint_url('orders'));
    $account_url = esc_url(wc_get_account_endpoint_url('edit-account'));
    $address_url = esc_url(wc_get_account_endpoint_url('edit-address'));
    ?>
    <br>
    <div class="lk-tiles">
        <a href="<?php echo $orders_url; ?>" class="lk-tile" aria-label="Заказы">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
                <path d="M411.883 127.629h-310.08c-18.313 0-33.227 14.921-33.227 33.26v190.095c0 18.332 14.914 33.253 33.227 33.253h310.08c18.32 0 33.24-14.921 33.24-33.253V160.889c-.002-18.34-14.92-33.26-33.24-33.26zM311.34 293.18h-110.67v-27.57h110.67v27.57zm86.11-67.097H115.83v-24.64h281.62v24.64z"/>
            </svg>
            <br>Заказы
        </a>
        
        <a href="<?php echo $account_url; ?>" class="lk-tile" aria-label="Мои данные">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 12c2.7 0 4.85-2.15 4.85-4.85S14.7 2.3 12 2.3 7.15 4.45 7.15 7.15 9.3 12 12 12zm0 2.7c-3.15 0-9.45 1.6-9.45 4.85v2.15h18.9v-2.15c0-3.25-6.3-4.85-9.45-4.85z"/>
            </svg>
            <br>Мои данные
        </a>
        
        <a href="<?php echo $address_url; ?>" class="lk-tile" aria-label="Адрес доставки">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
            <br>Адрес доставки
        </a>
    </div>
    
    <style>
    .lk-tiles {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        margin: 30px 0;
    }
    .lk-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px 20px;
        background: #ffffff;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    .lk-tile:hover {
        border-color: #3aa655;
        background: #f5faf7;
        color: #3aa655;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(58, 166, 85, 0.15);
    }
    .lk-tile svg {
        width: 50px;
        height: 50px;
        margin-bottom: 15px;
        fill: currentColor;
    }
    @media (max-width: 768px) {
        .lk-tiles {
            grid-template-columns: 1fr;
        }
        .lk-tile {
            padding: 25px 15px;
        }
    }
    </style>
    <?php
}
add_action('woocommerce_account_dashboard', 'parusweb_account_dashboard_tiles');

// ============================================================================
// КАСТОМИЗАЦИЯ ЗАГОЛОВКОВ
// ============================================================================

/**
 * Изменение заголовка страницы заказов
 */
function parusweb_orders_page_title($title) {
    if (is_account_page() && is_wc_endpoint_url('orders')) {
        return 'История заказов';
    }
    return $title;
}
add_filter('the_title', 'parusweb_orders_page_title', 10, 1);

/**
 * Изменение текста "Добро пожаловать" на дашборде
 */
function parusweb_account_dashboard_greeting() {
    $user = wp_get_current_user();
    $first_name = $user->first_name;
    
    if ($first_name) {
        echo '<h2>Добро пожаловать, ' . esc_html($first_name) . '!</h2>';
    } else {
        echo '<h2>Добро пожаловать в личный кабинет!</h2>';
    }
}
remove_action('woocommerce_account_content', 'woocommerce_account_content');
add_action('woocommerce_account_content', function() {
    if (is_wc_endpoint_url('dashboard')) {
        parusweb_account_dashboard_greeting();
        parusweb_account_dashboard_tiles();
    } else {
        woocommerce_account_content();
    }
});

// ============================================================================
// КАСТОМИЗАЦИЯ ТАБЛИЦЫ ЗАКАЗОВ
// ============================================================================

/**
 * Изменение колонок в таблице заказов
 */
function parusweb_customize_orders_columns($columns) {
    $new_columns = [
        'order-number'  => 'Номер заказа',
        'order-date'    => 'Дата',
        'order-status'  => 'Статус',
        'order-total'   => 'Сумма',
        'order-actions' => 'Действия',
    ];
    
    return $new_columns;
}
add_filter('woocommerce_account_orders_columns', 'parusweb_customize_orders_columns');

/**
 * Кастомизация названий статусов заказов
 */
function parusweb_custom_order_status_names($status) {
    $statuses = [
        'pending'    => 'Ожидает оплаты',
        'processing' => 'Обрабатывается',
        'on-hold'    => 'На удержании',
        'completed'  => 'Выполнен',
        'cancelled'  => 'Отменен',
        'refunded'   => 'Возврат',
        'failed'     => 'Не удался',
    ];
    
    $status_key = str_replace('wc-', '', $status);
    return isset($statuses[$status_key]) ? $statuses[$status_key] : $status;
}
add_filter('woocommerce_order_status_name', 'parusweb_custom_order_status_names');

// ============================================================================
// ОГРАНИЧЕНИЯ ДОСТУПА
// ============================================================================

/**
 * Перенаправление неавторизованных пользователей со страниц ЛК
 */
function parusweb_redirect_unauth_from_account() {
    if (is_account_page() && !is_user_logged_in() && !is_wc_endpoint_url('lost-password')) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'parusweb_redirect_unauth_from_account');

/**
 * Скрытие админ-бара для клиентов
 */
function parusweb_hide_admin_bar_for_customers() {
    if (current_user_can('customer') && !current_user_can('edit_posts')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'parusweb_hide_admin_bar_for_customers');

// ============================================================================
// ДОПОЛНИТЕЛЬНАЯ КАСТОМИЗАЦИЯ
// ============================================================================

/**
 * Добавление кастомного CSS для личного кабинета
 */
function parusweb_account_custom_css() {
    if (!is_account_page()) return;
    ?>
    <style>
    .woocommerce-MyAccount-navigation ul {
        list-style: none;
        padding: 0;
        margin: 0;
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .woocommerce-MyAccount-navigation ul li {
        border-bottom: 1px solid #f0f0f0;
    }
    .woocommerce-MyAccount-navigation ul li:last-child {
        border-bottom: none;
    }
    .woocommerce-MyAccount-navigation ul li a {
        display: block;
        padding: 15px 20px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .woocommerce-MyAccount-navigation ul li.is-active a,
    .woocommerce-MyAccount-navigation ul li a:hover {
        background: #3aa655;
        color: #ffffff;
    }
    .woocommerce-MyAccount-content {
        background: #ffffff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    @media (max-width: 768px) {
        .woocommerce-MyAccount-navigation {
            margin-bottom: 20px;
        }
        .woocommerce-MyAccount-content {
            padding: 20px;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'parusweb_account_custom_css');

/**
 * Удаление ненужных уведомлений WooCommerce
 */
function parusweb_remove_account_notices() {
    if (is_account_page()) {
        remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices');
    }
}
add_action('wp', 'parusweb_remove_account_notices');
