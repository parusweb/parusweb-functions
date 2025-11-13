<?php
/**
 * Калькулятор крепежа для пиломатериалов
 * v2.3 - Исправлен вывод после калькулятора размеров, упрощен расчет, возвращена кнопка попапа
 *
 * @package ParusWeb_Functions
 */

if (!defined('ABSPATH')) exit;

add_action('acf/init', 'pw_register_fasteners_category_fields');
function pw_register_fasteners_category_fields() {
    if (!function_exists('acf_add_local_field_group')) return;

    // ПОЛЯ ДЛЯ КАТЕГОРИИ
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
                'layout' => 'table',
                'button_label' => 'Добавить крепёж',
                'sub_fields' => array(
                    array(
                        'key' => 'field_fastener_product',
                        'label' => 'Товар',
                        'name' => 'product',
                        'type' => 'post_object',
                        'post_type' => array('product'),
                        'taxonomy' => array('product_cat:77'),
                        'return_format' => 'id',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_fastener_individual_type',
                        'label' => 'Тип',
                        'name' => 'fastener_type',
                        'type' => 'select',
                        'choices' => array(
                            'auto' => 'Авто (по названию)',
                            'kleimer' => 'Крепеж',
                            'screw' => 'Саморез',
                        ),
                        'default_value' => 'auto',
                        'instructions' => 'Авто: определяется по слову "саморез" в названии',
                    ),
                    array(
                        'key' => 'field_fastener_width_min',
                        'label' => 'Ширина мин (мм)',
                        'name' => 'width_min',
                        'type' => 'number',
                        'instructions' => 'Минимальная ширина для этого крепежа',
                        'default_value' => 0,
                    ),
                    array(
                        'key' => 'field_fastener_width_max',
                        'label' => 'Ширина макс (мм)',
                        'name' => 'width_max',
                        'type' => 'number',
                        'instructions' => 'Максимальная ширина для этого крепежа (0 = без ограничений)',
                        'default_value' => 0,
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

    // ПОЛЯ ДЛЯ ТОВАРА (только для категорий пиломатериалов 87-93)
    acf_add_local_field_group(array(
        'key' => 'group_product_fasteners',
        'title' => 'Настройки крепежа для товара',
        'fields' => array(
            array(
                'key' => 'field_product_enable_fasteners',
                'label' => 'Переопределить крепеж для этого товара',
                'name' => 'product_enable_fasteners',
                'type' => 'true_false',
                'instructions' => 'Если включено, будут использоваться настройки крепежа из этого товара вместо категории',
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_product_fasteners_products',
                'label' => 'Товары крепежа',
                'name' => 'product_fasteners_products',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Добавить крепёж',
                'sub_fields' => array(
                    array(
                        'key' => 'field_product_fastener_product',
                        'label' => 'Товар',
                        'name' => 'product',
                        'type' => 'post_object',
                        'post_type' => array('product'),
                        'taxonomy' => array('product_cat:77'),
                        'return_format' => 'id',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_product_fastener_individual_type',
                        'label' => 'Тип',
                        'name' => 'fastener_type',
                        'type' => 'select',
                        'choices' => array(
                            'auto' => 'Авто (по названию)',
                            'kleimer' => 'Крепеж',
                            'screw' => 'Саморез',
                        ),
                        'default_value' => 'auto',
                        'instructions' => 'Авто: определяется по слову "саморез" в названии',
                    ),
                    array(
                        'key' => 'field_product_fastener_width_min',
                        'label' => 'Ширина мин (мм)',
                        'name' => 'width_min',
                        'type' => 'number',
                        'default_value' => 0,
                    ),
                    array(
                        'key' => 'field_product_fastener_width_max',
                        'label' => 'Ширина макс (мм)',
                        'name' => 'width_max',
                        'type' => 'number',
                        'default_value' => 0,
                    ),
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_product_enable_fasteners',
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

function pw_detect_fastener_category_by_title($product_title) {
    $title_lower = mb_strtolower($product_title, 'UTF-8');
    
    $keyword_map = array(
        'вагонка' => array('category_id' => 88, 'keywords' => array('вагонка', 'евровагонка')),
        'планкен' => array('category_id' => 91, 'keywords' => array('планкен')),
        'террасная' => array('category_id' => 91, 'keywords' => array('террасная', 'террасная доска')),
        'имитация' => array('category_id' => 90, 'keywords' => array('имитация', 'имитация бруса')),
    );
    
    foreach ($keyword_map as $key => $group_data) {
        foreach ($group_data['keywords'] as $keyword) {
            if (strpos($title_lower, $keyword) !== false) {
                return $group_data['category_id'];
            }
        }
    }
    
    return null;
}

function pw_get_category_fasteners_data($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return null;

    $product_categories = $product->get_category_ids();
    $lumber_categories = array(87, 88, 89, 90, 91, 92, 93); // Пиломатериалы
    $is_lumber = !empty(array_intersect($product_categories, $lumber_categories));

    // ПРИОРИТЕТ 1: Настройки крепежа для конкретного товара (только для пиломатериалов)
    if ($is_lumber && get_field('product_enable_fasteners', $product_id)) {
        $products = get_field('product_fasteners_products', $product_id);
        if (!empty($products)) {
            return array(
                'enabled' => true,
                'products' => $products,
                'source' => 'product',
            );
        }
    }

    // ПРИОРИТЕТ 2: Термодревесина - определение по названию
    if (in_array(92, $product_categories)) {
        $product_title = $product->get_name();
        $detected_category_id = pw_detect_fastener_category_by_title($product_title);
        
        if ($detected_category_id) {
            $term_id = 'product_cat_' . $detected_category_id;
            if (get_field('enable_fasteners_calc', $term_id)) {
                $products = get_field('fasteners_products', $term_id);
                if (!empty($products)) {
                    return array(
                        'enabled' => true,
                        'products' => $products,
                        'source' => 'category_by_title',
                    );
                }
            }
        }
    }
    
    // ПРИОРИТЕТ 3: Обычная логика - крепеж из категории товара
    foreach ($product_categories as $cat_id) {
        $term_id = 'product_cat_' . $cat_id;
        if (get_field('enable_fasteners_calc', $term_id)) {
            $products = get_field('fasteners_products', $term_id);
            if (!empty($products)) {
                return array(
                    'enabled' => true,
                    'products' => $products,
                    'source' => 'category',
                );
            }
        }
    }
    
    return null;
}

add_action('woocommerce_after_add_to_cart_button', 'pw_output_fasteners_calculator', 5);
function pw_output_fasteners_calculator() {
    global $product;
    if (!$product) return;

    $fasteners_data = pw_get_category_fasteners_data($product->get_id());
    if (!$fasteners_data) return;

    $fasteners_products = array();
    foreach ($fasteners_data['products'] as $fastener) {
        $f_id = is_array($fastener) && isset($fastener['product']) ? $fastener['product'] : intval($fastener);
        $f = wc_get_product($f_id);
        if ($f) {
            $image_id = $f->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src('medium');
            
            $description = $f->get_short_description();
            if (empty($description)) {
                $description = $f->get_description();
            }
            $description = wp_trim_words($description, 25, '...');
            
            // Получаем диапазоны ширины и тип из ACF
            $width_min = 0;
            $width_max = 0;
            $fastener_type_acf = 'auto';
            
            if (is_array($fastener)) {
                $width_min = isset($fastener['width_min']) ? intval($fastener['width_min']) : 0;
                $width_max = isset($fastener['width_max']) ? intval($fastener['width_max']) : 0;
                $fastener_type_acf = isset($fastener['fastener_type']) ? $fastener['fastener_type'] : 'auto';
            }
            
            // Определяем финальный тип
            if ($fastener_type_acf === 'auto') {
                // Автоматическое определение по названию товара
                $fastener_name_lower = mb_strtolower($f->get_name(), 'UTF-8');
                $fastener_type = (strpos($fastener_name_lower, 'саморез') !== false) ? 'screw' : 'kleimer';
            } else {
                // Используем ручной выбор
                $fastener_type = $fastener_type_acf;
            }
            
            $fasteners_products[] = array(
                'id'          => $f->get_id(),
                'name'        => $f->get_name(),
                'price'       => floatval($f->get_price()),
                'image'       => $image_url,
                'description' => $description,
                'permalink'   => $f->get_permalink(),
                'width_min'   => $width_min,
                'width_max'   => $width_max,
                'type'        => $fastener_type,
            );
        }
    }
    if (empty($fasteners_products)) return;

    $attr_shirina = $product->get_attribute('shirina');
    $attr_dlina  = $product->get_attribute('dlina');
    $wc_width = floatval($product->get_width());

    $parse_number = function($s) {
        if (!$s) return null;
        $s = trim(str_ireplace(',', '.', $s));
        if (preg_match('/(\d+(\.\d+)?)/', $s, $m)) {
            return floatval($m[1]);
        }
        return null;
    };

    $default_shirina = $parse_number($attr_shirina);
    $default_dlina  = $parse_number($attr_dlina);
    
    if (!$default_shirina && $wc_width > 0) {
        $default_shirina = $wc_width;
    }

    ?>
    <div id="fastener-modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6);">
        <div style="position:relative; background-color:#fff; margin:5% auto; padding:0; width:90%; max-width:600px; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
            <div style="padding:20px 25px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; background:#f8f9fa; border-radius:10px 10px 0 0;">
                <h3 style="margin:0; font-size:1.3em;">О крепеже</h3>
                <button type="button" id="fastener-modal-close" style="background:none; border:none; font-size:28px; font-weight:bold; color:#999; cursor:pointer; padding:0; width:30px; height:30px;">&times;</button>
            </div>
            <div id="fastener-modal-content" style="padding:25px;"></div>
        </div>
    </div>

    <script type="text/javascript">
    (function(){
        const fastenersData = <?php echo json_encode($fasteners_products); ?>;
        const defaultProductWidth = <?php echo json_encode($default_shirina); ?>;

        function parsePiecesPerPack(name) {
            let m;
            m = name.match(/(\d+)\s*шт\s*\/\s*уп/i);
            if (m) return parseInt(m[1], 10);
            m = name.match(/(\d+)\s*шт/i);
            if (m) return parseInt(m[1], 10);
            return 100;
        }

        function limitWords(text, maxWords) {
            const words = String(text).split(/\s+/);
            return words.length > maxWords ? words.slice(0, maxWords).join(' ') + '...' : text;
        }

        const fastenersDataWithQty = fastenersData.map(f => ({
            ...f,
            piecesPerPack: parsePiecesPerPack(f.name),
            displayName: limitWords(f.name, 10),
            width_min: f.width_min || 0,
            width_max: f.width_max || 0,
            type: f.type || 'kleimer'
        }));

        function getFilteredFastenersByWidth(widthMm) {
            return fastenersDataWithQty.filter(f => {
                // Если нет ограничений по ширине - крепеж подходит всем
                if (f.width_min === 0 && f.width_max === 0) return true;
                
                // Если задан только минимум
                if (f.width_min > 0 && f.width_max === 0) {
                    return widthMm >= f.width_min;
                }
                
                // Если задан только максимум
                if (f.width_min === 0 && f.width_max > 0) {
                    return widthMm <= f.width_max;
                }
                
                // Если задан диапазон
                return widthMm >= f.width_min && widthMm <= f.width_max;
            });
        }

        function updateFastenerSelect(widthMm) {
            const filtered = getFilteredFastenersByWidth(widthMm);
            const select = document.getElementById('fastener_select');
            if (!select) return;
            
            const currentValue = select.value;
            select.innerHTML = '<option value="">-- Выберите крепёж --</option>';
            
            filtered.forEach(f => {
                const option = document.createElement('option');
                option.value = f.id;
                option.dataset.price = f.price;
                option.dataset.piecesperpack = f.piecesPerPack;
                option.textContent = f.displayName;
                select.appendChild(option);
            });
            
            // Восстанавливаем выбранное значение если оно все еще доступно
            if (currentValue && filtered.find(f => f.id == currentValue)) {
                select.value = currentValue;
            }
        }

        function openFastenerModal(fastenerId) {
            const fastener = fastenersData.find(f => f.id === fastenerId);
            if (!fastener) return;

            const content = document.getElementById('fastener-modal-content');
            let html = '<div style="text-align:center; margin-bottom:20px;">';
            html += '<img src="' + fastener.image + '" style="max-width:100%; max-height:300px; border-radius:8px;">';
            html += '</div>';
            html += '<h4 style="margin:0 0 15px 0;">' + fastener.name + '</h4>';
            if (fastener.description) {
                html += '<div style="color:#666; margin-bottom:20px;">' + fastener.description + '</div>';
            }
            html += '<div style="background:#f8f9fa; padding:15px; border-radius:6px; margin-bottom:15px;">';
            html += '<div style="display:flex; justify-content:space-between;">';
            html += '<span>Цена за упаковку:</span>';
            html += '<span style="color:#8bc34a; font-size:1.3em; font-weight:600;">' + fastener.price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</span>';
            html += '</div></div>';
            html += '<a href="' + fastener.permalink + '" target="_blank" style="display:inline-block; padding:10px 20px; background:#8bc34a; color:#fff; text-decoration:none; border-radius:5px;">Подробнее →</a>';
            content.innerHTML = html;
            document.getElementById('fastener-modal').style.display = 'block';
        }

        function closeFastenerModal() {
            document.getElementById('fastener-modal').style.display = 'none';
        }

        document.getElementById('fastener-modal-close').addEventListener('click', closeFastenerModal);
        window.addEventListener('click', function(e) {
            if (e.target.id === 'fastener-modal') closeFastenerModal();
        });

        function tryToGetWidthMeters(raw) {
            if (!raw) return null;
            const n = parseFloat(raw);
            if (isNaN(n)) return null;
            if (n > 10 && n < 1000) return n / 100;
            if (n > 10) return n / 1000;
            return n;
        }

        function insertFastenerBlock() {
            const areaBlock = document.querySelector('#calc-area');
            if (!areaBlock) {
                setTimeout(insertFastenerBlock, 300);
                return;
            }
            if (document.querySelector('#fasteners-calculator-block')) return;

            const block = document.createElement('div');
            block.id = 'fasteners-calculator-block';
            block.style.cssText = 'margin-top:18px; padding:12px;';

            let html = '<h4>Расчёт крепежа</h4>';
            html += '<select id="fastener_select" style="width:100%; padding:8px; margin-bottom:10px;">';
            html += '<option value="">-- Выберите крепёж --</option>';
            fastenersDataWithQty.forEach(f => {
                html += `<option value="${f.id}" data-price="${f.price}" data-piecesperpack="${f.piecesPerPack}">${f.displayName}</option>`;
            });
            html += '</select>';
            html += '<button type="button" id="fastener_info_btn" style="display:none; width:100%; padding:10px; margin-bottom:10px; background:#8bc34a; color:#fff; border:1px solid #8bc34a; border-radius:5px; cursor:pointer; font-weight:500; transition:all 0.2s;">О крепеже</button>';
            html += '<div id="fastener_calculation_result" style="display:none; background:#fff; padding:10px; border-radius:5px;"></div>';
            
            block.innerHTML = html;
            areaBlock.appendChild(block);

            const select = block.querySelector('#fastener_select');
            const result = block.querySelector('#fastener_calculation_result');
            const infoBtn = block.querySelector('#fastener_info_btn');

            infoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (select.value) openFastenerModal(parseInt(select.value));
            });
            
            // Hover эффект для кнопки
            infoBtn.addEventListener('mouseover', function() {
                this.style.background = '#7cb342';
            });
            infoBtn.addEventListener('mouseout', function() {
                this.style.background = '#8bc34a';
            });

            function getFieldValue(id) {
                const el = document.getElementById(id);
                if (!el) return null;
                const v = parseFloat(String(el.value).replace(',', '.'));
                return isNaN(v) ? null : v;
            }

            function getEffectiveWidthMeters() {
                const w = getFieldValue('sq_width');
                if (w) return tryToGetWidthMeters(w);
                if (defaultProductWidth) return tryToGetWidthMeters(defaultProductWidth);
                return null;
            }

            let lastWidthMm = 0;

            function updateCalculation() {
                const widthMeters = getEffectiveWidthMeters();
                if (widthMeters) {
                    let widthMm = Math.round(widthMeters * 1000);
                    while (widthMm > 300) widthMm = Math.round(widthMm / 10);
                    while (widthMm < 80) widthMm = Math.round(widthMm * 10);
                    
                    // Обновляем список крепежа если ширина изменилась
                    if (widthMm !== lastWidthMm) {
                        updateFastenerSelect(widthMm);
                        lastWidthMm = widthMm;
                    }
                }
                
                if (!select.value) {
                    result.style.display = 'none';
                    infoBtn.style.display = 'none';
                    return;
                }
                
                infoBtn.style.display = 'block';
                
                const opt = select.options[select.selectedIndex];
                const price = parseFloat(opt.dataset.price || '0');
                const piecesPerPack = parseInt(opt.dataset.piecesperpack || '100');
                
                // Получаем тип выбранного крепежа
                const selectedFastener = fastenersDataWithQty.find(f => f.id == select.value);
                const fastenerType = selectedFastener ? selectedFastener.type : 'kleimer';

                const areaInput = getFieldValue('calc_area_input') || 1;
                const quantityInput = parseInt(document.getElementById('quantity_input')?.value || '1');
                const totalArea = areaInput * quantityInput;

                if (!widthMeters) {
                    result.innerHTML = '<p>Укажите ширину доски.</p>';
                    result.style.display = 'block';
                    return;
                }

                let widthMm = Math.round(widthMeters * 1000);
                while (widthMm > 300) widthMm = Math.round(widthMm / 10);
                while (widthMm < 80) widthMm = Math.round(widthMm * 10);

                let perM2 = 30;
                if (widthMm >= 85 && widthMm <= 90) perM2 = 30;
                else if (widthMm >= 115 && widthMm <= 120) perM2 = 24;
                else if (widthMm >= 140 && widthMm <= 145) perM2 = 19;
                else if (widthMm >= 165 && widthMm <= 175) perM2 = 16;
                else if (widthMm >= 190 && widthMm <= 195) perM2 = 15;

                const qtyByFormula = Math.ceil((totalArea / widthMeters) * 2.7);
                const neededByPerM2 = Math.ceil(totalArea * perM2);
                let neededPieces = Math.max(qtyByFormula, neededByPerM2);
                
                // Если тип крепежа - саморезы, умножаем на 2
                if (fastenerType === 'screw') {
                    neededPieces = neededPieces * 2;
                }
                
                const packsNeeded = Math.max(1, Math.ceil(neededPieces / piecesPerPack));
                const totalPrice = packsNeeded * price;

                result.innerHTML = ''
                    + `<p>Площадь: <strong>${totalArea.toFixed(2)} м²</strong></p>`
                    + `<p>Необходимо упаковок: <strong>${packsNeeded} уп.</strong></p>`
                    + `<p>Стоимость крепежа: <strong>${totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</strong></p>`;

                result.style.display = 'block';

                window.pw_fastener_calculation = {
                    fastener_id: parseInt(select.value),
                    packs_needed: packsNeeded
                };
            }

            ['sq_width','calc_area_input','quantity_input'].forEach(id=>{
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateCalculation);
                    el.addEventListener('change', updateCalculation);
                }
            });
            select.addEventListener('change', updateCalculation);
            
            const quantityInputs = document.querySelectorAll('input[name="quantity"]');
            quantityInputs.forEach(el => el.addEventListener('change', updateCalculation));

            setTimeout(updateCalculation, 60);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', insertFastenerBlock);
        } else {
            insertFastenerBlock();
        }
    })();
    </script>
    <?php
}

add_action('wp_footer', 'pw_inject_fastener_handler', 999);
function pw_inject_fastener_handler() {
    if (!is_product()) return;
    ?>
    <script type="text/javascript">
    (function(){
        if (!window.pw_fastener_calculation) {
            window.pw_fastener_calculation = { fastener_id: 0, packs_needed: 0 };
        }

        const addToCartBtn = document.querySelector('button[name="add-to-cart"], button.single_add_to_cart_button');
        if (!addToCartBtn) return;

        addToCartBtn.addEventListener('click', function() {
            const form = document.querySelector('form.cart');
            if (!form || !window.pw_fastener_calculation.fastener_id) return;

            const calc = window.pw_fastener_calculation;
            form.querySelectorAll('input[name="fastener_select"], input[name="fastener_packs_needed"]').forEach(f => f.remove());

            const input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'fastener_select';
            input1.value = calc.fastener_id;
            form.appendChild(input1);

            const input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'fastener_packs_needed';
            input2.value = calc.packs_needed;
            form.appendChild(input2);
        });
    })();
    </script>
    <?php
}

$GLOBALS['pw_fastener_adding'] = false;

add_action('woocommerce_add_to_cart', 'pw_add_fastener_to_cart', 20, 6);
function pw_add_fastener_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation_data, $cart_item_data) {
    if (!empty($GLOBALS['pw_fastener_adding'])) return;
    
    $fastener_id = !empty($_POST['fastener_select']) ? intval($_POST['fastener_select']) : 0;
    $fastener_qty = !empty($_POST['fastener_packs_needed']) ? intval($_POST['fastener_packs_needed']) : 0;
    
    if (!$fastener_id || $fastener_qty <= 0) return;
    
    $cart = WC()->cart->get_cart();
    foreach ($cart as $item_key => $item) {
        if (isset($item['added_with_product']) && $item['added_with_product'] == $product_id && $item['product_id'] == $fastener_id) {
            return;
        }
    }
    
    $GLOBALS['pw_fastener_adding'] = true;
    WC()->cart->add_to_cart($fastener_id, $fastener_qty, 0, array(), array('added_with_product' => $product_id));
    $GLOBALS['pw_fastener_adding'] = false;
}

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

add_action('woocommerce_checkout_create_order_line_item', 'pw_save_fastener_meta', 10, 4);
function pw_save_fastener_meta($item, $key, $values, $order) {
    if (isset($values['added_with_product'])) {
        $item->add_meta_data('_fastener_for_product', $values['added_with_product']);
    }
}
