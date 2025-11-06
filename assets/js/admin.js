/**
 * ============================================================================
 * PARUSWEB FUNCTIONS - ADMIN JAVASCRIPT
 * ============================================================================
 * 
 * JavaScript для административной панели WordPress
 * 
 * @package ParusWeb_Functions
 * @version 2.0.0
 */

(function($) {
    'use strict';

    // ========================================================================
    // УПРАВЛЕНИЕ МОДУЛЯМИ
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Обработка зависимостей модулей
         */
        if (typeof paruswebModules !== 'undefined') {
            const dependencies = paruswebModules.dependencies || {};
            const dependents = paruswebModules.dependents || {};
            
            $('.module-checkbox').on('change', function() {
                const moduleId = $(this).val();
                const isChecked = $(this).is(':checked');
                
                if (isChecked) {
                    enableDependencies(moduleId);
                } else {
                    disableDependents(moduleId);
                }
            });
            
            /**
             * Включить зависимости модуля
             */
            function enableDependencies(moduleId) {
                if (dependencies[moduleId]) {
                    dependencies[moduleId].forEach(function(depId) {
                        const $checkbox = $('.module-checkbox[value="' + depId + '"]');
                        if (!$checkbox.prop('disabled')) {
                            $checkbox.prop('checked', true);
                        }
                    });
                }
            }
            
            /**
             * Отключить зависимые модули
             */
            function disableDependents(moduleId) {
                if (dependents[moduleId]) {
                    const deps = dependents[moduleId];
                    if (deps.length > 0) {
                        const depNames = deps.join(', ');
                        if (confirm('Отключение этого модуля также отключит зависимые модули: ' + depNames + '. Продолжить?')) {
                            deps.forEach(function(depId) {
                                $('.module-checkbox[value="' + depId + '"]').prop('checked', false);
                                disableDependents(depId);
                            });
                        } else {
                            $('.module-checkbox[value="' + moduleId + '"]').prop('checked', true);
                        }
                    }
                }
            }
        }
        
        /**
         * Подсветка строк при наведении
         */
        $('.parusweb-modules-page .wp-list-table tr').hover(
            function() {
                $(this).css('background-color', '#f8f8f8');
            },
            function() {
                $(this).css('background-color', '');
            }
        );
        
    });

    // ========================================================================
    // МЕТАПОЛЯ ТОВАРОВ
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Управление полями фальшбалок
         */
        $('[id^="shape_"][id$="_enabled"]').each(function() {
            const shapeKey = $(this).attr('id').replace('shape_', '').replace('_enabled', '');
            const $fields = $('[id^="shape_' + shapeKey + '_"]').not('[id$="_enabled"]');
            
            function toggleFields() {
                if ($(this).is(':checked')) {
                    $fields.closest('p').show();
                } else {
                    $fields.closest('p').hide();
                }
            }
            
            $(this).on('change', toggleFields);
            toggleFields.call(this);
        });
        
        /**
         * Валидация размеров фальшбалок
         */
        $('[id^="shape_"][id$="_width"], [id^="shape_"][id$="_height"]').on('blur', function() {
            const value = parseFloat($(this).val());
            if (value && (value < 0 || value > 10000)) {
                alert('Размер должен быть от 0 до 10000 мм');
                $(this).val('');
            }
        });
        
        /**
         * Управление полями услуг покраски
         */
        $('#_enable_painting_services').on('change', function() {
            const $fields = $('.painting-services-fields');
            if ($(this).is(':checked')) {
                $fields.show();
            } else {
                $fields.hide();
            }
        }).trigger('change');
        
        /**
         * Управление полями множителей
         */
        $('#_has_multiplier').on('change', function() {
            const $fields = $('.multiplier-fields-wrapper');
            if ($(this).is(':checked')) {
                $fields.show();
            } else {
                $fields.hide();
            }
        }).trigger('change');
        
    });

    // ========================================================================
    // НАСТРОЙКИ КАТЕГОРИЙ
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Валидация полей множителя цен
         */
        $('input[name="price_multiplier"]').on('blur', function() {
            const value = parseFloat($(this).val());
            if (value && (value < 0.1 || value > 10)) {
                alert('Множитель должен быть от 0.1 до 10');
                $(this).val('');
            }
        });
        
    });

    // ========================================================================
    // СТРАНИЦА ЗАКАЗА
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Сворачивание/разворачивание блоков данных
         */
        $('.order-calculator-data h4, .order-delivery-info h4').css('cursor', 'pointer').on('click', function() {
            $(this).next().slideToggle();
        });
        
    });

    // ========================================================================
    // ТЕСТИРОВАНИЕ API
    // ========================================================================

    $(document).ready(function() {
        
        /**
         * Тест API ключей DaData
         */
        $('#test_inn_btn').on('click', function() {
            const $btn = $(this);
            const $result = $('#test_result');
            const inn = $('#test_inn').val().trim();
            
            if (!inn) {
                alert('Введите ИНН');
                return;
            }
            
            $btn.prop('disabled', true).text('Проверка...');
            $result.html('<p>Загрузка...</p>');
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'inn_lookup',
                    inn: inn
                },
                success: function(response) {
                    if (response.success) {
                        const info = response.data;
                        let html = '<div style="background: #f0f0f1; padding: 15px; border-radius: 5px;">';
                        html += '<h3 style="margin-top: 0; color: #46b450;">✓ Данные успешно получены:</h3>';
                        html += '<p><strong>Полное наименование:</strong> ' + (info.full_name || '-') + '</p>';
                        html += '<p><strong>Краткое наименование:</strong> ' + (info.short_name || '-') + '</p>';
                        html += '<p><strong>Юридический адрес:</strong> ' + (info.legal_address || '-') + '</p>';
                        html += '<p><strong>КПП:</strong> ' + (info.kpp || '-') + '</p>';
                        html += '<p><strong>ОГРН:</strong> ' + (info.ogrn || '-') + '</p>';
                        html += '<p><strong>Руководитель:</strong> ' + (info.director || '-') + '</p>';
                        html += '</div>';
                        $result.html(html);
                    } else {
                        $result.html('<div class="notice notice-error"><p>❌ Ошибка: ' + (response.data || 'Неизвестная ошибка') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $result.html('<div class="notice notice-error"><p>❌ Ошибка запроса: ' + error + '</p></div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Проверить');
                }
            });
        });
        
        /**
         * Enter для отправки
         */
        $('#test_inn').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#test_inn_btn').click();
            }
        });
        
    });

    // ========================================================================
    // ОБЩИЕ УТИЛИТЫ
    // ========================================================================

    /**
     * Подтверждение удаления
     */
    $(document).on('click', '[data-confirm]', function(e) {
        const message = $(this).data('confirm');
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });

    /**
     * Копирование в буфер обмена
     */
    $(document).on('click', '[data-copy]', function(e) {
        e.preventDefault();
        const text = $(this).data('copy');
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.text('Скопировано!');
        setTimeout(function() {
            $btn.text(originalText);
        }, 2000);
    });

    /**
     * Автосохранение для полей
     */
    $('[data-autosave]').on('change', function() {
        const $field = $(this);
        const fieldName = $field.attr('name');
        const fieldValue = $field.val();
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'parusweb_autosave_field',
                field_name: fieldName,
                field_value: fieldValue,
                nonce: $field.data('nonce')
            },
            success: function(response) {
                if (response.success) {
                    $field.addClass('parusweb-saved');
                    setTimeout(function() {
                        $field.removeClass('parusweb-saved');
                    }, 2000);
                }
            }
        });
    });

    /**
     * Загрузка индикатора
     */
    function showLoading($element) {
        $element.append('<span class="parusweb-spinner"></span>');
    }

    function hideLoading($element) {
        $element.find('.parusweb-spinner').remove();
    }

    /**
     * Уведомления
     */
    window.paruswebNotice = function(message, type) {
        type = type || 'info';
        const noticeClass = 'notice notice-' + type;
        const $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap > h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };

    // Экспорт функций для глобального использования
    window.paruswebAdmin = {
        showLoading: showLoading,
        hideLoading: hideLoading,
        notice: window.paruswebNotice
    };

})(jQuery);
