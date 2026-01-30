<?php
/**
 * Performance Optimierungs-Modul
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Performance_Module extends CWO_Module_Base {
    
    public function __construct() {
        $this->id = 'performance';
        $this->name = 'Performance Optimierungen';
        $this->description = 'Optimiere WordPress durch Deaktivierung unnötiger Features und Skripte.';
    }
    
    /**
     * Modul initialisieren
     */
    public function init() {
        // Emojis deaktivieren
        if ($this->get_option('disable_emojis') === '1') {
            $this->disable_emojis();
        }
        
        // Embeds deaktivieren
        if ($this->get_option('disable_embeds') === '1') {
            $this->disable_embeds();
        }
        
        // XML-RPC deaktivieren
        if ($this->get_option('disable_xmlrpc') === '1') {
            $this->disable_xmlrpc();
        }
        
        // Dashicons im Frontend deaktivieren
        if ($this->get_option('disable_dashicons') === '1') {
            $this->disable_dashicons_frontend();
        }
        
        // jQuery Migrate entfernen
        if ($this->get_option('remove_jquery_migrate') === '1') {
            $this->remove_jquery_migrate();
        }
        
        // RSS Feed Links entfernen
        if ($this->get_option('remove_feed_links') === '1') {
            $this->remove_feed_links();
        }
        
        // RSD Link entfernen
        if ($this->get_option('remove_rsd_link') === '1') {
            remove_action('wp_head', 'rsd_link');
        }
        
        // Shortlinks deaktivieren
        if ($this->get_option('disable_shortlinks') === '1') {
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('template_redirect', 'wp_shortlink_header', 11);
        }
        
        // Windows Live Writer Manifest entfernen
        if ($this->get_option('remove_wlw_manifest') === '1') {
            remove_action('wp_head', 'wlwmanifest_link');
        }
        
        // Heartbeat API kontrollieren
        $heartbeat_location = $this->get_option('heartbeat_location', 'default');
        if ($heartbeat_location !== 'default') {
            add_action('init', array($this, 'control_heartbeat'));
        }
        
        // Heartbeat Intervall anpassen
        $heartbeat_interval = $this->get_option('heartbeat_interval', '60');
        if ($heartbeat_interval !== '60') {
            add_filter('heartbeat_settings', array($this, 'modify_heartbeat_interval'));
        }
        
        // Post Revisions limitieren
        $max_revisions = $this->get_option('max_revisions', '');
        if ($max_revisions !== '' && !defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', intval($max_revisions));
        }
        
        // Autosave Intervall erhöhen
        $autosave_interval = $this->get_option('autosave_interval', '60');
        if ($autosave_interval !== '60' && !defined('AUTOSAVE_INTERVAL')) {
            define('AUTOSAVE_INTERVAL', intval($autosave_interval));
        }
        
        // Trash leeren nach X Tagen
        $empty_trash_days = $this->get_option('empty_trash_days', '30');
        if ($empty_trash_days !== '30' && !defined('EMPTY_TRASH_DAYS')) {
            define('EMPTY_TRASH_DAYS', intval($empty_trash_days));
        }
        
        // AJAX Handler für Transients aufräumen
        add_action('wp_ajax_cwo_clean_transients', array($this, 'ajax_clean_transients'));
    // Cache aktivieren
if ($this->get_option('cache_enabled') === '1') {
    $this->init_cache();
}
// HIER HINZUFÜGEN:
// AJAX Handler für Cache Warmup
add_action('wp_ajax_cwo_get_warmup_urls', array($this, 'ajax_get_warmup_urls'));
add_action('wp_ajax_cwo_warmup_url', array($this, 'ajax_warmup_url'));

// Auto-Warmup bei Post-Update
if ($this->get_option('cache_auto_warmup', '0') === '1') {
    add_action('save_post', array($this, 'auto_warmup_on_save'), 10, 1);
}
// Browser Caching (.htaccess)
if ($this->get_option('browser_caching_enabled') === '1') {
    $this->enable_browser_caching();
}

// AJAX Handlers
add_action('wp_ajax_cwo_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Emojis komplett deaktivieren (JS + CSS + DNS Prefetch)
     */
    private function disable_emojis() {
        // Alle Emoji-bezogenen Actions entfernen
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        
        // TinyMCE Emoji Plugin entfernen
        add_filter('tiny_mce_plugins', array($this, 'disable_emojis_tinymce'));
        
        // DNS Prefetch für emoji CDN entfernen
        add_filter('wp_resource_hints', array($this, 'disable_emojis_dns_prefetch'), 10, 2);
    }
    
    public function disable_emojis_tinymce($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, array('wpemoji'));
        }
        return array();
    }
    
    public function disable_emojis_dns_prefetch($urls, $relation_type) {
        if ('dns-prefetch' === $relation_type) {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
            $urls = array_diff($urls, array($emoji_svg_url));
        }
        return $urls;
    }
    
    /**
     * Embeds deaktivieren
     */
    private function disable_embeds() {
        // oEmbed Discovery Links entfernen
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        
        // oEmbed-spezifische JavaScript entfernen
        remove_action('wp_head', 'wp_oembed_add_host_js');
        
        // REST API Endpunkt für oEmbeds entfernen
        add_filter('rest_endpoints', array($this, 'disable_embeds_rest_endpoint'));
        
        // Embed Rewrite Rules entfernen
        add_filter('rewrite_rules_array', array($this, 'disable_embeds_rewrites'));
        
        // oEmbed Filter entfernen
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        
        // Embed Handler entfernen
        remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
    }
    
    public function disable_embeds_rest_endpoint($endpoints) {
        if (isset($endpoints['/oembed/1.0/embed'])) {
            unset($endpoints['/oembed/1.0/embed']);
        }
        return $endpoints;
    }
    
    public function disable_embeds_rewrites($rules) {
        foreach ($rules as $rule => $rewrite) {
            if (strpos($rewrite, 'embed=true') !== false) {
                unset($rules[$rule]);
            }
        }
        return $rules;
    }
    
    /**
     * XML-RPC komplett deaktivieren
     */
    private function disable_xmlrpc() {
        // XML-RPC komplett deaktivieren
        add_filter('xmlrpc_enabled', '__return_false');
        
        // XML-RPC Methoden blockieren
        add_filter('xmlrpc_methods', '__return_empty_array');
        
        // Pingback Header entfernen
        add_filter('wp_headers', array($this, 'remove_xmlrpc_pingback_header'));
        
        // RSD Link entfernen (wird oft für XML-RPC genutzt)
        remove_action('wp_head', 'rsd_link');
    }
    
    public function remove_xmlrpc_pingback_header($headers) {
        if (isset($headers['X-Pingback'])) {
            unset($headers['X-Pingback']);
        }
        return $headers;
    }
    
    /**
     * Dashicons im Frontend für nicht eingeloggte User deaktivieren
     */
    private function disable_dashicons_frontend() {
        add_action('wp_enqueue_scripts', array($this, 'dequeue_dashicons'), 20);
    }
    
    public function dequeue_dashicons() {
        if (!is_user_logged_in()) {
            wp_deregister_style('dashicons');
            wp_dequeue_style('dashicons');
        }
    }
    
    /**
     * jQuery Migrate entfernen
     */
    private function remove_jquery_migrate() {
        add_action('wp_default_scripts', array($this, 'remove_jquery_migrate_script'));
    }
    
    public function remove_jquery_migrate_script($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            
            if ($script->deps) {
                // jQuery Migrate aus den Dependencies entfernen
                $script->deps = array_diff($script->deps, array('jquery-migrate'));
            }
        }
    }
    
    /**
     * RSS Feed Links entfernen
     */
    private function remove_feed_links() {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }
    
    /**
     * Heartbeat API kontrollieren
     */
    public function control_heartbeat() {
        $location = $this->get_option('heartbeat_location', 'default');
        
        switch ($location) {
            case 'disable_all':
                // Überall deaktivieren
                wp_deregister_script('heartbeat');
                break;
                
            case 'disable_frontend':
                // Nur im Frontend deaktivieren
                if (!is_admin()) {
                    wp_deregister_script('heartbeat');
                }
                break;
                
            case 'allow_post_editor':
                // Überall außer im Post-Editor deaktivieren
                global $pagenow;
                if (!is_admin() || ($pagenow !== 'post.php' && $pagenow !== 'post-new.php')) {
                    wp_deregister_script('heartbeat');
                }
                break;
        }
    }
    
    /**
     * Heartbeat Intervall anpassen
     */
    public function modify_heartbeat_interval($settings) {
        $interval = intval($this->get_option('heartbeat_interval', '60'));
        $settings['interval'] = $interval;
        return $settings;
    }
    
    /**
     * Transients aufräumen (AJAX)
     */
    public function ajax_clean_transients() {
        check_ajax_referer('cwo_performance_nonce', 'nonce');
        
        global $wpdb;
        
        // Abgelaufene Transients löschen
        $time = time();
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                $time
            )
        );
        
        // Zugehörige Transient-Werte löschen
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' 
             AND option_name NOT LIKE '_transient_timeout_%' 
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_timeout', '') 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_%'
             )"
        );
        
        // Site Transients für Multisite
        if (is_multisite()) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d",
                    $wpdb->esc_like('_site_transient_timeout_') . '%',
                    $time
                )
            );
            
            $wpdb->query(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_%' 
                 AND meta_key NOT LIKE '_site_transient_timeout_%' 
                 AND meta_key NOT IN (
                     SELECT REPLACE(meta_key, '_timeout', '') 
                     FROM {$wpdb->sitemeta} 
                     WHERE meta_key LIKE '_site_transient_timeout_%'
                 )"
            );
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Erfolgreich %d abgelaufene Transients gelöscht.', $deleted)
        ));
    }
    
    /**
     * Settings-Felder rendern
     */
    public function render_settings() {
        $disable_emojis = $this->get_option('disable_emojis', '0');
        $disable_embeds = $this->get_option('disable_embeds', '0');
        $disable_xmlrpc = $this->get_option('disable_xmlrpc', '0');
        $disable_dashicons = $this->get_option('disable_dashicons', '0');
        $remove_jquery_migrate = $this->get_option('remove_jquery_migrate', '0');
        $remove_feed_links = $this->get_option('remove_feed_links', '0');
        $remove_rsd_link = $this->get_option('remove_rsd_link', '0');
        $disable_shortlinks = $this->get_option('disable_shortlinks', '0');
        $remove_wlw_manifest = $this->get_option('remove_wlw_manifest', '0');
        $heartbeat_location = $this->get_option('heartbeat_location', 'default');
        $heartbeat_interval = $this->get_option('heartbeat_interval', '60');
        $max_revisions = $this->get_option('max_revisions', '5');
        $autosave_interval = $this->get_option('autosave_interval', '300');
        $empty_trash_days = $this->get_option('empty_trash_days', '30');
        ?>
        
        <h3 style="margin-top: 0;">WordPress Features</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Emojis</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_disable_emojis" value="1" <?php checked($disable_emojis, '1'); ?>>
                        Emoji-Unterstützung deaktivieren (spart ~10KB JS + CSS + DNS Prefetch)
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Embeds</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_disable_embeds" value="1" <?php checked($disable_embeds, '1'); ?>>
                        WordPress Embeds deaktivieren (spart wp-embed.min.js)
                    </label>
                    <p class="description">Deaktiviert oEmbed-Funktionen für externe Inhalte wie YouTube, Twitter, etc.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">XML-RPC</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_disable_xmlrpc" value="1" <?php checked($disable_xmlrpc, '1'); ?>>
                        XML-RPC komplett deaktivieren
                    </label>
                    <p class="description">Erhöht Sicherheit und Performance. Nur deaktivieren wenn du keine Remote-Publishing-Apps verwendest.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Dashicons</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_disable_dashicons" value="1" <?php checked($disable_dashicons, '1'); ?>>
                        Dashicons im Frontend für nicht-eingeloggte User deaktivieren (spart ~50KB CSS)
                    </label>
                    <p class="description">Sicher wenn du keine Dashicons im Frontend verwendest.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">jQuery Migrate</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_remove_jquery_migrate" value="1" <?php checked($remove_jquery_migrate, '1'); ?>>
                        jQuery Migrate entfernen (spart ~9KB JS)
                    </label>
                    <p class="description">Nur deaktivieren wenn du keine alten Plugins/Themes mit veraltetem jQuery-Code verwendest.</p>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h3>HTTP Requests reduzieren</h3>
        <table class="form-table">
            <tr>
                <th scope="row">RSS Feed Links</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_remove_feed_links" value="1" <?php checked($remove_feed_links, '1'); ?>>
                        RSS Feed Links aus dem Header entfernen
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">RSD Link</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_remove_rsd_link" value="1" <?php checked($remove_rsd_link, '1'); ?>>
                        Really Simple Discovery Link entfernen
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Shortlinks</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_disable_shortlinks" value="1" <?php checked($disable_shortlinks, '1'); ?>>
                        WordPress Shortlinks deaktivieren
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Windows Live Writer</th>
                <td>
                    <label>
                        <input type="checkbox" name="cwo_perf_remove_wlw_manifest" value="1" <?php checked($remove_wlw_manifest, '1'); ?>>
                        Windows Live Writer Manifest entfernen
                    </label>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h3>Heartbeat API</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Heartbeat Kontrolle</th>
                <td>
                    <select name="cwo_perf_heartbeat_location">
                        <option value="default" <?php selected($heartbeat_location, 'default'); ?>>Standard (überall aktiv)</option>
                        <option value="disable_frontend" <?php selected($heartbeat_location, 'disable_frontend'); ?>>Im Frontend deaktivieren</option>
                        <option value="allow_post_editor" <?php selected($heartbeat_location, 'allow_post_editor'); ?>>Nur im Post-Editor erlauben</option>
                        <option value="disable_all" <?php selected($heartbeat_location, 'disable_all'); ?>>Überall deaktivieren</option>
                    </select>
                    <p class="description">Die Heartbeat API sendet regelmäßig AJAX-Anfragen. Im Frontend meist unnötig.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Heartbeat Intervall</th>
                <td>
                    <input type="number" name="cwo_perf_heartbeat_interval" value="<?php echo esc_attr($heartbeat_interval); ?>" min="15" max="300" step="5" class="small-text"> Sekunden
                    <p class="description">Standard: 60 Sekunden. Höhere Werte = weniger Server-Last.</p>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h3>Datenbank Optimierung</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Post Revisions</th>
                <td>
                    <input type="number" name="cwo_perf_max_revisions" value="<?php echo esc_attr($max_revisions); ?>" min="0" max="50" class="small-text"> Revisionen
                    <p class="description">Maximale Anzahl an gespeicherten Post-Revisionen. 0 = unbegrenzt, -1 = deaktiviert.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Autosave Intervall</th>
                <td>
                    <input type="number" name="cwo_perf_autosave_interval" value="<?php echo esc_attr($autosave_interval); ?>" min="60" max="600" step="30" class="small-text"> Sekunden
                    <p class="description">Standard: 60 Sekunden. Höhere Werte = weniger DB-Schreibvorgänge.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Papierkorb leeren</th>
                <td>
                    <input type="number" name="cwo_perf_empty_trash_days" value="<?php echo esc_attr($empty_trash_days); ?>" min="1" max="90" class="small-text"> Tage
                    <p class="description">Standard: 30 Tage. Danach werden Beiträge aus dem Papierkorb automatisch gelöscht.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Transients aufräumen</th>
                <td>
                    <button type="button" class="button" onclick="cwoCleanTransients()">
                        <span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span> Abgelaufene Transients jetzt löschen
                    </button>
                    <span id="transients-result" style="margin-left: 10px;"></span>
                    <p class="description">Löscht abgelaufene Transients aus der Datenbank.</p>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        var perfNonce = '<?php echo wp_create_nonce('cwo_performance_nonce'); ?>';
        
        function cwoCleanTransients() {
            var result = document.getElementById('transients-result');
            result.textContent = 'Bereinige...';
            result.style.color = '#000';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            result.textContent = response.data.message;
                            result.style.color = 'green';
                        } else {
                            result.textContent = 'Fehler beim Bereinigen.';
                            result.style.color = 'red';
                        }
                    } catch(e) {
                        result.textContent = 'Fehler beim Parsen der Antwort.';
                        result.style.color = 'red';
                    }
                } else {
                    result.textContent = 'Fehler bei der Anfrage.';
                    result.style.color = 'red';
                }
            };
            
            xhr.send('action=cwo_clean_transients&nonce=' + perfNonce);
        }
        </script>
        <?php
    }
    
    /**
     * Settings speichern
     */
    public function save_settings($post_data) {
        // WordPress Features
        $this->update_option('disable_emojis', isset($post_data['cwo_perf_disable_emojis']) ? '1' : '0');
        $this->update_option('disable_embeds', isset($post_data['cwo_perf_disable_embeds']) ? '1' : '0');
        $this->update_option('disable_xmlrpc', isset($post_data['cwo_perf_disable_xmlrpc']) ? '1' : '0');
        $this->update_option('disable_dashicons', isset($post_data['cwo_perf_disable_dashicons']) ? '1' : '0');
        $this->update_option('remove_jquery_migrate', isset($post_data['cwo_perf_remove_jquery_migrate']) ? '1' : '0');
        
        // HTTP Requests
        $this->update_option('remove_feed_links', isset($post_data['cwo_perf_remove_feed_links']) ? '1' : '0');
        $this->update_option('remove_rsd_link', isset($post_data['cwo_perf_remove_rsd_link']) ? '1' : '0');
        $this->update_option('disable_shortlinks', isset($post_data['cwo_perf_disable_shortlinks']) ? '1' : '0');
        $this->update_option('remove_wlw_manifest', isset($post_data['cwo_perf_remove_wlw_manifest']) ? '1' : '0');
        
        // Heartbeat
        if (isset($post_data['cwo_perf_heartbeat_location'])) {
            $this->update_option('heartbeat_location', sanitize_text_field($post_data['cwo_perf_heartbeat_location']));
        }
        if (isset($post_data['cwo_perf_heartbeat_interval'])) {
            $this->update_option('heartbeat_interval', intval($post_data['cwo_perf_heartbeat_interval']));
        }
        
        // Datenbank
        if (isset($post_data['cwo_perf_max_revisions'])) {
            $this->update_option('max_revisions', intval($post_data['cwo_perf_max_revisions']));
        }
        if (isset($post_data['cwo_perf_autosave_interval'])) {
            $this->update_option('autosave_interval', intval($post_data['cwo_perf_autosave_interval']));
        }
        if (isset($post_data['cwo_perf_empty_trash_days'])) {
            $this->update_option('empty_trash_days', intval($post_data['cwo_perf_empty_trash_days']));
        }
        //Cache Settings
    $cache_enabled_old = $this->get_option('cache_enabled', '0');
    $cache_enabled_new = isset($post_data['cwo_cache_enabled']) ? '1' : '0';
    $this->update_option('cache_enabled', $cache_enabled_new);
    
    if (isset($post_data['cwo_cache_exclude_urls'])) {
        $this->update_option('cache_exclude_urls', sanitize_textarea_field($post_data['cwo_cache_exclude_urls']));
    }
    if (isset($post_data['cwo_cache_exclude_css'])) {
        $this->update_option('cache_exclude_css', sanitize_textarea_field($post_data['cwo_cache_exclude_css']));
    }
    if (isset($post_data['cwo_cache_exclude_js'])) {
        $this->update_option('cache_exclude_js', sanitize_textarea_field($post_data['cwo_cache_exclude_js']));
    }
    // Cache Warmup Settings
$this->update_option('cache_auto_warmup', isset($post_data['cwo_cache_auto_warmup']) ? '1' : '0');

if (isset($post_data['cwo_cache_warmup_scope'])) {
    $this->update_option('cache_warmup_scope', sanitize_text_field($post_data['cwo_cache_warmup_scope']));
}

if (isset($post_data['cwo_cache_warmup_delay'])) {
    $this->update_option('cache_warmup_delay', intval($post_data['cwo_cache_warmup_delay']));
}
    // Browser Caching
    $browser_caching_old = $this->get_option('browser_caching_enabled', '0');
    $browser_caching_new = isset($post_data['cwo_browser_caching_enabled']) ? '1' : '0';
    $this->update_option('browser_caching_enabled', $browser_caching_new);
    
    // .htaccess aktualisieren oder Regeln entfernen
    if ($browser_caching_new === '1' && $browser_caching_old === '0') {
        $this->update_htaccess();
    } elseif ($browser_caching_new === '0' && $browser_caching_old === '1') {
        $this->remove_htaccess_rules();
    }
}
    /**
 * Cache initialisieren
 */
private function init_cache() {
    // Cache Verzeichnis erstellen
    $cache_dir = WP_CONTENT_DIR . '/cache/olpomizer/';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }
    
    // Cache für Frontend-Seiten (nur für nicht-eingeloggte User)
    if (!is_admin() && !is_user_logged_in()) {
        add_action('template_redirect', array($this, 'serve_cached_page'), 1);
        
        // WICHTIG: Output Buffering STARTEN
        add_action('template_redirect', array($this, 'start_output_buffering'), 2);
        
        add_action('shutdown', array($this, 'cache_page'), 999);
    }
    
    // Cache bei Post-Updates leeren
    add_action('save_post', array($this, 'clear_post_cache'));
    add_action('deleted_post', array($this, 'clear_post_cache'));
    add_action('switch_theme', array($this, 'clear_all_cache'));
    
    // CSS/JS Cache-Kontrolle
    add_filter('style_loader_src', array($this, 'maybe_exclude_from_cache'), 10, 2);
    add_filter('script_loader_src', array($this, 'maybe_exclude_from_cache'), 10, 2);
}

/**
 * Gecachte Seite ausliefern
 */
public function serve_cached_page() {
    // Prüfen ob URL ausgeschlossen ist
    if ($this->is_url_excluded()) {
        return;
    }
    
    // Keine gecachte Version für POST-Requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return;
    }
    
    $cache_file = $this->get_cache_file_path();
    
    // Cache-Datei existiert und ist gültig
    if (file_exists($cache_file)) {
        $cache_time = filemtime($cache_file);
        $cache_lifetime = 3600; // 1 Stunde
        
        if ((time() - $cache_time) < $cache_lifetime) {
            header('X-OlpoMizer-Cache: HIT');
            readfile($cache_file);
            exit;
        } else {
            // Abgelaufener Cache löschen
            @unlink($cache_file);
        }
    }
}

/**
 * Seite cachen
 */
public function cache_page() {
    // Nicht cachen wenn URL ausgeschlossen ist
    if ($this->is_url_excluded()) {
        return;
    }
    
    // Nicht cachen bei Fehlern oder Redirects
    if (is_404() || is_search() || http_response_code() !== 200) {
        return;
    }
    
    $cache_file = $this->get_cache_file_path();
    $output = ob_get_contents();
    
    if ($output && strlen($output) > 0) {
        // Cache-Verzeichnis sicherstellen
        $cache_dir = dirname($cache_file);
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        // HTML-Kommentar mit Cache-Info hinzufügen
        $output .= "\n<!-- Cached by OlpoMizer on " . date('Y-m-d H:i:s') . " -->";
        
        file_put_contents($cache_file, $output, LOCK_EX);
    }
}

/**
 * Cache-Dateipfad generieren
 */
private function get_cache_file_path() {
    $url = $_SERVER['REQUEST_URI'];
    $cache_key = md5($url);
    
    $cache_dir = WP_CONTENT_DIR . '/cache/olpomizer/';
    
    return $cache_dir . $cache_key . '.html';
}

/**
 * Prüfen ob URL vom Cache ausgeschlossen ist
 */
private function is_url_excluded() {
    $current_url = $_SERVER['REQUEST_URI'];
    $excluded_urls = $this->get_option('cache_exclude_urls', '');
    
    if (empty($excluded_urls)) {
        return false;
    }
    
    $patterns = array_filter(array_map('trim', explode("\n", $excluded_urls)));
    
    foreach ($patterns as $pattern) {
        // Wildcard-Pattern in Regex umwandeln
        $regex = str_replace(
            array('\*', '\?'),
            array('.*', '.'),
            preg_quote($pattern, '#')
        );
        
        if (preg_match('#^' . $regex . '$#i', $current_url)) {
            return true;
        }
    }
    
    return false;
}

/**
 * CSS/JS vom Cache ausschließen
 */
public function maybe_exclude_from_cache($src, $handle) {
    // Prüfen ob es eine CSS oder JS Datei ist
    $is_css = strpos($src, '.css') !== false;
    $is_js = strpos($src, '.js') !== false;
    
    if (!$is_css && !$is_js) {
        return $src;
    }
    
    // Ausschlussliste laden
    $exclude_option = $is_css ? 'cache_exclude_css' : 'cache_exclude_js';
    $excluded_files = $this->get_option($exclude_option, '');
    
    if (empty($excluded_files)) {
        return $src;
    }
    
    $patterns = array_filter(array_map('trim', explode("\n", $excluded_files)));
    
    foreach ($patterns as $pattern) {
        if (strpos($src, $pattern) !== false || fnmatch($pattern, $src)) {
            // Version-Parameter hinzufügen um Browser-Cache zu umgehen
            $separator = strpos($src, '?') !== false ? '&' : '?';
            return $src . $separator . 'nocache=' . time();
        }
    }
    
    return $src;
}

/**
 * Post-spezifischen Cache leeren
 */
public function clear_post_cache($post_id) {
    $post_url = get_permalink($post_id);
    
    if ($post_url) {
        $cache_key = md5(parse_url($post_url, PHP_URL_PATH));
        $cache_file = WP_CONTENT_DIR . '/cache/olpomizer/' . $cache_key . '.html';
        
        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }
}

/**
 * Gesamten Cache leeren
 */
public function clear_all_cache() {
    $cache_dir = WP_CONTENT_DIR . '/cache/olpomizer/';
    
    if (!is_dir($cache_dir)) {
        return;
    }
    
    $files = glob($cache_dir . '*.html');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    
    // Letzte Leerung speichern
    $this->update_option('cache_last_cleared', current_time('mysql'));
}

/**
 * Cache-Statistiken abrufen
 */
public function get_cache_stats() {
    $cache_dir = WP_CONTENT_DIR . '/cache/olpomizer/';
    
    $stats = array(
        'files' => 0,
        'size' => '0 B',
        'last_cleared' => 'Nie'
    );
    
    // Cache-Verzeichnis erstellen falls nicht vorhanden
    if (!is_dir($cache_dir)) {
        wp_mkdir_p($cache_dir);
        return $stats;
    }
    
    // Dateien zählen mit Fehlerbehandlung
    $files = @glob($cache_dir . '*.html');
    if ($files === false || !is_array($files)) {
        $files = array();
    }
    
    $stats['files'] = count($files);
    
    // Gesamtgröße berechnen
    $total_size = 0;
    foreach ($files as $file) {
        if (file_exists($file) && is_readable($file)) {
            $size = @filesize($file);
            if ($size !== false) {
                $total_size += $size;
            }
        }
    }
    
    // Größe formatieren
    if ($total_size > 0) {
        $stats['size'] = size_format($total_size);
    }
    
    // Letzte Leerung
    $last_cleared = $this->get_option('cache_last_cleared', '');
    if (!empty($last_cleared)) {
        $timestamp = strtotime($last_cleared);
        if ($timestamp !== false) {
            $stats['last_cleared'] = date_i18n('d.m.Y H:i', $timestamp);
        }
    }
    
    return $stats;
}

/**
 * Cache leeren (AJAX)
 */
public function ajax_clear_cache() {
    check_ajax_referer('cwo_cache_nonce', 'nonce');
    
    $this->clear_all_cache();
    
    wp_send_json_success(array(
        'message' => 'Cache erfolgreich geleert!'
    ));
}

/**
 * Browser Caching aktivieren
 */
private function enable_browser_caching() {
    add_action('admin_init', array($this, 'update_htaccess'));
}

/**
 * .htaccess aktualisieren
 */
public function update_htaccess() {
    $htaccess_file = ABSPATH . '.htaccess';
    
    if (!is_writable($htaccess_file)) {
        return false;
    }
    
    $current_content = file_get_contents($htaccess_file);
    $marker_begin = '# BEGIN OlpoMizer Browser Caching';
    $marker_end = '# END OlpoMizer Browser Caching';
    
    // Alte Regeln entfernen falls vorhanden
    $pattern = '/' . preg_quote($marker_begin, '/') . '.*?' . preg_quote($marker_end, '/') . '\s*/s';
    $current_content = preg_replace($pattern, '', $current_content);
    
    // Neue Regeln hinzufügen
    $rules = $this->get_htaccess_rules();
    $new_content = $marker_begin . "\n" . $rules . "\n" . $marker_end . "\n\n" . $current_content;
    
    return file_put_contents($htaccess_file, $new_content, LOCK_EX);
}

/**
 * .htaccess Regeln generieren
 */
public function get_htaccess_rules() {
    return '<IfModule mod_expires.c>
    ExpiresActive On
    
    # Images
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    
    # Video
    ExpiresByType video/mp4 "access plus 1 year"
    ExpiresByType video/mpeg "access plus 1 year"
    
    # CSS, JavaScript
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    
    # Fonts
    ExpiresByType font/ttf "access plus 1 year"
    ExpiresByType font/otf "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"
    
    # Documents
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/html "access plus 0 seconds"
</IfModule>

<IfModule mod_headers.c>
    # Cache-Control für verschiedene Dateitypen
    <FilesMatch "\.(jpg|jpeg|png|gif|webp|svg|ico)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
    
    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "max-age=2592000, public"
    </FilesMatch>
    
    <FilesMatch "\.(woff|woff2|ttf|otf|eot)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
    
    # ETags entfernen
    Header unset ETag
</IfModule>

# ETags deaktivieren
FileETag None

# Kompression aktivieren
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>';
}

/**
 * .htaccess Regeln entfernen (bei Deaktivierung)
 */
public function remove_htaccess_rules() {
    $htaccess_file = ABSPATH . '.htaccess';
    
    if (!is_writable($htaccess_file)) {
        return false;
    }
    
    $current_content = file_get_contents($htaccess_file);
    $marker_begin = '# BEGIN OlpoMizer Browser Caching';
    $marker_end = '# END OlpoMizer Browser Caching';
    
    // Regeln entfernen
    $pattern = '/' . preg_quote($marker_begin, '/') . '.*?' . preg_quote($marker_end, '/') . '\s*/s';
    $new_content = preg_replace($pattern, '', $current_content);
    
    return file_put_contents($htaccess_file, $new_content, LOCK_EX);
}
    /**
 * URLs für Warmup abrufen (AJAX)
 */
public function ajax_get_warmup_urls() {
    check_ajax_referer('cwo_cache_nonce', 'nonce');
    
    $scope = $this->get_option('cache_warmup_scope', 'essential');
    $urls = $this->get_warmup_urls($scope);
    
    wp_send_json_success(array(
        'urls' => $urls,
        'count' => count($urls)
    ));
}

/**
 * Einzelne URL aufwärmen (AJAX)
 */
public function ajax_warmup_url() {
    check_ajax_referer('cwo_cache_nonce', 'nonce');
    
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    
    if (empty($url)) {
        wp_send_json_error(array('message' => 'Keine URL angegeben'));
    }
    
    // Request an die URL senden (dadurch wird sie gecached)
    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'sslverify' => false,
        'headers' => array(
            'User-Agent' => 'OlpoMizer Cache Warmup'
        )
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => $response->get_error_message(),
            'url' => $url
        ));
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    wp_send_json_success(array(
        'url' => $url,
        'status' => $status_code,
        'cached' => $status_code === 200
    ));
}

// ============================================
// WARMUP URL SAMMLER
// ============================================

/**
 * URLs zum Aufwärmen sammeln
 */
private function get_warmup_urls($scope = 'essential') {
    $urls = array();
    
    // IMMER: Homepage
    $urls[] = home_url('/');
    
    // Essential: Homepage + letzte 10 Posts
    if ($scope === 'essential') {
        $urls = array_merge($urls, $this->get_recent_post_urls(10));
    }
    
    // Extended: + alle Seiten + Archive
    elseif ($scope === 'extended') {
        $urls = array_merge($urls, $this->get_recent_post_urls(20));
        $urls = array_merge($urls, $this->get_all_page_urls());
        $urls = array_merge($urls, $this->get_archive_urls());
    }
    
    // Full: Alles
    elseif ($scope === 'full') {
        $urls = array_merge($urls, $this->get_all_post_urls());
        $urls = array_merge($urls, $this->get_all_page_urls());
        $urls = array_merge($urls, $this->get_archive_urls());
        $urls = array_merge($urls, $this->get_category_urls());
        $urls = array_merge($urls, $this->get_tag_urls());
    }
    
    // Duplikate entfernen
    $urls = array_unique($urls);
    
    // Ausgeschlossene URLs filtern
    $urls = $this->filter_excluded_urls($urls);
    
    return array_values($urls);
}

/**
 * Letzte X Posts
 */
private function get_recent_post_urls($count = 10) {
    $urls = array();
    
    $posts = get_posts(array(
        'posts_per_page' => $count,
        'post_status' => 'publish',
        'post_type' => 'post'
    ));
    
    foreach ($posts as $post) {
        $urls[] = get_permalink($post->ID);
    }
    
    return $urls;
}

/**
 * Alle Posts
 */
private function get_all_post_urls() {
    $urls = array();
    
    $posts = get_posts(array(
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'post_type' => 'post'
    ));
    
    foreach ($posts as $post) {
        $urls[] = get_permalink($post->ID);
    }
    
    return $urls;
}

/**
 * Alle Seiten
 */
private function get_all_page_urls() {
    $urls = array();
    
    $pages = get_pages(array(
        'post_status' => 'publish'
    ));
    
    foreach ($pages as $page) {
        $urls[] = get_permalink($page->ID);
    }
    
    return $urls;
}

/**
 * Archiv-URLs (Blog-Seite, etc.)
 */
private function get_archive_urls() {
    $urls = array();
    
    // Blog-Seite
    if (get_option('page_for_posts')) {
        $urls[] = get_permalink(get_option('page_for_posts'));
    }
    
    return $urls;
}

/**
 * Kategorie-URLs
 */
private function get_category_urls() {
    $urls = array();
    
    $categories = get_categories(array(
        'hide_empty' => true
    ));
    
    foreach ($categories as $category) {
        $urls[] = get_category_link($category->term_id);
    }
    
    return $urls;
}

/**
 * Tag-URLs
 */
private function get_tag_urls() {
    $urls = array();
    
    $tags = get_tags(array(
        'hide_empty' => true
    ));
    
    foreach ($tags as $tag) {
        $urls[] = get_tag_link($tag->term_id);
    }
    
    return $urls;
}

/**
 * Ausgeschlossene URLs filtern
 */
private function filter_excluded_urls($urls) {
    $excluded_patterns = $this->get_option('cache_exclude_urls', '');
    
    if (empty($excluded_patterns)) {
        return $urls;
    }
    
    $patterns = array_filter(array_map('trim', explode("\n", $excluded_patterns)));
    $filtered_urls = array();
    
    foreach ($urls as $url) {
        $url_path = parse_url($url, PHP_URL_PATH);
        $exclude = false;
        
        foreach ($patterns as $pattern) {
            $regex = str_replace(
                array('\*', '\?'),
                array('.*', '.'),
                preg_quote($pattern, '#')
            );
            
            if (preg_match('#^' . $regex . '$#i', $url_path)) {
                $exclude = true;
                break;
            }
        }
        
        if (!$exclude) {
            $filtered_urls[] = $url;
        }
    }
    
    return $filtered_urls;
}

// ============================================
// AUTO-WARMUP NACH POST UPDATE
// ============================================

/**
 * Automatischer Warmup nach Post-Update
 */
public function auto_warmup_on_save($post_id) {
    // Nur wenn Auto-Warmup aktiviert ist
    if ($this->get_option('cache_auto_warmup', '0') !== '1') {
        return;
    }
    
    // Keine Revisions
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Nur published Posts
    if (get_post_status($post_id) !== 'publish') {
        return;
    }
    
    // Post-URL direkt aufwärmen
    $url = get_permalink($post_id);
    
    wp_remote_get($url, array(
        'blocking' => false, // Non-blocking!
        'timeout' => 0.01,
        'headers' => array(
            'User-Agent' => 'OlpoMizer Auto-Warmup'
        )
    ));
    
    // Optional: Auch Homepage aufwärmen (da dort neue Posts erscheinen)
    wp_remote_get(home_url('/'), array(
        'blocking' => false,
        'timeout' => 0.01,
        'headers' => array(
            'User-Agent' => 'OlpoMizer Auto-Warmup'
        )
    ));
}
}
