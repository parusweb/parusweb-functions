<?php
/**
 * Калькулятор крепежа для пиломатериалов с добавлением в корзину
 * Полная версия с автоматическим расчетом и извлечением количества крепежа из названия
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
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_fasteners_type',
                'label' => 'Тип крепежа',
                'name' => 'fasteners_type',
                'type' => 'select',
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
                'key' => 'field_fasteners_products',
                'label' => 'Товары крепежа',
                'name' => 'fasteners_products',
                'type' => 'repeater',
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
// ПОЛУЧЕНИЕ ДАННЫХ КРЕПЕЖА ДЛЯ КАТЕГОРИИ
// ============================================================================

function pw_get_category_fasteners_data($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return null;

    $category_ids = $product->get_category_ids();
    if (empty($category_ids)) return null;

    foreach ($category_ids as $cat_id) {
        $term_id = 'product_cat_' . $cat_id;
        $enabled = get_field('enable_fasteners_calc', $term_id);

        if ($enabled) {
            $type = get_field('fasteners_type', $term_id);
            $products = get_field('fasteners_products', $term_id);

            if (!empty($products)) {
                return array(
                    'enabled' => true,
                    'type' => $type,
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
    $fasteners_data = pw_get_category_fasteners_data($product_id);
    if (!$fasteners_data) return;

    $fasteners_products = array();
    foreach ($fasteners_data['products'] as $fastener) {
        $fastener_product_id = $fastener['product'];
        $fastener_product = wc_get_product($fastener_product_id);
        if (!$fastener_product) continue;

        $fasteners_products[] = array(
            'id' => $fastener_product_id,
            'name' => $fastener_product->get_name(),
            'price' => floatval($fastener_product->get_price())
        );
    }

    if (empty($fasteners_products)) return;

    ?>
    <script type="text/javascript">
    const fastenersData = <?php echo json_encode($fasteners_products); ?>;
    const fastenerSelectId = 'fastener_select';

    function calculateFastenerQuantity(area_sqm, qty_per_sqm) {
        return Math.ceil(area_sqm * qty_per_sqm);
    }

    function parsePiecesPerPack(name) {
        let match = name.match(/\(\s*(\d+)\s*шт\s*\/\s*уп\s*\)/i);
        if (match) return parseInt(match[1]);
        match = name.match(/(\d+)\s*шт/i);
        if (match) return parseInt(match[1]);
        return 100;
    }

    function initFastenersModule() {
        const calcArea = document.querySelector('#calc-area');
        if (!calcArea) {
            setTimeout(initFastenersModule, 300);
            return;
        }

        const block = document.createElement('div');
        block.id = 'fasteners-calculator-block';
        block.style.cssText = 'margin-top:20px; padding:15px; background:#f0fff4; border:2px solid #22c55e; border-radius:8px;';

        let html = '<h4 style="margin:0 0 15px 0; color:#16a34a;">Расчёт крепежа</h4>';
        html += '<label>Выберите крепёж:</label>';
        html += `<select id="${fastenerSelectId}" style="width:100%; padding:8px; margin-top:5px; margin-bottom:15px;">`;
        html += '<option value="">-- Выберите крепёж --</option>';
        fastenersData.forEach(f => {
            html += `<option value="${f.id}" data-price="${f.price}">${f.name}</option>`;
        });
        html += '</select>';
        html += '<div id="fastener_calculation_result" style="padding:10px; background:#fff; border-radius:5px; display:none;"></div>';
        block.innerHTML = html;

        calcArea.appendChild(block);

        const fastenerSelect = document.getElementById(fastenerSelectId);
        const resultDiv = document.getElementById('fastener_calculation_result');

        function updateCalculation() {
            let width = parseFloat(document.getElementById('sq_width')?.value) || 0;
            let length = parseFloat(document.getElementById('sq_length')?.value) || 0;
            let areaInput = parseFloat(document.getElementById('calc_area_input')?.value) || 1;
            let quantity = parseInt(document.getElementById('quantity_input')?.value) || 1;

            let area = areaInput * quantity;

            if (!fastenerSelect.value) {
                resultDiv.style.display = 'none';
                return;
            }

            const selectedOption = fastenerSelect.options[fastenerSelect.selectedIndex];
            const price = parseFloat(selectedOption.dataset.price);
            const piecesPerPack = parsePiecesPerPack(selectedOption.text);

            let qtyPerSqm = 30;
            if (width >= 85 && width <= 90) qtyPerSqm = 30;
            else if (width >= 115 && width <= 120) qtyPerSqm = 24;
            else if (width >= 140 && width <= 145) qtyPerSqm = 19;
            else if (width >= 165 && width <= 175) qtyPerSqm = 16;
            else if (width >= 190 && width <= 195) qtyPerSqm = 15;

            const qtyNeeded = calculateFastenerQuantity(area, qtyPerSqm);
            const packsNeeded = Math.ceil(qtyNeeded / piecesPerPack);
            const totalPieces = packsNeeded * piecesPerPack;
            const totalPrice = packsNeeded * price;

            resultDiv.innerHTML = `
                <p>Площадь: <strong>${area.toFixed(2)} м²</strong></p>
                <p>Требуется крепежа: <strong>${qtyNeeded} шт</strong></p>
                <p>Необходимо упаковок: <strong>${packsNeeded} уп. (${totalPieces} шт)</strong></p>
                <p>Стоимость крепежа: <strong>${totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</strong></p>
            `;
            resultDiv.style.display = 'block';
        }

        const idsToWatch = ['sq_width', 'sq_length', 'calc_area_input', 'quantity_input'];
        idsToWatch.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updateCalculation);
                el.addEventListener('change', updateCalculation);
            }
        });

        fastenerSelect.addEventListener('change', updateCalculation);

        // первый расчет сразу при загрузке
        updateCalculation();
    }

    document.addEventListener('DOMContentLoaded', initFastenersModule);
    </script>
    <?php
}

// ============================================================================
// ДОБАВЛЕНИЕ В КОРЗИНУ
// ============================================================================

add_filter('woocommerce_add_cart_item_data', 'pw_save_fastener_data_to_cart', 10, 2);
function pw_save_fastener_data_to_cart($cart_item_data, $product_id) {
    if (isset($_POST['fastener_select']) && !empty($_POST['fastener_select'])) {
        $fastener_id = intval($_POST['fastener_select']);
        $cart_item_data['fastener_data'] = array('product_id' => $fastener_id);
    }
    return $cart_item_data;
}

add_action('woocommerce_add_to_cart', 'pw_auto_add_fastener_to_cart', 10, 6);
function pw_auto_add_fastener_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (!isset($cart_item_data['fastener_data'])) return;

    $fastener_product_id = $cart_item_data['fastener_data']['product_id'];
    if (!$fastener_product_id) return;

    WC()->cart->add_to_cart($fastener_product_id, 1, 0, array(), array('added_with_product' => $product_id, 'parent_cart_item_key' => $cart_item_key));
}

add_filter('woocommerce_get_item_data', 'pw_display_fastener_data_in_cart', 10, 2);
function pw_display_fastener_data_in_cart($item_data, $cart_item) {
    if (isset($cart_item['fastener_data'])) {
        $fastener = wc_get_product($cart_item['fastener_data']['product_id']);
        if ($fastener) {
            $item_data[] = array(
                'key' => 'Крепёж',
                'value' => $fastener->get_name(),
            );
        }
    }
    return $item_data;
}
