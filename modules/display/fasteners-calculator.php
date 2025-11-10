<?php
if (!defined('ABSPATH')) exit;

// ACF ПОЛЯ
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
                'key' => 'field_fasteners_products',
                'label' => 'Товары крепежа',
                'name' => 'fasteners_products',
                'type' => 'repeater',
                'button_label' => 'Добавить крепёж',
                'sub_fields' => array(
                    array(
                        'key' => 'field_fastener_product',
                        'label' => 'Товар',
                        'name' => 'product',
                        'type' => 'post_object',
                        'post_type' => array('product'),
                        'return_format' => 'id',
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

// ПОЛУЧЕНИЕ ДАННЫХ
function pw_get_category_fasteners_data($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return null;

    foreach ($product->get_category_ids() as $cat_id) {
        $term_id = 'product_cat_' . $cat_id;
        if (get_field('enable_fasteners_calc', $term_id)) {
            $products = get_field('fasteners_products', $term_id);
            if (!empty($products)) {
                return $products;
            }
        }
    }
    return null;
}

// ВЫВОД БЛОКА
add_action('woocommerce_after_add_to_cart_form', 'pw_output_fasteners_calculator');
function pw_output_fasteners_calculator() {
    global $product;
    if (!is_product()) return;

    $fasteners_data = pw_get_category_fasteners_data($product->get_id());
    if (empty($fasteners_data)) return;

    $fasteners_products = array();
    foreach ($fasteners_data as $item) {
        $product_id = isset($item['product']) ? $item['product'] : $item;
        $f = wc_get_product($product_id);
        if ($f) {
            $name = $f->get_name();
            $words = explode(' ', $name);
            $short_name = implode(' ', array_slice($words, 0, 3));
            
            $fasteners_products[] = array(
                'id' => $f->get_id(),
                'name' => $short_name,
                'price' => floatval($f->get_price()),
            );
        }
    }

    if (empty($fasteners_products)) return;
    
    ?>
    <div style="margin-top:20px; padding:10px 0;">
        <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px;">🔩 Крепеж:</label>
        <select id="fastener_select" name="fastener_select" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
            <option value="">-- не добавлять --</option>
            <?php foreach ($fasteners_products as $f): ?>
                <option value="<?php echo $f['id']; ?>" data-price="<?php echo $f['price']; ?>">
                    <?php echo $f['name']; ?> (<?php echo wc_price($f['price']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <div id="fastener_calc" style="margin-top:12px; font-size:16px; line-height:1.8; color:#2c5cc5; font-weight:600; padding:10px; background:#f0f8ff; border-radius:4px; border-left:4px solid #2c5cc5;"></div>
        <input type="hidden" id="fastener_qty_hidden" name="fastener_qty" value="0" />
    </div>

    <script>
    jQuery(function($) {
        const select = $('#fastener_select');
        const calcDiv = $('#fastener_calc');
        const hiddenQty = $('#fastener_qty_hidden');
        const form = $('form.cart');

        function parsePiecesPerPack(name) {
            let m = name.match(/\b(\d+)\s*(?:шт|piece)/i);
            return m ? parseInt(m[1]) : 100;
        }

        function getWidthFromAttribute() {
            let width = 0;
            
            const widthSelect = $('select[name*="shirina"], select[data-attribute_name="pa_shirina"]');
            if (widthSelect.length && widthSelect.val()) {
                width = parseFloat(widthSelect.val());
            }
            
            if (!width) {
                const widthInput = $('input[name*="width"], input[id*="width"]');
                if (widthInput.length && widthInput.val()) {
                    width = parseFloat(widthInput.val());
                }
            }
            
            return width;
        }

        function getAreaValue() {
            let area = 0;
            
            const areaInput = $('#calc_area_input, input[name*="area"]');
            if (areaInput.length && areaInput.val()) {
                area = parseFloat(areaInput.val());
            }
            
            if (!area) {
                const width = getWidthFromAttribute();
                const lengthInput = $('select[name*="dlina"], select[data-attribute_name="pa_dlina"], input[name*="length"]');
                if (lengthInput.length && lengthInput.val() && width) {
                    area = (parseFloat(width) / 1000) * parseFloat(lengthInput.val());
                }
            }
            
            return area;
        }

        function getQuantity() {
            const qtyInput = $('input[name="quantity"], input.qty');
            return qtyInput.length && qtyInput.val() ? parseInt(qtyInput.val()) : 1;
        }

        function recalculate() {
            const selectValue = select.val();
            
            if (!selectValue) {
                calcDiv.html('');
                hiddenQty.val(0);
                hiddenSelect.val(0);
                return;
            }

            const opt = select.find('option:selected');
            const price = parseFloat(opt.data('price') || 0);
            const fastenerName = opt.text();
            const perPack = parsePiecesPerPack(fastenerName);

            const width = getWidthFromAttribute();
            const area = getAreaValue();
            const qty = getQuantity();
            const totalArea = area * qty;

            if (totalArea <= 0) {
                calcDiv.html('');
                hiddenQty.val(0);
                hiddenSelect.val(selectValue);
                return;
            }

            let perM2 = 30;
            if (width >= 85 && width <= 90) perM2 = 30;
            else if (width >= 115 && width <= 120) perM2 = 24;
            else if (width >= 140 && width <= 145) perM2 = 19;
            else if (width >= 165 && width <= 175) perM2 = 16;
            else if (width >= 190 && width <= 195) perM2 = 15;

            const needed = Math.ceil(totalArea * perM2);
            const packs = Math.ceil(needed / perPack);
            const total = packs * price;

            calcDiv.html(needed + ' шт в ' + packs + ' упак. → <strong>' + total.toFixed(2) + ' ₽</strong>');
            
            // Синхронизируем скрытое поле количества
            hiddenQty.val(needed);
        }

        // Обновляем при изменении селекта
        select.on('change', recalculate);
        
        // Обновляем при изменении размеров
        $('input[name="quantity"], select[name*="shirina"], select[data-attribute_name="pa_shirina"], select[name*="dlina"], select[data-attribute_name="pa_dlina"], #calc_area_input').on('change input', recalculate);
        
        // Обновляем при выборе вариации
        $(document).on('found_variation', recalculate);
        
        // ГЛАВНОЕ: перехватываем отправку формы
        form.on('submit', function(e) {
            // Перед отправкой пересчитываем и заполняем скрытые поля
            recalculate();
            // Обновляем SELECT напрямую (он сам уходит в форму)
            console.log('Form submit - fastener_select:', select.val(), 'fastener_qty:', hiddenQty.val());
        });
        
        // Инициируем при загрузке
        setTimeout(recalculate, 500);
    });
    </script>
    <?php
}

// ДОБАВЛЕНИЕ В КОРЗИНУ
add_action('woocommerce_add_to_cart', 'pw_add_fastener_to_cart', 20, 6);
function pw_add_fastener_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation_data, $cart_item_data) {
    // Получаем выбранный крепеж и его количество из POST
    $fastener_id = !empty($_POST['fastener_select']) ? intval($_POST['fastener_select']) : 0;
    $fastener_qty = !empty($_POST['fastener_qty']) ? intval($_POST['fastener_qty']) : 0;
    
    error_log('=== PW FASTENER DEBUG ===');
    error_log('POST fastener_select: ' . (!empty($_POST['fastener_select']) ? $_POST['fastener_select'] : 'EMPTY'));
    error_log('POST fastener_qty: ' . (!empty($_POST['fastener_qty']) ? $_POST['fastener_qty'] : 'EMPTY'));
    error_log('fastener_id parsed: ' . $fastener_id);
    error_log('fastener_qty parsed: ' . $fastener_qty);
    error_log('product_id: ' . $product_id);
    
    // Если крепеж не выбран или количество = 0, не добавляем
    if (!$fastener_id || $fastener_qty <= 0) {
        error_log('RETURN: No fastener_id or qty <= 0');
        return;
    }
    
    $fastener_product = wc_get_product($fastener_id);
    if (!$fastener_product) {
        error_log('RETURN: Fastener product not found');
        return;
    }
    
    error_log('Fastener product found: ' . $fastener_product->get_name());
    
    // ГЛАВНАЯ ПРОВЕРКА: ищем уже добавленный крепеж этого товара
    $cart = WC()->cart->get_cart();
    $found = false;
    
    foreach ($cart as $item) {
        if (isset($item['added_with_product']) && $item['added_with_product'] == $product_id) {
            if ($item['product_id'] == $fastener_id) {
                error_log('RETURN: Fastener already in cart');
                $found = true;
                break;
            }
        }
    }
    
    if ($found) return;
    
    // Добавляем крепеж только один раз с правильным количеством
    error_log('ADDING: fastener_id=' . $fastener_id . ', qty=' . $fastener_qty);
    WC()->cart->add_to_cart($fastener_id, $fastener_qty, 0, array(), array(
        'added_with_product' => $product_id,
    ));
    error_log('SUCCESS: Fastener added');
}

// ОТОБРАЖЕНИЕ В КОРЗИНЕ
add_filter('woocommerce_cart_item_name', 'pw_fastener_cart_label', 10, 3);
function pw_fastener_cart_label($name, $cart_item, $key) {
    if (isset($cart_item['added_with_product'])) {
        $parent = wc_get_product($cart_item['added_with_product']);
        if ($parent) {
            $name .= '<br><small style="color:#999;">(к ' . $parent->get_name() . ')</small>';
        }
    }
    return $name;
}

// МЕТАДАННЫЕ ЗАКАЗА
add_action('woocommerce_checkout_create_order_line_item', 'pw_save_fastener_meta', 10, 4);
function pw_save_fastener_meta($item, $key, $values, $order) {
    if (isset($values['added_with_product'])) {
        $item->add_meta_data('_fastener_for_product', $values['added_with_product']);
    }
}
