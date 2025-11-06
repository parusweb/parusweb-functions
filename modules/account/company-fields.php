<?php
/**
 * ============================================================================
 * МОДУЛЬ: РЕКВИЗИТЫ КОМПАНИИ
 * ============================================================================
 * 
 * Поля юридических лиц и ИП для регистрации, checkout и личного кабинета.
 * 
 * @package ParusWeb_Functions
 * @subpackage Account
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// ФОРМА РЕГИСТРАЦИИ
// ============================================================================

/**
 * Добавление выбора типа клиента в форму регистрации
 */
function parusweb_add_client_type_to_registration() {
    ?>
    <p class="form-row form-row-wide">
        <label for="reg_client_type">Тип клиента <span class="required">*</span></label>
        <select name="client_type" id="reg_client_type" class="input-text" required>
            <option value="">Выберите тип</option>
            <option value="fiz">Физическое лицо</option>
            <option value="jur">Юридическое лицо / ИП</option>
        </select>
    </p>
    <?php
}
add_action('woocommerce_register_form_start', 'parusweb_add_client_type_to_registration');

/**
 * Поля юридического лица в форме регистрации
 */
function parusweb_add_company_fields_to_registration() {
    ?>
    <div id="reg-jur-fields" style="display:none;">
        <p class="form-row form-row-wide">
            <label for="reg_billing_inn">ИНН <span class="required">*</span></label>
            <input type="text" class="input-text" name="billing_inn" id="reg_billing_inn" pattern="[0-9]{10,12}" />
            <button type="button" id="reg-inn-lookup-btn" style="margin-top:10px;">Заполнить по ИНН</button>
        </p>
        <p class="form-row form-row-wide">
            <label for="reg_billing_full_name">Полное наименование</label>
            <input type="text" class="input-text" name="billing_full_name" id="reg_billing_full_name" />
        </p>
        <p class="form-row form-row-wide">
            <label for="reg_billing_short_name">Краткое наименование</label>
            <input type="text" class="input-text" name="billing_short_name" id="reg_billing_short_name" />
        </p>
        <p class="form-row form-row-wide">
            <label for="reg_billing_legal_address">Юридический адрес</label>
            <input type="text" class="input-text" name="billing_legal_address" id="reg_billing_legal_address" />
        </p>
        <p class="form-row form-row-wide">
            <label for="reg_billing_kpp">КПП</label>
            <input type="text" class="input-text" name="billing_kpp" id="reg_billing_kpp" pattern="[0-9]{9}" />
        </p>
        <p class="form-row form-row-wide">
            <label for="reg_billing_ogrn">ОГРН / ОГРНИП</label>
            <input type="text" class="input-text" name="billing_ogrn" id="reg_billing_ogrn" pattern="[0-9]{13,15}" />
        </p>
        <p class="form-row form-row-wide">
            <label for="reg_billing_director">Должность и ФИО руководителя</label>
            <input type="text" class="input-text" name="billing_director" id="reg_billing_director" />
        </p>
    </div>
    <?php
}
add_action('woocommerce_register_form', 'parusweb_add_company_fields_to_registration');

/**
 * JavaScript для формы регистрации
 */
function parusweb_registration_company_fields_js() {
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const regSelect = document.getElementById('reg_client_type');
        const regJurFields = document.getElementById('reg-jur-fields');
        
        if (!regSelect || !regJurFields) return;
        
        function toggleRegFields() {
            regJurFields.style.display = regSelect.value === 'jur' ? 'block' : 'none';
        }
        
        toggleRegFields();
        regSelect.addEventListener('change', toggleRegFields);
    });
    </script>
    <?php
}
add_action('woocommerce_register_form_end', 'parusweb_registration_company_fields_js');

/**
 * Валидация при регистрации
 */
function parusweb_validate_registration_company_fields($errors, $username, $email) {
    if (empty($_POST['client_type'])) {
        $errors->add('client_type_error', 'Пожалуйста, выберите тип клиента.');
    }
    
    if (isset($_POST['client_type']) && $_POST['client_type'] === 'jur') {
        if (empty($_POST['billing_inn'])) {
            $errors->add('billing_inn_error', 'Для юридических лиц обязательно указание ИНН.');
        } else {
            $inn = sanitize_text_field($_POST['billing_inn']);
            if (!preg_match('/^\d{10}$|^\d{12}$/', $inn)) {
                $errors->add('billing_inn_format_error', 'ИНН должен содержать 10 или 12 цифр.');
            }
        }
    }
    
    return $errors;
}
add_filter('woocommerce_registration_errors', 'parusweb_validate_registration_company_fields', 10, 3);

/**
 * Сохранение данных при регистрации
 */
function parusweb_save_registration_company_fields($customer_id) {
    if (isset($_POST['client_type'])) {
        update_user_meta($customer_id, 'client_type', sanitize_text_field($_POST['client_type']));
    }

    $fields = [
        'billing_inn', 
        'billing_full_name', 
        'billing_short_name', 
        'billing_legal_address', 
        'billing_kpp', 
        'billing_ogrn', 
        'billing_director'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field]) && !empty($_POST[$field])) {
            update_user_meta($customer_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('woocommerce_created_customer', 'parusweb_save_registration_company_fields');

// ============================================================================
// ФОРМА CHECKOUT
// ============================================================================

/**
 * Добавление полей юридического лица в checkout
 */
function parusweb_add_company_fields_to_checkout($checkout) {
    $user_id = get_current_user_id();
    $client_type = $user_id ? get_user_meta($user_id, 'client_type', true) : '';
    
    echo '<div class="checkout-client-type-wrapper">';
    
    woocommerce_form_field('checkout_client_type', [
        'type' => 'select',
        'class' => ['form-row-wide'],
        'label' => 'Тип клиента',
        'required' => true,
        'options' => [
            '' => 'Выберите тип',
            'fiz' => 'Физическое лицо',
            'jur' => 'Юридическое лицо / ИП'
        ]
    ], $checkout->get_value('checkout_client_type') ?: $client_type);
    
    echo '<div id="checkout-jur-fields" style="display:none;">';
    
    $jur_fields = [
        'checkout_billing_inn' => [
            'label' => 'ИНН',
            'required' => true,
            'class' => ['form-row-wide', 'inn-field']
        ],
        'checkout_billing_full_name' => [
            'label' => 'Полное наименование',
            'class' => ['form-row-wide']
        ],
        'checkout_billing_short_name' => [
            'label' => 'Краткое наименование',
            'class' => ['form-row-wide']
        ],
        'checkout_billing_legal_address' => [
            'label' => 'Юридический адрес',
            'class' => ['form-row-wide']
        ],
        'checkout_billing_kpp' => [
            'label' => 'КПП',
            'class' => ['form-row-first']
        ],
        'checkout_billing_ogrn' => [
            'label' => 'ОГРН / ОГРНИП',
            'class' => ['form-row-last']
        ],
        'checkout_billing_director' => [
            'label' => 'Должность и ФИО руководителя',
            'class' => ['form-row-wide']
        ]
    ];

    foreach ($jur_fields as $key => $args) {
        $value = '';
        if ($user_id) {
            $meta_key = str_replace('checkout_', '', $key);
            $value = get_user_meta($user_id, $meta_key, true);
        }
        woocommerce_form_field($key, $args, $checkout->get_value($key) ?: $value);
    }
    
    echo '<button type="button" id="checkout-inn-lookup-btn" class="button" style="margin-bottom:20px;">Заполнить по ИНН</button>';
    echo '</div>';
    echo '</div>';
}
add_action('woocommerce_after_checkout_billing_form', 'parusweb_add_company_fields_to_checkout');

/**
 * JavaScript для checkout
 */
function parusweb_checkout_company_fields_js() {
    if (!is_checkout()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('checkout_client_type');
        const jurFields = document.getElementById('checkout-jur-fields');
        
        if (!select || !jurFields) return;
        
        function toggleFields() {
            jurFields.style.display = select.value === 'jur' ? 'block' : 'none';
        }
        
        toggleFields();
        select.addEventListener('change', toggleFields);
    });
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_checkout_company_fields_js');

/**
 * Валидация полей checkout
 */
function parusweb_validate_checkout_company_fields() {
    if (empty($_POST['checkout_client_type'])) {
        wc_add_notice('Пожалуйста, выберите тип клиента.', 'error');
    }
    
    if (isset($_POST['checkout_client_type']) && $_POST['checkout_client_type'] === 'jur') {
        if (empty($_POST['checkout_billing_inn'])) {
            wc_add_notice('Для юридических лиц обязательно указание ИНН.', 'error');
        }
    }
}
add_action('woocommerce_checkout_process', 'parusweb_validate_checkout_company_fields');

/**
 * Сохранение данных checkout
 */
function parusweb_save_checkout_company_fields($order_id) {
    $checkout_fields = [
        'checkout_client_type' => 'client_type',
        'checkout_billing_inn' => 'billing_inn',
        'checkout_billing_full_name' => 'billing_full_name',
        'checkout_billing_short_name' => 'billing_short_name',
        'checkout_billing_legal_address' => 'billing_legal_address',
        'checkout_billing_kpp' => 'billing_kpp',
        'checkout_billing_ogrn' => 'billing_ogrn',
        'checkout_billing_director' => 'billing_director'
    ];

    $user_id = get_current_user_id();
    
    foreach ($checkout_fields as $checkout_field => $meta_key) {
        if (!empty($_POST[$checkout_field])) {
            $value = sanitize_text_field($_POST[$checkout_field]);
            
            update_post_meta($order_id, '_' . $meta_key, $value);
            
            if ($user_id) {
                update_user_meta($user_id, $meta_key, $value);
            }
        }
    }
}
add_action('woocommerce_checkout_update_order_meta', 'parusweb_save_checkout_company_fields');

// ============================================================================
// ОТОБРАЖЕНИЕ В АДМИНКЕ ЗАКАЗА
// ============================================================================

/**
 * Отображение реквизитов в админке заказа
 */
function parusweb_display_company_fields_in_admin($order) {
    $client_type = get_post_meta($order->get_id(), '_client_type', true);
    
    if ($client_type === 'jur') {
        echo '<h3 style="margin-top: 20px; padding: 10px; background: #f0f0f1;">Реквизиты юридического лица</h3>';
        echo '<div style="padding: 10px; background: white; border: 1px solid #c3c4c7;">';
        
        $jur_fields = [
            '_billing_inn' => 'ИНН',
            '_billing_full_name' => 'Полное наименование',
            '_billing_short_name' => 'Краткое наименование',
            '_billing_legal_address' => 'Юридический адрес',
            '_billing_kpp' => 'КПП',
            '_billing_ogrn' => 'ОГРН / ОГРНИП',
            '_billing_director' => 'Руководитель'
        ];
        
        foreach ($jur_fields as $meta_key => $label) {
            $value = get_post_meta($order->get_id(), $meta_key, true);
            if ($value) {
                echo '<p style="margin: 5px 0;"><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</p>';
            }
        }
        
        echo '</div>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'parusweb_display_company_fields_in_admin');

// ============================================================================
// ЛИЧНЫЙ КАБИНЕТ
// ============================================================================

/**
 * Добавление полей в настройки аккаунта
 */
function parusweb_add_company_fields_to_account() {
    $user_id = get_current_user_id();
    $client_type = get_user_meta($user_id, 'client_type', true);
    ?>
    
    <fieldset>
        <legend>Тип клиента</legend>
        <p class="form-row form-row-wide">
            <label for="client_type">Тип клиента</label>
            <select name="client_type" id="client_type" class="input-text">
                <option value="fiz" <?php selected($client_type, 'fiz'); ?>>Физическое лицо</option>
                <option value="jur" <?php selected($client_type, 'jur'); ?>>Юридическое лицо / ИП</option>
            </select>
        </p>
    </fieldset>
    
    <fieldset id="account-jur-fields" style="<?php echo $client_type !== 'jur' ? 'display:none;' : ''; ?>">
        <legend>Реквизиты компании</legend>
        
        <p class="form-row form-row-wide">
            <label for="billing_inn">ИНН</label>
            <input type="text" class="input-text" name="billing_inn" id="billing_inn" 
                   value="<?php echo esc_attr(get_user_meta($user_id, 'billing_inn', true)); ?>" />
            <button type="button" id="account-inn-lookup-btn" class="button" style="margin-top:10px;">Заполнить по ИНН</button>
        </p>
        
        <?php
        $company_fields = [
            'billing_full_name' => 'Полное наименование',
            'billing_short_name' => 'Краткое наименование',
            'billing_legal_address' => 'Юридический адрес',
            'billing_kpp' => 'КПП',
            'billing_ogrn' => 'ОГРН / ОГРНИП',
            'billing_director' => 'Руководитель',
        ];
        
        foreach ($company_fields as $field_key => $field_label) {
            $value = get_user_meta($user_id, $field_key, true);
            ?>
            <p class="form-row form-row-wide">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?></label>
                <input type="text" class="input-text" name="<?php echo esc_attr($field_key); ?>" 
                       id="<?php echo esc_attr($field_key); ?>" value="<?php echo esc_attr($value); ?>" />
            </p>
            <?php
        }
        ?>
    </fieldset>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('client_type');
        const fields = document.getElementById('account-jur-fields');
        
        if (select && fields) {
            select.addEventListener('change', function() {
                fields.style.display = this.value === 'jur' ? 'block' : 'none';
            });
        }
    });
    </script>
    <?php
}
add_action('woocommerce_edit_account_form', 'parusweb_add_company_fields_to_account');

/**
 * Сохранение полей в личном кабинете
 */
function parusweb_save_account_company_fields($user_id) {
    if (isset($_POST['client_type'])) {
        update_user_meta($user_id, 'client_type', sanitize_text_field($_POST['client_type']));
    }
    
    $fields = [
        'billing_inn',
        'billing_full_name',
        'billing_short_name',
        'billing_legal_address',
        'billing_kpp',
        'billing_ogrn',
        'billing_director'
    ];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('woocommerce_save_account_details', 'parusweb_save_account_company_fields');
