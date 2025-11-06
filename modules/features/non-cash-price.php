<?php
/**
 * Non-Cash Price Module
 * 
 * Функционал вывода цены с наценкой 10% для безналичного расчёта.
 * Отображает дополнительный блок с ценой для юридических лиц
 * и других клиентов, оплачивающих по безналу.
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ВЫВОД ЦЕНЫ ПО БЕЗНАЛУ НА СТРАНИЦЕ ТОВАРА
// ============================================================================

/**
 * Добавление блока с ценой по безналичному расчёту
 * Увеличивает цену на 10% и выводит в отдельном блоке
 */
function parusweb_render_non_cash_price() {
    // Только на странице отдельного товара
    if (!is_product()) {
        return;
    }
    
    global $product;
    
    if (!$product) {
        return;
    }
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Ищем блок с ценой
        var priceElement = $('p.price').first();
        
        if (priceElement.length === 0) {
            return;
        }
        
        // Клонируем элемент цены для модификации
        var priceClone = priceElement.clone();
        
        // Находим все элементы с ценами (учитываем разные структуры WooCommerce)
        var amounts = priceClone.find('.woocommerce-Price-amount, .amount, bdi');
        
        // Обрабатываем каждый элемент с ценой
        amounts.each(function() {
            var $this = $(this);
            var originalText = $this.text();
            
            // Извлекаем число (убираем все кроме цифр, пробелов, запятых)
            var priceStr = originalText.replace(/[^\d\s,]/g, '');
            priceStr = priceStr.replace(/\s/g, '').replace(',', '.');
            
            var price = parseFloat(priceStr);
            
            if (!isNaN(price) && price > 0) {
                // Увеличиваем на 10%
                var newPrice = price * 1.1;
                
                // Форматируем новую цену (округляем до целого)
                var newPriceFormatted = Math.round(newPrice)
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                
                // Заменяем старую цену на новую в тексте элемента
                var newText = originalText.replace(/[\d\s,]+/, newPriceFormatted);
                
                $this.text(newText);
            }
        });
        
        // Получаем HTML модифицированной цены
        var newPriceHTML = priceClone.html();
        
        // Создаём HTML блока с ценой по безналу
        var html = '<div class="non-cash-price-block" style="margin: 15px 0; padding: 15px; background: #e5e5e5; border-radius: 4px; width:100%">';
        html += '<p style="color: #333; font-size: 15px; display: block; margin-bottom: 10px;">Цена по безналичному расчету (+10%):</p>';
        html += '<div class="non-cash-price-content" style="font-size: 15px; color: #0073aa; font-weight: 600; line-height: 1.3;">';
        html += newPriceHTML;
        html += '</div>';
        html += '</div>';
        
        // Вставляем блок после основной цены
        priceElement.after(html);
        
        // Добавляем дополнительные стили
        $('<style>')
            .text(`
                .non-cash-price-block .woocommerce-Price-amount {
                    color: #0073aa !important;
                }
                @media (max-width: 768px) {
                    .non-cash-price-block {
                        padding: 12px;
                    }
                    .non-cash-price-block p {
                        font-size: 14px !important;
                    }
                    .non-cash-price-content {
                        font-size: 16px !important;
                    }
                }
            `)
            .appendTo('head');
    });
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_render_non_cash_price', 999);

// ============================================================================
// БЛОК 2: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Расчёт цены с наценкой для безнала
 * 
 * @param float $price Базовая цена
 * @param float $markup Процент наценки (по умолчанию 10%)
 * @return float Цена с наценкой
 */
function parusweb_calculate_non_cash_price($price, $markup = 10) {
    return $price * (1 + ($markup / 100));
}

/**
 * Получение процента наценки для безнала
 * Можно расширить для разных категорий товаров
 * 
 * @param int|null $product_id ID товара (опционально)
 * @return float Процент наценки
 */
function parusweb_get_non_cash_markup($product_id = null) {
    // Базовая наценка 10%
    $markup = 10;
    
    // Можно добавить логику для разных категорий
    // Например:
    // if ($product_id && has_term([81, 82], 'product_cat', $product_id)) {
    //     $markup = 15; // для ЛКМ 15%
    // }
    
    /**
     * Фильтр для изменения процента наценки
     * 
     * @param float $markup Процент наценки
     * @param int|null $product_id ID товара
     */
    return apply_filters('parusweb_non_cash_markup', $markup, $product_id);
}

/**
 * Форматирование цены для безнала (с округлением)
 * 
 * @param float $price Цена
 * @return string Отформатированная цена
 */
function parusweb_format_non_cash_price($price) {
    return wc_price(round($price));
}

// ============================================================================
// БЛОК 3: ДОПОЛНИТЕЛЬНЫЕ ИНТЕГРАЦИИ
// ============================================================================

/**
 * Добавление информации о безнале в письма заказов (опционально)
 * Раскомментируйте если нужно
 */
/*
function parusweb_add_non_cash_info_to_email($order, $sent_to_admin) {
    // Проверяем способ оплаты заказа
    $payment_method = $order->get_payment_method();
    
    // Если оплата банковским переводом или другим безналичным способом
    if (in_array($payment_method, ['bacs', 'cheque'])) {
        echo '<h2>Оплата по безналичному расчёту</h2>';
        echo '<p>К стоимости заказа применена наценка 10% для безналичного расчёта.</p>';
    }
}
add_action('woocommerce_email_after_order_table', 'parusweb_add_non_cash_info_to_email', 10, 2);
*/

/**
 * Вывод информации о безнале на странице корзины (опционально)
 * Раскомментируйте если нужно
 */
/*
function parusweb_cart_non_cash_notice() {
    if (!is_cart()) {
        return;
    }
    
    wc_print_notice(
        'При оплате по безналичному расчёту к стоимости заказа будет применена наценка 10%.',
        'notice'
    );
}
add_action('woocommerce_before_cart', 'parusweb_cart_non_cash_notice');
*/
