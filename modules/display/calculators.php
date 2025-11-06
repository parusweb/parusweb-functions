<?php
/**
 * ============================================================================
 * МОДУЛЬ: ОТОБРАЖЕНИЕ КАЛЬКУЛЯТОРОВ
 * ============================================================================
 * 
 * Вывод всех типов калькуляторов на странице товара:
 * - Калькулятор площади
 * - Калькулятор размеров
 * - Калькулятор с множителем
 * - Калькулятор погонных метров
 * - Калькулятор квадратных метров
 * - Калькулятор фальшбалок
 * - Калькулятор реечных перегородок
 * 
 * @package ParusWeb_Functions
 * @subpackage Display
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ОСНОВНОЙ ХЕЛПЕР - ПРОВЕРКА ОТОБРАЖЕНИЯ КАЛЬКУЛЯТОРА
// ============================================================================

add_action('woocommerce_after_add_to_cart_button', 'parusweb_render_calculators');


function parusweb_render_calculators() {
    if (!is_product()) return;
    
    global $product;
    $product_id = $product->get_id();
    
    $is_target = is_in_target_categories($product_id);
    $is_multiplier = is_in_multiplier_categories($product_id);
    $is_square_meter = is_square_meter_category($product_id);
    $is_running_meter = is_running_meter_category($product_id);
    $is_partition_slat = is_partition_slat_category($product_id);
    
    if (!$is_target && !$is_multiplier) {
        return;
    }
    
    $title = $product->get_name();
    $pack_area = extract_area_with_qty($title, $product_id);
    $dims = extract_dimensions_from_title($title);
    $painting_services = get_available_painting_services_by_material($product_id);
    $price_multiplier = get_price_multiplier($product_id);
    
    $calc_settings = null;
    if ($is_multiplier) {
        $calc_settings = [
            'width_min' => floatval(get_post_meta($product_id, '_calc_width_min', true)),
            'width_max' => floatval(get_post_meta($product_id, '_calc_width_max', true)),
            'width_step' => floatval(get_post_meta($product_id, '_calc_width_step', true)) ?: 100,
            'length_min' => floatval(get_post_meta($product_id, '_calc_length_min', true)),
            'length_max' => floatval(get_post_meta($product_id, '_calc_length_max', true)),
            'length_step' => floatval(get_post_meta($product_id, '_calc_length_step', true)) ?: 0.01,
        ];
    }
    
    $leaf_ids = array_merge([190], [191, 127, 94]);
    $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
    $unit_text = $is_leaf_category ? 'лист' : 'упаковку';
    $unit_forms = $is_leaf_category ? ['лист', 'листа', 'листов'] : ['упаковка', 'упаковки', 'упаковок'];
    
    $show_falsebalk_calc = false;
    $shapes_data = array();
    
    if ($is_square_meter) {
        $is_falsebalk = has_term(266, 'product_cat', $product_id);
        if ($is_falsebalk) {
            $shapes_data = get_post_meta($product_id, '_falsebalk_shapes_data', true);
            if (!is_array($shapes_data)) {
                $shapes_data = array();
            }
            
            foreach ($shapes_data as $shape_key => $shape_info) {
                if (is_array($shape_info) && !empty($shape_info['enabled'])) {
                    $has_width = !empty($shape_info['width_min']) || !empty($shape_info['width_max']);
                    $has_height = !empty($shape_info['height_min']) || !empty($shape_info['height_max']);
                    $has_length = !empty($shape_info['length_min']) || !empty($shape_info['length_max']);
                    $has_old_format = !empty($shape_info['widths']) || !empty($shape_info['heights']) || !empty($shape_info['lengths']);
                    
                    if ($has_width || $has_height || $has_length || $has_old_format) {
                        $show_falsebalk_calc = true;
                        break;
                    }
                }
            }
        }
    }
    
    ?>
    <script>
    console.log('calculators - загружен');
const isSquareMeter = <?php echo $is_square_meter ? 'true' : 'false'; ?>;
const isRunningMeter = <?php echo $is_running_meter ? 'true' : 'false'; ?>;
const paintingServices = <?php echo json_encode($painting_services); ?>;
const priceMultiplier = <?php echo $price_multiplier; ?>;
const isMultiplierCategory = <?php echo $is_multiplier ? 'true' : 'false'; ?>;
const calcSettings = <?php echo $calc_settings ? json_encode($calc_settings) : 'null'; ?>;
const quantityInput = document.querySelector('input.qty');
let isAutoUpdate = false;

// ============================================================================
// БЛОК УСЛУГ ПОКРАСКИ
// ============================================================================

let paintingBlock = null;
if (paintingServices && paintingServices.length > 0) {
    paintingBlock = document.createElement('div');
    paintingBlock.id = 'painting_services_block';
    paintingBlock.style.cssText = 'margin-top:20px; padding:15px; background:#f9f9f9; border-radius:8px;';
    
    let paintingHTML = '<h4 style="margin-bottom:15px;">Дополнительные услуги покраски</h4>';
    paintingHTML += '<div style="display:flex; flex-direction:column; gap:12px;">';
    
    paintingServices.forEach((service, index) => {
        const radioId = 'painting_' + index;
        paintingHTML += '<label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px; background:#fff; border-radius:6px; border:2px solid #ddd; transition:all .2s;">';
        paintingHTML += '<input type="radio" name="painting_service" value="' + service.id + '" data-service-index="' + index + '" id="' + radioId + '" style="cursor:pointer;">';
        paintingHTML += '<div style="flex:1;"><strong>' + service.name + '</strong>';
        if (service.color) paintingHTML += '<br><span style="font-size:0.9em; color:#666;">Цвет: ' + service.color + '</span>';
        paintingHTML += '</div>';
        paintingHTML += '<span class="painting_service_cost" style="font-weight:600; font-size:1.1em;">0 ₽</span>';
        paintingHTML += '</label>';
    });
    
    paintingHTML += '</div>';
    paintingBlock.innerHTML = paintingHTML;
}

// ============================================================================
// ФУНКЦИИ ОБНОВЛЕНИЯ СТОИМОСТИ ПОКРАСКИ
// ============================================================================

function updatePaintingServiceCost(totalArea) {
    const selectedRadio = document.querySelector('input[name="painting_service"]:checked');
    
    document.querySelectorAll('.painting_service_cost').forEach(span => {
        const label = span.closest('label');
        const radio = label.querySelector('input[type="radio"]');
        const serviceIndex = parseInt(radio.dataset.serviceIndex);
        const service = paintingServices[serviceIndex];
        
        let cost = 0;
        if (service.price_per_m2 > 0 && totalArea > 0) {
            cost = service.price_per_m2 * totalArea;
        }
        
        span.textContent = cost > 0 ? (cost.toFixed(2).replace(/\.00$/, '').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽') : '0 ₽';
        
        if (selectedRadio && radio === selectedRadio) {
            label.style.borderColor = '#2c5cc5';
            label.style.background = '#f0f4ff';
        } else {
            label.style.borderColor = '#ddd';
            label.style.background = '#fff';
        }
    });
    
    const event = new Event('painting_cost_updated');
    document.dispatchEvent(event);
}

if (paintingBlock) {
    document.querySelectorAll('input[name="painting_service"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('label:has(input[name="painting_service"])').forEach(lbl => {
                lbl.style.borderColor = '#ddd';
                lbl.style.background = '#fff';
            });
            
            const selectedLabel = radio.closest('label');
            selectedLabel.style.borderColor = '#2c5cc5';
            selectedLabel.style.background = '#f0f4ff';
        });
    });
}

// ============================================================================
// HIDDEN FIELDS HELPERS
// ============================================================================

function removeHiddenFields(prefix) {
    document.querySelectorAll('input[name^="' + prefix + '"]').forEach(input => input.remove());
}

function addHiddenField(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    document.querySelector('form.cart').appendChild(input);
}

function getPaintingServiceData() {
    const selectedRadio = document.querySelector('input[name="painting_service"]:checked');
    if (!selectedRadio) return null;
    
    const serviceIndex = parseInt(selectedRadio.dataset.serviceIndex);
    const service = paintingServices[serviceIndex];
    const costSpan = selectedRadio.closest('label').querySelector('.painting_service_cost');
    const totalCost = parseFloat(costSpan.textContent.replace(/[^\d.-]/g, ''));
    
    return {
        id: service.id,
        name: service.name,
        color: service.color || '',
        name_with_color: service.color ? (service.name + ' (' + service.color + ')') : service.name,
        price_per_m2: service.price_per_m2,
        total_cost: totalCost
    };
}

// ============================================================================
// КАЛЬКУЛЯТОР ПЛОЩАДИ
// ============================================================================

<?php if($is_target && $pack_area): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultBlock = document.querySelector('form.cart') || document.querySelector('.summary');
    if (!resultBlock) return;
    
    const areaCalc = document.createElement('div');
    areaCalc.id = 'calc-area';
    
    let areaCalcHTML = '<br><h4>Калькулятор площади</h4>';
    areaCalcHTML += '<p style="margin-bottom:10px;">Площадь упаковки: <strong><?php echo $pack_area; ?> м²</strong></p>';
    areaCalcHTML += '<label>Необходимая площадь (м²): <input type="number" id="custom_area" min="0.01" step="0.01" placeholder="Например: 25.5" style="margin-left:10px; width:150px; background:#fff"></label>';
    areaCalcHTML += '<div id="calc_result" style="margin-top:10px;"></div>';
    
    areaCalc.innerHTML = areaCalcHTML;
    resultBlock.appendChild(areaCalc);
    
    if (paintingBlock) {
        areaCalc.appendChild(paintingBlock);
    }
    
    const areaInput = document.getElementById('custom_area');
    const resultDiv = document.getElementById('calc_result');
    const packArea = <?php echo $pack_area; ?>;
    const basePrice = <?php echo floatval($product->get_price()); ?>;
    
    function updateAreaCalc() {
        const areaValue = parseFloat(areaInput.value);
        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        
        if (!areaValue || areaValue <= 0) {
            resultDiv.innerHTML = '';
            removeHiddenFields('custom_area_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const packs = Math.ceil(areaValue / packArea);
        const totalPrice = basePrice * packs * priceMultiplier;
        const totalArea = packArea * packs;
        
        updatePaintingServiceCost(totalArea * quantity);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        const unitText = '<?php echo $unit_text; ?>';
        const plural = (packs % 10 === 1 && packs % 100 !== 11) ? '<?php echo $unit_forms[0]; ?>' :
                      ((packs % 10 >= 2 && packs % 10 <= 4 && (packs % 100 < 10 || packs % 100 >= 20)) ? '<?php echo $unit_forms[1]; ?>' : '<?php echo $unit_forms[2]; ?>');
        
        let resultText = '<div style="padding:15px; background:#f0f4ff; border-radius:8px; margin-top:10px;">';
        resultText += '<div style="font-size:1.1em; margin-bottom:5px;">Потребуется: <strong>' + packs + ' ' + plural + '</strong> (' + totalArea.toFixed(2) + ' м²)</div>';
        resultText += '<div style="font-size:1.3em; font-weight:600; color:#2c5cc5;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.4em; font-weight:700; color:#2c5cc5; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        resultDiv.innerHTML = resultText;
        
        removeHiddenFields('custom_area_');
        addHiddenField('custom_area_packs', packs);
        addHiddenField('custom_area_area_value', areaValue);
        addHiddenField('custom_area_total_price', totalPrice);
        addHiddenField('custom_area_grand_total', grandTotal);
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', totalArea * quantity);
        }
        
        if (quantityInput) {
            isAutoUpdate = true;
            quantityInput.value = packs;
            quantityInput.dispatchEvent(new Event('change'));
            setTimeout(() => { isAutoUpdate = false; }, 100);
        }
    }
    
    areaInput.addEventListener('input', updateAreaCalc);
    document.addEventListener('painting_cost_updated', updateAreaCalc);
});
<?php endif; ?>

// ============================================================================
// КАЛЬКУЛЯТОР РАЗМЕРОВ (СТАРЫЙ)
// ============================================================================

<?php if($dims): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultBlock = document.querySelector('form.cart') || document.querySelector('.summary');
    if (!resultBlock) return;
    
    const dimCalc = document.createElement('div');
    dimCalc.id = 'calc-dim';
    
    let dimCalcHTML = '<br><h4>Калькулятор размеров</h4>';
    dimCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';
    dimCalcHTML += '<label>Ширина (мм): <select id="custom_width" style="background:#fff; margin-left:10px;">';
    dimCalcHTML += '<option value="">Выберите...</option>';
    <?php foreach($dims['widths'] as $w): ?>
    dimCalcHTML += '<option value="<?php echo $w; ?>"><?php echo $w; ?></option>';
    <?php endforeach; ?>
    dimCalcHTML += '</select></label>';
    
    dimCalcHTML += '<label>Длина (мм): <input type="number" id="custom_length" min="<?php echo $dims['length_min']; ?>" max="<?php echo $dims['length_max']; ?>" placeholder="1000" style="width:100px; margin-left:10px; background:#fff"></label>';
    dimCalcHTML += '<label style="display:none">Количество (шт): <span id="dim_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>';
    dimCalcHTML += '</div>';
    dimCalcHTML += '<div id="calc_dim_result" style="margin-top:10px;"></div>';
    
    dimCalc.innerHTML = dimCalcHTML;
    resultBlock.appendChild(dimCalc);
    
    if (paintingBlock) {
        dimCalc.appendChild(paintingBlock);
    }
    
    const widthSel = document.getElementById('custom_width');
    const lengthInp = document.getElementById('custom_length');
    const dimResult = document.getElementById('calc_dim_result');
    const dimQuantityDisplay = document.getElementById('dim_quantity_display');
    const basePrice = <?php echo floatval($product->get_price()); ?>;
    
    function updateDimCalc() {
        const w = parseFloat(widthSel.value);
        const l = parseFloat(lengthInp.value);
        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        dimQuantityDisplay.textContent = quantity;
        
        if (!w || !l) {
            dimResult.innerHTML = '';
            removeHiddenFields('custom_width_');
            removeHiddenFields('custom_length_');
            removeHiddenFields('custom_dim_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const area = (w / 1000) * (l / 1000);
        const totalPrice = basePrice * area * priceMultiplier;
        const totalArea = area * quantity;
        
        updatePaintingServiceCost(totalArea);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        let resultText = '<div style="padding:15px; background:#f0f4ff; border-radius:8px; margin-top:10px;">';
        resultText += '<div style="font-size:1.1em; margin-bottom:5px;">Площадь: <strong>' + area.toFixed(3) + ' м²</strong></div>';
        resultText += '<div style="font-size:1.3em; font-weight:600; color:#2c5cc5;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.4em; font-weight:700; color:#2c5cc5; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        dimResult.innerHTML = resultText;
        
        removeHiddenFields('custom_width_');
        removeHiddenFields('custom_length_');
        removeHiddenFields('custom_dim_');
        addHiddenField('custom_width_val', w);
        addHiddenField('custom_length_val', l);
        addHiddenField('custom_dim_price', totalPrice);
        addHiddenField('custom_dim_grand_total', grandTotal);
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', totalArea);
        }
    }
    
    widthSel.addEventListener('change', updateDimCalc);
    lengthInp.addEventListener('input', updateDimCalc);
    document.addEventListener('painting_cost_updated', updateDimCalc);
});
<?php endif; ?>

// ============================================================================
// КАЛЬКУЛЯТОР С МНОЖИТЕЛЕМ
// ============================================================================

<?php if($is_multiplier && !$is_square_meter && !$is_running_meter && !$is_partition_slat): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultBlock = document.querySelector('form.cart') || document.querySelector('.summary');
    if (!resultBlock) return;
    
    const multCalc = document.createElement('div');
    multCalc.id = 'calc-multiplier';
    
    let multCalcHTML = '<br><h4>Калькулятор стоимости</h4>';
    multCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';
    
    if (calcSettings && calcSettings.width_min > 0 && calcSettings.width_max > 0) {
        multCalcHTML += '<label>Ширина (мм): <select id="mult_width" style="background:#fff; margin-left:10px;"><option value="">Выберите...</option>';
        for (let w = calcSettings.width_min; w <= calcSettings.width_max; w += calcSettings.width_step) {
            multCalcHTML += '<option value="' + w + '">' + w + '</option>';
        }
        multCalcHTML += '</select></label>';
    } else {
        multCalcHTML += '<label>Ширина (мм): <input type="number" id="mult_width" min="1" step="100" placeholder="1000" style="width:100px; margin-left:10px; background:#fff"></label>';
    }
    
    if (calcSettings && calcSettings.length_min > 0 && calcSettings.length_max > 0) {
        multCalcHTML += '<label>Длина (м): <select id="mult_length" style="background:#fff; margin-left:10px;"><option value="">Выберите...</option>';
        const lengthMin = calcSettings.length_min;
        const lengthMax = calcSettings.length_max;
        const lengthStep = calcSettings.length_step;
        const stepsCount = Math.round((lengthMax - lengthMin) / lengthStep) + 1;
        for (let i = 0; i < stepsCount; i++) {
            const value = (lengthMin + (i * lengthStep)).toFixed(2);
            multCalcHTML += '<option value="' + value + '">' + value + '</option>';
        }
        multCalcHTML += '</select></label>';
    } else {
        multCalcHTML += '<label>Длина (м): <input type="number" id="mult_length" min="0.01" step="0.01" placeholder="2.0" style="width:100px; margin-left:10px; background:#fff"></label>';
    }
    
    multCalcHTML += '<label style="display:none">Количество (шт): <span id="mult_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>';
    multCalcHTML += '</div>';
    multCalcHTML += '<div id="calc_mult_result" style="margin-top:10px;"></div>';
    
    multCalc.innerHTML = multCalcHTML;
    resultBlock.appendChild(multCalc);
    
    if (paintingBlock) {
        multCalc.appendChild(paintingBlock);
    }
    
    const multWidthEl = document.getElementById('mult_width');
    const multLengthEl = document.getElementById('mult_length');
    const multResult = document.getElementById('calc_mult_result');
    const multQuantityDisplay = document.getElementById('mult_quantity_display');
    const basePrice = <?php echo floatval($product->get_price()); ?>;
    
    function updateMultiplierCalc() {
        const widthValue = parseFloat(multWidthEl.value);
        const lengthValue = parseFloat(multLengthEl.value);
        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        multQuantityDisplay.textContent = quantity;
        
        if (!widthValue || !lengthValue) {
            multResult.innerHTML = '';
            removeHiddenFields('custom_mult_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const areaPerItem = (widthValue / 1000) * lengthValue;
        const totalArea = areaPerItem * quantity;
        const totalPrice = basePrice * areaPerItem * priceMultiplier;
        
        updatePaintingServiceCost(totalArea);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        let resultText = '<div style="padding:15px; background:#f0f4ff; border-radius:8px; margin-top:10px;">';
        resultText += '<div style="font-size:1.1em; margin-bottom:5px;">Площадь: <strong>' + areaPerItem.toFixed(3) + ' м²</strong></div>';
        resultText += '<div style="font-size:1.3em; font-weight:600; color:#2c5cc5;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.4em; font-weight:700; color:#2c5cc5; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        multResult.innerHTML = resultText;
        
        removeHiddenFields('custom_mult_');
        addHiddenField('custom_mult_width', widthValue);
        addHiddenField('custom_mult_length', lengthValue);
        addHiddenField('custom_mult_quantity', quantity);
        addHiddenField('custom_mult_area_per_item', areaPerItem);
        addHiddenField('custom_mult_total_area', totalArea);
        addHiddenField('custom_mult_multiplier', priceMultiplier);
        addHiddenField('custom_mult_price', totalPrice);
        addHiddenField('custom_mult_grand_total', grandTotal);
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', totalArea);
        }
    }
    
    multWidthEl.addEventListener('change', updateMultiplierCalc);
    multLengthEl.addEventListener('change', updateMultiplierCalc);
    multWidthEl.addEventListener('input', updateMultiplierCalc);
    multLengthEl.addEventListener('input', updateMultiplierCalc);
    document.addEventListener('painting_cost_updated', updateMultiplierCalc);
});
<?php endif; ?>

// ============================================================================
// КАЛЬКУЛЯТОР ПОГОННЫХ МЕТРОВ И ФАЛЬШБАЛОК
// ============================================================================

<?php if($is_running_meter): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultBlock = document.querySelector('form.cart') || document.querySelector('.summary');
    if (!resultBlock) return;
    
    <?php if ($show_falsebalk_calc): ?>
    if (resultBlock) {
        resultBlock.innerHTML = '';
    }
    <?php endif; ?>
    
    const runningMeterCalc = document.createElement('div');
    runningMeterCalc.id = 'calc-running-meter';
    
    let rmCalcHTML = '<br><h4>Калькулятор стоимости</h4>';
    
    <?php if ($show_falsebalk_calculator): ?>
    const shapesData = <?php echo json_encode($shapes_data); ?>;
    
    <?php 
    $shape_icons = [
        'g' => '<svg width="60" height="60" viewBox="0 0 60 60"><rect x="5" y="5" width="10" height="50" fill="#000"/><rect x="5" y="45" width="50" height="10" fill="#000"/></svg>',
        'p' => '<svg width="60" height="60" viewBox="0 0 60 60"><rect x="5" y="5" width="10" height="50" fill="#000"/><rect x="45" y="5" width="10" height="50" fill="#000"/><rect x="5" y="5" width="50" height="10" fill="#000"/></svg>',
        'o' => '<svg width="60" height="60" viewBox="0 0 60 60"><rect x="5" y="5" width="50" height="50" fill="none" stroke="#000" stroke-width="10"/></svg>'
    ];
    
    $shape_labels = [
        'g' => 'Г-образная',
        'p' => 'П-образная',
        'o' => 'О-образная'
    ];
    
    $shapes_buttons_html = '';
    foreach ($shapes_data as $shape_key => $shape_info):
        if (is_array($shape_info) && !empty($shape_info['enabled'])):
            $shape_label = isset($shape_labels[$shape_key]) ? $shape_labels[$shape_key] : ucfirst($shape_key);
            $shapes_buttons_html .= '<label class="shape-tile" data-shape="' . esc_attr($shape_key) . '" style="cursor:pointer; border:2px solid #ccc; border-radius:10px; padding:10px; background:#fff; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; transition:all .2s; min-width:100px;">';
            $shapes_buttons_html .= '<input type="radio" name="falsebalk_shape" value="' . esc_attr($shape_key) . '" style="display:none;">';
            $shapes_buttons_html .= '<div>' . $shape_icons[$shape_key] . '</div>';
            $shapes_buttons_html .= '<span style="font-size:12px; color:#666; text-align:center;">' . esc_html($shape_label) . '</span>';
            $shapes_buttons_html .= '</label>';
        endif;
    endforeach;
    ?>
    
    rmCalcHTML += '<div style="margin-bottom:20px; border:2px solid #e0e0e0; padding:15px; border-radius:8px; background:#f9f9f9;">';
    rmCalcHTML += '<label style="display:block; margin-bottom:15px; font-weight:600; font-size:1.1em;">Шаг 1: Выберите форму сечения фальшбалки</label>';
    rmCalcHTML += '<div style="display:flex; gap:15px; flex-wrap:wrap;">';
    rmCalcHTML += <?php echo json_encode($shapes_buttons_html); ?>;
    rmCalcHTML += '</div></div>';
    
    rmCalcHTML += '<div id="falsebalk_params" style="display:none; margin-bottom:20px; border:2px solid #e0e0e0; padding:15px; border-radius:8px; background:#f9f9f9;">';
    rmCalcHTML += '<label style="display:block; margin-bottom:15px; font-weight:600; font-size:1.1em;">Шаг 2: Выберите размеры</label>';
    rmCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';
    
    rmCalcHTML += '<label style="display:flex; flex-direction:column; gap:5px;"><span style="font-weight:500;">Ширина (мм):</span>';
    rmCalcHTML += '<select id="rm_width" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">';
    rmCalcHTML += '<option value="">Сначала выберите форму</option></select></label>';
    
    rmCalcHTML += '<div id="height_container" style="display:contents"></div>';
    
    rmCalcHTML += '<label style="display:flex; flex-direction:column; gap:5px;"><span style="font-weight:500;">Длина (м):</span>';
    rmCalcHTML += '<select id="rm_length" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">';
    rmCalcHTML += '<option value="">Сначала выберите форму</option></select></label>';
    
    rmCalcHTML += '<label style="display:none; flex-direction:column; gap:5px;"><span style="font-weight:500;">Количество (шт):</span>';
    rmCalcHTML += '<span id="rm_quantity_display" style="font-weight:600; font-size:1.1em;">1</span></label>';
    
    rmCalcHTML += '</div></div>';
    
    rmCalcHTML += '<label style="display:none">Количество (шт): <span id="rm_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>';
    rmCalcHTML += '<div id="calc_rm_result" style="margin-top:10px;"></div>';
    
    <?php else: ?>
    
    rmCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';
    rmCalcHTML += '<label>Ширина (мм): <input type="number" id="rm_width" min="1" placeholder="100" style="width:100px; margin-left:10px; background:#fff"></label>';
    rmCalcHTML += '<label>Длина (м): <input type="number" id="rm_length" min="0.1" step="0.1" placeholder="2.0" style="width:100px; margin-left:10px; background:#fff"></label>';
    rmCalcHTML += '<label style="display:none">Количество (шт): <span id="rm_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>';
    rmCalcHTML += '</div>';
    rmCalcHTML += '<div id="calc_rm_result" style="margin-top:10px;"></div>';
    
    <?php endif; ?>
    
    runningMeterCalc.innerHTML = rmCalcHTML;
    if (resultBlock) {
        resultBlock.appendChild(runningMeterCalc);
    }
    
    if (paintingBlock) {
        runningMeterCalc.appendChild(paintingBlock);
    }
    
    <?php if ($show_falsebalk_calculator): ?>
    
    function generateOptions(min, max, step, unit = '') {
        const options = ['<option value="">Выберите...</option>'];
        if (!min || !max || !step || min > max) return options.join('');
        const stepsCount = Math.round((max - min) / step) + 1;
        for (let i = 0; i < stepsCount; i++) {
            const value = min + (i * step);
            const displayValue = unit === 'м' ? value.toFixed(2) : Math.round(value);
            const rawValue = unit === 'м' ? value.toFixed(2) : Math.round(value);
            options.push('<option value="' + rawValue + '">' + displayValue + (unit ? ' ' + unit : '') + '</option>');
        }
        return options.join('');
    }
    
    function parseOldFormat(data) {
        if (typeof data === 'string' && data.includes(',')) {
            const values = data.split(',').map(v => v.trim()).filter(v => v);
            return values.map(v => '<option value="' + v + '">' + v + '</option>').join('');
        }
        return null;
    }
    
    const falsebalkaParams = document.getElementById('falsebalk_params');
    const rmWidthEl = document.getElementById('rm_width');
    const heightContainer = document.getElementById('height_container');
    const rmLengthEl = document.getElementById('rm_length');
    
    function updateDimensions(selectedShape) {
        const shapeData = shapesData[selectedShape];
        if (!shapeData || !shapeData.enabled) return;
        
        falsebalkaParams.style.display = 'block';
        
        const oldWidthFormat = parseOldFormat(shapeData.widths);
        if (oldWidthFormat) {
            rmWidthEl.innerHTML = oldWidthFormat;
        } else if (shapeData.width_min && shapeData.width_max && shapeData.width_step) {
            rmWidthEl.innerHTML = generateOptions(shapeData.width_min, shapeData.width_max, shapeData.width_step);
        } else {
            rmWidthEl.innerHTML = '<option value="">Нет данных</option>';
        }
        
        heightContainer.innerHTML = '';
        if (selectedShape === 'p') {
            const oldHeightFormat = parseOldFormat(shapeData.heights);
            const heightHTML1 = oldHeightFormat || generateOptions(shapeData.height_min, shapeData.height_max, shapeData.height_step);
            const heightHTML2 = oldHeightFormat || generateOptions(shapeData.height2_min || shapeData.height_min, shapeData.height2_max || shapeData.height_max, shapeData.height2_step || shapeData.height_step);
            
            heightContainer.innerHTML = '<label style="display:flex; flex-direction:column; gap:5px;"><span style="font-weight:500;">Высота 1 (мм):</span>';
            heightContainer.innerHTML += '<select id="rm_height1" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">' + heightHTML1 + '</select></label>';
            heightContainer.innerHTML += '<label style="display:flex; flex-direction:column; gap:5px;"><span style="font-weight:500;">Высота 2 (мм):</span>';
            heightContainer.innerHTML += '<select id="rm_height2" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">' + heightHTML2 + '</select></label>';
        } else {
            const oldHeightFormat = parseOldFormat(shapeData.heights);
            const heightHTML = oldHeightFormat || generateOptions(shapeData.height_min, shapeData.height_max, shapeData.height_step);
            
            heightContainer.innerHTML = '<label style="display:flex; flex-direction:column; gap:5px;"><span style="font-weight:500;">Высота (мм):</span>';
            heightContainer.innerHTML += '<select id="rm_height" style="background:#fff; padding:8px 12px; border:1px solid #ddd; border-radius:4px; min-width:150px;">' + heightHTML + '</select></label>';
        }
        
        const oldLengthFormat = parseOldFormat(shapeData.lengths);
        if (oldLengthFormat) {
            rmLengthEl.innerHTML = oldLengthFormat;
        } else if (shapeData.length_min && shapeData.length_max && shapeData.length_step) {
            rmLengthEl.innerHTML = generateOptions(shapeData.length_min, shapeData.length_max, shapeData.length_step, 'м');
        } else {
            rmLengthEl.innerHTML = '<option value="">Нет данных</option>';
        }
        
        const heightInputs = document.querySelectorAll('#rm_height, #rm_height1, #rm_height2');
        heightInputs.forEach(input => {
            input.addEventListener('change', updateRunningMeterCalc);
        });
    }
    
    document.querySelectorAll('.shape-tile').forEach(tile => {
        tile.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            document.querySelectorAll('.shape-tile').forEach(t => {
                t.style.borderColor = '#ccc';
                t.style.transform = 'scale(1)';
            });
            
            this.style.borderColor = '#2c5cc5';
            this.style.transform = 'scale(1.05)';
            
            const shapeValue = radio.value;
            updateDimensions(shapeValue);
            updateRunningMeterCalc();
        });
        
        tile.addEventListener('mouseenter', function() {
            const radio = this.querySelector('input[name="falsebalk_shape"]');
            if (!radio || !radio.checked) {
                this.style.borderColor = '#2c5cc5';
                this.style.transform = 'scale(1.02)';
            }
        });
        
        tile.addEventListener('mouseleave', function() {
            const radio = this.querySelector('input[name="falsebalk_shape"]');
            if (!radio || !radio.checked) {
                this.style.borderColor = '#ccc';
                this.style.transform = 'scale(1)';
            }
        });
    });
    
    <?php else: ?>
    const rmWidthEl = document.getElementById('rm_width');
    <?php endif; ?>
    
    const rmLengthEl = document.getElementById('rm_length');
    const rmQuantityDisplay = document.getElementById('rm_quantity_display');
    const rmResult = document.getElementById('calc_rm_result');
    const basePriceRM = <?php echo floatval($product->get_price()); ?>;
    
    function updateRunningMeterCalc() {
        <?php if ($show_falsebalk_calculator): ?>
        const selectedShape = document.querySelector('input[name="falsebalk_shape"]:checked');
        if (!selectedShape) {
            rmResult.innerHTML = '<span style="color: #999;">⬆️ Выберите форму сечения фальшбалки</span>';
            return;
        }
        
        const widthValue = rmWidthEl ? parseFloat(rmWidthEl.value) : 0;
        const lengthValue = parseFloat(rmLengthEl.value);
        
        let heightValue = 0;
        let height2Value = 0;
        
        if (selectedShape.value === 'p') {
            const height1El = document.getElementById('rm_height1');
            const height2El = document.getElementById('rm_height2');
            heightValue = height1El ? parseFloat(height1El.value) : 0;
            height2Value = height2El ? parseFloat(height2El.value) : 0;
        } else {
            const heightEl = document.getElementById('rm_height');
            heightValue = heightEl ? parseFloat(heightEl.value) : 0;
        }
        <?php else: ?>
        const widthValue = rmWidthEl ? parseFloat(rmWidthEl.value) : 0;
        const lengthValue = parseFloat(rmLengthEl.value);
        <?php endif; ?>
        
        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        rmQuantityDisplay.textContent = quantity;
        
        if (!lengthValue || lengthValue <= 0) {
            rmResult.innerHTML = '';
            removeHiddenFields('custom_rm_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const totalLength = lengthValue * quantity;
        
        let paintingArea = 0;
        if (widthValue > 0) {
            const width_m = widthValue / 1000;
            const height_m = (typeof heightValue !== 'undefined' ? heightValue : 0) / 1000;
            const height2_m = (typeof height2Value !== 'undefined' ? height2Value : 0) / 1000;
            
            <?php if ($show_falsebalk_calculator): ?>
            const shapeValue = selectedShape.value;
            if (shapeValue === 'g') {
                paintingArea = ((width_m + height_m) * 2) * totalLength;
            } else if (shapeValue === 'p') {
                paintingArea = ((width_m + height_m + height2_m) * 2) * totalLength;
            } else if (shapeValue === 'o') {
                paintingArea = ((width_m + height_m) * 2) * totalLength;
            }
            <?php else: ?>
            paintingArea = ((width_m + height_m) * 2) * totalLength;
            <?php endif; ?>
        }
        
        const totalPrice = basePriceRM * totalLength * priceMultiplier;
        
        updatePaintingServiceCost(paintingArea);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        let resultText = '<div style="padding:15px; background:#f0f4ff; border-radius:8px; margin-top:10px;">';
        
        <?php if ($show_falsebalk_calculator): ?>
        const shapeLabels = {'g': 'Г-образная', 'p': 'П-образная', 'o': 'О-образная'};
        resultText += '<div style="font-size:1.05em; margin-bottom:5px;">Форма: <strong>' + shapeLabels[selectedShape.value] + '</strong></div>';
        if (widthValue > 0) resultText += '<div style="font-size:0.95em; color:#666;">Ширина: ' + widthValue + ' мм</div>';
        if (heightValue > 0) resultText += '<div style="font-size:0.95em; color:#666;">Высота: ' + heightValue + ' мм</div>';
        if (height2Value > 0) resultText += '<div style="font-size:0.95em; color:#666;">Высота 2: ' + height2Value + ' мм</div>';
        <?php else: ?>
        if (widthValue > 0) resultText += '<div style="font-size:0.95em; color:#666; margin-bottom:5px;">Ширина: ' + widthValue + ' мм</div>';
        <?php endif; ?>
        
        resultText += '<div style="font-size:1.1em; margin-top:8px;">Длина: <strong>' + lengthValue + ' м</strong></div>';
        resultText += '<div style="font-size:1.1em;">Общая длина: <strong>' + totalLength + ' пог. м</strong></div>';
        resultText += '<div style="font-size:1.3em; font-weight:600; color:#2c5cc5; margin-top:8px;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.4em; font-weight:700; color:#2c5cc5; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        rmResult.innerHTML = resultText;
        
        removeHiddenFields('custom_rm_');
        addHiddenField('custom_rm_width', widthValue);
        addHiddenField('custom_rm_length', lengthValue);
        addHiddenField('custom_rm_quantity', quantity);
        addHiddenField('custom_rm_total_length', totalLength);
        addHiddenField('custom_rm_painting_area', paintingArea);
        addHiddenField('custom_rm_multiplier', priceMultiplier);
        addHiddenField('custom_rm_price', totalPrice);
        addHiddenField('custom_rm_grand_total', grandTotal);
        
        <?php if ($show_falsebalk_calculator): ?>
        const shapeLabels = {'g': 'Г-образная', 'p': 'П-образная', 'o': 'О-образная'};
        addHiddenField('custom_rm_shape', selectedShape.value);
        addHiddenField('custom_rm_shape_label', shapeLabels[selectedShape.value]);
        if (heightValue > 0) addHiddenField('custom_rm_height', heightValue);
        if (height2Value > 0) addHiddenField('custom_rm_height2', height2Value);
        <?php endif; ?>
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', paintingArea);
        }
    }
    
    <?php if ($show_falsebalk_calculator): ?>
    rmWidthEl.addEventListener('change', updateRunningMeterCalc);
    <?php else: ?>
    rmWidthEl.addEventListener('input', updateRunningMeterCalc);
    <?php endif; ?>
    
    rmLengthEl.addEventListener('change', updateRunningMeterCalc);
    
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            if (!isAutoUpdate && rmLengthEl && rmLengthEl.value) {
                updateRunningMeterCalc();
            }
        });
    }
    
    document.addEventListener('painting_cost_updated', updateRunningMeterCalc);
});
<?php endif; ?>

// ============================================================================
// КАЛЬКУЛЯТОР КВАДРАТНЫХ МЕТРОВ
// ============================================================================

<?php if($is_square_meter && !$is_running_meter && !$is_multiplier): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultBlock = document.querySelector('form.cart') || document.querySelector('.summary');
    if (!resultBlock) return;
    
    const sqMeterCalc = document.createElement('div');
    sqMeterCalc.id = 'calc-square-meter';
    
    let sqCalcHTML = '<br><h4>Калькулятор стоимости</h4>';
    sqCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';
    
    if (calcSettings && calcSettings.width_min > 0 && calcSettings.width_max > 0) {
        sqCalcHTML += '<label>Ширина (мм): <select id="sq_width" style="background:#fff; margin-left:10px;"><option value="">Выберите...</option>';
        for (let w = calcSettings.width_min; w <= calcSettings.width_max; w += calcSettings.width_step) {
            sqCalcHTML += '<option value="' + w + '">' + w + '</option>';
        }
        sqCalcHTML += '</select></label>';
    } else {
        sqCalcHTML += '<label>Ширина (мм): <input type="number" id="sq_width" min="1" step="100" placeholder="1000" style="width:100px; margin-left:10px; background:#fff"></label>';
    }
    
    if (calcSettings && calcSettings.length_min > 0 && calcSettings.length_max > 0) {
        sqCalcHTML += '<label>Длина (м): <select id="sq_length" style="background:#fff; margin-left:10px;"><option value="">Выберите...</option>';
        const lengthMin = calcSettings.length_min;
        const lengthMax = calcSettings.length_max;
        const lengthStep = calcSettings.length_step;
        const stepsCount = Math.round((lengthMax - lengthMin) / lengthStep) + 1;
        for (let i = 0; i < stepsCount; i++) {
            const value = (lengthMin + (i * lengthStep)).toFixed(2);
            sqCalcHTML += '<option value="' + value + '">' + value + '</option>';
        }
        sqCalcHTML += '</select></label>';
    } else {
        sqCalcHTML += '<label>Длина (м): <input type="number" id="sq_length" min="0.01" step="0.01" placeholder="2.0" style="width:100px; margin-left:10px; background:#fff"></label>';
    }
    
    sqCalcHTML += '<label style="display:none">Количество (шт): <span id="sq_quantity_display" style="margin-left:10px; font-weight:600;">1</span></label>';
    sqCalcHTML += '</div>';
    sqCalcHTML += '<div id="calc_sq_result" style="margin-top:10px;"></div>';
    
    sqMeterCalc.innerHTML = sqCalcHTML;
    resultBlock.appendChild(sqMeterCalc);
    
    if (paintingBlock) {
        sqMeterCalc.appendChild(paintingBlock);
    }
    
    const sqWidthEl = document.getElementById('sq_width');
    const sqLengthEl = document.getElementById('sq_length');
    const sqResult = document.getElementById('calc_sq_result');
    const sqQuantityDisplay = document.getElementById('sq_quantity_display');
    const basePriceSq = <?php echo floatval($product->get_price()); ?>;
    
    function updateSquareMeterCalc() {
        const widthValue = parseFloat(sqWidthEl.value);
        const lengthValue = parseFloat(sqLengthEl.value);
        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        sqQuantityDisplay.textContent = quantity;
        
        if (!widthValue || !lengthValue) {
            sqResult.innerHTML = '';
            removeHiddenFields('custom_sq_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const area = (widthValue / 1000) * lengthValue;
        const totalArea = area * quantity;
        const totalPrice = basePriceSq * area * priceMultiplier;
        
        updatePaintingServiceCost(totalArea);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        let resultText = '<div style="padding:15px; background:#f0f4ff; border-radius:8px; margin-top:10px;">';
        resultText += '<div style="font-size:1.1em; margin-bottom:5px;">Площадь: <strong>' + area.toFixed(3) + ' м²</strong></div>';
        resultText += '<div style="font-size:1.3em; font-weight:600; color:#2c5cc5;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.4em; font-weight:700; color:#2c5cc5; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        sqResult.innerHTML = resultText;
        
        removeHiddenFields('custom_sq_');
        addHiddenField('custom_sq_width', widthValue);
        addHiddenField('custom_sq_length', lengthValue);
        addHiddenField('custom_sq_quantity', quantity);
        addHiddenField('custom_sq_area', area);
        addHiddenField('custom_sq_total_area', totalArea);
        addHiddenField('custom_sq_multiplier', priceMultiplier);
        addHiddenField('custom_sq_price', totalPrice);
        addHiddenField('custom_sq_grand_total', grandTotal);
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', totalArea);
        }
    }
    
    sqWidthEl.addEventListener('change', updateSquareMeterCalc);
    sqLengthEl.addEventListener('change', updateSquareMeterCalc);
    sqWidthEl.addEventListener('input', updateSquareMeterCalc);
    sqLengthEl.addEventListener('input', updateSquareMeterCalc);
    document.addEventListener('painting_cost_updated', updateSquareMeterCalc);
});
<?php endif; ?>

// ============================================================================
// КАЛЬКУЛЯТОР РЕЕЧНЫХ ПЕРЕГОРОДОК
// ============================================================================

<?php if($is_partition_slat): ?>
document.addEventListener('DOMContentLoaded', function() {
    const resultBlock = document.querySelector('form.cart') || document.querySelector('.summary');
    
    const partitionCalc = document.createElement('div');
    partitionCalc.id = 'calc-partition-slat';
    
    let partCalcHTML = '<br><h4>Калькулятор стоимости</h4>';
    partCalcHTML += '<div style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';
    
    partCalcHTML += '<label>Ширина (мм): <select id="part_width" style="background:#fff; margin-left:10px;"><option value="">Выберите...</option>';
    for (let w = 30; w <= 150; w += 10) {
        partCalcHTML += '<option value="' + w + '">' + w + '</option>';
    }
    partCalcHTML += '</select></label>';
    
    partCalcHTML += '<div style="display:flex; flex-direction:column;"><span style="font-size:0.9em; color:#666;">Длина:</span><strong style="font-size:1.1em;">3м</strong></div>';
    partCalcHTML += '<div style="display:flex; flex-direction:column;"><span style="font-size:0.9em; color:#666;">Толщина:</span><strong style="font-size:1.1em;">40мм</strong></div>';
    partCalcHTML += '</div>';
    partCalcHTML += '<div id="calc_part_result" style="margin-top:10px; font-size:1.3em;"></div>';
    
    partitionCalc.innerHTML = partCalcHTML;
    
    if (resultBlock) {
        resultBlock.appendChild(partitionCalc);
    } else {
        const form = document.querySelector('form.cart');
        if (form) {
            form.insertAdjacentElement('afterend', partitionCalc);
        }
    }
    
    if (paintingBlock) {
        partitionCalc.appendChild(paintingBlock);
    }
    
    const partWidthEl = document.getElementById('part_width');
    const partQuantityDisplay = document.getElementById('part_quantity_display');
    const partResult = document.getElementById('calc_part_result');
    const basePricePart = <?php echo floatval($product->get_price()); ?>;
    
    const FIXED_LENGTH = 3;
    const FIXED_THICKNESS = 40;
    
    window.updatePartitionSlatCalc = function() {
        if (!partWidthEl) return;
        
        const widthValue = parseFloat(partWidthEl.value);
        const quantity = (quantityInput && !isNaN(parseInt(quantityInput.value))) ? parseInt(quantityInput.value) : 1;
        
        if (!widthValue || widthValue <= 0) {
            partResult.innerHTML = '';
            removeHiddenFields('custom_part_');
            updatePaintingServiceCost(0);
            return;
        }
        
        const volume_m3 = (widthValue / 1000) * (FIXED_LENGTH) * (FIXED_THICKNESS / 1000);
        const totalVolume = volume_m3 * quantity;
        const paintingArea = ((widthValue / 1000) + (FIXED_THICKNESS / 1000)) * 2 * FIXED_LENGTH * quantity;
        const totalPrice = basePricePart * volume_m3 * priceMultiplier;
        
        updatePaintingServiceCost(paintingArea);
        
        const paintingService = getPaintingServiceData();
        const grandTotal = paintingService ? (totalPrice + paintingService.total_cost) : totalPrice;
        
        let resultText = '<div style="padding:15px; background:#f0f4ff; border-radius:8px; margin-top:10px;">';
        resultText += '<div style="font-size:1em; margin-bottom:5px;">Площадь: <strong>' + paintingArea.toFixed(2) + ' м<sup>2</sup></strong></div>';
        resultText += '<div style="font-size:1.2em; font-weight:600;">Стоимость: ' + totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        
        if (paintingService) {
            resultText += '<div style="font-size:1.05em; margin-top:8px; padding-top:8px; border-top:1px solid #ddd;">+ ' + paintingService.name_with_color + ': ' + paintingService.total_cost.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
            resultText += '<div style="font-size:1.4em; font-weight:700; color:#2c5cc5; margin-top:8px;">Итого: ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽</div>';
        }
        
        resultText += '</div>';
        partResult.innerHTML = resultText;
        
        removeHiddenFields('custom_part_');
        addHiddenField('custom_part_width', widthValue);
        addHiddenField('custom_part_length', FIXED_LENGTH);
        addHiddenField('custom_part_thickness', FIXED_THICKNESS);
        addHiddenField('custom_part_volume', volume_m3);
        addHiddenField('custom_part_total_volume', totalVolume);
        addHiddenField('custom_part_painting_area', paintingArea);
        addHiddenField('custom_part_multiplier', priceMultiplier);
        addHiddenField('custom_part_price', totalPrice);
        addHiddenField('custom_part_grand_total', grandTotal);
        
        if (paintingService) {
            addHiddenField('painting_service_id', paintingService.id);
            addHiddenField('painting_service_name', paintingService.name_with_color);
            addHiddenField('painting_service_cost', paintingService.total_cost);
            addHiddenField('painting_service_area', paintingArea);
        }
    }
    
    partWidthEl.addEventListener('change', window.updatePartitionSlatCalc);
    
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            if (!isAutoUpdate && partWidthEl && partWidthEl.value) {
                window.updatePartitionSlatCalc();
            }
        });
    }
    
    document.addEventListener('painting_cost_updated', window.updatePartitionSlatCalc);
});
<?php endif; ?>

// ============================================================================
// УНИВЕРСАЛЬНОЕ ОБНОВЛЕНИЕ ЦЕНЫ
// ============================================================================

if (quantityInput) {
    quantityInput.addEventListener('change', function() {
        if (isAutoUpdate) return;
        
        const areaInput = document.getElementById('custom_area');
        const dimWidth = document.getElementById('custom_width');
        const dimLength = document.getElementById('custom_length');
        const multWidthEl = document.getElementById('mult_width');
        const multLengthEl = document.getElementById('mult_length');
        const rmLengthEl = document.getElementById('rm_length');
        const sqWidthEl = document.getElementById('sq_width');
        const sqLengthEl = document.getElementById('sq_length');
        
        if (areaInput && areaInput.value) {
            const areaValue = parseFloat(areaInput.value);
            const packArea = <?php echo $pack_area ?: 0; ?>;
            if (packArea > 0) {
                const packs = Math.ceil(areaValue / packArea);
                isAutoUpdate = true;
                quantityInput.value = packs;
                quantityInput.dispatchEvent(new Event('change'));
                setTimeout(() => { isAutoUpdate = false; }, 100);
            }
        }
        
        if (dimWidth && dimLength && dimWidth.value && dimLength.value) {
            const updateDimCalc = window.updateDimCalc;
            if (typeof updateDimCalc === 'function') updateDimCalc();
        }
        
        if (multWidthEl && multLengthEl && multWidthEl.value && multLengthEl.value) {
            const updateMultiplierCalc = window.updateMultiplierCalc;
            if (typeof updateMultiplierCalc === 'function') updateMultiplierCalc();
        }
        
        if (rmLengthEl && rmLengthEl.value) {
            const updateRunningMeterCalc = window.updateRunningMeterCalc;
            if (typeof updateRunningMeterCalc === 'function') updateRunningMeterCalc();
        }
        
        if (sqWidthEl && sqLengthEl && sqWidthEl.value && sqLengthEl.value) {
            const updateSquareMeterCalc = window.updateSquareMeterCalc;
            if (typeof updateSquareMeterCalc === 'function') updateSquareMeterCalc();
        }
    });
}

</script>
    <?php
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
