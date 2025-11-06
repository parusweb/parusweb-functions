<?php
/**
 * ============================================================================
 * МОДУЛЬ: ФУНКЦИОНАЛ КОРЗИНЫ
 * ============================================================================
 * 
 * Добавление данных калькуляторов в корзину:
 * - Калькулятор площади
 * - Калькулятор размеров
 * - Калькулятор множителя
 * - Калькулятор погонных метров
 * - Калькулятор квадратных метров
 * - Калькулятор реечных перегородок
 * - Обычные покупки без калькулятора
 * - Покупки из карточек товаров
 * 
 * @package ParusWeb_Functions
 * @subpackage Cart
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// БЛОК 1: ДОБАВЛЕНИЕ ДАННЫХ КАЛЬКУЛЯТОРОВ В КОРЗИНУ
// ============================================================================

add_filter('woocommerce_add_cart_item_data', 'parusweb_add_calculator_data_to_cart', 10, 3);

function parusweb_add_calculator_data_to_cart($cart_item_data, $product_id, $variation_id) {
    
    if (!is_in_target_categories($product_id)) {
        return $cart_item_data;
    }
    
    $product = wc_get_product($product_id);
    if (!$product) return $cart_item_data;
    
    $title = $product->get_name();
    $pack_area = extract_area_with_qty($title, $product_id);
    $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
    
    $leaf_parent_id = 190;
    $leaf_children = [191, 127, 94];
    $leaf_ids = array_merge([$leaf_parent_id], $leaf_children);
    $is_leaf_category = has_term($leaf_ids, 'product_cat', $product_id);
    
    $painting_service = parusweb_get_painting_service_from_post();
    
    $scheme_data = parusweb_get_scheme_data_from_post();
    if ($scheme_data) {
        $cart_item_data = array_merge($cart_item_data, $scheme_data);
    }
    
    if (!empty($_POST['custom_area_packs']) && !empty($_POST['custom_area_area_value'])) {
        $cart_item_data['custom_area_calc'] = [
            'packs' => intval($_POST['custom_area_packs']),
            'area' => floatval($_POST['custom_area_area_value']),
            'total_price' => floatval($_POST['custom_area_total_price']),
            'grand_total' => floatval($_POST['custom_area_grand_total'] ?? $_POST['custom_area_total_price']),
            'is_leaf' => $is_leaf_category,
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_width_val']) && !empty($_POST['custom_length_val'])) {
        $cart_item_data['custom_dimensions'] = [
            'width' => intval($_POST['custom_width_val']),
            'length'=> intval($_POST['custom_length_val']),
            'price'=> floatval($_POST['custom_dim_price']),
            'grand_total' => floatval($_POST['custom_dim_grand_total'] ?? $_POST['custom_dim_price']),
            'is_leaf' => $is_leaf_category,
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_mult_width']) && !empty($_POST['custom_mult_length'])) {
        $cart_item_data['custom_multiplier_calc'] = [
            'width' => floatval($_POST['custom_mult_width']),
            'length' => floatval($_POST['custom_mult_length']),
            'quantity' => intval($_POST['custom_mult_quantity'] ?? 1),
            'area_per_item' => floatval($_POST['custom_mult_area_per_item']),
            'total_area' => floatval($_POST['custom_mult_total_area']),
            'multiplier' => floatval($_POST['custom_mult_multiplier']),
            'price' => floatval($_POST['custom_mult_price']),
            'grand_total' => floatval($_POST['custom_mult_grand_total'] ?? $_POST['custom_mult_price']),
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_rm_length'])) {
        $rm_data = [
            'width' => floatval($_POST['custom_rm_width'] ?? 0),
            'length' => floatval($_POST['custom_rm_length']),
            'quantity' => intval($_POST['custom_rm_quantity'] ?? 1),
            'total_length' => floatval($_POST['custom_rm_total_length']),
            'painting_area' => floatval($_POST['custom_rm_painting_area'] ?? 0),
            'multiplier' => floatval($_POST['custom_rm_multiplier'] ?? 1),
            'price' => floatval($_POST['custom_rm_price']),
            'grand_total' => floatval($_POST['custom_rm_grand_total'] ?? $_POST['custom_rm_price']),
            'painting_service' => $painting_service
        ];
        
        if (!empty($_POST['custom_rm_shape'])) {
            $rm_data['shape'] = sanitize_text_field($_POST['custom_rm_shape']);
            $rm_data['shape_label'] = sanitize_text_field($_POST['custom_rm_shape_label'] ?? '');
        }
        
        if (!empty($_POST['custom_rm_height'])) {
            $rm_data['height'] = floatval($_POST['custom_rm_height']);
        }
        
        if (!empty($_POST['custom_rm_height2'])) {
            $rm_data['height2'] = floatval($_POST['custom_rm_height2']);
        }
        
        $cart_item_data['custom_running_meter_calc'] = $rm_data;
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_sq_width']) && !empty($_POST['custom_sq_length'])) {
        $cart_item_data['custom_square_meter_calc'] = [
            'width' => floatval($_POST['custom_sq_width']),
            'length' => floatval($_POST['custom_sq_length']),
            'quantity' => intval($_POST['custom_sq_quantity'] ?? 1),
            'area' => floatval($_POST['custom_sq_area']),
            'total_area' => floatval($_POST['custom_sq_total_area']),
            'multiplier' => floatval($_POST['custom_sq_multiplier'] ?? 1),
            'price' => floatval($_POST['custom_sq_price']),
            'grand_total' => floatval($_POST['custom_sq_grand_total'] ?? $_POST['custom_sq_price']),
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['custom_part_width'])) {
        $cart_item_data['custom_partition_slat_calc'] = [
            'width' => floatval($_POST['custom_part_width']),
            'length' => floatval($_POST['custom_part_length'] ?? 3),
            'thickness' => floatval($_POST['custom_part_thickness'] ?? 40),
            'volume' => floatval($_POST['custom_part_volume']),
            'total_volume' => floatval($_POST['custom_part_total_volume']),
            'painting_area' => floatval($_POST['custom_part_painting_area'] ?? 0),
            'multiplier' => floatval($_POST['custom_part_multiplier'] ?? 1),
            'price' => floatval($_POST['custom_part_price']),
            'grand_total' => floatval($_POST['custom_part_grand_total'] ?? $_POST['custom_part_price']),
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if (!empty($_POST['card_pack_purchase'])) {
        $cart_item_data['card_pack_purchase'] = [
            'area' => $pack_area,
            'price_per_m2' => $base_price_m2,
            'total_price' => $base_price_m2 * $pack_area,
            'is_leaf' => $is_leaf_category,
            'unit_type' => $is_leaf_category ? 'лист' : 'упаковка',
            'painting_service' => $painting_service
        ];
        return $cart_item_data;
    }
    
    if ($pack_area > 0) {
        $cart_item_data['standard_pack_purchase'] = [
            'area' => $pack_area,
            'price_per_m2' => $base_price_m2,
            'total_price' => $base_price_m2 * $pack_area,
            'is_leaf' => $is_leaf_category,
            'unit_type' => $is_leaf_category ? 'лист' : 'упаковка',
            'painting_service' => $painting_service
        ];
    }
    
    return $cart_item_data;
}

// ============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

function parusweb_get_painting_service_from_post() {
    if (empty($_POST['painting_service_id'])) {
        return null;
    }
    
    $painting_service = [
        'id' => intval($_POST['painting_service_id']),
        'name' => sanitize_text_field($_POST['painting_service_name'] ?? ''),
        'price_per_m2' => floatval($_POST['painting_service_price_per_m2'] ?? 0),
        'area' => floatval($_POST['painting_service_area'] ?? 0),
        'total_cost' => floatval($_POST['painting_service_cost'] ?? 0)
    ];
    
    if (!empty($_POST['painting_service_color'])) {
        $painting_service['color'] = sanitize_text_field($_POST['painting_service_color']);
    }
    
    $painting_service['name_with_color'] = $painting_service['name'];
    if (!empty($painting_service['color'])) {
        $painting_service['name_with_color'] .= ' (' . $painting_service['color'] . ')';
    }
    
    return $painting_service;
}

function parusweb_get_scheme_data_from_post() {
    $scheme_data = [];
    
    if (!empty($_POST['pm_selected_scheme_name'])) {
        $scheme_data['pm_selected_scheme_name'] = sanitize_text_field($_POST['pm_selected_scheme_name']);
    }
    
    if (!empty($_POST['pm_selected_scheme_slug'])) {
        $scheme_data['pm_selected_scheme_slug'] = sanitize_text_field($_POST['pm_selected_scheme_slug']);
    }
    
    if (!empty($_POST['pm_selected_color_image'])) {
        $scheme_data['pm_selected_color_image'] = esc_url_raw($_POST['pm_selected_color_image']);
    }
    
    if (!empty($_POST['pm_selected_color_filename'])) {
        $scheme_data['pm_selected_color'] = sanitize_text_field($_POST['pm_selected_color_filename']);
        $scheme_data['pm_selected_color_filename'] = sanitize_text_field($_POST['pm_selected_color_filename']);
    }
    
    return !empty($scheme_data) ? $scheme_data : null;
}

// ============================================================================
// БЛОК 2: УСТАНОВКА ПРАВИЛЬНОГО КОЛИЧЕСТВА
// ============================================================================

add_filter('woocommerce_add_to_cart_quantity', 'parusweb_adjust_cart_quantity', 10, 2);

function parusweb_adjust_cart_quantity($quantity, $product_id) {
    if (!is_in_target_categories($product_id)) {
        return $quantity;
    }
    
    if (isset($_POST['custom_area_packs']) && !empty($_POST['custom_area_packs']) && 
        isset($_POST['custom_area_area_value']) && !empty($_POST['custom_area_area_value'])) {
        return intval($_POST['custom_area_packs']);
    }
    
    if (isset($_POST['custom_width_val']) && !empty($_POST['custom_width_val']) && 
        isset($_POST['custom_length_val']) && !empty($_POST['custom_length_val'])) {
        return 1;
    }
    
    return $quantity;
}

// ============================================================================
// БЛОК 3: КОРРЕКТИРОВКА ПОСЛЕ ДОБАВЛЕНИЯ
// ============================================================================

add_action('woocommerce_add_to_cart', 'parusweb_correct_cart_quantity', 10, 6);

function parusweb_correct_cart_quantity($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (!is_in_target_categories($product_id)) {
        return;
    }
    
    if (isset($cart_item_data['custom_area_calc'])) {
        $packs = intval($cart_item_data['custom_area_calc']['packs']);
        if ($packs > 0 && $quantity !== $packs) {
            WC()->cart->set_quantity($cart_item_key, $packs);
        }
    }
}

// ============================================================================
// БЛОК 4: ПЕРЕСЧЕТ ЦЕН В КОРЗИНЕ
// ============================================================================

add_action('woocommerce_before_calculate_totals', 'parusweb_recalculate_cart_prices');

function parusweb_recalculate_cart_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        
        if (isset($cart_item['custom_area_calc'])) {
            $area_calc = $cart_item['custom_area_calc'];
            $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
            $pack_area = $area_calc['area'] / $area_calc['packs'];
            $price_multiplier = get_price_multiplier($product->get_id());
            $total_price = $base_price_m2 * $pack_area * $price_multiplier;
            
            if (isset($area_calc['painting_service']) && $area_calc['painting_service']) {
                $total_price += $area_calc['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['custom_dimensions'])) {
            $dims = $cart_item['custom_dimensions'];
            $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
            $area = ($dims['width'] / 1000) * ($dims['length'] / 1000);
            $price_multiplier = get_price_multiplier($product->get_id());
            $total_price = $base_price_m2 * $area * $price_multiplier;
            
            if (isset($dims['painting_service']) && $dims['painting_service']) {
                $total_price += $dims['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['custom_multiplier_calc'])) {
            $mult_calc = $cart_item['custom_multiplier_calc'];
            $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
            $total_price = $base_price_m2 * $mult_calc['area_per_item'] * $mult_calc['multiplier'];
            
            if (isset($mult_calc['painting_service']) && $mult_calc['painting_service']) {
                $total_price += $mult_calc['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['custom_running_meter_calc'])) {
            $rm_calc = $cart_item['custom_running_meter_calc'];
            $base_price = floatval($product->get_regular_price() ?: $product->get_price());
            $total_price = $base_price * $rm_calc['length'] * $rm_calc['multiplier'];
            
            if (isset($rm_calc['painting_service']) && $rm_calc['painting_service']) {
                $total_price += $rm_calc['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['custom_square_meter_calc'])) {
            $sq_calc = $cart_item['custom_square_meter_calc'];
            $base_price_m2 = floatval($product->get_regular_price() ?: $product->get_price());
            $total_price = $base_price_m2 * $sq_calc['area'] * $sq_calc['multiplier'];
            
            if (isset($sq_calc['painting_service']) && $sq_calc['painting_service']) {
                $total_price += $sq_calc['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['custom_partition_slat_calc'])) {
            $part_calc = $cart_item['custom_partition_slat_calc'];
            $base_price = floatval($product->get_regular_price() ?: $product->get_price());
            $total_price = $base_price * $part_calc['volume'] * $part_calc['multiplier'];
            
            if (isset($part_calc['painting_service']) && $part_calc['painting_service']) {
                $total_price += $part_calc['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['card_pack_purchase'])) {
            $pack_data = $cart_item['card_pack_purchase'];
            $total_price = $pack_data['total_price'];
            
            if (isset($pack_data['painting_service']) && $pack_data['painting_service']) {
                $total_price += $pack_data['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
        
        elseif (isset($cart_item['standard_pack_purchase'])) {
            $pack_data = $cart_item['standard_pack_purchase'];
            $total_price = $pack_data['total_price'];
            
            if (isset($pack_data['painting_service']) && $pack_data['painting_service']) {
                $total_price += $pack_data['painting_service']['total_cost'];
            }
            
            $product->set_price($total_price);
        }
    }
}

// ============================================================================
// БЛОК 5: JAVASCRIPT ДЛЯ КАРТОЧЕК ТОВАРОВ
// ============================================================================

add_action('wp_footer', 'parusweb_card_purchase_script');

function parusweb_card_purchase_script() {
    if (!is_shop() && !is_product_category() && !is_product_tag()) {
        return;
    }
    
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartButtons = document.querySelectorAll('.add_to_cart_button:not(.product_type_variable)');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const productId = this.getAttribute('data-product_id');
                
                if (!productId) return;
                
                const formData = new FormData();
                formData.append('card_pack_purchase', '1');
                formData.append('product_id', productId);
                formData.append('quantity', this.getAttribute('data-quantity') || 1);
                
                const href = this.getAttribute('href');
                if (href && href.includes('add-to-cart=')) {
                    e.preventDefault();
                    
                    fetch(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error adding to cart:', data);
                            return;
                        }
                        
                        jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, button]);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    });
    </script>
    <?php
}

// ============================================================================
// КОНЕЦ ФАЙЛА
// ============================================================================
