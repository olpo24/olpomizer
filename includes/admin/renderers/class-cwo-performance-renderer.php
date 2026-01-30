<?php
/**
 * Performance Module Renderer
 * Rendert Performance-spezifische Sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Performance_Renderer {
    
    /**
     * Performance General Section rendern
     */
    public function render_general_section($section, $category) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Optimiere WordPress durch Deaktivierung unnötiger Features</p>
        </div>
        
        <?php 
        foreach ($category['modules'] as $module_id => $module):
            if ($module_id === 'performance') {
                ?>
                <div class="olpo-performance-settings">
                    <?php $module->render_settings(); ?>
                </div>
                <?php
            }
        endforeach;
        ?>
        <?php
    }
    
    /**
     * Performance Cache Section rendern
     */
    public function render_cache_section($section, $category) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Konfiguriere den Page Cache für maximale Performance</p>
        </div>
        
        <?php 
        foreach ($category['modules'] as $module_id => $module):
            if ($module_id === 'performance') {
                $this->render_performance_cache($module);
            }
        endforeach;
        ?>
        <?php
    }
    
    /**
     * Performance Cache Settings rendern
     */
    private function render_performance_cache($module) {
        $cache_enabled = $module->get_option('cache_enabled', '0');
        $cache_exclude_urls = $module->get_option('cache_exclude_urls', '');
        $cache_exclude_css = $module->get_option('cache_exclude_css', '');
        $cache_exclude_js = $module->get_option('cache_exclude_js', '');
        $browser_caching_enabled = $module->get_option('browser_caching_enabled', '0');
        
        // Cache Statistiken abrufen
        $cache_stats = $module->get_cache_stats();
        ?>
        
        <div class="olpo-performance-settings">
            <h3 style="margin-top: 0;">Cache Aktivierung</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Page Cache</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_cache_enabled" value="1" <?php checked($cache_enabled, '1'); ?>>
                            Page Cache aktivieren
                        </label>
                        <p class="description">Speichert generierte HTML-Seiten als statische Dateien für schnellere Ladezeiten.</p>
                    </td>
                </tr>
            </table>
            
            <?php if ($cache_enabled === '1'): ?>
            <hr style="margin: 30px 0;">
            
            <h3>Cache Statistiken</h3>
            <div style="background: #f6f7f7; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div>
                        <div style="font-size: 24px; font-weight: 600; color: #2271b1;"><?php echo esc_html($cache_stats['files']); ?></div>
                        <div style="color: #646970;">Gecachte Seiten</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 600; color: #2271b1;"><?php echo esc_html($cache_stats['size']); ?></div>
                        <div style="color: #646970;">Cache Größe</div>
                    </div>
                    <div>
                        <div style="font-size: 24px; font-weight: 600; color: #2271b1;"><?php echo esc_html($cache_stats['last_cleared']); ?></div>
                        <div style="color: #646970;">Zuletzt geleert</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <button type="button" class="button button-primary" onclick="cwoClearCache()">
                    <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Gesamten Cache leeren
                </button>
                <span id="cache-clear-result" style="margin-left: 10px;"></span>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h3>Cache Ausschlüsse</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">URLs vom Cache ausschließen</th>
                    <td>
                        <textarea name="cwo_cache_exclude_urls" rows="5" class="large-text code"><?php echo esc_textarea($cache_exclude_urls); ?></textarea>
                        <p class="description">
                            Eine URL pro Zeile. Unterstützt Wildcards (*). Beispiele:<br>
                            <code>/warenkorb/*</code><br>
                            <code>/mein-konto/*</code><br>
                            <code>/checkout/*</code><br>
                            <code>*.pdf</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">CSS-Dateien vom Cache ausschließen</th>
                    <td>
                        <textarea name="cwo_cache_exclude_css" rows="3" class="large-text code"><?php echo esc_textarea($cache_exclude_css); ?></textarea>
                        <p class="description">
                            Eine Datei pro Zeile. Beispiel: <code>custom-styles.css</code> oder <code>*/dynamic.css</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">JavaScript-Dateien vom Cache ausschließen</th>
                    <td>
                        <textarea name="cwo_cache_exclude_js" rows="3" class="large-text code"><?php echo esc_textarea($cache_exclude_js); ?></textarea>
                        <p class="description">
                            Eine Datei pro Zeile. Beispiel: <code>analytics.js</code> oder <code>*/tracking.js</code>
                        </p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h3>Cache Warmup</h3>
            <?php 
            $cache_auto_warmup = $module->get_option('cache_auto_warmup', '0');
            $cache_warmup_scope = $module->get_option('cache_warmup_scope', 'essential');
            $cache_warmup_delay = $module->get_option('cache_warmup_delay', '100');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Automatischer Warmup</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_cache_auto_warmup" value="1" <?php checked($cache_auto_warmup, '1'); ?>>
                            Cache automatisch aufwärmen nach Post-Updates
                        </label>
                        <p class="description">Wärmt die Post-URL und Homepage automatisch nach dem Speichern auf.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Warmup-Umfang</th>
                    <td>
                        <select name="cwo_cache_warmup_scope">
                            <option value="essential" <?php selected($cache_warmup_scope, 'essential'); ?>>Essential (Homepage + 10 neueste Posts)</option>
                            <option value="extended" <?php selected($cache_warmup_scope, 'extended'); ?>>Extended (+ alle Seiten + Archive)</option>
                            <option value="full" <?php selected($cache_warmup_scope, 'full'); ?>>Full (alle Inhalte + Kategorien + Tags)</option>
                        </select>
                        <p class="description">Bestimmt welche URLs beim manuellen Warmup berücksichtigt werden.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Warmup-Verzögerung</th>
                    <td>
                        <input type="number" name="cwo_cache_warmup_delay" value="<?php echo esc_attr($cache_warmup_delay); ?>" min="50" max="5000" step="50" class="small-text"> Millisekunden
                        <p class="description">Verzögerung zwischen einzelnen Requests beim Warmup (Standard: 100ms).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Manueller Warmup</th>
                    <td>
                        <button type="button" class="button button-primary" onclick="cwoStartCacheWarmup()">
                            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Cache jetzt aufwärmen
                        </button>
                        <div id="warmup-progress" style="margin-top: 15px; display: none;">
                            <div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
                                <div style="margin-bottom: 10px;">
                                    <strong>Fortschritt:</strong> 
                                    <span id="warmup-current">0</span> / <span id="warmup-total">0</span> URLs
                                </div>
                                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; overflow: hidden; height: 20px;">
                                    <div id="warmup-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
                                </div>
                                <div id="warmup-status" style="margin-top: 10px; color: #646970; font-size: 12px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <?php endif; ?>
            
            <hr style="margin: 30px 0;">
            
            <h3>Browser Caching (.htaccess)</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Browser Caching Regeln</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_browser_caching_enabled" value="1" <?php checked($browser_caching_enabled, '1'); ?>>
                            Leverage Browser Caching via .htaccess aktivieren
                        </label>
                        <p class="description">Fügt optimierte Caching-Regeln zur .htaccess Datei hinzu.</p>
                        
                        <?php if ($browser_caching_enabled === '1'): ?>
                            <div style="margin-top: 15px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                                <strong style="color: #155724;">✓ Browser Caching ist aktiv</strong>
                                <p style="margin: 10px 0 0 0; color: #155724;">Die .htaccess wurde erfolgreich aktualisiert.</p>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                                <strong style="color: #856404;">⚠ Hinweis</strong>
                                <p style="margin: 10px 0 0 0; color: #856404;">
                                    Die .htaccess Datei muss beschreibbar sein. Aktueller Status: 
                                    <code><?php echo is_writable(ABSPATH . '.htaccess') ? 'Beschreibbar ✓' : 'Nicht beschreibbar ✗'; ?></code>
                                </p>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php if ($browser_caching_enabled === '1'): ?>
            <div style="margin-top: 20px;">
                <details style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
                    <summary style="cursor: pointer; font-weight: 600; user-select: none;">
                        <span class="dashicons dashicons-editor-code" style="margin-right: 5px;"></span>
                        .htaccess Regeln anzeigen
                    </summary>
                    <pre style="margin-top: 15px; padding: 15px; background: #1e1e1e; color: #d4d4d4; border-radius: 4px; overflow-x: auto; font-size: 12px; line-height: 1.5;"><?php echo esc_html($module->get_htaccess_rules()); ?></pre>
                </details>
            </div>
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        var cacheNonce = '<?php echo wp_create_nonce('cwo_cache_nonce'); ?>';
        
        function cwoClearCache() {
            var result = document.getElementById('cache-clear-result');
            result.textContent = 'Leere Cache...';
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
                            
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            result.textContent = 'Fehler: ' + (response.data.message || 'Unbekannter Fehler');
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
            
            xhr.send('action=cwo_clear_cache&nonce=' + cacheNonce);
        }
        
        // Cache Warmup Funktion
        function cwoStartCacheWarmup() {
            var progressDiv = document.getElementById('warmup-progress');
            var statusDiv = document.getElementById('warmup-status');
            var currentSpan = document.getElementById('warmup-current');
            var totalSpan = document.getElementById('warmup-total');
            var barDiv = document.getElementById('warmup-bar');
            
            progressDiv.style.display = 'block';
            statusDiv.textContent = 'Sammle URLs...';
            
            // 1. URLs abrufen
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data.urls) {
                            var urls = response.data.urls;
                            totalSpan.textContent = urls.length;
                            
                            // 2. URLs der Reihe nach aufwärmen
                            var delay = <?php echo intval($module->get_option('cache_warmup_delay', '100')); ?>;
                            var current = 0;
                            
                            function warmupNext() {
                                if (current >= urls.length) {
                                    statusDiv.textContent = 'Warmup abgeschlossen! ✓';
                                    statusDiv.style.color = '#46b450';
                                    return;
                                }
                                
                                var url = urls[current];
                                statusDiv.textContent = 'Wärme auf: ' + url;
                                
                                var warmupXhr = new XMLHttpRequest();
                                warmupXhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                                warmupXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                
                                warmupXhr.onload = function() {
                                    current++;
                                    currentSpan.textContent = current;
                                    var percent = (current / urls.length) * 100;
                                    barDiv.style.width = percent + '%';
                                    
                                    setTimeout(warmupNext, delay);
                                };
                                
                                warmupXhr.onerror = function() {
                                    current++;
                                    setTimeout(warmupNext, delay);
                                };
                                
                                warmupXhr.send('action=cwo_warmup_url&nonce=' + cacheNonce + '&url=' + encodeURIComponent(url));
                            }
                            
                            warmupNext();
                        } else {
                            statusDiv.textContent = 'Fehler beim Abrufen der URLs.';
                            statusDiv.style.color = 'red';
                        }
                    } catch(e) {
                        statusDiv.textContent = 'Fehler: ' + e.message;
                        statusDiv.style.color = 'red';
                    }
                }
            };
            
            xhr.send('action=cwo_get_warmup_urls&nonce=' + cacheNonce);
        }
        </script>
        <?php
    }
}
