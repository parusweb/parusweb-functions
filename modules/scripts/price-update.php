<?php
/**
 * Price Update Module
 * 
 * Автоматическое обновление отображаемой цены товара при изменении калькулятора:
 * - Патчинг функций калькулятора для перехвата результатов
 * - Извлечение цены за единицу из результатов расчёта
 * - Обновление DOM элемента с ценой
 * - Визуальная индикация (рассчитанная цена)
 * - Сброс к базовой цене при очистке полей
 * 
 * @package ParusWeb_Functions
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// БЛОК 1: ОСНОВНОЙ СКРИПТ АВТООБНОВЛЕНИЯ ЦЕНЫ
// ============================================================================

/**
 * Вывод JavaScript для автоматического обновления цены
 */
function parusweb_render_price_update_script() {
    // Только на странице товара
    if (!is_product()) {
        return;
    }
    
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    
    // Проверяем нужен ли калькулятор
    if (function_exists('get_calculator_type')) {
        $calculator_type = get_calculator_type($product_id);
        
        if ($calculator_type === 'none') {
            return;
        }
    }
    
    ?>
    <script>
    (function() {
        'use strict';

        // ====================================================================
        // ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ
        // ====================================================================
        
        let originalBasePrice = null;
        const MAX_PATCH_ATTEMPTS = 20;
        let patchAttempts = 0;
        const DEBUG = <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false'; ?>;

        function log(...args) {
            if (DEBUG) console.log('[PRICE UPDATE]', ...args);
        }

        // ====================================================================
        // ОСНОВНЫЕ ФУНКЦИИ
        // ====================================================================

        /**
         * Обновляет отображаемую цену товара на странице
         */
        window.updateDisplayedProductPrice = function(newPrice, isCalculated = false) {
            let priceEl = document.querySelector('p.price .woocommerce-Price-amount.amount');
            
            if (!priceEl) priceEl = document.querySelector('.woocommerce-Price-amount.amount');
            if (!priceEl) priceEl = document.querySelector('p.price span.amount');
            if (!priceEl) priceEl = document.querySelector('.price .amount');
            
            if (!priceEl) {
                log('⚠️ Элемент цены не найден');
                return false;
            }
            
            const formattedPrice = Math.round(newPrice);
            priceEl.innerHTML = '<bdi>' + formattedPrice + '&nbsp;<span class="woocommerce-Price-currencySymbol">₽</span></bdi>';
            
            const priceContainer = priceEl.closest('p.price');
            if (priceContainer) {
                if (isCalculated) {
                    priceContainer.style.color = '#2c5282';
                    priceContainer.style.fontWeight = 'bold';
                } else {
                    priceContainer.style.color = '';
                    priceContainer.style.fontWeight = '';
                }
            }
            
            log('✓ Цена обновлена:', formattedPrice, '₽', isCalculated ? '(рассчитанная)' : '(базовая)');
            return true;
        };

        /**
         * Извлекает цену за единицу из HTML результата калькулятора
         */
        function extractPricePerItem(resultElement) {
            if (!resultElement) {
                log('⚠️ resultElement не передан');
                return null;
            }
            
            const html = resultElement.innerHTML;
            
            const patterns = [
                // Основной паттерн для всех калькуляторов
                /Цена за 1 шт:\s*<b>([\d,\s.]+)\s*₽<\/b>/i,
                /Цена за 1 шт:\s*<strong>([\d,\s.]+)\s*₽<\/strong>/i,
                
                // Для реечных перегородок
                /Цена за 1 шт:<\/strong>\s*([\d,\s.]+)\s*₽/i,
                
                // Запасные варианты
                /за 1 шт:\s*<b>([\d,\s.]+)\s*₽<\/b>/i,
                /Стоимость материала:\s*<b>([\d,\s.]+)\s*₽<\/b>/i,
                
                // Итого (только если нет других)
                /Итого:\s*<b>([\d,\s.]+)\s*₽<\/b>/i,
                /Итого:\s*<strong[^>]*>([\d,\s.]+)\s*₽<\/strong>/i,
            ];
            
            for (let i = 0; i < patterns.length; i++) {
                const pattern = patterns[i];
                const match = html.match(pattern);
                if (match) {
                    const priceStr = match[1].replace(/[\s,]/g, '');
                    const price = parseFloat(priceStr);
                    if (!isNaN(price) && price > 0) {
                        log('✓ Цена извлечена (паттерн', i + 1, '):', price, '₽');
                        return price;
                    }
                }
            }
            
            log('⚠️ Цена не найдена в результатах калькулятора');
            return null;
        }

        /**
         * Получает оригинальную цену товара со страницы
         */
        function getOriginalPriceFromPage() {
            const priceEl = document.querySelector('p.price .woocommerce-Price-amount.amount');
            if (!priceEl) return null;
            
            const priceText = priceEl.textContent || priceEl.innerText;
            const priceMatch = priceText.match(/(\d+(?:[,\s]\d+)?)/);
            
            if (priceMatch) {
                const priceStr = priceMatch[1].replace(/[\s,]/g, '');
                return parseFloat(priceStr);
            }
            
            return null;
        }

        /**
         * Сброс к базовой цене
         */
        function resetToBasePrice() {
            if (originalBasePrice) {
                log('🔄 Сброс к базовой цене:', originalBasePrice, '₽');
                updateDisplayedProductPrice(originalBasePrice, false);
            }
        }

        // ====================================================================
        // ПАТЧИНГ КАЛЬКУЛЯТОРОВ
        // ====================================================================

        /**
         * Патчинг функции калькулятора
         */
        function patchCalculatorFunction(funcName, resultElementId) {
            if (typeof window[funcName] !== 'function') {
                return false;
            }
            
            const originalFunc = window[funcName];
            
            window[funcName] = function(...args) {
                // Вызываем оригинальную функцию
                const result = originalFunc.apply(this, args);
                
                // Даём время на рендеринг результата
                setTimeout(() => {
                    const resultEl = document.getElementById(resultElementId);
                    if (!resultEl) return;
                    
                    const pricePerItem = extractPricePerItem(resultEl);
                    if (pricePerItem) {
                        updateDisplayedProductPrice(pricePerItem, true);
                    }
                }, 50);
                
                return result;
            };
            
            log('✓ Функция', funcName, 'успешно пропатчена');
            return true;
        }

        /**
         * Попытка пропатчить все калькуляторы
         */
        function tryPatchAllCalculators() {
            const calculators = [
                { func: 'updateAreaCalculator', result: 'area-calc-result' },
                { func: 'updateDimensionCalculator', result: 'dimension-calc-result' },
                { func: 'updateMultiplierCalc', result: 'multiplier-calc-result' },
                { func: 'updateRunningMeterCalc', result: 'running-meter-result' },
                { func: 'updateSquareMeterCalc', result: 'square-meter-result' },
                { func: 'updatePartitionCalc', result: 'partition-calc-result' },
                { func: 'updateFalsebalkCalc', result: 'falsebalk-result' },
                { func: 'updateShtaketnikCalc', result: 'shtaketnik-result' },
            ];
            
            let patchedCount = 0;
            
            for (const calc of calculators) {
                if (patchCalculatorFunction(calc.func, calc.result)) {
                    patchedCount++;
                }
            }
            
            if (patchedCount > 0) {
                log(`✓ Пропатчено калькуляторов: ${patchedCount}`);
                return true;
            }
            
            patchAttempts++;
            
            if (patchAttempts >= MAX_PATCH_ATTEMPTS) {
                log('⚠️ Превышено максимальное количество попыток патчинга');
                return false;
            }
            
            return false;
        }

        // ====================================================================
        // СЛУШАТЕЛИ ПОЛЕЙ КАЛЬКУЛЯТОРА
        // ====================================================================

        /**
         * Установка слушателей на поля калькулятора
         */
        function setupCalculatorFieldListeners() {
            const calcInputIds = [
                'area_input', 'dim_width', 'dim_length',
                'mult_width', 'mult_length', 'fb_width', 'fb_length',
                'sq_width', 'sq_length', 'calc_area_input', 'part_width',
                'rm_length', 'sh_width', 'sh_length'
            ];
            
            calcInputIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', function() {
                        if (!this.value) {
                            const allEmpty = calcInputIds.every(id => {
                                const el = document.getElementById(id);
                                return !el || !el.value;
                            });
                            
                            if (allEmpty) {
                                resetToBasePrice();
                            }
                        }
                    });
                }
            });
            
            log('✓ Слушатели полей установлены');
        }

        /**
         * Дополнительные наблюдатели
         */
        function setupFieldWatchers() {
            document.addEventListener('change', function(e) {
                const calcInputIds = [
                    'area_input', 'dim_width', 'dim_length',
                    'mult_width', 'mult_length', 'fb_width', 'fb_length',
                    'sq_width', 'sq_length', 'calc_area_input', 'part_width'
                ];
                
                if (calcInputIds.includes(e.target.id) && !e.target.value) {
                    const allEmpty = calcInputIds.every(id => {
                        const el = document.getElementById(id);
                        return !el || !el.value;
                    });
                    if (allEmpty) {
                        resetToBasePrice();
                    }
                }
            });
        }

        // ====================================================================
        // ИНИЦИАЛИЗАЦИЯ
        // ====================================================================

        function init() {
            log('=== 🚀 ИНИЦИАЛИЗАЦИЯ АВТООБНОВЛЕНИЯ ЦЕНЫ ===');
            
            // Сохраняем базовую цену
            setTimeout(() => {
                originalBasePrice = getOriginalPriceFromPage();
                if (originalBasePrice) {
                    log('💰 Базовая цена сохранена:', originalBasePrice, '₽');
                } else {
                    log('⚠️ Не удалось получить базовую цену');
                }
            }, 100);

            // Множественные попытки патчинга
            const patchIntervals = [500, 1000, 1500, 2000, 2500, 3000, 4000, 5000];
            
            patchIntervals.forEach((delay, index) => {
                setTimeout(() => {
                    if (!tryPatchAllCalculators()) {
                        log('⏭️ Продолжаем попытки патчинга...');
                    } else {
                        log('✅ Патчинг завершен на попытке #' + (index + 1));
                    }
                }, delay);
            });

            // Устанавливаем слушатели
            setTimeout(() => setupCalculatorFieldListeners(), 2000);
            setupFieldWatchers();
            
            log('✓ Инициализация завершена');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_render_price_update_script', 30);

// ============================================================================
// БЛОК 2: ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Проверка активности модуля обновления цен
 * 
 * @return bool
 */
function parusweb_is_price_update_active() {
    return apply_filters('parusweb_price_update_active', true);
}

/**
 * Отключение автообновления цены для конкретного товара
 * 
 * @param int $product_id ID товара
 */
function parusweb_disable_price_update_for_product($product_id) {
    add_filter('parusweb_price_update_active', function($active) use ($product_id) {
        global $product;
        if ($product && $product->get_id() == $product_id) {
            return false;
        }
        return $active;
    });
}

/**
 * Кастомизация паттернов извлечения цены
 * 
 * @param array $patterns Массив регулярных выражений
 * @return array Модифицированный массив
 */
function parusweb_customize_price_patterns($patterns) {
    // Пример добавления кастомного паттерна
    // $patterns[] = '/Моя цена:\s*<b>([\d,\s.]+)\s*₽<\/b>/i';
    
    return $patterns;
}
add_filter('parusweb_price_extraction_patterns', 'parusweb_customize_price_patterns');

// ============================================================================
// БЛОК 3: НАСТРОЙКИ
// ============================================================================

/**
 * Получение настроек автообновления цены
 * 
 * @return array Массив настроек
 */
function parusweb_get_price_update_settings() {
    return [
        'enabled'           => true,
        'debug'             => defined('WP_DEBUG') && WP_DEBUG,
        'max_attempts'      => 20,
        'retry_intervals'   => [500, 1000, 1500, 2000, 2500, 3000, 4000, 5000],
        'visual_indication' => true, // Визуальное выделение рассчитанной цены
        'color_calculated'  => '#2c5282',
        'reset_on_empty'    => true, // Сброс к базовой при очистке полей
    ];
}

/**
 * Обновление настроек через фильтр
 */
function parusweb_customize_price_update_settings($settings) {
    // Пример кастомизации
    // $settings['max_attempts'] = 30;
    // $settings['color_calculated'] = '#00ff00';
    
    return $settings;
}
add_filter('parusweb_price_update_settings', 'parusweb_customize_price_update_settings');
