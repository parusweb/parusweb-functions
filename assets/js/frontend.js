/**
 * ============================================================================
 * PARUSWEB FUNCTIONS - FRONTEND JAVASCRIPT
 * ============================================================================
 * 
 * JavaScript для фронтенда сайта
 * 
 * @package ParusWeb_Functions
 * @version 2.0.0
 */

(function($) {
    'use strict';

    // ========================================================================
    // ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ
    // ========================================================================

    const ajaxUrl = paruswebData?.ajax_url || '/wp-admin/admin-ajax.php';
    const currencySymbol = paruswebData?.currency_symbol || '₽';
    const decimalSeparator = paruswebData?.decimal_separator || ',';
    const decimals = paruswebData?.decimals || 2;

    // ========================================================================
    // УТИЛИТЫ
    // ========================================================================

    /**
     * Форматирование цены
     */
    function formatPrice(price) {
        price = parseFloat(price) || 0;
        const formatted = price.toFixed(decimals).replace('.', decimalSeparator);
        return formatted + ' ' + currencySymbol;
    }

    /**
     * Парсинг числа
     */
    function parseNumber(value) {
        if (typeof value === 'number') return value;
        value = String(value).replace(/\s/g, '').replace(',', '.');
        return parseFloat(value) || 0;
    }

    /**
     * Показать загрузку
     */
    function showLoading($element) {
        $element.addClass('parusweb-loading');
        $element.append('<span class="parusweb-spinner"></span>');
    }

    /**
     * Скрыть загрузку
     */
    function hideLoading($element) {
        $element.removeClass('parusweb-loading');
        $element.find('.parusweb-spinner').remove();
    }

    /**
     * Показать сообщение
     */
    function showMessage(message, type) {
        type = type || 'success';
        const className = 'parusweb-' + type + '-message';
        const $message = $('<div class="' + className + '">' + message + '</div>');
        
        $('.calculator-container, .woocommerce').first().prepend($message);
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // ========================================================================
    // ПЕРЕКЛЮЧЕНИЕ ПОЛЕЙ ЮРИДИЧЕСКОГО ЛИЦА
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Регистрация
         */
        const $regSelect = $('#reg_client_type');
        const $regJurFields = $('#reg-jur-fields');
        
        if ($regSelect.length && $regJurFields.length) {
            function toggleRegFields() {
                if ($regSelect.val() === 'jur') {
                    $regJurFields.slideDown();
                } else {
                    $regJurFields.slideUp();
                }
            }
            
            $regSelect.on('change', toggleRegFields);
            toggleRegFields();
        }
        
        /**
         * Checkout
         */
        const $checkoutSelect = $('#checkout_client_type');
        const $checkoutJurFields = $('#checkout-jur-fields');
        
        if ($checkoutSelect.length && $checkoutJurFields.length) {
            function toggleCheckoutFields() {
                if ($checkoutSelect.val() === 'jur') {
                    $checkoutJurFields.slideDown();
                } else {
                    $checkoutJurFields.slideUp();
                }
            }
            
            $checkoutSelect.on('change', toggleCheckoutFields);
            toggleCheckoutFields();
        }
        
        /**
         * Личный кабинет
         */
        const $accountSelect = $('#client_type');
        const $accountJurFields = $('#account-jur-fields');
        
        if ($accountSelect.length && $accountJurFields.length) {
            function toggleAccountFields() {
                if ($accountSelect.val() === 'jur') {
                    $accountJurFields.slideDown();
                } else {
                    $accountJurFields.slideUp();
                }
            }
            
            $accountSelect.on('change', toggleAccountFields);
            toggleAccountFields();
        }
        
    });

    // ========================================================================
    // АВТОЗАПОЛНЕНИЕ ПО ИНН
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Обработчик заполнения по ИНН
         */
        function handleInnLookup(btnSelector, innSelector, fieldsMapping) {
            $(document).on('click', btnSelector, function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const $inn = $(innSelector);
                const inn = $inn.val().trim();
                
                if (!inn) {
                    alert('Введите ИНН');
                    return;
                }
                
                if (!/^\d{10}$|^\d{12}$/.test(inn)) {
                    alert('ИНН должен содержать 10 или 12 цифр');
                    return;
                }
                
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('Загрузка...');
                
                $.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'inn_lookup',
                        inn: inn
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            
                            $.each(fieldsMapping, function(dataKey, selector) {
                                if (data[dataKey]) {
                                    $(selector).val(data[dataKey]).trigger('change');
                                }
                            });
                            
                            showMessage('Данные успешно загружены', 'success');
                        } else {
                            showMessage('Ошибка: ' + (response.data || 'Неизвестная ошибка'), 'error');
                        }
                    },
                    error: function() {
                        showMessage('Ошибка соединения с сервером', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        }
        
        // Регистрация
        handleInnLookup('#reg-inn-lookup-btn', '#reg_billing_inn', {
            'full_name': '#reg_billing_full_name',
            'short_name': '#reg_billing_short_name',
            'legal_address': '#reg_billing_legal_address',
            'kpp': '#reg_billing_kpp',
            'ogrn': '#reg_billing_ogrn',
            'director': '#reg_billing_director'
        });
        
        // Checkout
        handleInnLookup('#checkout-inn-lookup-btn', '#checkout_billing_inn', {
            'full_name': '#checkout_billing_full_name',
            'short_name': '#checkout_billing_short_name',
            'legal_address': '#checkout_billing_legal_address',
            'kpp': '#checkout_billing_kpp',
            'ogrn': '#checkout_billing_ogrn',
            'director': '#checkout_billing_director'
        });
        
        // Личный кабинет
        handleInnLookup('#account-inn-lookup-btn', '#billing_inn', {
            'full_name': '#billing_full_name',
            'short_name': '#billing_short_name',
            'legal_address': '#billing_legal_address',
            'kpp': '#billing_kpp',
            'ogrn': '#billing_ogrn',
            'director': '#billing_director'
        });
        
    });

    // ========================================================================
    // УСЛУГИ ПОКРАСКИ
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Выбор цвета покраски
         */
        $(document).on('click', '.color-option', function() {
            const $option = $(this);
            const $container = $option.closest('.painting-colors');
            
            $container.find('.color-option').removeClass('selected');
            $option.addClass('selected');
            
            const colorName = $option.find('.color-name').text();
            const $input = $container.siblings('input[name^="painting_color_"]');
            if ($input.length) {
                $input.val(colorName);
            }
        });
        
        /**
         * Включение/выключение услуги покраски
         */
        $(document).on('change', 'input[name^="painting_service_"]', function() {
            const $checkbox = $(this);
            const serviceId = $checkbox.attr('name').replace('painting_service_', '');
            const $colors = $('#painting_colors_' + serviceId);
            
            if ($checkbox.is(':checked')) {
                $colors.slideDown();
            } else {
                $colors.slideUp();
                $colors.find('.color-option').removeClass('selected');
            }
        });
        
    });

    // ========================================================================
    // КОРЗИНА
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Обновление корзины при изменении количества
         */
        $(document).on('change', 'input.qty', function() {
            $('[name="update_cart"]').prop('disabled', false);
        });
        
        /**
         * Автообновление корзины
         */
        let updateCartTimer;
        $(document).on('input', 'input.qty', function() {
            clearTimeout(updateCartTimer);
            updateCartTimer = setTimeout(function() {
                $('[name="update_cart"]').trigger('click');
            }, 1000);
        });
        
    });

    // ========================================================================
    // ЛИЧНЫЙ КАБИНЕТ
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Анимация плиток
         */
        $('.lk-tile').hover(
            function() {
                $(this).addClass('animated');
            },
            function() {
                $(this).removeClass('animated');
            }
        );
        
        /**
         * Валидация формы редактирования аккаунта
         */
        $('form.woocommerce-EditAccountForm').on('submit', function(e) {
            const $form = $(this);
            const clientType = $('#client_type').val();
            
            if (clientType === 'jur') {
                const inn = $('#billing_inn').val().trim();
                
                if (!inn) {
                    e.preventDefault();
                    alert('Для юридических лиц обязательно указание ИНН');
                    $('#billing_inn').focus();
                    return false;
                }
                
                if (!/^\d{10}$|^\d{12}$/.test(inn)) {
                    e.preventDefault();
                    alert('ИНН должен содержать 10 или 12 цифр');
                    $('#billing_inn').focus();
                    return false;
                }
            }
        });
        
    });

    // ========================================================================
    // ОБЩИЕ ФУНКЦИИ
    // ========================================================================

    /**
     * Плавная прокрутка к якорям
     */
    $(document).on('click', 'a[href^="#"]', function(e) {
        const target = $(this).attr('href');
        if (target === '#' || target === '') return;
        
        const $target = $(target);
        if ($target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $target.offset().top - 100
            }, 500);
        }
    });

    /**
     * Tooltip
     */
    $(document).on('mouseenter', '[data-tooltip]', function() {
        const text = $(this).data('tooltip');
        const $tooltip = $('<div class="parusweb-tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        const $this = $(this);
        const offset = $this.offset();
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 10,
            left: offset.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
        });
    });

    $(document).on('mouseleave', '[data-tooltip]', function() {
        $('.parusweb-tooltip').remove();
    });

    /**
     * Подтверждение действий
     */
    $(document).on('click', '[data-confirm]', function(e) {
        const message = $(this).data('confirm');
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });

    /**
     * Маски для полей
     */
    if ($.fn.mask) {
        $('input[name*="inn"]').mask('000000000099');
        $('input[name*="kpp"]').mask('000000000');
        $('input[name*="ogrn"]').mask('000000000000099');
    }

    // ========================================================================
    // ЭКСПОРТ ФУНКЦИЙ
    // ========================================================================

    window.parusweb = {
        formatPrice: formatPrice,
        parseNumber: parseNumber,
        showLoading: showLoading,
        hideLoading: hideLoading,
        showMessage: showMessage,
        ajaxUrl: ajaxUrl
    };

})(jQuery);

/**
 * Инициализация после загрузки страницы
 */
jQuery(document).ready(function($) {
    console.log('ParusWeb Functions loaded');
});
