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
                display: none;
            }
            
            .olpo-module-settings.show {
                display: block;
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
                'modules' => array(),
                'sections' => array(
                    'smtp' => array(
                        'title' => 'SMTP Einstellungen',
                        'icon' => 'admin-settings'
                    ),
                    'log' => array(
                        'title' => 'E-Mail Log',
                        'icon' => 'list-view'
                    )
                )
            ),
            'debug' => array(
                'title' => 'Debug',
                'icon' => 'admin-tools',
