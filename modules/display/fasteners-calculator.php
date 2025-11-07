<?php
/**
 * Система расчёта крепежа для пиломатериалов - ФИНАЛЬНАЯ ВЕРСИЯ
 * 
 * @package ParusWeb_Functions
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ACF ПОЛЯ ДЛЯ КАТЕГОРИЙ
// ============================================================================

add_action('acf/init', 'pw_register_fasteners_category_fields');

function pw_register_fasteners_category_fields() {
    if (!function_exists('acf_add_local_field_group')) return;
    
    acf_add_local_field_group(array(
        'key' => 'group_fasteners_calculator',
        'title' => 'Калькулятор крепежа',
        'fields' => array(
            array(
                'key' => 'field_enable_fasteners_calc',
                'label' => 'Включить расчёт крепежа',
                'name' => 'enable_fasteners_calc',
                'type' => 'true_false',
                'instructions' => 'Добавить калькулятор крепежа к товарам этой категории',
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_fasteners_type',
                'label' => 'Тип крепежа',
                'name' => 'fasteners_type',
                'type' => 'select',
                'instructions' => 'Выберите тип крепежа для расчёта',
                'choices' => array(
                    'kleimer' => 'Кляймер (евровагонка, блокхаус)',
                    'screw' => 'Крепёж (планкен, террасная доска)',
                ),
                'default_value' => 'kleimer',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_enable_fasteners_calc',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_fasteners_board_width',
                'label' => 'Ширина доски (мм)',
                'name' => 'fasteners_board_width',
                'type' => 'number',
                'instructions' => 'Стандартная ширина доски для этой категории (например, 120мм для фанеры)',
                'default_value' => 120,
                'min' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_enable_fasteners_calc',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_fasteners_products',
                'label' => 'Товары крепежа',
                'name' => 'fasteners_products',
                'type' => 'repeater',
                'instructions' => 'Добавьте товары крепежа, которые будут доступны для выбора',
                'layout' => 'table',
                'button_label' => 'Добавить крепёж',
                'sub_fields' => array(
                    array(
                        'key' => 'field_fastener_product',
                        'label' => 'Товар',
                        'name' => 'product',
                        'type' => 'post_object',
                        'post_type' => array('product'),
                        'return_format' => 'id',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_fastener_width_min',
                        'label' => 'Мин. ширина (мм)',
                        'name' => 'width_min',
                        'type' => 'number',
                        'instructions' => 'Минимальная ширина доски для этого крепежа',
                        'default_value' => 85,
                        'min' => 1,
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_fastener_width_max',
                        'label' => 'Макс. ширина (мм)',
                        'name' => 'width_max',
                        'type' => 'number',
                        'instructions' => 'Максимальная ширина доски для этого крепежа',
                        'default_value' => 90,
                        'min' => 1,
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_fastener_qty_per_sqm',
                        'label' => 'Кол-во на м²',
                        'name' => 'qty_per_sqm',
                        'type' => 'number',
                        'instructions' => 'Количество штук на квадратный метр',
                        'default_value' => 30,
                        'min' => 1,
                        'required' => 1,
                    ),
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_enable_fasteners_calc',
                            'operator' => '==',
                            'value' => '1',
                        ),
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
    ));
}

// ============================================================================
// ACF ПОЛЯ ДЛЯ ТОВАРОВ КРЕПЕЖА
// ============================================================================

add_action('acf/init', 'pw_register_fastener_product_fields');

function pw_register_fastener_product_fields() {
    if (!function_exists('acf_add_local_field_group')) return;
    
    acf_add_local_field_group(array(
        'key' => 'group_fastener_product',
        'title' => 'Настройки крепежа',
        'fields' => array(
            array(
                'key' => 'field_is_fastener_product',
                'label' => 'Это товар-крепёж',
                'name' => 'is_fastener_product',
                'type' => 'true_false',
                'instructions' => 'Отметьте, если это товар крепежа (кляймер, саморезы и т.д.)',
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_fastener_pieces_per_pack',
                'label' => 'Количество штук в упаковке',
                'name' => 'fastener_pieces_per_pack',
                'type' => 'number',
                'instructions' => 'Сколько штук крепежа в одной упаковке (например, 150)',
                'default_value' => 100,
                'min' => 1,
                'required' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_is_fastener_product',
                            'operator' => '==',
                            'value' => '1',
                        ),
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
    ));
}

// ============================================================================
// ПОЛУЧЕНИЕ ДАННЫХ КРЕПЕЖА ДЛЯ КАТЕГОРИИ
// ============================================================================

function pw_get_category_fasteners_data($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return null;
    
    $category_ids = $product->get_category_ids();
    if (empty($category_ids)) return null;
    
    // Ищем первую категорию с включённым калькулятором крепежа
    foreach ($category_ids as $cat_id) {
        $term_id = 'product_cat_' . $cat_id;
        $enabled = get_field('enable_fasteners_calc', $term_id);
        
        if ($enabled) {
            $type = get_field('fasteners_type', $term_id);
            $board_width = intval(get_field('fasteners_board_width', $term_id)) ?: 120;
            $products = get_field('fasteners_products', $term_id);
            
            if (!empty($products)) {
                return array(
                    'enabled' => true,
                    'type' => $type,
                    'board_width' => $board_width,
                    'products' => $products,
                );
            }
        }
    }
    
    return null;
}

// ============================================================================
// ВЫВОД КАЛЬКУЛЯТОРА КРЕПЕЖА
// ============================================================================

add_action('woocommerce_before_add_to_cart_button', 'pw_output_fasteners_calculator', 15);

function pw_output_fasteners_calculator() {
    global $product;
    
    if (!$product) return;
    
    $product_id = $product->get_id();
    
    // Проверяем, включён ли калькулятор квадратных метров ИЛИ есть площадь упаковки
    $is_square_meter = get_post_meta($product_id, '_square_meter_pricing', true) === 'yes';
    $is_target = function_exists('is_in_target_categories') ? is_in_target_categories($product_id) : false;
    
    $pack_area = 0;
    if (function_exists('extract_area_with_qty')) {
        $pack_area = extract_area_with_qty($product->get_name(), $product_id);
    }
    
    // Калькулятор крепежа работает для квадратных метров ИЛИ площади упаковки
    if (!$is_square_meter && !($is_target && $pack_area)) return;
    
    // Получаем данные крепежа для категории
    if (!function_exists('pw_get_category_fasteners_data')) return;
    
    $fasteners_data = pw_get_category_fasteners_data($product_id);
    if (!$fasteners_data) return;
    
    // Фильтруем товары только из категорий крепежа (77-80, 123)
    $fastener_categories = array(77, 78, 79, 80, 123);
    
    // Подготавливаем данные для JS
    $fasteners_products = array();
    foreach ($fasteners_data['products'] as $fastener) {
        $fastener_product_id = $fastener['product'];
        $fastener_product = wc_get_product($fastener_product_id);
        
        if (!$fastener_product) continue;
        
        // Проверяем категорию товара
        $product_categories = wp_get_post_terms($fastener_product_id, 'product_cat', array('fields' => 'ids'));
        $has_fastener_category = !empty(array_intersect($product_categories, $fastener_categories));
        
        if (!$has_fastener_category) continue;
        
        // Получаем количество штук в упаковке
        $pieces_per_pack = intval(get_field('fastener_pieces_per_pack', $fastener_product_id));
        if (!$pieces_per_pack) {
            $name = $fastener_product->get_name();
            if (preg_match('/\((\d+)\s*шт/', $name, $matches)) {
                $pieces_per_pack = intval($matches[1]);
            } else {
                $pieces_per_pack = 100;
            }
        }
        
        $fasteners_products[] = array(
            'id' => $fastener_product_id,
            'name' => $fastener_product->get_name(),
            'price' => floatval($fastener_product->get_price()),
            'width_min' => intval($fastener['width_min']),
            'width_max' => intval($fastener['width_max']),
            'qty_per_sqm' => intval($fastener['qty_per_sqm']),
            'pieces_per_pack' => $pieces_per_pack,
        );
    }
    
    if (empty($fasteners_products)) return;
    
    $board_width = $fasteners_data['board_width'];
    
    ?>
    <script type="text/javascript">
    (function() {
        // Данные крепежа
        const fastenersData = <?php echo json_encode($fasteners_products); ?>;
        const defaultBoardWidth = <?php echo $board_width; ?>;
        const packAreaValue = <?php echo $pack_area ? $pack_area : 0; ?>;
        
        console.log('=== Калькулятор крепежа инициализирован ===');
        console.log('defaultBoardWidth:', defaultBoardWidth);
        console.log('packAreaValue:', packAreaValue);
        console.log('fastenersData:', fastenersData);
        
        // Функции
        function findFastenerByWidth(width_mm) {
            for (let fastener of fastenersData) {
                if (width_mm >= fastener.width_min && width_mm <= fastener.width_max) {
                    return fastener;
                }
            }
            return null;
        }
        
        function calculateFastenerQuantity(area_sqm, width_mm) {
            if (!area_sqm || !width_mm) return 0;
            const width_m = width_mm / 1000;
            const quantity = (area_sqm / width_m) * 2.7;
            return Math.ceil(quantity);
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                console.log('Запуск инициализации блока крепежа');
                
                const calcArea = document.getElementById('calc-area');
                const calcSq = document.getElementById('calc-square-meter');
                const targetCalc = calcSq || calcArea;
                
                if (!targetCalc) {
                    console.error('Целевой калькулятор не найден!');
                    return;
                }
                
                console.log('Целевой калькулятор найден:', targetCalc.id);
                
                // Создаём блок
                const fastenersBlock = document.createElement('div');
                fastenersBlock.id = 'fasteners-calculator-block';
                fastenersBlock.style.cssText = 'margin-top:20px; padding:15px; background:#f0fff4; border:2px solid #22c55e; border-radius:8px;';
                
                let html = '<h4 style="margin:0 0 15px 0; color:#16a34a;">Расчёт крепежа</h4>';
                html += '<div id="fasteners_auto_suggestion" style="margin-bottom:15px; padding:10px; background:#dcfce7; border-radius:5px; display:none;"></div>';
                html += '<label style="display:block; margin-bottom:10px; font-weight:500;">Выберите крепёж:</label>';
                html += '<select id="fastener_select" style="width:100%; max-width:600px; padding:8px; border:1px solid #ddd; border-radius:4px; background:#fff; margin-bottom:15px;">';
                html += '<option value="">-- Выберите крепёж --</option>';
                
                fastenersData.forEach(function(fastener) {
                    html += '<option value="' + fastener.id + '" ';
                    html += 'data-width-min="' + fastener.width_min + '" ';
                    html += 'data-width-max="' + fastener.width_max + '" ';
                    html += 'data-qty-per-sqm="' + fastener.qty_per_sqm + '" ';
                    html += 'data-pieces-per-pack="' + fastener.pieces_per_pack + '">';
                    html += fastener.name + ' (' + fastener.width_min + '-' + fastener.width_max + 'мм)</option>';
                });
                
                html += '</select>';
                html += '<div id="fastener_calculation_result" style="padding:10px; background:#fff; border-radius:5px; display:none;"></div>';
                
                fastenersBlock.innerHTML = html;
                targetCalc.appendChild(fastenersBlock);
                
                console.log('Блок добавлен в DOM');
                
                // Получаем элементы
                const fastenerSelect = document.getElementById('fastener_select');
                const fastenerResult = document.getElementById('fastener_calculation_result');
                const fastenerSuggestion = document.getElementById('fasteners_auto_suggestion');
                const customArea = document.getElementById('custom_area');
                const sqWidthEl = document.getElementById('sq_width');
                const sqLengthEl = document.getElementById('sq_length');
                const quantityInput = document.querySelector('input.qty[name="quantity"]');
                
                console.log('Элементы получены:');
                console.log('- fastenerSelect:', !!fastenerSelect);
                console.log('- customArea:', !!customArea);
                console.log('- sqWidthEl:', !!sqWidthEl);
                
                if (!fastenerSelect) {
                    console.error('fastenerSelect не найден!');
                    return;
                }
                
                // Функция обновления
                function updateFastenerCalculation() {
                    console.log('>>> updateFastenerCalculation()');
                    
                    let widthValue = 0;
                    let areaValue = 0;
                    
                    // Для калькулятора квадратных метров
                    if (sqWidthEl && sqLengthEl) {
                        widthValue = parseFloat(sqWidthEl.value);
                        const lengthValue = parseFloat(sqLengthEl.value);
                        
                        console.log('Кв.метры: ширина=', widthValue, ', длина=', lengthValue);
                        
                        if (widthValue && lengthValue) {
                            areaValue = (widthValue / 1000) * lengthValue;
                        }
                    }
                    // Для калькулятора площади упаковки
                    else if (customArea) {
                        const inputArea = parseFloat(customArea.value);
                        
                        console.log('Площадь упаковки: введено=', inputArea);
                        
                        if (!inputArea || inputArea <= 0) {
                            console.log('Нет площади');
                            fastenerResult.style.display = 'none';
                            fastenerSuggestion.style.display = 'none';
                            return;
                        }
                        
                        const packs = Math.ceil(inputArea / packAreaValue);
                        areaValue = packs * packAreaValue;
                        widthValue = defaultBoardWidth;
                        
                        console.log('Упаковок:', packs, ', реальная площадь:', areaValue, ', ширина:', widthValue);
                    }
                    
                    if (!widthValue || !areaValue) {
                        console.log('Нет данных для расчёта');
                        fastenerResult.style.display = 'none';
                        fastenerSuggestion.style.display = 'none';
                        return;
                    }
                    
                    const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
                    const totalArea = areaValue * quantity;
                    
                    console.log('Общая площадь:', totalArea);
                    
                    // Автоподбор
                    const suggestedFastener = findFastenerByWidth(widthValue);
                    if (suggestedFastener) {
                        console.log('Рекомендуем:', suggestedFastener.name);
                        fastenerSuggestion.innerHTML = '<strong>💡 Рекомендуем:</strong> ' + suggestedFastener.name + ' (' + suggestedFastener.width_min + '-' + suggestedFastener.width_max + 'мм)';
                        fastenerSuggestion.style.display = 'block';
                        
                        if (!fastenerSelect.value) {
                            fastenerSelect.value = suggestedFastener.id;
                            console.log('Автовыбор крепежа:', suggestedFastener.id);
                        }
                    }
                    
                    // Расчёт
                    if (!fastenerSelect.value || fastenerSelect.value === '') {
                        console.log('Крепёж не выбран');
                        fastenerResult.style.display = 'none';
                        return;
                    }
                    
                    const fastenerId = parseInt(fastenerSelect.value);
                    const selectedOption = fastenerSelect.options[fastenerSelect.selectedIndex];
                    const piecesPerPack = parseInt(selectedOption.dataset.piecesPerPack);
                    const selectedFastener = fastenersData.find(f => f.id === fastenerId);
                    
                    if (!selectedFastener) {
                        console.error('Крепёж не найден!');
                        return;
                    }
                    
                    console.log('Выбран крепёж:', selectedFastener.name);
                    console.log('Штук в упаковке:', piecesPerPack);
                    
                    const calculatedQty = calculateFastenerQuantity(totalArea, widthValue);
                    const packsNeeded = Math.ceil(calculatedQty / piecesPerPack);
                    const totalPieces = packsNeeded * piecesPerPack;
                    const totalPrice = packsNeeded * selectedFastener.price;
                    
                    console.log('Требуется штук:', calculatedQty);
                    console.log('Нужно упаковок:', packsNeeded);
                    console.log('Стоимость:', totalPrice);
                    
                    // Вывод
                    let resultHTML = '<p><strong>Расчёт для площади:</strong> ' + totalArea.toFixed(2) + ' м² (ширина доски: ' + widthValue + 'мм)</p>';
                    resultHTML += '<p>Требуется крепежа: <strong>' + calculatedQty + ' шт</strong></p>';
                    resultHTML += '<p>Необходимо купить: <strong>' + packsNeeded + ' упаковок</strong> (' + totalPieces + ' шт крепежа)</p>';
                    resultHTML += '<p>Стоимость крепежа: <strong>' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</strong></p>';
                    
                    fastenerResult.innerHTML = resultHTML;
                    fastenerResult.style.display = 'block';
                    
                    console.log('Результат выведен!');
                    
                    // Скрытые поля
                    removeHiddenFields('fastener_');
                    addHiddenField('fastener_product_id', fastenerId);
                    addHiddenField('fastener_quantity_needed', calculatedQty);
                    addHiddenField('fastener_packs_needed', packsNeeded);
                    addHiddenField('fastener_total_pieces', totalPieces);
                    addHiddenField('fastener_total_price', totalPrice);
                    addHiddenField('fastener_width_used', widthValue);
                    addHiddenField('fastener_area_used', totalArea);
                    
                    console.log('Скрытые поля добавлены');
                }
                
                // Обработчики СНАРУЖИ функции updateFastenerCalculation
                console.log('Привязываем обработчики событий');
                
                fastenerSelect.addEventListener('change', function() {
                    console.log('!!! SELECT CHANGED !!!');
                    updateFastenerCalculation();
                });
                
                if (customArea) {
                    customArea.addEventListener('input', function() {
                        console.log('!!! AREA INPUT !!!');
                        updateFastenerCalculation();
                    });
                }
                
                if (sqWidthEl) {
                    sqWidthEl.addEventListener('change', function() {
                        console.log('!!! WIDTH CHANGED !!!');
                        updateFastenerCalculation();
                    });
                }
                
                if (sqLengthEl) {
                    sqLengthEl.addEventListener('change', function() {
                        console.log('!!! LENGTH CHANGED !!!');
                        updateFastenerCalculation();
                    });
                }
                
                if (quantityInput) {
                    quantityInput.addEventListener('change', function() {
                        console.log('!!! QUANTITY CHANGED !!!');
                        updateFastenerCalculation();
                    });
                }
                
                console.log('Обработчики привязаны, запускаем первичный расчёт');
                
                // Первичный запуск
                setTimeout(function() {
                    updateFastenerCalculation();
                }, 100);
                
            }, 200);
        });
    })();
    </script>
    <?php
}

// ============================================================================
// ДОБАВЛЕНИЕ КРЕПЕЖА В КОРЗИНУ ВМЕСТЕ С ТОВАРОМ
// ============================================================================

add_filter('woocommerce_add_cart_item_data', 'pw_save_fastener_data_to_cart', 10, 2);

function pw_save_fastener_data_to_cart($cart_item_data, $product_id) {
    // Проверяем наличие данных крепежа
    if (isset($_POST['fastener_product_id']) && !empty($_POST['fastener_product_id'])) {
        $cart_item_data['fastener_data'] = array(
            'product_id' => intval($_POST['fastener_product_id']),
            'quantity_needed' => isset($_POST['fastener_quantity_needed']) ? intval($_POST['fastener_quantity_needed']) : 0,
            'packs_needed' => isset($_POST['fastener_packs_needed']) ? intval($_POST['fastener_packs_needed']) : 0,
            'total_pieces' => isset($_POST['fastener_total_pieces']) ? intval($_POST['fastener_total_pieces']) : 0,
            'total_price' => isset($_POST['fastener_total_price']) ? floatval($_POST['fastener_total_price']) : 0,
            'width_used' => isset($_POST['fastener_width_used']) ? intval($_POST['fastener_width_used']) : 0,
            'area_used' => isset($_POST['fastener_area_used']) ? floatval($_POST['fastener_area_used']) : 0,
        );
    }
    
    return $cart_item_data;
}

// Автоматически добавляем крепёж в корзину
add_action('woocommerce_add_to_cart', 'pw_auto_add_fastener_to_cart', 10, 6);

function pw_auto_add_fastener_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    // Проверяем, есть ли данные крепежа
    if (!isset($cart_item_data['fastener_data'])) return;
    
    $fastener_data = $cart_item_data['fastener_data'];
    $fastener_product_id = $fastener_data['product_id'];
    $packs_needed = $fastener_data['packs_needed'];
    
    if (!$fastener_product_id || !$packs_needed) return;
    
    // Добавляем крепёж в корзину
    $fastener_cart_item_data = array(
        'added_with_product' => $product_id,
        'parent_cart_item_key' => $cart_item_key,
        'fastener_details' => array(
            'quantity_needed' => $fastener_data['quantity_needed'],
            'width_used' => $fastener_data['width_used'],
            'area_used' => $fastener_data['area_used'],
        ),
    );
    
    WC()->cart->add_to_cart($fastener_product_id, $packs_needed, 0, array(), $fastener_cart_item_data);
}

// Отображение информации о крепеже в корзине
add_filter('woocommerce_get_item_data', 'pw_display_fastener_data_in_cart', 10, 2);

function pw_display_fastener_data_in_cart($item_data, $cart_item) {
    if (isset($cart_item['fastener_data'])) {
        $fastener_data = $cart_item['fastener_data'];
        
        $item_data[] = array(
            'key' => 'Расчёт крепежа',
            'value' => 'Площадь: ' . $fastener_data['area_used'] . ' м² (ширина: ' . $fastener_data['width_used'] . 'мм)',
        );
        
        $item_data[] = array(
            'key' => 'Требуется крепежа',
            'value' => $fastener_data['quantity_needed'] . ' шт',
        );
        
        $item_data[] = array(
            'key' => 'Упаковок',
            'value' => $fastener_data['packs_needed'] . ' шт (' . $fastener_data['total_pieces'] . ' шт крепежа)',
        );
    }
    
    if (isset($cart_item['added_with_product'])) {
        $parent_product = wc_get_product($cart_item['added_with_product']);
        if ($parent_product) {
            $item_data[] = array(
                'key' => 'Добавлен с товаром',
                'value' => $parent_product->get_name(),
            );
        }
        
        if (isset($cart_item['fastener_details'])) {
            $details = $cart_item['fastener_details'];
            $item_data[] = array(
                'key' => 'Для площади',
                'value' => $details['area_used'] . ' м² (ширина доски: ' . $details['width_used'] . 'мм)',
            );
            
            $item_data[] = array(
                'key' => 'Расчётное количество',
                'value' => $details['quantity_needed'] . ' шт',
            );
        }
    }
    
    return $item_data;
}

// Сохранение данных крепежа в заказе
add_action('woocommerce_checkout_create_order_line_item', 'pw_save_fastener_data_to_order', 10, 4);

function pw_save_fastener_data_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['fastener_data'])) {
        $fastener_data = $values['fastener_data'];
        
        $item->add_meta_data('Площадь для расчёта', $fastener_data['area_used'] . ' м²', true);
        $item->add_meta_data('Ширина доски', $fastener_data['width_used'] . ' мм', true);
        $item->add_meta_data('Требуется крепежа', $fastener_data['quantity_needed'] . ' шт', true);
        $item->add_meta_data('Упаковок крепежа', $fastener_data['packs_needed'] . ' шт', true);
        $item->add_meta_data('Всего крепежа', $fastener_data['total_pieces'] . ' шт', true);
    }
    
    if (isset($values['added_with_product'])) {
        $parent_product = wc_get_product($values['added_with_product']);
        if ($parent_product) {
            $item->add_meta_data('Добавлен с товаром', $parent_product->get_name(), true);
        }
        
        if (isset($values['fastener_details'])) {
            $details = $values['fastener_details'];
            $item->add_meta_data('Для площади', $details['area_used'] . ' м²', true);
            $item->add_meta_data('Ширина доски', $details['width_used'] . ' мм', true);
            $item->add_meta_data('Расчётное количество', $details['quantity_needed'] . ' шт', true);
        }
    }
}
