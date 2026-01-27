<?php
/**
 * Admin-Interface für das Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        add_options_page(
            'OlpoMizer',
            'OlpoMizer',
            'manage_options',
            'olpomizer',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Settings registrieren
     */
    public function register_settings() {
        register_setting('cwo_modules_group', 'cwo_modules');
    }
    
    /**
     * Admin-Assets laden
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_olpomizer' !== $hook) {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            /* Layout Container */
            .olpo-container {
                margin: 20px 0 0 0;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            
            /* Haupttabs oben */
            .olpo-main-tabs {
                display: flex;
                border-bottom: 1px solid #ccd0d4;
                background: #f6f7f7;
                margin: 0;
                padding: 0;
            }
            
            .olpo-main-tab {
                padding: 15px 25px;
                cursor: pointer;
                border: none;
                background: transparent;
                border-bottom: 3px solid transparent;
                font-size: 14px;
                font-weight: 500;
                color: #50575e;
                transition: all 0.2s;
                position: relative;
            }
            
            .olpo-main-tab:hover {
                background: #fff;
                color: #2271b1;
            }
            
            .olpo-main-tab.active {
                background: #fff;
                color: #2271b1;
                border-bottom-color: #2271b1;
            }
            
            .olpo-main-tab .dashicons {
                margin-right: 5px;
                font-size: 18px;
                height: 18px;
                width: 18px;
            }
            
            /* Content Bereich mit Sidebar */
            .olpo-content-wrapper {
                display: flex;
                min-height: 500px;
            }
            
            .olpo-tab-content {
                display: none;
                width: 100%;
            }
            
            .olpo-tab-content.active {
                display: flex;
            }
            
            /* Sidebar Links */
            .olpo-sidebar {
                width: 220px;
                border-right: 1px solid #ccd0d4;
                background: #fafafa;
                padding: 20px 0;
            }
            
            .olpo-sidebar-item {
                padding: 12px 20px;
                cursor: pointer;
                color: #50575e;
                border-left: 3px solid transparent;
                transition: all 0.2s;
                display: block;
                text-decoration: none;
                font-size: 14px;
            }
            
            .olpo-sidebar-item:hover {
                background: #fff;
                color: #2271b1;
                border-left-color: #2271b1;
            }
            
            .olpo-sidebar-item.active {
                background: #fff;
                color: #2271b1;
                border-left-color: #2271b1;
                font-weight: 600;
            }
            
            .olpo-sidebar-item .dashicons {
                margin-right: 8px;
                font-size: 16px;
                height: 16px;
                width: 16px;
                color: #a7aaad;
            }
            
            .olpo-sidebar-item.active .dashicons {
                color: #2271b1;
            }
            
            /* Content Bereich Rechts */
            .olpo-main-content {
                flex: 1;
                padding: 30px;
            }
            
            .olpo-section {
                display: none;
            }
            
            .olpo-section.active {
                display: block;
            }
            
            /* Module Cards */
            .olpo-module-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .olpo-module-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            
            .olpo-module-title {
                font-size: 16px;
                font-weight: 600;
                margin: 0;
            }
            
            .olpo-module-description {
                color: #646970;
                margin-bottom: 15px;
            }
            
            .olpo-module-settings {
                padding: 20px;
                background: #f6f7f7;
                border-radius: 4px;
                margin-top: 15px;
            }
            
            /* Toggle Switch */
            .olpo-toggle {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            
            .olpo-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .olpo-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }
            
            .olpo-toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            .olpo-toggle input:checked + .olpo-toggle-slider {
                background-color: #2271b1;
            }
            
            .olpo-toggle input:checked + .olpo-toggle-slider:before {
                transform: translateX(26px);
            }
            
            /* Section Header */
            .olpo-section-header {
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ccd0d4;
            }
            
            .olpo-section-title {
                font-size: 20px;
                font-weight: 600;
                margin: 0 0 5px 0;
            }
            
            .olpo-section-description {
                color: #646970;
                margin: 0;
            }
        ');
    }
    
    /**
     * Admin-Seite rendern
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Settings speichern
        if (isset($_POST['cwo_save_settings']) && check_admin_referer('cwo_settings_nonce')) {
            $this->save_settings();
        }
        
        $optimizer = Custom_WP_Optimizer::get_instance();
        $modules = $optimizer->get_modules();
        $enabled_modules = get_option('cwo_modules', array());
        
        // Module nach Kategorie organisieren
        $categories = array(
            'email' => array(
                'title' => 'E-Mail',
                'icon' => 'email',
                'modules' => array()
            ),
            'debug' => array(
                'title' => 'Debug',
                'icon' => 'admin-tools',
                'modules' => array(),
                'sections' => array(
                    'settings' => array(
                        'title' => 'Einstellungen',
                        'icon' => 'admin-settings'
                    ),
                    'viewer' => array(
                        'title' => 'Log Viewer',
                        'icon' => 'media-text'
                    )
                )
            ),
            'performance' => array(
                'title' => 'Performance',
                'icon' => 'performance',
                'modules' => array()
            )
        );
        
        // Module zuordnen
        foreach ($modules as $module_id => $module) {
            if ($module_id === 'smtp') {
                $categories['email']['modules'][$module_id] = $module;
            } elseif ($module_id === 'debug') {
                $categories['debug']['modules'][$module_id] = $module;
            } else {
                $categories['performance']['modules'][$module_id] = $module;
            }
        }
        
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-generic" style="font-size: 28px; margin-right: 10px;"></span>
                OlpoMizer
            </h1>
            <p style="color: #646970; margin-top: 5px;">Modulares WordPress Optimierungs-Plugin</p>
            
            <form method="post" action="" id="olpo-settings-form">
                <?php wp_nonce_field('cwo_settings_nonce'); ?>
                
                <div class="olpo-container">
                    <!-- Haupttabs oben -->
                    <div class="olpo-main-tabs">
                        <?php 
                        $first = true;
                        foreach ($categories as $cat_id => $category): 
                            if (empty($category['modules'])) continue;
                        ?>
                            <button type="button" 
                                    class="olpo-main-tab <?php echo $first ? 'active' : ''; ?>" 
                                    data-tab="<?php echo esc_attr($cat_id); ?>">
                                <span class="dashicons dashicons-<?php echo esc_attr($category['icon']); ?>"></span>
                                <?php echo esc_html($category['title']); ?>
                            </button>
                        <?php 
                            $first = false;
                        endforeach; 
                        ?>
                    </div>
                    
                    <!-- Content Bereiche -->
                    <?php 
                    $first = true;
                    foreach ($categories as $cat_id => $category): 
                        if (empty($category['modules'])) continue;
                        $has_sections = !empty($category['sections']);
                    ?>
                        <div class="olpo-tab-content <?php echo $first ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($cat_id); ?>">
                            
                            <?php if ($has_sections): ?>
                                <!-- Mit Sidebar -->
                                <div class="olpo-sidebar">
                                    <?php 
                                    $first_section = true;
                                    foreach ($category['sections'] as $section_id => $section): 
                                    ?>
                                        <a href="#" 
                                           class="olpo-sidebar-item <?php echo $first_section ? 'active' : ''; ?>" 
                                           data-section="<?php echo esc_attr($cat_id . '-' . $section_id); ?>">
                                            <span class="dashicons dashicons-<?php echo esc_attr($section['icon']); ?>"></span>
                                            <?php echo esc_html($section['title']); ?>
                                        </a>
                                    <?php 
                                        $first_section = false;
                                    endforeach; 
                                    ?>
                                </div>
                                
                                <div class="olpo-main-content">
                                    <?php 
                                    $first_section = true;
                                    foreach ($category['sections'] as $section_id => $section): 
                                    ?>
                                        <div class="olpo-section <?php echo $first_section ? 'active' : ''; ?>" 
                                             data-section="<?php echo esc_attr($cat_id . '-' . $section_id); ?>">
                                            
                                            <?php if ($section_id === 'settings'): ?>
                                                <!-- Debug Einstellungen -->
                                                <div class="olpo-section-header">
                                                    <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
                                                    <p class="olpo-section-description">Konfiguriere die Debug-Optionen für WordPress</p>
                                                </div>
                                                
                                                <?php 
                                                foreach ($category['modules'] as $module_id => $module):
                                                    $is_enabled = isset($enabled_modules[$module_id]) && $enabled_modules[$module_id] === '1';
                                                ?>
                                                    <div class="olpo-module-card">
                                                        <div class="olpo-module-header">
                                                            <h3 class="olpo-module-title"><?php echo esc_html($module->get_name()); ?></h3>
                                                            <label class="olpo-toggle">
                                                                <input type="checkbox" 
                                                                       class="olpo-module-toggle"
                                                                       name="cwo_modules[<?php echo esc_attr($module_id); ?>]" 
                                                                       value="1" 
                                                                       data-module-id="<?php echo esc_attr($module_id); ?>"
                                                                       <?php checked($is_enabled); ?>>
                                                                <span class="olpo-toggle-slider"></span>
                                                            </label>
                                                        </div>
                                                        <p class="olpo-module-description"><?php echo esc_html($module->get_description()); ?></p>
                                                        
                                                        <?php if ($is_enabled): ?>
                                                            <div class="olpo-module-settings">
                                                                <?php 
                                                                // Nur Settings rendern, nicht den Log Viewer
                                                                $this->render_debug_settings_only($module);
                                                                ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                            <?php elseif ($section_id === 'viewer'): ?>
                                                <!-- Log Viewer -->
                                                <div class="olpo-section-header">
                                                    <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
                                                    <p class="olpo-section-description">Zeige und verwalte das WordPress Debug-Log</p>
                                                </div>
                                                
                                                <?php 
                                                foreach ($category['modules'] as $module_id => $module):
                                                    if ($module_id === 'debug') {
                                                        $this->render_debug_viewer_only($module);
                                                    }
                                                endforeach;
                                                ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php 
                                        $first_section = false;
                                    endforeach; 
                                    ?>
                                </div>
                                
                            <?php else: ?>
                                <!-- Ohne Sidebar (z.B. Email) -->
                                <div class="olpo-main-content" style="width: 100%;">
                                    <div class="olpo-section-header">
                                        <h2 class="olpo-section-title"><?php echo esc_html($category['title']); ?> Module</h2>
                                        <p class="olpo-section-description">Konfiguriere deine <?php echo esc_html(strtolower($category['title'])); ?>-bezogenen Module</p>
                                    </div>
                                    
                                    <?php 
                                    foreach ($category['modules'] as $module_id => $module):
                                        $is_enabled = isset($enabled_modules[$module_id]) && $enabled_modules[$module_id] === '1';
                                    ?>
                                        <div class="olpo-module-card">
                                            <div class="olpo-module-header">
                                                <h3 class="olpo-module-title"><?php echo esc_html($module->get_name()); ?></h3>
                                                <label class="olpo-toggle">
                                                    <input type="checkbox" 
                                                           class="olpo-module-toggle"
                                                           name="cwo_modules[<?php echo esc_attr($module_id); ?>]" 
                                                           value="1" 
                                                           data-module-id="<?php echo esc_attr($module_id); ?>"
                                                           <?php checked($is_enabled); ?>>
                                                    <span class="olpo-toggle-slider"></span>
                                                </label>
                                            </div>
                                            <p class="olpo-module-description"><?php echo esc_html($module->get_description()); ?></p>
                                            
                                            <?php if ($is_enabled): ?>
                                                <div class="olpo-module-settings">
                                                    <?php $module->render_settings(); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    <?php 
                        $first = false;
                    endforeach; 
                    ?>
                </div>
                
                <p class="submit" style="margin-top: 20px;">
                    <?php submit_button('Einstellungen speichern', 'primary large', 'cwo_save_settings', false); ?>
                </p>
            </form>
        </div>
        
        <script type="text/javascript">
        (function() {
            'use strict';
            
            document.addEventListener('DOMContentLoaded', function() {
                // Haupttabs
                var mainTabs = document.querySelectorAll('.olpo-main-tab');
                var tabContents = document.querySelectorAll('.olpo-tab-content');
                
                mainTabs.forEach(function(tab) {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetTab = this.getAttribute('data-tab');
                        
                        // Alle Tabs deaktivieren
                        mainTabs.forEach(function(t) { t.classList.remove('active'); });
                        tabContents.forEach(function(c) { c.classList.remove('active'); });
                        
                        // Aktiven Tab aktivieren
                        this.classList.add('active');
                        document.querySelector('.olpo-tab-content[data-tab="' + targetTab + '"]').classList.add('active');
                    });
                });
                
                // Sidebar Navigation
                var sidebarItems = document.querySelectorAll('.olpo-sidebar-item');
                var sections = document.querySelectorAll('.olpo-section');
                
                sidebarItems.forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetSection = this.getAttribute('data-section');
                        var parentTab = this.closest('.olpo-tab-content');
                        
                        // Alle Sidebar-Items in diesem Tab deaktivieren
                        parentTab.querySelectorAll('.olpo-sidebar-item').forEach(function(i) {
                            i.classList.remove('active');
                        });
                        
                        // Alle Sections in diesem Tab deaktivieren
                        parentTab.querySelectorAll('.olpo-section').forEach(function(s) {
                            s.classList.remove('active');
                        });
                        
                        // Aktives Item und Section aktivieren
                        this.classList.add('active');
                        parentTab.querySelector('.olpo-section[data-section="' + targetSection + '"]').classList.add('active');
                    });
                });
                
                // Module Toggle
                var form = document.getElementById('olpo-settings-form');
                var toggles = document.querySelectorAll('.olpo-module-toggle');
                
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!e.submitter || e.submitter.name !== 'cwo_save_settings') {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    });
                }
                
                toggles.forEach(function(toggle) {
                    toggle.addEventListener('change', function(e) {
                        var moduleId = this.getAttribute('data-module-id');
                        var card = this.closest('.olpo-module-card');
                        var settings = card.querySelector('.olpo-module-settings');
                        
                        if (!settings) return;
                        
                        if (this.checked) {
                            settings.style.display = 'block';
                        } else {
                            settings.style.display = 'none';
                        }
                    });
                });
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Nur Debug Settings rendern (ohne Log Viewer)
     */
    private function render_debug_settings_only($module) {
        $display = $module->get_option('display', '1');
        $log_to_file = $module->get_option('log_to_file', '0');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">Debug-Ausgabe</th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="cwo_debug_display" value="1" <?php checked($display, '1'); ?>>
                            Debug-Meldungen im Browser anzeigen (WP_DEBUG_DISPLAY)
                        </label>
                        <p class="description">Zeigt Fehler und Warnungen direkt auf der Website an.</p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Debug-Protokollierung</th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="cwo_debug_log_to_file" value="1" <?php checked($log_to_file, '1'); ?>>
                            Debug-Meldungen in Datei schreiben (WP_DEBUG_LOG)
                        </label>
                        <p class="description">Schreibt alle Fehler in /wp-content/debug.log</p>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Nur Debug Log Viewer rendern
     */
    private function render_debug_viewer_only($module) {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        $log_exists = file_exists($log_file);
        ?>
        <p class="description" style="margin-bottom: 15px;">
            <?php if ($log_exists): ?>
                Log-Datei: <code><?php echo esc_html($log_file); ?></code> 
                | Größe: <strong id="debug-log-size"><?php echo size_format(filesize($log_file)); ?></strong>
            <?php else: ?>
                Keine Debug-Log Datei vorhanden. Aktiviere die Debug-Protokollierung in den Einstellungen.
            <?php endif; ?>
        </p>
        
        <div style="margin: 15px 0;">
            <button type="button" class="button button-primary" onclick="cwoLoadDebugLog()">
                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Aktualisieren
            </button>
            <button type="button" class="button" onclick="cwoDownloadDebugLog()">
                <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Herunterladen
            </button>
            <button type="button" class="button" onclick="cwoClearDebugLog()" style="color: #b32d2e;">
                <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Log leeren
            </button>
        </div>
        
        <div id="debug-log-container" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">
            <span style="color: #888;">Klicke auf "Aktualisieren" um das Debug-Log zu laden...</span>
        </div>
        
        <style>
        #debug-log-container::-webkit-scrollbar {
            width: 10px;
        }
        #debug-log-container::-webkit-scrollbar-track {
            background: #2d2d2d;
        }
        #debug-log-container::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 5px;
        }
        #debug-log-container::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
        </style>
        
        <script type="text/javascript">
        var debugNonce = '<?php echo wp_create_nonce('cwo_debug_nonce'); ?>';
        
        function cwoLoadDebugLog() {
            var container = document.getElementById('debug-log-container');
            container.innerHTML = '<span style="color: #888;">Lade Debug-Log...</span>';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            container.textContent = response.data.content || 'Debug-Log ist leer.';
                            
                            var sizeElement = document.getElementById('debug-log-size');
                            if (sizeElement) {
                                sizeElement.textContent = response.data.size;
                            }
                            
                            container.scrollTop = container.scrollHeight;
                        } else {
                            container.innerHTML = '<span style="color: #ff6b6b;">Fehler beim Laden des Debug-Logs.</span>';
                        }
                    } catch(e) {
                        container.innerHTML = '<span style="color: #ff6b6b;">Fehler beim Parsen der Antwort.</span>';
                    }
                } else {
                    container.innerHTML = '<span style="color: #ff6b6b;">Fehler bei der Anfrage.</span>';
                }
            };
            
            xhr.send('action=cwo_get_debug_log&nonce=' + debugNonce);
        }
        
        function cwoClearDebugLog() {
            if (!confirm('Möchtest du das Debug-Log wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
                return;
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var container = document.getElementById('debug-log-container');
                            container.innerHTML = '<span style="color: #4ade80;">Debug-Log wurde gelöscht.</span>';
                            
                            var sizeElement = document.getElementById('debug-log-size');
                            if (sizeElement) {
                                sizeElement.textContent = '0 B';
                            }
                        }
                    } catch(e) {
                        alert('Fehler beim Parsen der Antwort.');
                    }
                }
            };
            
            xhr.send('action=cwo_clear_debug_log&nonce=' + debugNonce);
        }
        
        function cwoDownloadDebugLog() {
            var downloadUrl = '<?php echo content_url('debug.log'); ?>';
            window.open(downloadUrl, '_blank');
        }
        </script>
        <?php
    }
    
    /**
     * Einstellungen speichern
     */
    private function save_settings() {
        $optimizer = Custom_WP_Optimizer::get_instance();
        $all_modules = array();
        
        foreach ($optimizer->get_modules() as $module_id => $module) {
            $all_modules[$module_id] = '0';
        }
        
        if (isset($_POST['cwo_modules']) && is_array($_POST['cwo_modules'])) {
            foreach ($_POST['cwo_modules'] as $module_id => $value) {
                if ($value === '1') {
                    $all_modules[$module_id] = '1';
                }
            }
        }
        
        update_option('cwo_modules', $all_modules);
        
        foreach ($optimizer->get_modules() as $module_id => $module) {
            if ($all_modules[$module_id] === '1') {
                $module->save_settings($_POST);
            }
        }
        
        add_settings_error('cwo_messages', 'cwo_message', 'Einstellungen gespeichert', 'updated');
        settings_errors('cwo_messages');
    }
}
