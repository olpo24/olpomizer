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
    }
}
