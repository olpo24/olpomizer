<?php
/**
 * Admin-Interface Hauptklasse (Koordinator)
 * Schlanke Version - delegiert an spezialisierte Klassen
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Admin {
    
    private $page_renderer;
    private $assets_manager;
    private $settings_handler;
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->init_hooks();
    }
    
    /**
     * Abhängigkeiten laden
     */
    private function load_dependencies() {
        require_once CWO_PLUGIN_DIR . 'includes/admin/class-cwo-admin-page.php';
        require_once CWO_PLUGIN_DIR . 'includes/admin/class-cwo-admin-assets.php';
        require_once CWO_PLUGIN_DIR . 'includes/admin/class-cwo-admin-settings.php';
        
        // Modul-Renderer
        require_once CWO_PLUGIN_DIR . 'includes/admin/renderers/class-cwo-debug-renderer.php';
        require_once CWO_PLUGIN_DIR . 'includes/admin/renderers/class-cwo-smtp-renderer.php';
        require_once CWO_PLUGIN_DIR . 'includes/admin/renderers/class-cwo-performance-renderer.php';
    }
    
    /**
     * Komponenten initialisieren
     */
    private function init_components() {
        $this->page_renderer = new CWO_Admin_Page();
        $this->assets_manager = new CWO_Admin_Assets();
        $this->settings_handler = new CWO_Admin_Settings();
    }
    
    /**
     * WordPress Hooks registrieren
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this->assets_manager, 'enqueue_assets'));
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
            array($this->page_renderer, 'render')
        );
    }
    
    /**
     * Settings registrieren
     */
    public function register_settings() {
        register_setting('cwo_modules_group', 'cwo_modules');
        
        // Settings speichern wenn Formular submitted wurde
        if (isset($_POST['cwo_save_settings']) && check_admin_referer('cwo_settings_nonce')) {
            $this->settings_handler->save($_POST);
        }
    }
}
