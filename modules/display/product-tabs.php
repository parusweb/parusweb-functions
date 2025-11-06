<?php
/**
 * ============================================================================
 * МОДУЛЬ: КАСТОМИЗАЦИЯ ТАБОВ ТОВАРА
 * ============================================================================
 * 
 * Настройка табов на странице товара WooCommerce.
 * 
 * @package ParusWeb_Functions
 * @subpackage Display
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ИЗМЕНЕНИЕ СТАНДАРТНЫХ ТАБОВ
// ============================================================================

/**
 * Изменение названий и приоритетов табов
 */
function parusweb_customize_product_tabs($tabs) {
    global $product;
    
    // Переименование стандартных табов
    if (isset($tabs['description'])) {
        $tabs['description']['title'] = 'Описание';
        $tabs['description']['priority'] = 10;
    }
    
    if (isset($tabs['additional_information'])) {
        $tabs['additional_information']['title'] = 'Характеристики';
        $tabs['additional_information']['priority'] = 20;
    }
    
    if (isset($tabs['reviews'])) {
        $tabs['reviews']['title'] = 'Отзывы (' . $product->get_review_count() . ')';
        $tabs['reviews']['priority'] = 30;
    }
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'parusweb_customize_product_tabs', 98);

// ============================================================================
// ДОБАВЛЕНИЕ НОВЫХ ТАБОВ
// ============================================================================

/**
 * Добавление таба "Доставка и оплата"
 */
function parusweb_add_delivery_tab($tabs) {
    $tabs['delivery'] = [
        'title' => 'Доставка и оплата',
        'priority' => 40,
        'callback' => 'parusweb_delivery_tab_content'
    ];
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'parusweb_add_delivery_tab', 98);

/**
 * Контент таба доставки
 */
function parusweb_delivery_tab_content() {
    ?>
    <div class="delivery-tab-content">
        <h3>🚚 Доставка</h3>
        <p>Мы осуществляем доставку по Москве и Московской области.</p>
        
        <ul>
            <li><strong>По Москве (в пределах МКАД):</strong> от 1500 ₽</li>
            <li><strong>За МКАД:</strong> +50 ₽ за каждый км</li>
            <li><strong>Самовывоз:</strong> бесплатно</li>
        </ul>
        
        <p>Точная стоимость доставки рассчитывается при оформлении заказа.</p>
        
        <h3>💳 Оплата</h3>
        <p>Мы принимаем следующие способы оплаты:</p>
        
        <ul>
            <li>Наличными при получении</li>
            <li>Банковской картой</li>
            <li>Безналичный расчет для юридических лиц</li>
            <li>Онлайн-оплата на сайте</li>
        </ul>
        
        <p><em>При безналичном расчете действует надбавка 10%</em></p>
    </div>
    <style>
    .delivery-tab-content h3 {
        margin-top: 20px;
        margin-bottom: 15px;
        color: #3aa655;
    }
    .delivery-tab-content ul {
        margin: 15px 0;
        padding-left: 20px;
    }
    .delivery-tab-content li {
        margin: 8px 0;
    }
    </style>
    <?php
}

/**
 * Добавление таба "Гарантия"
 */
function parusweb_add_warranty_tab($tabs) {
    $tabs['warranty'] = [
        'title' => 'Гарантия',
        'priority' => 50,
        'callback' => 'parusweb_warranty_tab_content'
    ];
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'parusweb_add_warranty_tab', 98);

/**
 * Контент таба гарантии
 */
function parusweb_warranty_tab_content() {
    global $product;
    
    // Проверяем ACF поле с гарантией
    $warranty_period = get_field('warranty_period', $product->get_id());
    
    ?>
    <div class="warranty-tab-content">
        <h3>🛡️ Гарантия качества</h3>
        
        <?php if ($warranty_period): ?>
            <p><strong>Гарантийный срок:</strong> <?php echo esc_html($warranty_period); ?></p>
        <?php else: ?>
            <p><strong>Гарантийный срок:</strong> 12 месяцев</p>
        <?php endif; ?>
        
        <p>На все товары распространяется гарантия производителя. Мы гарантируем качество поставляемой продукции.</p>
        
        <h4>Условия гарантии:</h4>
        <ul>
            <li>Сохранение товарного вида</li>
            <li>Отсутствие механических повреждений</li>
            <li>Соблюдение условий хранения и эксплуатации</li>
            <li>Наличие документов о покупке</li>
        </ul>
        
        <p>При обнаружении дефектов или брака свяжитесь с нашим отделом продаж.</p>
    </div>
    <?php
}

// ============================================================================
// УСЛОВНОЕ ОТОБРАЖЕНИЕ ТАБОВ
// ============================================================================

/**
 * Таб с инструкцией по монтажу (только для определенных категорий)
 */
function parusweb_add_installation_tab($tabs) {
    global $product;
    $product_id = $product->get_id();
    
    // Проверяем категории, для которых нужен этот таб
    $installation_categories = [266, 270, 268]; // ID категорий столярных изделий
    
    $has_installation = false;
    foreach ($installation_categories as $cat_id) {
        if (has_term($cat_id, 'product_cat', $product_id)) {
            $has_installation = true;
            break;
        }
    }
    
    if ($has_installation) {
        $tabs['installation'] = [
            'title' => 'Монтаж',
            'priority' => 45,
            'callback' => 'parusweb_installation_tab_content'
        ];
    }
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'parusweb_add_installation_tab', 98);

/**
 * Контент таба монтажа
 */
function parusweb_installation_tab_content() {
    global $product;
    
    // Проверяем ACF поле с инструкцией
    $installation_text = get_field('installation_instructions', $product->get_id());
    
    ?>
    <div class="installation-tab-content">
        <h3>🔧 Инструкция по монтажу</h3>
        
        <?php if ($installation_text): ?>
            <div class="custom-installation">
                <?php echo wp_kses_post($installation_text); ?>
            </div>
        <?php else: ?>
            <p>Для качественного монтажа рекомендуем обратиться к профессионалам.</p>
            
            <h4>Общие рекомендации:</h4>
            <ol>
                <li>Подготовьте поверхность перед установкой</li>
                <li>Используйте качественный крепеж</li>
                <li>Соблюдайте технологию монтажа</li>
                <li>При необходимости используйте защитные материалы</li>
            </ol>
            
            <p><strong>Нужна помощь с монтажом?</strong> Мы можем порекомендовать проверенных специалистов.</p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Таб с сертификатами (если есть файлы)
 */
function parusweb_add_certificates_tab($tabs) {
    global $product;
    
    // Проверяем ACF поле с сертификатами
    $certificates = get_field('certificates', $product->get_id());
    
    if ($certificates && is_array($certificates) && count($certificates) > 0) {
        $tabs['certificates'] = [
            'title' => 'Сертификаты',
            'priority' => 55,
            'callback' => 'parusweb_certificates_tab_content'
        ];
    }
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'parusweb_add_certificates_tab', 98);

/**
 * Контент таба сертификатов
 */
function parusweb_certificates_tab_content() {
    global $product;
    
    $certificates = get_field('certificates', $product->get_id());
    
    if ($certificates && is_array($certificates)) {
        ?>
        <div class="certificates-tab-content">
            <h3>📜 Сертификаты и документация</h3>
            <div class="certificates-grid">
                <?php foreach ($certificates as $certificate): ?>
                    <div class="certificate-item">
                        <?php if ($certificate['mime_type'] === 'application/pdf'): ?>
                            <a href="<?php echo esc_url($certificate['url']); ?>" target="_blank" class="certificate-link">
                                <span class="certificate-icon">📄</span>
                                <span class="certificate-name"><?php echo esc_html($certificate['title']); ?></span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($certificate['url']); ?>" target="_blank">
                                <img src="<?php echo esc_url($certificate['url']); ?>" alt="<?php echo esc_attr($certificate['title']); ?>" />
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .certificate-item {
            text-align: center;
        }
        .certificate-item img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .certificate-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .certificate-link:hover {
            background: #e8f5e9;
            transform: translateY(-2px);
        }
        .certificate-icon {
            font-size: 48px;
        }
        .certificate-name {
            font-weight: 600;
        }
        </style>
        <?php
    }
}

// ============================================================================
// УДАЛЕНИЕ НЕНУЖНЫХ ТАБОВ
// ============================================================================

/**
 * Удаление табов для определенных категорий
 */
function parusweb_remove_tabs_for_categories($tabs) {
    global $product;
    $product_id = $product->get_id();
    
    // Убираем "Дополнительная информация" если она пустая
    if (isset($tabs['additional_information'])) {
        $attributes = $product->get_attributes();
        if (empty($attributes)) {
            unset($tabs['additional_information']);
        }
    }
    
    // Убираем отзывы для товаров без отзывов
    if (isset($tabs['reviews']) && $product->get_review_count() === 0) {
        unset($tabs['reviews']);
    }
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'parusweb_remove_tabs_for_categories', 99);

// ============================================================================
// СТИЛИЗАЦИЯ ТАБОВ
// ============================================================================

/**
 * Добавление кастомных стилей для табов
 */
function parusweb_product_tabs_styles() {
    if (!is_product()) return;
    ?>
    <style>
    .woocommerce-tabs ul.tabs {
        padding: 0;
        margin: 0 0 20px;
        list-style: none;
        border-bottom: 2px solid #e0e0e0;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .woocommerce-tabs ul.tabs li {
        margin: 0;
        padding: 0;
        background: none;
        border: none;
    }
    .woocommerce-tabs ul.tabs li a {
        display: block;
        padding: 12px 20px;
        color: #666;
        text-decoration: none;
        font-weight: 600;
        border-radius: 8px 8px 0 0;
        transition: all 0.3s;
        background: #f5f5f5;
    }
    .woocommerce-tabs ul.tabs li a:hover {
        color: #3aa655;
        background: #e8f5e9;
    }
    .woocommerce-tabs ul.tabs li.active a {
        color: #3aa655;
        background: #ffffff;
        border-bottom: 3px solid #3aa655;
    }
    .woocommerce-tabs .panel {
        background: #ffffff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    @media (max-width: 768px) {
        .woocommerce-tabs ul.tabs {
            flex-direction: column;
        }
        .woocommerce-tabs ul.tabs li {
            width: 100%;
        }
        .woocommerce-tabs .panel {
            padding: 20px;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'parusweb_product_tabs_styles');
