<?php
/**
 * Шаблон страницы управления модулями
 * 
 * @var array $enabled_modules
 * @var array $groups
 * @var array $group_names
 * @var ParusWeb_Functions $this
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap parusweb-modules-page">
    <h1>⚙️ ParusWeb Functions - Управление модулями</h1>
    
    <div class="notice notice-info">
        <p><strong>ℹ️ Информация:</strong></p>
        <ul style="margin: 10px 0;">
            <li>🔧 <strong>Критические модули</strong> отмечены значком и не могут быть отключены</li>
            <li>🔗 При отключении модуля автоматически отключаются зависимые от него модули</li>
            <li>📁 Модули сгруппированы по функциональному назначению</li>
            <li>🔄 После сохранения изменений обновите страницу для применения</li>
        </ul>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('parusweb_modules_save'); ?>
        
        <?php foreach ($groups as $group): ?>
            <?php
            $group_modules = array_filter($this->available_modules, function($module) use ($group) {
                return $module['group'] === $group;
            });
            
            if (empty($group_modules)) continue;
            ?>
            
            <div class="card module-group">
                <h2><?php echo esc_html($group_names[$group] ?? $group); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="40">Вкл.</th>
                            <th width="30%">Модуль</th>
                            <th width="40%">Описание</th>
                            <th width="20%">Зависимости</th>
                            <th width="10%">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group_modules as $module_id => $module): ?>
                            <?php
                            $is_enabled = in_array($module_id, $enabled_modules);
                            $is_critical = !empty($module['critical']);
                            $deps_met = $this->check_dependencies($module_id);
                            $is_loaded = in_array($module_id, $this->active_modules);
                            ?>
                            <tr data-module="<?php echo esc_attr($module_id); ?>"
                                <?php if ($is_critical) echo 'style="background:#fff3cd;"'; ?>>
                                <td>
                                    <input type="checkbox" 
                                           name="parusweb_modules[]" 
                                           value="<?php echo esc_attr($module_id); ?>"
                                           <?php checked($is_enabled); ?>
                                           <?php disabled($is_critical); ?>
                                           class="module-checkbox">
                                    <?php if ($is_critical): ?>
                                        <input type="hidden" name="parusweb_modules[]" value="<?php echo esc_attr($module_id); ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($module['name']); ?></strong>
                                    <br><code style="font-size:11px;color:#666;"><?php echo esc_html($module['file']); ?></code>
                                    <?php if ($module['admin_only']): ?>
                                        <span class="dashicons dashicons-admin-tools" title="Только для админки" style="font-size:14px;"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($module['description']); ?></td>
                                <td>
                                    <?php if (!empty($module['dependencies'])): ?>
                                        <?php foreach ($module['dependencies'] as $dep): ?>
                                            <?php
                                            $dep_name = isset($this->available_modules[$dep]) 
                                                ? $this->available_modules[$dep]['name'] 
                                                : $dep;
                                            ?>
                                            <span class="dependency-badge">
                                                <?php echo esc_html($dep_name); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="no-deps">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_loaded): ?>
                                        <span class="status-loaded">✓ Загружен</span>
                                    <?php elseif ($is_enabled && !$deps_met): ?>
                                        <span class="status-error">⚠ Нет зависимостей</span>
                                    <?php elseif ($is_enabled): ?>
                                        <span class="status-pending">○ Ожидает</span>
                                    <?php else: ?>
                                        <span class="status-disabled">− Отключен</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php endforeach; ?>
        
        <p class="submit">
            <input type="submit" name="parusweb_save_modules" class="button button-primary button-large" value="💾 Сохранить изменения">
        </p>
    </form>
    
    <div class="card parusweb-info">
        <h3>📊 Текущий статус системы</h3>
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;">
            <div>
                <p><strong>Всего модулей:</strong> <?php echo count($this->available_modules); ?></p>
                <p><strong>Включено:</strong> <?php echo count($enabled_modules); ?></p>
                <p><strong>Загружено:</strong> <?php echo count($this->active_modules); ?></p>
            </div>
            <div>
                <p><strong>Критических:</strong> <?php 
                    echo count(array_filter($this->available_modules, function($m) { 
                        return !empty($m['critical']); 
                    })); 
                ?></p>
                <p><strong>Только админка:</strong> <?php 
                    echo count(array_filter($this->available_modules, function($m) { 
                        return !empty($m['admin_only']); 
                    })); 
                ?></p>
            </div>
            <div>
                <p><strong>Версия плагина:</strong> <?php echo PARUSWEB_VERSION; ?></p>
                <p><strong>PHP:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.parusweb-modules-page .card {
    padding: 0;
    margin: 20px 0;
}
.parusweb-modules-page .card h2,
.parusweb-modules-page .card h3 {
    margin: 0;
    padding: 15px 20px;
    background: #f0f0f1;
    border-bottom: 1px solid #c3c4c7;
}
.parusweb-modules-page .card table {
    margin: 0;
}
.parusweb-modules-page .parusweb-info {
    padding: 20px;
}
.dependency-badge {
    display: inline-block;
    background: #e0e0e0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin: 2px;
}
.no-deps {
    color: #999;
}
.status-loaded { color: #46b450; font-weight: 600; }
.status-pending { color: #f0b849; font-weight: 600; }
.status-error { color: #dc3232; font-weight: 600; }
.status-disabled { color: #999; }
</style>
