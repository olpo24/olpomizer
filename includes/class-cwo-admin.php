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
            'Custom WP Optimizer',
            'WP Optimizer',
            'manage_options',
            'custom-wp-optimizer',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Settings registrieren
     */
    public function register_settings() {
        register_setting('cwo_modules_group', 'cwo_modules');
        
        // Settings für jedes Modul registrieren
        $optimizer = Custom_WP_Optimizer::get_instance();
        foreach ($optimizer->get_modules() as $module_id => $module) {
            // Hier können modulspezifische Settings registriert werden
        }
    }
    
    /**
     * Admin-Assets laden
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_custom-wp-optimizer' !== $hook) {
            return;
        }
        
        // Inline-CSS für besseres Styling
        wp_add_inline_style('wp-admin', '
            .cwo-module-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .cwo-module-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            .cwo-module-title {
                font-size: 16px;
                font-weight: 600;
                margin: 0;
            }
            .cwo-module-description {
                color: #646970;
                margin-bottom: 15px;
            }
            .cwo-module-settings {
                padding: 15px;
                background: #f6f7f7;
                border-radius: 4px;
                margin-top: 15px;
            }
            .cwo-toggle {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            .cwo-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .cwo-toggle-slider {
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
            .cwo-toggle-slider:before {
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
            .cwo-toggle input:checked + .cwo-toggle-slider {
                background-color: #2271b1;
            }
            .cwo-toggle input:checked + .cwo-toggle-slider:before {
                transform: translateX(26px);
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
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('cwo_settings_nonce'); ?>
                
                <h2 class="nav-tab-wrapper">
                    <a href="#" class="nav-tab nav-tab-active">Module</a>
                </h2>
                
                <div style="margin-top: 20px;">
                    <?php foreach ($modules as $module_id => $module): 
                        $is_enabled = isset($enabled_modules[$module_id]) && $enabled_modules[$module_id] === '1';
                    ?>
                        <div class="cwo-module-card">
                            <div class="cwo-module-header">
                                <h3 class="cwo-module-title"><?php echo esc_html($module->get_name()); ?></h3>
                                <label class="cwo-toggle">
                                    <input type="checkbox" 
                                           name="cwo_modules[<?php echo esc_attr($module_id); ?>]" 
                                           value="1" 
                                           <?php checked($is_enabled); ?>
                                           onchange="this.form.submit()">
                                    <span class="cwo-toggle-slider"></span>
                                </label>
                            </div>
                            <p class="cwo-module-description"><?php echo esc_html($module->get_description()); ?></p>
                            
                            <?php if ($is_enabled): ?>
                                <div class="cwo-module-settings">
                                    <h4>Einstellungen</h4>
                                    <?php $module->render_settings(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php submit_button('Einstellungen speichern', 'primary', 'cwo_save_settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Einstellungen speichern
     */
    private function save_settings() {
        // Module Status speichern
        $modules = isset($_POST['cwo_modules']) ? $_POST['cwo_modules'] : array();
        update_option('cwo_modules', $modules);
        
        // Modulspezifische Settings speichern
        $optimizer = Custom_WP_Optimizer::get_instance();
        foreach ($optimizer->get_modules() as $module_id => $module) {
            if (isset($modules[$module_id]) && $modules[$module_id] === '1') {
                $module->save_settings($_POST);
            }
        }
        
        add_settings_error('cwo_messages', 'cwo_message', 'Einstellungen gespeichert', 'updated');
        settings_errors('cwo_messages');
    }
}
