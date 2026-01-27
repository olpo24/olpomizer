<?php
/**
 * Plugin Name: OlpoMizer
 * Plugin URI: https://olpo.de
 * Description: Modulares WordPress Optimierungs-Plugin mit konfigurierbaren Features
 * Version: 1.0.1
 * Author: Ole 
 * Author URI: https://olpo.de
 * Text Domain: olpomizer
 * Domain Path: /languages
 */
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten
define('CWO_VERSION', '1.0.0');
define('CWO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CWO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CWO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Haupt-Plugin-Klasse
 */
class Custom_WP_Optimizer {
    
    private static $instance = null;
    private $modules = array();
    
    /**
     * Singleton Instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
        $this->init_hooks();
    }
    
    /**
     * Abhängigkeiten laden
     */
    private function load_dependencies() {
        // Admin-Interface
        require_once CWO_PLUGIN_DIR . 'includes/class-cwo-admin.php';
        
        // Module Base Class
        require_once CWO_PLUGIN_DIR . 'includes/class-cwo-module.php';
        
        // Verfügbare Module laden
        require_once CWO_PLUGIN_DIR . 'modules/smtp/class-cwo-smtp.php';
        require_once CWO_PLUGIN_DIR . 'modules/debug/class-cwo-debug.php';
    }
    
    /**
     * Module initialisieren
     */
    private function init_modules() {
        // SMTP Modul registrieren
        $this->register_module('smtp', new CWO_SMTP_Module());
        
        // Hier können weitere Module registriert werden
        // $this->register_module('image-optimization', new CWO_Image_Module());
        // $this->register_module('disable-features', new CWO_Disable_Module());
    }
    
    /**
     * Modul registrieren
     */
    public function register_module($id, $module) {
        if ($module instanceof CWO_Module_Base) {
            $this->modules[$id] = $module;
            
            // Modul initialisieren wenn aktiviert
            if ($this->is_module_enabled($id)) {
                $module->init();
            }
        }
    }
    
    /**
     * Prüfen ob Modul aktiviert ist
     */
    public function is_module_enabled($module_id) {
        $options = get_option('cwo_modules', array());
        return isset($options[$module_id]) && $options[$module_id] === '1';
    }
    
    /**
     * Alle registrierten Module abrufen
     */
    public function get_modules() {
        return $this->modules;
    }
    
    /**
     * WordPress Hooks initialisieren
     */
    private function init_hooks() {
        // Admin-Interface initialisieren
        if (is_admin()) {
            new CWO_Admin();
        }
    }
}

/**
 * Plugin initialisieren
 */
function cwo_init() {
    return Custom_WP_Optimizer::get_instance();
}

// Plugin starten
add_action('plugins_loaded', 'cwo_init');

/**
 * Aktivierungs-Hook
 */
register_activation_hook(__FILE__, function() {
    // Standard-Optionen setzen
    if (!get_option('cwo_modules')) {
        add_option('cwo_modules', array());
    }
});

/**
 * Deaktivierungs-Hook
 */
register_deactivation_hook(__FILE__, function() {
    // Optional: Cleanup bei Deaktivierung
});
