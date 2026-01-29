<?php
/**
 * Debug Module Renderer
 * Rendert Debug-spezifische Sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Debug_Renderer {
    
    /**
     * Debug Settings Section rendern
     */
    public function render_settings_section($section, $category, $enabled_modules) {
        ?>
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
                
                <div class="olpo-module-settings <?php echo $is_enabled ? 'show' : ''; ?>">
                    <?php $this->render_debug_settings_only($module); ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
    }
    
    /**
     * Debug Viewer Section rendern
     */
    public function render_viewer_section($section, $category) {
        ?>
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
        #debug-log-container::-webkit-scrollbar { width: 10px; }
        #debug-log-container::-webkit-scrollbar-track { background: #2d2d2d; }
        #debug-log-container::-webkit-scrollbar-thumb { background: #555; border-radius: 5px; }
        #debug-log-container::-webkit-scrollbar-thumb:hover { background: #777; }
        </style>
        
        <script type="text/javascript">
        var debugNonce = '<?php echo wp_create_nonce('cwo_debug_nonce'); ?>';
        
        function cwoLoadDebugLog() {
            var container = document.getElementById('debug-log-container');
            container.innerHTML = '<span style="color:#888;">Lade Debug-Log...</span>';
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
                            if (sizeElement) sizeElement.textContent = response.data.size;
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
            if (!confirm('Möchtest du das Debug-Log wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) return;
            
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
                            if (sizeElement) sizeElement.textContent = '0 B';
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
}
