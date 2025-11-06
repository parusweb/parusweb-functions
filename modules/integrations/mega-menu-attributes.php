<?php
/**
 * Mega Menu Attributes Module
 * 
 * Интеграция атрибутов товаров в мега-меню:
 * - Загрузка атрибутов из JSON файла
 * - Динамическое отображение атрибутов для категорий
 * - Подмена атрибутов при наведении на подкатегории
 * - Автоматический подсчёт количества товаров
 * 
 * Требования:
 * - JSON файл /menu_attributes.json в корне сайта
 * - Структура: { "category_slug": { "attribute_name": [{name, slug, count}] } }
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ЗАГРУЗКА И ОТОБРАЖЕНИЕ АТРИБУТОВ В МЕГА-МЕНЮ
// ============================================================================

/**
 * JavaScript для работы с атрибутами в мега-меню
 */
function parusweb_mega_menu_attributes_script() {
    ?>
    <script>
    jQuery(function($) {
        'use strict';
        
        let cache = null;
        
        /**
         * Загрузка JSON с атрибутами один раз при загрузке страницы
         */
        $.getJSON('<?php echo home_url("/menu_attributes.json"); ?>', function(data) {
            cache = data;
            
            // Рендерим атрибуты для родительских категорий (если есть виджеты)
            $('.widget_layered_nav').each(function() {
                renderAttributes($(this));
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.warn('ParusWeb: Не удалось загрузить menu_attributes.json', textStatus);
        });
        
        /**
         * Подмена атрибутов при наведении на подкатегории в мега-меню
         */
        $(document).on('mouseenter', '.mega-menu-item-type-taxonomy', function() {
            let href = $(this).find('a').attr('href');
            if (!href) return;
            
            // Извлекаем slug категории из URL
            let parts = href.split('/');
            let catSlug = parts.filter(Boolean).pop();
            
            // Обновляем все виджеты атрибутов
            $('.widget_layered_nav').each(function() {
                renderAttributes($(this), catSlug);
            });
        });
        
        /**
         * Рендеринг списка атрибутов в виджете
         * 
         * @param {jQuery} $widget Элемент виджета
         * @param {string} overrideCat Slug категории для переопределения (опционально)
         */
        function renderAttributes($widget, overrideCat) {
            if (!cache) return;
            
            // Получаем атрибут и категорию из data-атрибутов виджета
            let attr = $widget.data('attribute');
            let cat = overrideCat || $widget.data('category');
            
            // Проверяем наличие данных для этой категории и атрибута
            if (cat && attr && cache[cat] && cache[cat][attr]) {
                let $ul = $('<ul class="attribute-list"/>');
                
                // Формируем список атрибутов
                cache[cat][attr].forEach(function(term) {
                    let base = '<?php echo home_url("/product-category/"); ?>' + cat + '/';
                    let attrSlug = attr.replace('pa_', '');
                    let url = base + '?_' + attrSlug + '=' + term.slug;
                    
                    $ul.append(
                        '<li>' +
                            '<a href="' + url + '">' +
                                term.name + 
                                ' <span class="count">(' + term.count + ')</span>' +
                            '</a>' +
                        '</li>'
                    );
                });
                
                $widget.html($ul);
            } else {
                // Если нет данных, показываем сообщение
                $widget.html('<div class="no-attributes">Нет атрибутов</div>');
            }
        }
    });
    </script>
    
    <style>
    /* Стили для списка атрибутов в мега-меню */
    .widget_layered_nav .attribute-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .widget_layered_nav .attribute-list li {
        margin-bottom: 5px;
        padding: 3px 0;
    }
    
    .widget_layered_nav .attribute-list a {
        text-decoration: none;
        color: #333;
        transition: color 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .widget_layered_nav .attribute-list a:hover {
        color: #8bc34a;
    }
    
    .widget_layered_nav .attribute-list .count {
        font-size: 0.85em;
        color: #999;
        margin-left: 5px;
    }
    
    .widget_layered_nav .no-attributes {
        padding: 10px;
        color: #999;
        font-style: italic;
        text-align: center;
    }
    </style>
    <?php
}
add_action('wp_footer', 'parusweb_mega_menu_attributes_script');

// ============================================================================
// БЛОК 2: ГЕНЕРАЦИЯ JSON ФАЙЛА С АТРИБУТАМИ
// ============================================================================

/**
 * Генерация JSON файла с атрибутами товаров для всех категорий
 * Вызывается вручную или по расписанию
 * 
 * @return bool true при успехе, false при ошибке
 */
function parusweb_generate_menu_attributes_json() {
    // Получаем все категории товаров
    $categories = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    ));
    
    if (is_wp_error($categories)) {
        return false;
    }
    
    $data = array();
    
    // Получаем все атрибуты товаров
    $attributes = wc_get_attribute_taxonomies();
    
    foreach ($categories as $category) {
        $cat_slug = $category->slug;
        $data[$cat_slug] = array();
        
        // Для каждого атрибута получаем значения
        foreach ($attributes as $attribute) {
            $taxonomy = 'pa_' . $attribute->attribute_name;
            
            // Получаем термины атрибута для данной категории
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
                'meta_query' => array(
                    array(
                        'key'     => 'product_cat',
                        'value'   => $category->term_id,
                        'compare' => 'LIKE'
                    )
                )
            ));
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $data[$cat_slug][$taxonomy] = array();
                
                foreach ($terms as $term) {
                    // Получаем количество товаров
                    $count = $term->count;
                    
                    $data[$cat_slug][$taxonomy][] = array(
                        'name'  => $term->name,
                        'slug'  => $term->slug,
                        'count' => $count
                    );
                }
            }
        }
    }
    
    // Сохраняем JSON файл
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $file_path = ABSPATH . 'menu_attributes.json';
    
    $result = file_put_contents($file_path, $json);
    
    return $result !== false;
}

/**
 * AJAX обработчик для генерации JSON (для админки)
 */
function parusweb_ajax_generate_menu_attributes() {
    // Проверка прав доступа
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Недостаточно прав');
    }
    
    $result = parusweb_generate_menu_attributes_json();
    
    if ($result) {
        wp_send_json_success('JSON файл успешно создан');
    } else {
        wp_send_json_error('Ошибка при создании JSON файла');
    }
}
add_action('wp_ajax_generate_menu_attributes', 'parusweb_ajax_generate_menu_attributes');

// ============================================================================
// БЛОК 3: АВТОМАТИЧЕСКАЯ РЕГЕНЕРАЦИЯ JSON
// ============================================================================

/**
 * Регенерация JSON при обновлении товара
 */
function parusweb_regenerate_on_product_update($post_id) {
    // Проверяем что это товар
    if (get_post_type($post_id) !== 'product') {
        return;
    }
    
    // Используем транзиент чтобы не генерировать слишком часто
    $transient_key = 'parusweb_menu_json_updated';
    
    if (get_transient($transient_key)) {
        return;
    }
    
    // Генерируем JSON
    parusweb_generate_menu_attributes_json();
    
    // Устанавливаем транзиент на 5 минут
    set_transient($transient_key, true, 5 * MINUTE_IN_SECONDS);
}
add_action('save_post_product', 'parusweb_regenerate_on_product_update');

/**
 * Регенерация JSON при изменении терма (категории или атрибута)
 */
function parusweb_regenerate_on_term_update($term_id, $tt_id, $taxonomy) {
    // Проверяем что это категория товара или атрибут
    if ($taxonomy === 'product_cat' || strpos($taxonomy, 'pa_') === 0) {
        $transient_key = 'parusweb_menu_json_updated';
        
        if (get_transient($transient_key)) {
            return;
        }
        
        parusweb_generate_menu_attributes_json();
        set_transient($transient_key, true, 5 * MINUTE_IN_SECONDS);
    }
}
add_action('edited_term', 'parusweb_regenerate_on_term_update', 10, 3);
add_action('created_term', 'parusweb_regenerate_on_term_update', 10, 3);

// ============================================================================
// БЛОК 4: АДМИН ИНТЕРФЕЙС
// ============================================================================

/**
 * Добавление кнопки генерации JSON в админ меню
 */
function parusweb_add_menu_attributes_admin_page() {
    add_submenu_page(
        'tools.php',
        'Атрибуты меню',
        'Атрибуты меню',
        'manage_options',
        'menu-attributes-generator',
        'parusweb_menu_attributes_admin_page'
    );
}
add_action('admin_menu', 'parusweb_add_menu_attributes_admin_page');

/**
 * HTML страницы генератора JSON
 */
function parusweb_menu_attributes_admin_page() {
    ?>
    <div class="wrap">
        <h1>Генератор атрибутов для мега-меню</h1>
        
        <p>Этот инструмент создаёт JSON файл с атрибутами товаров для использования в мега-меню.</p>
        
        <p>
            <button id="generate-menu-json" class="button button-primary">
                Сгенерировать menu_attributes.json
            </button>
        </p>
        
        <div id="generation-result" style="margin-top: 20px;"></div>
        
        <h2>Информация</h2>
        <ul>
            <li><strong>Файл:</strong> /menu_attributes.json</li>
            <li><strong>Автообновление:</strong> Да (при сохранении товаров/категорий)</li>
            <li><strong>Использование:</strong> Виджеты .widget_layered_nav с data-атрибутами</li>
        </ul>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#generate-menu-json').on('click', function() {
            const $button = $(this);
            const $result = $('#generation-result');
            
            $button.prop('disabled', true).text('Генерация...');
            $result.html('<p>Генерация JSON файла...</p>');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'generate_menu_attributes'
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    $result.html('<div class="notice notice-error"><p>Ошибка AJAX запроса</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Сгенерировать menu_attributes.json');
                }
            });
        });
    });
    </script>
    <?php
}

// ============================================================================
// БЛОК 5: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Проверка существования JSON файла
 * 
 * @return bool true если файл существует
 */
function parusweb_menu_json_exists() {
    return file_exists(ABSPATH . 'menu_attributes.json');
}

/**
 * Получение времени последнего обновления JSON файла
 * 
 * @return int|false Timestamp или false
 */
function parusweb_menu_json_last_modified() {
    $file_path = ABSPATH . 'menu_attributes.json';
    
    if (file_exists($file_path)) {
        return filemtime($file_path);
    }
    
    return false;
}
