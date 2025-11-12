<?php
/**
 * Калькулятор крепежа для пиломатериалов с добавлением в корзину
 * v2.2 - Добавлена логика определения крепежа по названию для термодревесины (ID 92)
 *
 * @package ParusWeb_Functions
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ACF: поля для категории
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
// НОВАЯ ФУНКЦИЯ: Определение категории крепежа по названию товара
// ============================================================================

/**
 * Определяет ID категории для поиска крепежа на основе ключевых слов в названии товара
 * Используется для термодревесины (категория 92)
 * 
 * @param string $product_title Название товара
 * @return int|null ID категории для поиска крепежа или null если не определено
 */
function pw_detect_fastener_category_by_title($product_title) {
    $title_lower = mb_strtolower($product_title, 'UTF-8');
    
    // Карта ключевых слов → ID категории
    $keyword_map = array(
        // Вагонка и евровагонка (предположим ID категории вагонки)
        'вагонка' => array('category_id' => 87, 'keywords' => array('вагонка', 'евровагонка')),
        
        // Планкен (предположим ID категории планкена)
        'планкен' => array('category_id' => 88, 'keywords' => array('планкен', 'планкен прямой', 'планкен скошенный')),
        
        // Террасная доска (предположим ID категории террасной доски)
        'террасная' => array('category_id' => 89, 'keywords' => array('террасная', 'террасная доска', 'террас', 'евровельвет')),

    );
    
    // Проверяем каждую группу ключевых слов
    foreach ($keyword_map as $group_data) {
        foreach ($group_data['keywords'] as $keyword) {
            if (strpos($title_lower, $keyword) !== false) {
                return $group_data['category_id'];
            }
        }
    }
    
    return null;
}

// ============================================================================
// ОБНОВЛЕННАЯ ФУНКЦИЯ: Получение данных крепежа с учетом определения по названию
// ============================================================================

function pw_get_category_fasteners_data($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return null;

    $product_categories = $product->get_category_ids();
    
    // СПЕЦИАЛЬНАЯ ЛОГИКА ДЛЯ ТЕРМОДРЕВЕСИНЫ (ID 92)
    if (in_array(92, $product_categories)) {
        $product_title = $product->get_name();
        $detected_category_id = pw_detect_fastener_category_by_title($product_title);
        
        if ($detected_category_id) {
            // Получаем настройки крепежа из определенной категории
            $term_id = 'product_cat_' . $detected_category_id;
            if (get_field('enable_fasteners_calc', $term_id)) {
                $type = get_field('fasteners_type', $term_id);
                $products = get_field('fasteners_products', $term_id);
                if (!empty($products)) {
                    return array(
                        'enabled' => true,
                        'type' => $type,
                        'products' => $products,
                        'detected_from' => $detected_category_id, // для отладки
                    );
                }
            }
        }
    }
    
    // ОБЫЧНАЯ ЛОГИКА для всех остальных категорий
    foreach ($product_categories as $cat_id) {
        $term_id = 'product_cat_' . $cat_id;
        if (get_field('enable_fasteners_calc', $term_id)) {
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
// Вывод фронтенда
// ============================================================================

add_action('woocommerce_before_add_to_cart_button', 'pw_output_fasteners_calculator', 15);
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
            // Получаем изображение товара
            $image_id = $f->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src('medium');
            
            // Получаем краткое описание (первые 150 символов)
            $description = $f->get_short_description();
            if (empty($description)) {
                $description = $f->get_description();
            }
            $description = wp_trim_words($description, 25, '...');
            
            $fasteners_products[] = array(
                'id'          => $f->get_id(),
                'name'        => $f->get_name(),
                'price'       => floatval($f->get_price()),
                'image'       => $image_url,
                'description' => $description,
                'permalink'   => $f->get_permalink(),
            );
        }
    }
    if (empty($fasteners_products)) return;

    $attr_shirina = $product->get_attribute('shirina');
    $attr_dlina  = $product->get_attribute('dlina');
    
    // Получаем стандартные размеры WooCommerce (в см)
    $wc_width = floatval($product->get_width());
    $wc_length = floatval($product->get_length());
    $wc_height = floatval($product->get_height());

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
    
    // Если нет атрибутов - берём из WC размеров (ширина - это width в см)
    if (!$default_shirina && $wc_width > 0) {
        $default_shirina = $wc_width;
    }

    ?>
    <!-- Модальное окно для описания крепежа -->
    <div id="fastener-modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6);">
        <div style="position:relative; background-color:#fff; margin:5% auto; padding:0; width:90%; max-width:600px; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3); animation:modalSlideIn 0.3s;">
            <div style="padding:20px 25px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; background:#f8f9fa; border-radius:10px 10px 0 0;">
                <h3 style="margin:0; font-size:1.3em;">О крепеже</h3>
                <button type="button" id="fastener-modal-close" style="background:none; border:none; font-size:28px; font-weight:bold; color:#999; cursor:pointer; padding:0; width:30px; height:30px; display:flex; align-items:center; justify-content:center; transition:color 0.2s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">&times;</button>
            </div>
            <div id="fastener-modal-content" style="padding:25px;">
                <!-- Контент загружается динамически -->
            </div>
        </div>
    </div>

    <style>
    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    #fastener-modal-close:hover {
        transform: scale(1.1);
    }
    </style>

    <script type="text/javascript">
    (function(){
        const fastenersData = <?php echo json_encode($fasteners_products); ?>;
        const defaultProductWidth = <?php echo json_encode($default_shirina !== null ? $default_shirina : null); ?>;
        const defaultProductLength = <?php echo json_encode($default_dlina !== null ? $default_dlina : null); ?>;
        
        // Информация о том, что ширина взята из WC размеров (в сантиметрах)
        const widthIsFromWC = <?php echo json_encode($default_shirina !== null && floatval($product->get_attribute('shirina')) === 0 && $wc_width > 0 ? true : false); ?>;

        <?php
        // Для отладки - показываем откуда взяли крепеж
        if (isset($fasteners_data['detected_from'])) {
            echo "console.log('Крепеж определен по названию товара, используется категория ID: " . $fasteners_data['detected_from'] . "');\n";
        }
        ?>

        function parsePiecesPerPack(name) {
            // Ищем количество в разных форматах
            let m;
            
            // Формат в конце: "300 шт/уп" или "300шт/уп"
            m = name.match(/(\d+)\s*шт\s*\/\s*уп/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат: "300шт/упаковка"
            m = name.match(/(\d+)\s*шт\s*\/\s*упаковк/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат: "123шт" или "123 шт"
            m = name.match(/(\d+)\s*шт\b/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат: "(123шт)" или "(123 шт)"
            m = name.match(/\((\d+)\s*шт\)/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат: "шт: 123" или "шт:123"
            m = name.match(/шт\s*:\s*(\d+)/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат: "x123" или "x 123"
            m = name.match(/\bx\s*(\d+)\b/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат: "пак. 123" или "пакет 123"
            m = name.match(/пак(?:ет)?\s*[\.\:]*\s*(\d+)/i);
            if (m) return parseInt(m[1], 10);
            
            // Формат в начале: "123 кляймер" или "123 крепеж"
            m = name.match(/^(\d+)\s/);
            if (m) return parseInt(m[1], 10);
            
            return 100; // Значение по умолчанию
        }

        function limitWords(text, maxWords) {
            const words = String(text).split(/\s+/);
            if (words.length > maxWords) {
                return words.slice(0, maxWords).join(' ') + '...';
            }
            return text;
        }

        // Парсим количество в упаковке ДО обрезки названия
        const fastenersDataWithQty = fastenersData.map(f => ({
            ...f,
            piecesPerPack: parsePiecesPerPack(f.name),
            displayName: limitWords(f.name, 5)
        }));

        // ============================================================================
        // МОДАЛЬНОЕ ОКНО
        // ============================================================================

        function openFastenerModal(fastenerId) {
            const fastener = fastenersData.find(f => f.id === fastenerId);
            if (!fastener) return;

            const modal = document.getElementById('fastener-modal');
            const content = document.getElementById('fastener-modal-content');

            let html = '<div style="text-align:center; margin-bottom:20px;">';
            html += '<img src="' + fastener.image + '" alt="' + fastener.name + '" style="max-width:100%; max-height:300px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">';
            html += '</div>';
            
            html += '<h4 style="margin:0 0 15px 0; color:#333; font-size:1.2em;">' + fastener.name + '</h4>';
            
            if (fastener.description) {
                html += '<div style="color:#666; line-height:1.6; margin-bottom:20px; font-size:0.95em;">' + fastener.description + '</div>';
            }
            
            html += '<div style="background:#f8f9fa; padding:15px; border-radius:6px; margin-bottom:15px;">';
            html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
            html += '<span style="color:#666; font-size:0.9em;">Цена за упаковку:</span>';
            html += '<span style="color:#8bc34a; font-size:1.3em; font-weight:600;">' + fastener.price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</span>';
            html += '</div>';
            html += '</div>';
            
            html += '<a href="' + fastener.permalink + '" target="_blank" style="display:inline-block; padding:10px 20px; background:#8bc34a; color:#fff; text-decoration:none; border-radius:5px; transition:background 0.2s; font-weight:500;" onmouseover="this.style.background=\'#7cb342\'" onmouseout="this.style.background=\'#8bc34a\'">Подробнее о товаре →</a>';

            content.innerHTML = html;
            modal.style.display = 'block';
        }

        function closeFastenerModal() {
            const modal = document.getElementById('fastener-modal');
            modal.style.display = 'none';
        }

        // Обработчики закрытия модального окна
        const modalCloseBtn = document.getElementById('fastener-modal-close');
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeFastenerModal();
            });
        }
        
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('fastener-modal');
            if (event.target === modal) {
                closeFastenerModal();
            }
        });

        // ============================================================================
        // ОСНОВНОЙ ФУНКЦИОНАЛ
        // ============================================================================

        function tryToGetWidthMeters(raw) {
            if (raw === null || raw === undefined) return null;
            const n = parseFloat(raw);
            if (isNaN(n)) return null;
            // Если значение из WC (до 1000), это сантиметры
            if (n > 10 && n < 1000) return n / 100;
            // Если больше 10, это миллиметры
            if (n > 10) return n / 1000;
            // Если меньше 10, уже в метрах
            return n;
        }

        function parseWidthFromTitle(title) {
            if (!title) return null;
            // Ищем формат "XXX×YY мм" или "XXXxYY мм" (ширина x толщина)
            let m = title.match(/(\d+)\s*[×x]\s*\d+\s*мм/i);
            if (m) {
                const width = parseInt(m[1], 10);
                if (width > 10) return width / 1000; // мм в метры
                return width;
            }
            return null;
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
            html += '<label style="display:block; margin-bottom:6px;">Выберите подходящий крепеж:</label>';
            html += '<select id="fastener_select" name="fastener_select" style="width:100%; padding:8px; margin-bottom:10px;">';
            html += '<option value="">-- Выберите крепёж --</option>';
            fastenersDataWithQty.forEach(f => {
                const safe = String(f.displayName).replace(/"/g,'&quot;');
                html += `<option value="${f.id}" data-price="${f.price}" data-piecesperpack="${f.piecesPerPack}">${safe}</option>`;
            });
            html += '</select>';
            
            // Кнопка "О крепеже" - БЕЗ иконки и с type="button"
            html += '<button type="button" id="fastener_info_btn" style="display:none; width:100%; padding:10px; margin-bottom:10px; background:#8bc34a; color:#fff; border-radius:5px; cursor:pointer; font-weight:600; transition:all 0.2s;">О крепеже</button>';
            
            html += '<div id="fastener_calculation_result" style="display:none; background:#fff; padding:10px; border-radius:5px; margin-bottom:8px;"></div>';
            
            block.innerHTML = html;
            areaBlock.appendChild(block);

            const select = block.querySelector('#fastener_select');
            const result = block.querySelector('#fastener_calculation_result');
            const infoBtn = block.querySelector('#fastener_info_btn');

            // Обработчик кнопки "О крепеже" - с preventDefault и stopPropagation
            if (infoBtn) {
                infoBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const selectedId = parseInt(select.value, 10);
                    if (selectedId) {
                        openFastenerModal(selectedId);
                    }
                    return false;
                });
            }

            function getFieldValue(id) {
                const el = document.getElementById(id);
                if (!el) return null;
                const v = el.value;
                if (v === '' || v === null || v === undefined) return null;
                const n = parseFloat(String(v).replace(',', '.'));
                return isNaN(n) ? null : n;
            }

            function getEffectiveWidthMeters() {
                const wRaw = getFieldValue('sq_width');
                if (wRaw !== null) {
                    const m = tryToGetWidthMeters(wRaw);
                    if (m !== null) return m;
                }
                if (defaultProductWidth !== null) {
                    const m = tryToGetWidthMeters(defaultProductWidth);
                    if (m !== null) return m;
                }
                
                // Ищем в стандартных input полях WooCommerce
                const widthInputs = document.querySelectorAll(
                    'input[name="product_width"], input[name="_width"], ' +
                    'input[data-meta="width"], input[placeholder*="width" i], ' +
                    'input[placeholder*="ширина" i], input[id*="width" i]'
                );
                
                for (let input of widthInputs) {
                    const value = input.value;
                    if (value && !isNaN(parseFloat(value))) {
                        const parsed = tryToGetWidthMeters(value);
                        if (parsed !== null) return parsed;
                    }
                }
                
                // Ищем второе число в группе Д/Ш/В (три input поля подряд)
                const allNumberInputs = document.querySelectorAll('input[type="number"], input[type="text"]');
                let foundNumbers = [];
                for (let input of allNumberInputs) {
                    const val = parseFloat(input.value);
                    if (!isNaN(val) && val > 0 && val < 10000) {
                        foundNumbers.push({ input, value: val });
                    }
                }
                
                // Если найдены 3 подряд идущих числа, второе - это ширина
                if (foundNumbers.length >= 3) {
                    const secondNum = foundNumbers[1].value;
                    if (secondNum < 1000) return secondNum / 100; // сантиметры
                    if (secondNum < 10000) return secondNum / 1000; // миллиметры
                }
                
                // Пытаемся парсить из названия товара
                const productTitle = document.querySelector('h1.product_title, h1, [class*="product-title"]')?.textContent || '';
                const widthFromTitle = parseWidthFromTitle(productTitle);
                if (widthFromTitle !== null) return widthFromTitle;
                
                return null;
            }

            function getEffectiveLengthMeters() {
                const lRaw = getFieldValue('sq_length');
                if (lRaw !== null) {
                    const m = tryToGetWidthMeters(lRaw);
                    if (m !== null) return m;
                }
                if (defaultProductLength !== null) {
                    const m = tryToGetWidthMeters(defaultProductLength);
                    if (m !== null) return m;
                }
                return null;
            }

            function updateCalculation() {
                if (!select.value) {
                    result.style.display = 'none';
                    infoBtn.style.display = 'none';
                    return;
                }
                
                // Показываем кнопку "О крепеже" когда выбран крепеж
                infoBtn.style.display = 'block';
                
                const opt = select.options[select.selectedIndex];
                const price = parseFloat(opt.dataset.price || '0') || 0;
                const piecesPerPack = parseInt(opt.dataset.piecesperpack || '100', 10) || 100;

                const areaInput = getFieldValue('calc_area_input') ?? 1;
                const quantityInput = parseInt(document.getElementById('quantity_input')?.value || '1', 10) || 1;
                const totalArea = areaInput * quantityInput;

                const widthMeters = getEffectiveWidthMeters();
                const lengthMeters = getEffectiveLengthMeters();

                if (!widthMeters || widthMeters <= 0) {
                    result.innerHTML = '<p>Укажите ширину доски (поле "sq_width" или атрибут товара "shirina").</p>';
                    result.style.display = 'block';
                    return;
                }

                let widthMm = Math.round(widthMeters * 1000);

                // если значение явно больше 300 — делим на 10, пока не войдёт в диапазон
                while (widthMm > 300) {
                    widthMm = Math.round(widthMm / 10);
                }

                // если меньше 80 — умножаем на 10, пока не войдёт в диапазон
                while (widthMm < 80) {
                    widthMm = Math.round(widthMm * 10);
                }

                let perM2 = 30;
                if (widthMm >= 85 && widthMm <= 90) perM2 = 30;
                else if (widthMm >= 115 && widthMm <= 120) perM2 = 24;
                else if (widthMm >= 140 && widthMm <= 145) perM2 = 19;
                else if (widthMm >= 165 && widthMm <= 175) perM2 = 16;
                else if (widthMm >= 190 && widthMm <= 195) perM2 = 15;

                const qtyByFormula = Math.ceil((totalArea / widthMeters) * 2.7);
                const neededByPerM2 = Math.ceil(totalArea * perM2);
                const neededPieces = Math.max(qtyByFormula, neededByPerM2);

                const packsNeeded = Math.max(1, Math.ceil(neededPieces / piecesPerPack));
                const totalPrice = packsNeeded * price;

                // УПРОЩЕННЫЙ ВЫВОД - только 3 строки
                result.innerHTML = ''
                    + `<p>Площадь: <strong>${totalArea.toFixed(2)} м²</strong></p>`
                    + `<p>Необходимо упаковок: <strong>${packsNeeded} уп.</strong></p>`
                    + `<p>Стоимость крепежа: <strong>${totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} ₽</strong></p>`;

                result.style.display = 'block';

                // ===== КЛЮЧЕВАЯ СТРОКА: сохраняем расчёт в глобальное состояние =====
                window.pw_fastener_calculation = {
                    fastener_id: parseInt(select.value, 10),
                    packs_needed: packsNeeded
                };
            }

            ['sq_width','sq_length','calc_area_input','quantity_input'].forEach(id=>{
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateCalculation);
                    el.addEventListener('change', updateCalculation);
                }
            });
            select.addEventListener('change', updateCalculation);
            document.querySelectorAll('select[name^="attribute_"]').forEach(s=>{
                s.addEventListener('change', function(){ setTimeout(updateCalculation, 120); });
            });
            
            // Синхронизация с кнопками +/- количества в WooCommerce
            const quantityInputs = document.querySelectorAll('input[name="quantity"], input[type="number"][name="product_quantity"]');
            quantityInputs.forEach(el => {
                el.addEventListener('change', updateCalculation);
                el.addEventListener('input', function() { setTimeout(updateCalculation, 100); });
            });
            
            // Отслеживание изменений через кнопки +/-
            const quantityPlus = document.querySelector('.plus, button[class*="plus"]');
            const quantityMinus = document.querySelector('.minus, button[class*="minus"]');
            if (quantityPlus) quantityPlus.addEventListener('click', function() { setTimeout(updateCalculation, 150); });
            if (quantityMinus) quantityMinus.addEventListener('click', function() { setTimeout(updateCalculation, 150); });
            
            document.body.addEventListener('variation:updated', function(){ setTimeout(updateCalculation, 150); });
            document.body.addEventListener('found_variation', function(){ setTimeout(updateCalculation, 150); });

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

// ============================================================================
// Остальной код без изменений
// ============================================================================

add_filter('woocommerce_add_to_cart_redirect', 'pw_capture_fastener_before_cart', 10, 2);
function pw_capture_fastener_before_cart($url, $product_id) {
    return $url;
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

        addToCartBtn.addEventListener('click', function(e) {
            const form = document.querySelector('form.cart');
            if (!form) return;

            if (window.pw_fastener_calculation && window.pw_fastener_calculation.fastener_id > 0) {
                const calc = window.pw_fastener_calculation;

                const oldFields = form.querySelectorAll('input[name="fastener_select"], input[name="fastener_packs_needed"]');
                oldFields.forEach(f => f.remove());

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

                console.log('Fastener fields injected:', { fastener_id: calc.fastener_id, packs_needed: calc.packs_needed });
            }
        }, false);
    })();
    </script>
    <?php
}

$GLOBALS['pw_fastener_adding'] = false;

add_action('woocommerce_add_to_cart', 'pw_add_fastener_to_cart', 20, 6);
function pw_add_fastener_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation_data, $cart_item_data) {
    if (!empty($GLOBALS['pw_fastener_adding'])) {
        return;
    }
    
    $fastener_id = !empty($_POST['fastener_select']) ? intval($_POST['fastener_select']) : 0;
    $fastener_qty = !empty($_POST['fastener_packs_needed']) ? intval($_POST['fastener_packs_needed']) : 0;
    
    if (!$fastener_id || $fastener_qty <= 0) {
        return;
    }
    
    $fastener_product = wc_get_product($fastener_id);
    if (!$fastener_product) {
        return;
    }
    
    $cart = WC()->cart->get_cart();
    $found_key = false;
    
    foreach ($cart as $item_key => $item) {
        if (isset($item['added_with_product']) && $item['added_with_product'] == $product_id) {
            if ($item['product_id'] == $fastener_id) {
                $found_key = $item_key;
                break;
            }
        }
    }
    
    if ($found_key !== false) {
        return;
    }
    
    $GLOBALS['pw_fastener_adding'] = true;
    WC()->cart->add_to_cart($fastener_id, $fastener_qty, 0, array(), array(
        'added_with_product' => $product_id,
    ));
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
