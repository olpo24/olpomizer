<?php
/**
 * Plugin Name: OlpoMizer
 * Plugin URI: https://olpo.de
 * Description: Modulares WordPress Optimierungs-Plugin mit konfigurierbaren Features
 * Version: 1.2.1
 * Author: Ole 
 * Author URI: https://olpo.de
 * Text Domain: olpomizer
 * Requires at least: 6.9
 * Tested up to: 6.9
 * Requires PHP: 8.4
 * Domain Path: /languages
 * Update URI: https://olpomizer.olpo24.de/wp-json/pblsh/v1/
 */
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Peak Publisher Bootstrap Code basicV1
 * Keep the code as it is, as it is optimized for several requirements.
 * 
 * Compatible with:
 * - PHP ≥ 5.3
 * - Wordpress ≥ 5.8
 */
add_action('plugin_loaded', function($plugin_full_path) {
    global $wp_version;
    static $done = false;
    if ($done) return;
    $done = true;

    $real_wp_version = function_exists('wp_get_wp_version') ? wp_get_wp_version() : $wp_version;
    $user_agent = 'PeakPublisherBootstrapCode/basicV1; WordPress/' . $real_wp_version . '; ' . home_url( '/' );
    $plugin_basename = plugin_basename($plugin_full_path);
    require_once ABSPATH . 'wp-admin/includes/plugin.php'; // For WordPress before version 6.8 we need to include this file to ensure the function get_plugin_data() is available.
    $update_url = trailingslashit(sanitize_url( get_plugin_data( $plugin_full_path, false, false )['UpdateURI'] ));
    $host = wp_parse_url($update_url, PHP_URL_HOST);

    add_filter('update_plugins_' . $host, function($false, $plugin_data, $plugin_file, $locales) use($user_agent, $plugin_full_path, $plugin_basename, $update_url) {
        if ($plugin_file !== $plugin_basename) return $false;
        $options = array(
            'timeout'    => wp_doing_cron() ? 30 : 3,
            'body'       => array(
                'plugins'      => wp_json_encode( array('plugins' => array($plugin_file => $plugin_data)) ),
                'locale'       => wp_json_encode( $locales )
            ),
            'user-agent' => $user_agent
        );
        $raw_response = wp_remote_post($update_url . 'plugins/update-check/', $options);
        $response = json_decode( wp_remote_retrieve_body( $raw_response ) ?: 'false', true );
        return empty($response['plugins'][$plugin_file]) ? $false : $response['plugins'][$plugin_file];
    }, 10, 4);

    add_filter('plugins_api', function($false, $action, $args) use($user_agent, $plugin_full_path, $plugin_basename, $update_url) {
        if ($action !== 'plugin_information' || empty($args->slug)) return $false;
        $transient = get_site_transient( 'update_plugins' );
        $plugins = array_merge($transient->response, $transient->no_update);
        if (empty($plugins[$plugin_basename]->slug) || $plugins[$plugin_basename]->slug !== $args->slug) return $false;
        $url = add_query_arg(
            array(
                'action'  => $action,
                'request' => $args,
            ),
            $update_url . 'plugins/info/'
        );
        $raw_response = wp_remote_get( $url, array(
            'timeout'    => 15,
            'user-agent' => $user_agent
        ));
        $response = json_decode( wp_remote_retrieve_body( $raw_response ) ?: 'false', true );
        return empty($response) ? $false : (object) $response;
    }, 9, 3);
}, 10, 1);

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
        require_once CWO_PLUGIN_DIR . 'modules/performance/class-cwo-performance.php';
    }
    
    /**
     * Module initialisieren
     */
    private function init_modules() {
        // SMTP Modul registrieren
        $this->register_module('smtp', new CWO_SMTP_Module());
        $this->register_module('debug', new CWO_Debug_Module());
        $this->register_module('performance', new CWO_Performance_Module());
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
    
    // Email Log Tabelle erstellen
    global $wpdb;
    $table_name = $wpdb->prefix . 'cwo_email_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        to_email varchar(255) NOT NULL,
        subject text NOT NULL,
        message longtext NOT NULL,
        headers text,
        status varchar(20) NOT NULL,
        error_message text,
        sent_time datetime NOT NULL,
        PRIMARY KEY (id),
        KEY sent_time (sent_time)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

/**
 * Deaktivierungs-Hook
 */
register_deactivation_hook(__FILE__, function() {
    // Optional: Cleanup bei Deaktivierung
});
