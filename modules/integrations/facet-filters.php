<?php
/**
 * FacetWP Integration Module
 * 
 * Кастомизация фильтров FacetWP для улучшения UX:
 * - Замена стандартных текстов на более понятные
 * - Автоматическое добавление заголовков к фильтрам
 * - Русификация интерфейса
 * - Отслеживание динамических изменений
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ЗАМЕНА ТЕКСТА В ФИЛЬТРАХ
// ============================================================================

/**
 * Замена стандартных текстов FacetWP на кастомные
 * Заменяет "Посмотреть X Подробнее" на "Развернуть (еще X)"
 */
function parusweb_facet_text_replacement() {
    ?>
    <script>
    (function() {
        'use strict';
        
        document.addEventListener('DOMContentLoaded', function() {
            
            /**
             * Функция для замены текста в элементах FacetWP
             */
            function replaceFacetWPText() {
                // Основные элементы с кнопками раскрытия
                const toggleElements = document.querySelectorAll('.facetwp-toggle');
                
                toggleElements.forEach(function(element) {
                    // Регулярное выражение для поиска "Посмотреть X Подробнее"
                    const regex = /Посмотреть\s+(\d+)\s+Подробнее/gi;
                    
                    if (element.textContent && regex.test(element.textContent)) {
                        element.textContent = element.textContent.replace(
                            regex, 
                            'Развернуть (еще $1)'
                        );
                    }
                });
                
                // Дополнительные элементы (на случай разной структуры)
                const otherElements = document.querySelectorAll(
                    '.facetwp-expand, .facetwp-collapse, [class*="facet"] a, [class*="facet"] span'
                );
                
                otherElements.forEach(function(element) {
                    const regex = /Посмотреть\s+(\d+)\s+Подробнее/gi;
                    
                    if (element.textContent && regex.test(element.textContent)) {
                        element.textContent = element.textContent.replace(
                            regex, 
                            'Раскрыть $1'
                        );
                    }
                });
            }
            
            // Запускаем замену при загрузке страницы
            replaceFacetWPText();
            
            // Запускаем замену после каждого обновления FacetWP
            document.addEventListener('facetwp-loaded', function() {
                setTimeout(replaceFacetWPText, 100);
            });
            
            // MutationObserver для отслеживания динамических изменений DOM
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        replaceFacetWPText();
                    }
                });
            });
            
            // Наблюдаем за контейнером с фильтрами
            const facetContainer = document.querySelector('.facetwp-template');
            if (facetContainer) {
                observer.observe(facetContainer, {
                    childList: true,
                    subtree: true
                });
            }
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_facet_text_replacement');

// ============================================================================
// БЛОК 2: АВТОМАТИЧЕСКИЕ ЗАГОЛОВКИ ФИЛЬТРОВ
// ============================================================================

/**
 * Добавление заголовков к фильтрам FacetWP
 * Создаёт H4 заголовки с названиями фильтров
 */
function parusweb_facet_titles() {
    ?>
    <script>
    (function() {
        'use strict';
        
        document.addEventListener('DOMContentLoaded', function() {
            
            /**
             * Карта соответствия: data-name фильтра → читаемое название
             */
            const facetMap = {
                'poroda': 'Порода',
                'sort_': 'Сорт',
                'profil': 'Профиль', 
                'dlina': 'Длина',
                'shirina': 'Ширина',
                'tolshina': 'Толщина',
                'proizvoditel': 'Производитель',
                'krepej': 'Крепёж',
                'tip': 'Тип',
                'brend': 'Бренд',
                'cvet': 'Цвет',
                'razmer': 'Размер',
                'material': 'Материал',
                'naznachenie': 'Назначение',
                'forma': 'Форма',
                'pokrietie': 'Покрытие'
            };
            
            /**
             * Функция добавления заголовков
             */
            function addFacetTitles() {
                // Находим все фильтры
                const facets = document.querySelectorAll('.facetwp-facet');
                
                facets.forEach(function(facet) {
                    const facetName = facet.getAttribute('data-name');
                    const titleText = facetMap[facetName];
                    
                    if (!titleText) return;
                    
                    // Проверяем, есть ли уже заголовок
                    const prevElement = facet.previousElementSibling;
                    const hasTitle = prevElement && 
                                   prevElement.classList.contains('facet-title-added');
                    
                    // Проверяем, есть ли внутри элементы (фильтр не пустой)
                    const hasContent = facet.querySelector('.facetwp-checkbox') || 
                                     facet.querySelector('.facetwp-search') ||
                                     facet.querySelector('.facetwp-slider') ||
                                     facet.querySelector('.facetwp-radio') ||
                                     facet.innerHTML.trim() !== '';
                    
                    // Добавляем заголовок если его нет и есть контент
                    if (!hasTitle && hasContent) {
                        const title = document.createElement('div');
                        title.className = 'facet-title-added';
                        title.innerHTML = '<h4 style="' +
                            'margin: 20px 0 10px 0; ' +
                            'padding: 8px 0 5px 0; ' +
                            'font-size: 16px; ' +
                            'font-weight: 600; ' +
                            'color: #333; ' +
                            'border-bottom: 2px solid #8bc34a; ' +
                            'text-transform: uppercase; ' +
                            'letter-spacing: 0.5px;' +
                        '">' + titleText + '</h4>';
                        
                        // Вставляем перед фильтром
                        facet.parentNode.insertBefore(title, facet);
                    }
                    
                    // Удаляем заголовок если фильтр стал пустым
                    if (hasTitle && !hasContent) {
                        const titleElement = facet.previousElementSibling;
                        if (titleElement && titleElement.classList.contains('facet-title-added')) {
                            titleElement.remove();
                        }
                    }
                });
            }
            
            // Запускаем сразу
            addFacetTitles();
            
            // Запускаем с интервалом для динамических фильтров
            const interval = setInterval(addFacetTitles, 300);
            
            // Останавливаем через 10 секунд
            setTimeout(function() {
                clearInterval(interval);
            }, 10000);
            
            // События FacetWP
            if (typeof FWP !== 'undefined') {
                document.addEventListener('facetwp-loaded', addFacetTitles);
                document.addEventListener('facetwp-refresh', addFacetTitles);
            }
            
            // MutationObserver для отслеживания изменений DOM
            const observer = new MutationObserver(addFacetTitles);
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    })();
    </script>
    
    <style>
    /* Дополнительные стили для заголовков фильтров */
    .facet-title-added h4 {
        transition: color 0.3s ease;
    }
    .facet-title-added h4:hover {
        color: #8bc34a;
    }
    </style>
    <?php
}
add_action('wp_footer', 'parusweb_facet_titles');

// ============================================================================
// БЛОК 3: ФИЛЬТРЫ WORDPRESS ДЛЯ FACETWP
// ============================================================================

/**
 * Кастомизация вывода значений фильтров
 * 
 * @param string $label Текст метки
 * @param array $params Параметры фильтра
 * @return string Модифицированная метка
 */
function parusweb_facetwp_facet_label($label, $params) {
    // Пример: добавление иконок к определённым значениям
    // if ($params['facet_name'] === 'poroda') {
    //     $label = '🌲 ' . $label;
    // }
    
    return $label;
}
// add_filter('facetwp_facet_label', 'parusweb_facetwp_facet_label', 10, 2);

/**
 * Изменение количества элементов до появления кнопки "Показать больше"
 * 
 * @param int $count Количество элементов
 * @param array $params Параметры фильтра
 * @return int Новое количество
 */
function parusweb_facetwp_facet_dropdown_show_counts($count, $params) {
    // По умолчанию 10, можно изменить для конкретных фильтров
    if ($params['facet_name'] === 'poroda') {
        return 15;
    }
    
    return $count;
}
// add_filter('facetwp_facet_dropdown_show_counts', 'parusweb_facetwp_facet_dropdown_show_counts', 10, 2);

/**
 * Сортировка значений фильтра
 * 
 * @param array $values Массив значений
 * @param array $params Параметры фильтра
 * @return array Отсортированный массив
 */
function parusweb_facetwp_sort_options($values, $params) {
    // Пример: сортировка по алфавиту для конкретного фильтра
    // if ($params['facet_name'] === 'poroda') {
    //     usort($values, function($a, $b) {
    //         return strcmp($a['facet_display_value'], $b['facet_display_value']);
    //     });
    // }
    
    return $values;
}
// add_filter('facetwp_facet_render_args', 'parusweb_facetwp_sort_options', 10, 2);

// ============================================================================
// БЛОК 4: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Получение карты названий фильтров
 * 
 * @return array Ассоциативный массив [slug => название]
 */
function parusweb_get_facet_names() {
    return array(
        'poroda'        => 'Порода',
        'sort_'         => 'Сорт',
        'profil'        => 'Профиль',
        'dlina'         => 'Длина',
        'shirina'       => 'Ширина',
        'tolshina'      => 'Толщина',
        'proizvoditel'  => 'Производитель',
        'krepej'        => 'Крепёж',
        'tip'           => 'Тип',
        'brend'         => 'Бренд',
        'cvet'          => 'Цвет',
        'razmer'        => 'Размер',
        'material'      => 'Материал',
        'naznachenie'   => 'Назначение',
        'forma'         => 'Форма',
        'pokrietie'     => 'Покрытие'
    );
}

/**
 * Проверка активности FacetWP
 * 
 * @return bool true если FacetWP активен
 */
function parusweb_is_facetwp_active() {
    return class_exists('FacetWP');
}

/**
 * Получение активных фильтров
 * 
 * @return array Массив активных фильтров
 */
function parusweb_get_active_facets() {
    if (!parusweb_is_facetwp_active()) {
        return array();
    }
    
    global $wpdb;
    
    $results = $wpdb->get_results("
        SELECT facet_name, facet_value 
        FROM {$wpdb->prefix}facetwp_index 
        WHERE facet_value != ''
        GROUP BY facet_name, facet_value
    ");
    
    $active_facets = array();
    
    foreach ($results as $row) {
        if (!isset($active_facets[$row->facet_name])) {
            $active_facets[$row->facet_name] = array();
        }
        $active_facets[$row->facet_name][] = $row->facet_value;
    }
    
    return $active_facets;
}
