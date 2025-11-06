<?php
/**
 * ACF Fields Registration
 * 
 * Registers Advanced Custom Fields for painting services configuration
 * using ACF's PHP registration API.
 * 
 * Three-tier hierarchy:
 * 1. Product level (highest priority)
 * 2. Category level (medium priority)
 * 3. Global level (default fallback)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register ACF field groups for painting services
 */
function parusweb_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Global Settings (Options Page)
    acf_add_local_field_group(array(
        'key' => 'group_painting_global',
        'title' => 'Настройки окраски (глобальные)',
        'fields' => array(
            array(
                'key' => 'field_painting_enabled_global',
                'label' => 'Включить услугу окраски',
                'name' => 'painting_enabled',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
            ),
            array(
                'key' => 'field_painting_price_global',
                'label' => 'Стоимость окраски за м²',
                'name' => 'painting_price',
                'type' => 'number',
                'min' => 0,
                'step' => 0.01,
                'prepend' => '₽',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_enabled_global',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_painting_colors_global',
                'label' => 'Доступные цвета',
                'name' => 'painting_colors',
                'type' => 'repeater',
                'min' => 0,
                'layout' => 'table',
                'button_label' => 'Добавить цвет',
                'sub_fields' => array(
                    array(
                        'key' => 'field_color_name',
                        'label' => 'Название цвета',
                        'name' => 'name',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_color_code',
                        'label' => 'Код цвета (HEX)',
                        'name' => 'code',
                        'type' => 'color_picker',
                        'required' => 1,
                    ),
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_enabled_global',
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
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'parusweb-settings',
                ),
            ),
        ),
    ));

    // Category Settings
    acf_add_local_field_group(array(
        'key' => 'group_painting_category',
        'title' => 'Настройки окраски (категория)',
        'fields' => array(
            array(
                'key' => 'field_painting_override_category',
                'label' => 'Переопределить глобальные настройки',
                'name' => 'painting_override',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
                'instructions' => 'Если включено, эти настройки будут использоваться вместо глобальных',
            ),
            array(
                'key' => 'field_painting_enabled_category',
                'label' => 'Включить услугу окраски',
                'name' => 'painting_enabled',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_override_category',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_painting_price_category',
                'label' => 'Стоимость окраски за м²',
                'name' => 'painting_price',
                'type' => 'number',
                'min' => 0,
                'step' => 0.01,
                'prepend' => '₽',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_override_category',
                            'operator' => '==',
                            'value' => '1',
                        ),
                        array(
                            'field' => 'field_painting_enabled_category',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_painting_colors_category',
                'label' => 'Доступные цвета',
                'name' => 'painting_colors',
                'type' => 'repeater',
                'min' => 0,
                'layout' => 'table',
                'button_label' => 'Добавить цвет',
                'sub_fields' => array(
                    array(
                        'key' => 'field_color_name_cat',
                        'label' => 'Название цвета',
                        'name' => 'name',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_color_code_cat',
                        'label' => 'Код цвета (HEX)',
                        'name' => 'code',
                        'type' => 'color_picker',
                        'required' => 1,
                    ),
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_override_category',
                            'operator' => '==',
                            'value' => '1',
                        ),
                        array(
                            'field' => 'field_painting_enabled_category',
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

    // Product Settings
    acf_add_local_field_group(array(
        'key' => 'group_painting_product',
        'title' => 'Настройки окраски',
        'fields' => array(
            array(
                'key' => 'field_painting_override_product',
                'label' => 'Переопределить настройки категории',
                'name' => 'painting_override',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
                'instructions' => 'Если включено, эти настройки будут использоваться вместо настроек категории',
            ),
            array(
                'key' => 'field_painting_enabled_product',
                'label' => 'Включить услугу окраски',
                'name' => 'painting_enabled',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_override_product',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_painting_price_product',
                'label' => 'Стоимость окраски за м²',
                'name' => 'painting_price',
                'type' => 'number',
                'min' => 0,
                'step' => 0.01,
                'prepend' => '₽',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_override_product',
                            'operator' => '==',
                            'value' => '1',
                        ),
                        array(
                            'field' => 'field_painting_enabled_product',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_painting_colors_product',
                'label' => 'Доступные цвета',
                'name' => 'painting_colors',
                'type' => 'repeater',
                'min' => 0,
                'layout' => 'table',
                'button_label' => 'Добавить цвет',
                'sub_fields' => array(
                    array(
                        'key' => 'field_color_name_prod',
                        'label' => 'Название цвета',
                        'name' => 'name',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_color_code_prod',
                        'label' => 'Код цвета (HEX)',
                        'name' => 'code',
                        'type' => 'color_picker',
                        'required' => 1,
                    ),
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_painting_override_product',
                            'operator' => '==',
                            'value' => '1',
                        ),
                        array(
                            'field' => 'field_painting_enabled_product',
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
add_action('acf/init', 'parusweb_register_acf_fields');