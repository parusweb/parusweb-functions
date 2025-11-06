<?php
/**
 * ============================================================================
 * МОДУЛЬ: АВТОЗАПОЛНЕНИЕ ПО ИНН
 * ============================================================================
 * 
 * Интеграция с DaData API для автоматического заполнения реквизитов компании.
 * 
 * @package ParusWeb_Functions
 * @subpackage Account
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// ============================================================================
// AJAX ОБРАБОТЧИК
// ============================================================================

/**
 * AJAX обработчик для получения данных по ИНН
 */
function parusweb_handle_inn_lookup() {
    $inn = sanitize_text_field($_POST['inn'] ?? '');
    
    if (empty($inn)) {
        wp_send_json_error('ИНН не указан');
    }
    
    if (!preg_match('/^\d{10}$|^\d{12}$/', $inn)) {
        wp_send_json_error('Неверный формат ИНН');
    }
    
    $api_key = get_option('dadata_api_key', '903f6c9ee3c3fabd7b9ae599e3735b164f9f71d9');
    $secret_key = get_option('dadata_secret_key', 'ea0595f2a66c84887976a56b8e57ec0aa329a9f7');
    
    $response = wp_remote_post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Token ' . $api_key,
            'X-Secret' => $secret_key
        ],
        'body' => json_encode(['query' => $inn]),
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Ошибка запроса к API: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['suggestions'])) {
        wp_send_json_error('Данные по указанному ИНН не найдены');
    }
    
    $suggestion = $data['suggestions'][0];
    $company_data = $suggestion['data'];
    
    $result = [
        'full_name' => $company_data['name']['full_with_opf'] ?? '',
        'short_name' => $company_data['name']['short_with_opf'] ?? '',
        'legal_address' => $company_data['address']['value'] 
                          ?? $company_data['address']['unrestricted_value'] 
                          ?? $suggestion['unrestricted_value'] 
                          ?? '',
        'kpp' => $company_data['kpp'] ?? '',
        'ogrn' => $company_data['ogrn'] ?? '',
        'director' => ''
    ];
    
    if (!empty($company_data['management']) && !empty($company_data['management']['name'])) {
        $management = $company_data['management'];
        $director_name = $management['name'];
        $director_post = $management['post'] ?? 'Руководитель';
        $result['director'] = $director_post . ' ' . $director_name;
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_inn_lookup', 'parusweb_handle_inn_lookup');
add_action('wp_ajax_nopriv_inn_lookup', 'parusweb_handle_inn_lookup');

// ============================================================================
// JAVASCRIPT ДЛЯ ФОРМЫ РЕГИСТРАЦИИ
// ============================================================================

/**
 * Подключение JavaScript для автозаполнения в регистрации
 */
function parusweb_inn_lookup_registration_js() {
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const regInnField = document.getElementById('reg_billing_inn');
        const regLookupBtn = document.getElementById('reg-inn-lookup-btn');
        
        if (!regLookupBtn || !regInnField) return;
        
        regLookupBtn.addEventListener('click', function() {
            const inn = regInnField.value.trim();
            
            if (!inn) {
                alert('Введите ИНН');
                return;
            }

            regLookupBtn.disabled = true;
            regLookupBtn.textContent = 'Загрузка...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=inn_lookup&inn=' + encodeURIComponent(inn)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const info = data.data;
                    const fullNameField = document.getElementById('reg_billing_full_name');
                    const shortNameField = document.getElementById('reg_billing_short_name');
                    const legalAddressField = document.getElementById('reg_billing_legal_address');
                    const kppField = document.getElementById('reg_billing_kpp');
                    const ogrnField = document.getElementById('reg_billing_ogrn');
                    const directorField = document.getElementById('reg_billing_director');
                    
                    if (info.full_name && fullNameField) fullNameField.value = info.full_name;
                    if (info.short_name && shortNameField) shortNameField.value = info.short_name;
                    if (info.legal_address && legalAddressField) legalAddressField.value = info.legal_address;
                    if (info.kpp && kppField) kppField.value = info.kpp;
                    if (info.ogrn && ogrnField) ogrnField.value = info.ogrn;
                    if (info.director && directorField) directorField.value = info.director;
                    
                    alert('Данные успешно загружены');
                } else {
                    alert('Ошибка: ' + (data.data || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('Ошибка запроса: ' + error.message);
            })
            .finally(() => {
                regLookupBtn.disabled = false;
                regLookupBtn.textContent = 'Заполнить по ИНН';
            });
        });
    });
    </script>
    <?php
}
add_action('woocommerce_register_form_end', 'parusweb_inn_lookup_registration_js', 20);

// ============================================================================
// JAVASCRIPT ДЛЯ CHECKOUT
// ============================================================================

/**
 * Подключение JavaScript для автозаполнения в checkout
 */
function parusweb_inn_lookup_checkout_js() {
    if (!is_checkout()) return;
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const innField = document.getElementById('checkout_billing_inn');
        const lookupBtn = document.getElementById('checkout-inn-lookup-btn');
        
        if (!lookupBtn || !innField) return;
        
        lookupBtn.addEventListener('click', function() {
            const inn = innField.value.trim();
            
            if (!inn) {
                alert('Введите ИНН');
                return;
            }

            lookupBtn.disabled = true;
            lookupBtn.textContent = 'Загрузка...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=inn_lookup&inn=' + encodeURIComponent(inn)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const info = data.data;
                    if (info.full_name) document.getElementById('checkout_billing_full_name').value = info.full_name;
                    if (info.short_name) document.getElementById('checkout_billing_short_name').value = info.short_name;
                    if (info.legal_address) document.getElementById('checkout_billing_legal_address').value = info.legal_address;
                    if (info.kpp) document.getElementById('checkout_billing_kpp').value = info.kpp;
                    if (info.ogrn) document.getElementById('checkout_billing_ogrn').value = info.ogrn;
                    if (info.director) document.getElementById('checkout_billing_director').value = info.director;
                    
                    alert('Данные успешно загружены');
                } else {
                    alert('Ошибка: ' + (data.data || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('Ошибка запроса: ' + error.message);
            })
            .finally(() => {
                lookupBtn.disabled = false;
                lookupBtn.textContent = 'Заполнить по ИНН';
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_inn_lookup_checkout_js');

// ============================================================================
// JAVASCRIPT ДЛЯ ЛИЧНОГО КАБИНЕТА
// ============================================================================

/**
 * Подключение JavaScript для автозаполнения в ЛК
 */
function parusweb_inn_lookup_account_js() {
    if (!is_account_page()) return;
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const innField = document.getElementById('billing_inn');
        const lookupBtn = document.getElementById('account-inn-lookup-btn');
        
        if (!lookupBtn || !innField) return;
        
        lookupBtn.addEventListener('click', function() {
            const inn = innField.value.trim();
            
            if (!inn) {
                alert('Введите ИНН');
                return;
            }

            lookupBtn.disabled = true;
            lookupBtn.textContent = 'Загрузка...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=inn_lookup&inn=' + encodeURIComponent(inn)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const info = data.data;
                    if (info.full_name) document.getElementById('billing_full_name').value = info.full_name;
                    if (info.short_name) document.getElementById('billing_short_name').value = info.short_name;
                    if (info.legal_address) document.getElementById('billing_legal_address').value = info.legal_address;
                    if (info.kpp) document.getElementById('billing_kpp').value = info.kpp;
                    if (info.ogrn) document.getElementById('billing_ogrn').value = info.ogrn;
                    if (info.director) document.getElementById('billing_director').value = info.director;
                    
                    alert('Данные успешно загружены');
                } else {
                    alert('Ошибка: ' + (data.data || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                alert('Ошибка запроса: ' + error.message);
            })
            .finally(() => {
                lookupBtn.disabled = false;
                lookupBtn.textContent = 'Заполнить по ИНН';
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'parusweb_inn_lookup_account_js');

// ============================================================================
// НАСТРОЙКИ API КЛЮЧЕЙ
// ============================================================================

/**
 * Добавление страницы настроек в админку
 */
function parusweb_add_inn_api_settings_page() {
    add_options_page(
        'Настройки ИНН API',
        'ИНН API (DaData)',
        'manage_options',
        'inn-api-settings',
        'parusweb_render_inn_api_settings_page'
    );
}
add_action('admin_menu', 'parusweb_add_inn_api_settings_page');

/**
 * Рендер страницы настроек
 */
function parusweb_render_inn_api_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['submit']) && check_admin_referer('inn_api_settings_save')) {
        update_option('dadata_api_key', sanitize_text_field($_POST['dadata_api_key']));
        update_option('dadata_secret_key', sanitize_text_field($_POST['dadata_secret_key']));
        echo '<div class="notice notice-success"><p>Настройки сохранены!</p></div>';
    }
    
    $api_key = get_option('dadata_api_key', '903f6c9ee3c3fabd7b9ae599e3735b164f9f71d9');
    $secret_key = get_option('dadata_secret_key', 'ea0595f2a66c84887976a56b8e57ec0aa329a9f7');
    ?>
    <div class="wrap">
        <h1>⚙️ Настройки ИНН API (DaData)</h1>
        
        <div class="notice notice-info">
            <p><strong>ℹ️ Информация:</strong></p>
            <ul style="margin: 10px 0;">
                <li>Для работы автозаполнения реквизитов необходим API ключ от <a href="https://dadata.ru" target="_blank">DaData.ru</a></li>
                <li>Регистрация на сайте DaData бесплатная</li>
                <li>Текущие ключи работают для тестирования</li>
            </ul>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('inn_api_settings_save'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="dadata_api_key">API ключ DaData</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="dadata_api_key" 
                               id="dadata_api_key" 
                               value="<?php echo esc_attr($api_key); ?>" 
                               class="regular-text" />
                        <p class="description">Токен авторизации из личного кабинета DaData</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="dadata_secret_key">Secret ключ DaData</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="dadata_secret_key" 
                               id="dadata_secret_key" 
                               value="<?php echo esc_attr($secret_key); ?>" 
                               class="regular-text" />
                        <p class="description">Секретный ключ из личного кабинета DaData</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Сохранить настройки'); ?>
        </form>
        
        <hr>
        
        <h2>📋 Тестирование API</h2>
        <p>Введите ИНН для проверки работы API:</p>
        <input type="text" id="test_inn" placeholder="7707083893" style="width: 300px;" />
        <button type="button" id="test_inn_btn" class="button">Проверить</button>
        <div id="test_result" style="margin-top: 20px;"></div>
        
        <script>
        document.getElementById('test_inn_btn').addEventListener('click', function() {
            const inn = document.getElementById('test_inn').value.trim();
            const resultDiv = document.getElementById('test_result');
            
            if (!inn) {
                alert('Введите ИНН');
                return;
            }
            
            resultDiv.innerHTML = '<p>Загрузка...</p>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=inn_lookup&inn=' + encodeURIComponent(inn)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const info = data.data;
                    resultDiv.innerHTML = `
                        <div style="background: #f0f0f1; padding: 15px; border-radius: 5px;">
                            <h3 style="margin-top: 0;">✓ Данные успешно получены:</h3>
                            <p><strong>Полное наименование:</strong> ${info.full_name || '-'}</p>
                            <p><strong>Краткое наименование:</strong> ${info.short_name || '-'}</p>
                            <p><strong>Юридический адрес:</strong> ${info.legal_address || '-'}</p>
                            <p><strong>КПП:</strong> ${info.kpp || '-'}</p>
                            <p><strong>ОГРН:</strong> ${info.ogrn || '-'}</p>
                            <p><strong>Руководитель:</strong> ${info.director || '-'}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="notice notice-error"><p>❌ Ошибка: ${data.data}</p></div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="notice notice-error"><p>❌ Ошибка запроса: ${error.message}</p></div>`;
            });
        });
        </script>
    </div>
    <?php
}
