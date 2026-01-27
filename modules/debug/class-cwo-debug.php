<?php
/**
 * WP Debug Modul
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Debug_Module extends CWO_Module_Base {
    
    public function __construct() {
        $this->id = 'debug';
        $this->name = 'WordPress Debug Modus';
        $this->description = 'Aktiviere WP_DEBUG und konfiguriere Debug-Ausgaben für die Entwicklung.';
    }
    
    /**
     * Modul initialisieren
     */
    public function init() {
        // Debug-Einstellungen in wp-config.php schreiben
        add_action('admin_init', array($this, 'update_wp_config'));
        
        // AJAX Handlers
        add_action('wp_ajax_cwo_get_debug_log', array($this, 'ajax_get_debug_log'));
        add_action('wp_ajax_cwo_clear_debug_log', array($this, 'ajax_clear_debug_log'));
    }
    
    /**
     * wp-config.php aktualisieren
     */
    public function update_wp_config() {
        $display = $this->get_option('display', '1');
        $log_to_file = $this->get_option('log_to_file', '0');
        
        // Wir setzen die Konstanten direkt, falls sie noch nicht gesetzt sind
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        if ($display === '1' && !defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', true);
        } elseif ($display === '0' && !defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', false);
        }
        
        if ($log_to_file === '1' && !defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
    }
    
    /**
     * Debug Log abrufen (AJAX)
     */
    public function ajax_get_debug_log() {
        check_ajax_referer('cwo_debug_nonce', 'nonce');
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            wp_send_json_success(array(
                'content' => 'Keine Debug-Log Datei vorhanden.',
                'size' => 0
            ));
        }
        
        $content = file_get_contents($log_file);
        $size = filesize($log_file);
        
        // Nur die letzten 500 Zeilen anzeigen
        $lines = explode("\n", $content);
        $lines = array_slice($lines, -500);
        $content = implode("\n", $lines);
        
        wp_send_json_success(array(
            'content' => $content,
            'size' => size_format($size)
        ));
    }
    
    /**
     * Debug Log löschen (AJAX)
     */
    public function ajax_clear_debug_log() {
        check_ajax_referer('cwo_debug_nonce', 'nonce');
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (file_exists($log_file)) {
            $result = unlink($log_file);
            
            if ($result) {
                wp_send_json_success(array('message' => 'Debug-Log erfolgreich gelöscht.'));
            } else {
                wp_send_json_error(array('message' => 'Fehler beim Löschen der Debug-Log Datei.'));
            }
        } else {
            wp_send_json_success(array('message' => 'Keine Debug-Log Datei vorhanden.'));
        }
    }
    
    /**
     * Settings-Felder rendern
     */
    public function render_settings() {
        $display = $this->get_option('display', '1');
        $log_to_file = $this->get_option('log_to_file', '0');
        $log_file = WP_CONTENT_DIR . '/debug.log';
        $log_exists = file_exists($log_file);
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
        
        <hr style="margin: 30px 0;">
        
        <h3>Debug Log Viewer</h3>
        <p class="description">
            <?php if ($log_exists): ?>
                Log-Datei: <code><?php echo esc_html($log_file); ?></code> 
                | Größe: <strong id="debug-log-size"><?php echo size_format(filesize($log_file)); ?></strong>
            <?php else: ?>
                Keine Debug-Log Datei vorhanden.
            <?php endif; ?>
        </p>
        
        <div style="margin: 15px 0;">
            <button type="button" class="button" onclick="cwoLoadDebugLog()">
                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Aktualisieren
            </button>
            <button type="button" class="button" onclick="cwoDownloadDebugLog()">
                <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Herunterladen
            </button>
            <button type="button" class="button button-danger" onclick="cwoClearDebugLog()" style="color: #b32d2e;">
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
        var debugLogFile = '<?php echo esc_js($log_file); ?>';
        
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
                            
                            // Auto-scroll nach unten
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
            
            var container = document.getElementById('debug-log-container');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            container.innerHTML = '<span style="color: #4ade80;">Debug-Log wurde gelöscht.</span>';
                            
                            var sizeElement = document.getElementById('debug-log-size');
                            if (sizeElement) {
                                sizeElement.textContent = '0 B';
                            }
                        } else {
                            alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                        }
                    } catch(e) {
                        alert('Fehler beim Parsen der Antwort.');
                    }
                } else {
                    alert('Fehler bei der Anfrage.');
                }
            };
            
            xhr.send('action=cwo_clear_debug_log&nonce=' + debugNonce);
        }
        
        function cwoDownloadDebugLog() {
            window.location.href = debugLogFile.replace('/var/www/vhosts/', '/').replace('/httpdocs', '');
            // Alternativ: direkter Download über PHP
            var downloadUrl = '<?php echo content_url('debug.log'); ?>';
            window.open(downloadUrl, '_blank');
        }
        
        // Auto-load beim Öffnen des Moduls
        document.addEventListener('DOMContentLoaded', function() {
            // Nur laden wenn das Modul sichtbar ist
            var container = document.getElementById('debug-log-container');
            if (container && container.closest('.cwo-module-settings').classList.contains('active')) {
                cwoLoadDebugLog();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Settings speichern
     */
    public function save_settings($post_data) {
        $display = isset($post_data['cwo_debug_display']) ? '1' : '0';
        $log_to_file = isset($post_data['cwo_debug_log_to_file']) ? '1' : '0';
        
        $this->update_option('display', $display);
        $this->update_option('log_to_file', $log_to_file);
        
        // wp-config.php Code generieren für manuelle Anpassung
        $this->generate_wp_config_instructions($display, $log_to_file);
    }
    
    /**
     * Generiert Anweisungen für wp-config.php
     */
    private function generate_wp_config_instructions($display, $log_to_file) {
        $code = "\n// Debug Einstellungen (von OlpoMizer generiert)\n";
        $code .= "define('WP_DEBUG', true);\n";
        $code .= "define('WP_DEBUG_DISPLAY', " . ($display === '1' ? 'true' : 'false') . ");\n";
        
        if ($log_to_file === '1') {
            $code .= "define('WP_DEBUG_LOG', true);\n";
        }
        
        $code .= "define('SCRIPT_DEBUG', true);\n";
        
        // Als Transient speichern für Anzeige
        set_transient('cwo_debug_config_code', $code, 300);
    }
}
