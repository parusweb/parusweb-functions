<?php
/**
 * Shortcodes Module
 * 
 * Кастомные шорткоды для использования в контенте:
 * - Вывод информации о товарах
 * - Списки товаров по категориям
 * - Кнопки и элементы интерфейса
 * - Статистика и счётчики
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ТОВАРЫ И КАТЕГОРИИ
// ============================================================================

/**
 * Шорткод: Вывод списка товаров категории
 * 
 * Использование: [parusweb_products category="123" limit="10"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_products($atts) {
    $atts = shortcode_atts([
        'category' => '',
        'limit'    => 10,
        'columns'  => 4,
        'orderby'  => 'date',
        'order'    => 'DESC'
    ], $atts, 'parusweb_products');
    
    if (empty($atts['category'])) {
        return '<p>Не указана категория</p>';
    }
    
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => intval($atts['limit']),
        'orderby'        => $atts['orderby'],
        'order'          => $atts['order'],
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => intval($atts['category'])
            ]
        ]
    ];
    
    $products = new WP_Query($args);
    
    if (!$products->have_posts()) {
        return '<p>Товары не найдены</p>';
    }
    
    ob_start();
    
    echo '<div class="parusweb-products-grid columns-' . esc_attr($atts['columns']) . '">';
    
    while ($products->have_posts()) {
        $products->the_post();
        wc_get_template_part('content', 'product');
    }
    
    echo '</div>';
    
    wp_reset_postdata();
    
    return ob_get_clean();
}
add_shortcode('parusweb_products', 'parusweb_shortcode_products');

/**
 * Шорткод: Информация о категории
 * 
 * Использование: [parusweb_category_info id="123"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_category_info($atts) {
    $atts = shortcode_atts([
        'id'    => 0,
        'show'  => 'all' // all, name, description, count, image
    ], $atts, 'parusweb_category_info');
    
    $category = get_term($atts['id'], 'product_cat');
    
    if (!$category || is_wp_error($category)) {
        return '<p>Категория не найдена</p>';
    }
    
    ob_start();
    
    echo '<div class="parusweb-category-info">';
    
    if (in_array($atts['show'], ['all', 'name'])) {
        echo '<h3>' . esc_html($category->name) . '</h3>';
    }
    
    if (in_array($atts['show'], ['all', 'description']) && !empty($category->description)) {
        echo '<div class="category-description">' . wp_kses_post($category->description) . '</div>';
    }
    
    if (in_array($atts['show'], ['all', 'count'])) {
        echo '<p class="category-count">Товаров: ' . intval($category->count) . '</p>';
    }
    
    if (in_array($atts['show'], ['all', 'image'])) {
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        if ($thumbnail_id) {
            echo wp_get_attachment_image($thumbnail_id, 'medium');
        }
    }
    
    echo '</div>';
    
    return ob_get_clean();
}
add_shortcode('parusweb_category_info', 'parusweb_shortcode_category_info');

// ============================================================================
// БЛОК 2: КНОПКИ И ДЕЙСТВИЯ
// ============================================================================

/**
 * Шорткод: Кнопка "Добавить в корзину"
 * 
 * Использование: [parusweb_add_to_cart id="123" quantity="1" text="Купить"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_add_to_cart($atts) {
    $atts = shortcode_atts([
        'id'       => 0,
        'quantity' => 1,
        'text'     => 'Добавить в корзину',
        'class'    => 'button'
    ], $atts, 'parusweb_add_to_cart');
    
    $product_id = intval($atts['id']);
    
    if (!$product_id) {
        return '<p>ID товара не указан</p>';
    }
    
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return '<p>Товар не найден</p>';
    }
    
    $url = add_query_arg([
        'add-to-cart' => $product_id,
        'quantity'    => intval($atts['quantity'])
    ], wc_get_cart_url());
    
    return sprintf(
        '<a href="%s" class="%s" data-product_id="%d" data-quantity="%d">%s</a>',
        esc_url($url),
        esc_attr($atts['class']),
        $product_id,
        intval($atts['quantity']),
        esc_html($atts['text'])
    );
}
add_shortcode('parusweb_add_to_cart', 'parusweb_shortcode_add_to_cart');

/**
 * Шорткод: Ссылка на товар
 * 
 * Использование: [parusweb_product_link id="123" text="Смотреть товар"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_product_link($atts) {
    $atts = shortcode_atts([
        'id'    => 0,
        'text'  => '',
        'class' => ''
    ], $atts, 'parusweb_product_link');
    
    $product_id = intval($atts['id']);
    
    if (!$product_id) {
        return '';
    }
    
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return '';
    }
    
    $text = !empty($atts['text']) ? $atts['text'] : $product->get_name();
    $class = !empty($atts['class']) ? ' class="' . esc_attr($atts['class']) . '"' : '';
    
    return sprintf(
        '<a href="%s"%s>%s</a>',
        esc_url($product->get_permalink()),
        $class,
        esc_html($text)
    );
}
add_shortcode('parusweb_product_link', 'parusweb_shortcode_product_link');

// ============================================================================
// БЛОК 3: ЦЕНЫ И СТОИМОСТЬ
// ============================================================================

/**
 * Шорткод: Цена товара
 * 
 * Использование: [parusweb_price id="123" type="regular"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_price($atts) {
    $atts = shortcode_atts([
        'id'   => 0,
        'type' => 'regular' // regular, sale, current
    ], $atts, 'parusweb_price');
    
    $product_id = intval($atts['id']);
    
    if (!$product_id) {
        return '';
    }
    
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return '';
    }
    
    switch ($atts['type']) {
        case 'sale':
            $price = $product->get_sale_price();
            break;
        case 'current':
            $price = $product->get_price();
            break;
        case 'regular':
        default:
            $price = $product->get_regular_price();
            break;
    }
    
    return wc_price($price);
}
add_shortcode('parusweb_price', 'parusweb_shortcode_price');

/**
 * Шорткод: Диапазон цен категории
 * 
 * Использование: [parusweb_price_range category="123"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_price_range($atts) {
    $atts = shortcode_atts([
        'category' => 0
    ], $atts, 'parusweb_price_range');
    
    $category_id = intval($atts['category']);
    
    if (!$category_id) {
        return '';
    }
    
    global $wpdb;
    
    $query = $wpdb->prepare("
        SELECT MIN(CAST(pm.meta_value AS DECIMAL(10,2))) as min_price,
               MAX(CAST(pm.meta_value AS DECIMAL(10,2))) as max_price
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND tr.term_taxonomy_id = %d
        AND pm.meta_key = '_price'
        AND pm.meta_value > 0
    ", $category_id);
    
    $result = $wpdb->get_row($query);
    
    if (!$result || $result->min_price === null) {
        return '<span class="price-range">Цены уточняйте</span>';
    }
    
    if ($result->min_price == $result->max_price) {
        return '<span class="price-range">' . wc_price($result->min_price) . '</span>';
    }
    
    return sprintf(
        '<span class="price-range">от %s до %s</span>',
        wc_price($result->min_price),
        wc_price($result->max_price)
    );
}
add_shortcode('parusweb_price_range', 'parusweb_shortcode_price_range');

// ============================================================================
// БЛОК 4: СТАТИСТИКА И СЧЁТЧИКИ
// ============================================================================

/**
 * Шорткод: Количество товаров в категории
 * 
 * Использование: [parusweb_product_count category="123"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string Число
 */
function parusweb_shortcode_product_count($atts) {
    $atts = shortcode_atts([
        'category' => 0
    ], $atts, 'parusweb_product_count');
    
    $category_id = intval($atts['category']);
    
    if (!$category_id) {
        return '0';
    }
    
    $category = get_term($category_id, 'product_cat');
    
    if (!$category || is_wp_error($category)) {
        return '0';
    }
    
    return intval($category->count);
}
add_shortcode('parusweb_product_count', 'parusweb_shortcode_product_count');

/**
 * Шорткод: Общее количество товаров
 * 
 * Использование: [parusweb_total_products]
 * 
 * @return string Число
 */
function parusweb_shortcode_total_products() {
    $count = wp_count_posts('product');
    return isset($count->publish) ? intval($count->publish) : 0;
}
add_shortcode('parusweb_total_products', 'parusweb_shortcode_total_products');

/**
 * Шорткод: Количество категорий
 * 
 * Использование: [parusweb_category_count]
 * 
 * @return string Число
 */
function parusweb_shortcode_category_count() {
    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'count'      => true
    ]);
    
    return is_wp_error($terms) ? 0 : count($terms);
}
add_shortcode('parusweb_category_count', 'parusweb_shortcode_category_count');

// ============================================================================
// БЛОК 5: ЭЛЕМЕНТЫ ИНТЕРФЕЙСА
// ============================================================================

/**
 * Шорткод: Кнопка "Наверх"
 * 
 * Использование: [parusweb_scroll_top text="Наверх"]
 * 
 * @param array $atts Атрибуты шорткода
 * @return string HTML код
 */
function parusweb_shortcode_scroll_top($atts) {
    $atts = shortcode_atts([
        'text'  => '↑ Наверх',
        'class' => 'scroll-to-top'
    ], $atts, 'parusweb_scroll_top');
    
    return sprintf(
        '<button class="%s" onclick="window.scrollTo({top:0,behavior:\'smooth\'})">%s</button>',
        esc_attr($atts['class']),
        esc_html($atts['text'])
    );
}
add_shortcode('parusweb_scroll_top', 'parusweb_shortcode_scroll_top');

/**
 * Шорткод: Блок с иконкой
 * 
 * Использование: [parusweb_icon_box icon="🚚" title="Доставка" text="По всей России"]
 * 
 * @param array $atts Атрибуты шорткода
 * @param string $content Контент между тегами
 * @return string HTML код
 */
function parusweb_shortcode_icon_box($atts, $content = '') {
    $atts = shortcode_atts([
        'icon'  => '',
        'title' => '',
        'text'  => '',
        'class' => 'icon-box'
    ], $atts, 'parusweb_icon_box');
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr($atts['class']); ?>">
        <?php if (!empty($atts['icon'])): ?>
            <div class="icon-box-icon"><?php echo esc_html($atts['icon']); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($atts['title'])): ?>
            <h4 class="icon-box-title"><?php echo esc_html($atts['title']); ?></h4>
        <?php endif; ?>
        
        <?php if (!empty($atts['text'])): ?>
            <p class="icon-box-text"><?php echo esc_html($atts['text']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($content)): ?>
            <div class="icon-box-content"><?php echo do_shortcode($content); ?></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('parusweb_icon_box', 'parusweb_shortcode_icon_box');

// ============================================================================
// БЛОК 6: УСЛОВНОЕ ОТОБРАЖЕНИЕ
// ============================================================================

/**
 * Шорткод: Отображать только авторизованным
 * 
 * Использование: [parusweb_logged_in]Секретный контент[/parusweb_logged_in]
 * 
 * @param array $atts Атрибуты шорткода
 * @param string $content Контент между тегами
 * @return string HTML код
 */
function parusweb_shortcode_logged_in($atts, $content = '') {
    if (!is_user_logged_in()) {
        return '';
    }
    
    return do_shortcode($content);
}
add_shortcode('parusweb_logged_in', 'parusweb_shortcode_logged_in');

/**
 * Шорткод: Отображать только гостям
 * 
 * Использование: [parusweb_guest]Войдите чтобы увидеть цены[/parusweb_guest]
 * 
 * @param array $atts Атрибуты шорткода
 * @param string $content Контент между тегами
 * @return string HTML код
 */
function parusweb_shortcode_guest($atts, $content = '') {
    if (is_user_logged_in()) {
        return '';
    }
    
    return do_shortcode($content);
}
add_shortcode('parusweb_guest', 'parusweb_shortcode_guest');

// ============================================================================
// БЛОК 7: РЕГИСТРАЦИЯ ДОКУМЕНТАЦИИ
// ============================================================================

/**
 * Добавление информации о шорткодах в админку
 */
function parusweb_shortcodes_help_page() {
    add_submenu_page(
        'tools.php',
        'Шорткоды ParusWeb',
        'Шорткоды ParusWeb',
        'manage_options',
        'parusweb-shortcodes',
        'parusweb_render_shortcodes_help'
    );
}
add_action('admin_menu', 'parusweb_shortcodes_help_page');

/**
 * Рендер страницы помощи по шорткодам
 */
function parusweb_render_shortcodes_help() {
    ?>
    <div class="wrap">
        <h1>Доступные шорткоды ParusWeb</h1>
        
        <h2>Товары и категории</h2>
        <ul>
            <li><code>[parusweb_products category="123" limit="10"]</code> — список товаров</li>
            <li><code>[parusweb_category_info id="123"]</code> — информация о категории</li>
        </ul>
        
        <h2>Кнопки</h2>
        <ul>
            <li><code>[parusweb_add_to_cart id="123" text="Купить"]</code> — кнопка в корзину</li>
            <li><code>[parusweb_product_link id="123"]</code> — ссылка на товар</li>
        </ul>
        
        <h2>Цены</h2>
        <ul>
            <li><code>[parusweb_price id="123" type="regular"]</code> — цена товара</li>
            <li><code>[parusweb_price_range category="123"]</code> — диапазон цен</li>
        </ul>
        
        <h2>Статистика</h2>
        <ul>
            <li><code>[parusweb_product_count category="123"]</code> — кол-во товаров</li>
            <li><code>[parusweb_total_products]</code> — всего товаров</li>
            <li><code>[parusweb_category_count]</code> — кол-во категорий</li>
        </ul>
        
        <h2>Интерфейс</h2>
        <ul>
            <li><code>[parusweb_scroll_top]</code> — кнопка "Наверх"</li>
            <li><code>[parusweb_icon_box icon="🚚" title="Доставка"]</code> — блок с иконкой</li>
        </ul>
        
        <h2>Условия</h2>
        <ul>
            <li><code>[parusweb_logged_in]Контент[/parusweb_logged_in]</code> — только для авторизованных</li>
            <li><code>[parusweb_guest]Контент[/parusweb_guest]</code> — только для гостей</li>
        </ul>
    </div>
    <?php
}
