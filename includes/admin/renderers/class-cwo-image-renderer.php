<?php
/**
 * Image Optimization Renderer
 * Rendert die Bild-Optimierungs Sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Image_Renderer {
    
    /**
     * General Settings Section rendern
     */
    public function render_general_section($section, $category) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Konfiguriere automatische Bildoptimierung und Qualitätseinstellungen</p>
        </div>
        
        <?php 
        foreach ($category['modules'] as $module_id => $module):
            if ($module_id === 'image-optimization') {
                $this->render_optimization_settings($module);
            }
        endforeach;
        ?>
        <?php
    }
    
    /**
     * Batch Processing Section rendern
     */
    public function render_batch_section($section, $category) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Optimiere alle vorhandenen Bilder in deiner Mediathek</p>
        </div>
        
        <?php 
        foreach ($category['modules'] as $module_id => $module):
            if ($module_id === 'image-optimization') {
                $this->render_batch_processor($module);
            }
        endforeach;
        ?>
        <?php
    }
    
    /**
     * WebP Settings Section rendern
     */
    public function render_webp_section($section, $category) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Konvertiere Bilder zu WebP für bessere Performance</p>
        </div>
        
        <?php 
        foreach ($category['modules'] as $module_id => $module):
            if ($module_id === 'image-optimization') {
                $this->render_webp_settings($module);
            }
        endforeach;
        ?>
        <?php
    }
    
    /**
     * Optimization Settings rendern
     */
    private function render_optimization_settings($module) {
        $auto_optimize = $module->get_option('auto_optimize', '0');
        $jpeg_quality = $module->get_option('jpeg_quality', '85');
        $progressive_jpeg = $module->get_option('progressive_jpeg', '1');
        $strip_exif = $module->get_option('strip_exif', '1');
        $create_backups = $module->get_option('create_backups', '1');
        $max_width = $module->get_option('max_width', '0');
        $max_height = $module->get_option('max_height', '0');
        $force_lazy_loading = $module->get_option('force_lazy_loading', '0');
        $disable_responsive = $module->get_option('disable_responsive', '0');
        $use_optipng = $module->get_option('use_optipng', '0');
        ?>
        
        <div class="olpo-performance-settings">
            <h3 style="margin-top: 0;">Automatische Optimierung</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Beim Upload optimieren</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_auto_optimize" value="1" <?php checked($auto_optimize, '1'); ?>>
                            Bilder automatisch beim Upload optimieren
                        </label>
                        <p class="description">Neue Bilder werden direkt nach dem Hochladen komprimiert.</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h3>Bildqualität</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">JPEG Qualität</th>
                    <td>
                        <input type="range" 
                               name="cwo_img_jpeg_quality" 
                               value="<?php echo esc_attr($jpeg_quality); ?>" 
                               min="1" 
                               max="100" 
                               step="1"
                               oninput="document.getElementById('jpeg-quality-value').textContent = this.value"
                               style="width: 300px; vertical-align: middle;">
                        <strong id="jpeg-quality-value" style="margin-left: 15px; font-size: 16px;"><?php echo esc_html($jpeg_quality); ?></strong>%
                        <p class="description">
                            Empfohlen: 80-90 für Web. Niedrigere Werte = kleinere Dateien, niedrigere Qualität.<br>
                            <strong>60-70:</strong> Hohe Kompression, sichtbarer Qualitätsverlust<br>
                            <strong>80-85:</strong> Gute Balance (empfohlen für die meisten Websites)<br>
                            <strong>90-95:</strong> Hohe Qualität, größere Dateien
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Progressive JPEG</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_progressive_jpeg" value="1" <?php checked($progressive_jpeg, '1'); ?>>
                            Progressive JPEG aktivieren
                        </label>
                        <p class="description">Lädt Bilder progressiv (von unscharf zu scharf). Bessere User Experience bei langsamem Internet.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">EXIF-Daten</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_strip_exif" value="1" <?php checked($strip_exif, '1'); ?>>
                            EXIF-Metadaten aus Bildern entfernen
                        </label>
                        <p class="description">Entfernt Kamera-Metadaten, GPS-Daten, etc. Reduziert Dateigröße und schützt Privatsphäre.</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h3>Bildabmessungen</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Maximale Breite</th>
                    <td>
                        <input type="number" 
                               name="cwo_img_max_width" 
                               value="<?php echo esc_attr($max_width); ?>" 
                               min="0" 
                               step="1" 
                               class="small-text"> Pixel
                        <p class="description">Bilder werden automatisch verkleinert. 0 = keine Begrenzung. Empfohlen: 2000-2500px</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Maximale Höhe</th>
                    <td>
                        <input type="number" 
                               name="cwo_img_max_height" 
                               value="<?php echo esc_attr($max_height); ?>" 
                               min="0" 
                               step="1" 
                               class="small-text"> Pixel
                        <p class="description">Bilder werden automatisch verkleinert. 0 = keine Begrenzung.</p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h3>Erweiterte Optionen</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Backups</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_create_backups" value="1" <?php checked($create_backups, '1'); ?>>
                            Backup vor Optimierung erstellen
                        </label>
                        <p class="description">Erstellt eine Sicherheitskopie in <code>/wp-content/uploads/olpomizer-backups/</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">PNG Optimierung (OptiPNG)</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_use_optipng" value="1" <?php checked($use_optipng, '1'); ?>>
                            OptiPNG verwenden (falls verfügbar)
                        </label>
                        <p class="description">
                            Verwendet das OptiPNG-Tool für verlustfreie PNG-Kompression.<br>
                            <?php if ($this->command_exists('optipng')): ?>
                                <span style="color: #46b450;">✓ OptiPNG ist installiert</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ OptiPNG ist nicht verfügbar</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Lazy Loading</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_force_lazy_loading" value="1" <?php checked($force_lazy_loading, '1'); ?>>
                            Lazy Loading für alle Bilder erzwingen
                        </label>
                        <p class="description">Lädt Bilder erst wenn sie im Viewport erscheinen. Verbessert Ladezeit.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Responsive Images</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_disable_responsive" value="1" <?php checked($disable_responsive, '1'); ?>>
                            WordPress Responsive Images deaktivieren
                        </label>
                        <p class="description">Verhindert automatische Erstellung von srcset. Nur aktivieren wenn du eigene Lösung verwendest.</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Batch Processor rendern
     */
    private function render_batch_processor($module) {
        ?>
        <div class="olpo-performance-settings">
            <h3 style="margin-top: 0;">Statistiken</h3>
            
            <div style="background: #f6f7f7; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    <div>
                        <div style="font-size: 28px; font-weight: 600; color: #2271b1;" id="stat-total-images">-</div>
                        <div style="color: #646970; margin-top: 5px;">Bilder gesamt</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: 600; color: #46b450;" id="stat-optimized">-</div>
                        <div style="color: #646970; margin-top: 5px;">Optimiert</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: 600; color: #2271b1;" id="stat-saved">-</div>
                        <div style="color: #646970; margin-top: 5px;">Gespart</div>
                    </div>
                    <div>
                        <div style="font-size: 28px; font-weight: 600; color: #2271b1;" id="stat-webp-count">-</div>
                        <div style="color: #646970; margin-top: 5px;">WebP Dateien</div>
                    </div>
                </div>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h3>Batch-Optimierung</h3>
            <p class="description" style="margin-bottom: 20px;">
                Optimiere alle vorhandenen Bilder in deiner Mediathek. Je nach Anzahl kann dies einige Zeit dauern.
            </p>
            
            <div style="margin-bottom: 20px;">
                <button type="button" class="button button-primary button-large" onclick="cwoStartBatchOptimization()">
                    <span class="dashicons dashicons-images-alt2" style="margin-top: 3px;"></span> 
                    Batch-Optimierung starten
                </button>
                <button type="button" class="button button-large" onclick="cwoRefreshImageStats()" style="margin-left: 10px;">
                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> 
                    Statistiken aktualisieren
                </button>
            </div>
            
            <div id="batch-progress-container" style="display: none; margin-top: 30px;">
                <div style="background: #f6f7f7; padding: 20px; border-radius: 4px;">
                    <div style="margin-bottom: 15px;">
                        <strong>Fortschritt:</strong>
                        <span id="batch-current">0</span> / <span id="batch-total">0</span> Bilder
                        <span id="batch-status" style="margin-left: 15px; color: #646970;"></span>
                    </div>
                    
                    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; overflow: hidden; height: 30px; margin-bottom: 15px;">
                        <div id="batch-progress-bar" style="background: linear-gradient(90deg, #2271b1 0%, #135e96 100%); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 12px;">
                            <span id="batch-percentage">0%</span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                        <div>
                            <div style="color: #646970; font-size: 12px; margin-bottom: 5px;">Optimiert</div>
                            <div style="font-size: 20px; font-weight: 600; color: #46b450;" id="batch-optimized-count">0</div>
                        </div>
                        <div>
                            <div style="color: #646970; font-size: 12px; margin-bottom: 5px;">Fehler</div>
                            <div style="font-size: 20px; font-weight: 600; color: #dc3232;" id="batch-error-count">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        var imageNonce = '<?php echo wp_create_nonce('cwo_image_nonce'); ?>';
        var batchRunning = false;
        var totalOptimized = 0;
        var totalErrors = 0;
        
        // Statistiken beim Laden abrufen
        document.addEventListener('DOMContentLoaded', function() {
            cwoRefreshImageStats();
        });
        
        function cwoRefreshImageStats() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            document.getElementById('stat-total-images').textContent = response.data.total_images;
                            document.getElementById('stat-optimized').textContent = response.data.images_optimized;
                            document.getElementById('stat-saved').textContent = response.data.total_saved;
                            document.getElementById('stat-webp-count').textContent = response.data.webp_count;
                        }
                    } catch(e) {
                        console.error('Fehler beim Parsen der Statistiken:', e);
                    }
                }
            };
            
            xhr.send('action=cwo_get_image_stats&nonce=' + imageNonce);
        }
        
        function cwoStartBatchOptimization() {
            if (batchRunning) {
                alert('Batch-Optimierung läuft bereits!');
                return;
            }
            
            if (!confirm('Möchtest du wirklich alle Bilder optimieren? Dies kann je nach Anzahl lange dauern.')) {
                return;
            }
            
            batchRunning = true;
            totalOptimized = 0;
            totalErrors = 0;
            
            document.getElementById('batch-progress-container').style.display = 'block';
            document.getElementById('batch-status').textContent = 'Starte...';
            
            // Gesamtzahl abrufen
            var totalImages = parseInt(document.getElementById('stat-total-images').textContent);
            document.getElementById('batch-total').textContent = totalImages;
            
            // Batch-Verarbeitung starten
            processBatch(0, totalImages);
        }
        
        function processBatch(offset, total) {
            if (!batchRunning) return;
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            totalOptimized += response.data.optimized;
                            totalErrors += response.data.errors;
                            
                            var processed = offset + 10;
                            var percentage = Math.min(100, Math.round((processed / total) * 100));
                            
                            document.getElementById('batch-current').textContent = Math.min(processed, total);
                            document.getElementById('batch-progress-bar').style.width = percentage + '%';
                            document.getElementById('batch-percentage').textContent = percentage + '%';
                            document.getElementById('batch-optimized-count').textContent = totalOptimized;
                            document.getElementById('batch-error-count').textContent = totalErrors;
                            document.getElementById('batch-status').textContent = 'Verarbeite Bilder...';
                            
                            // Weiter wenn noch Bilder übrig
                            if (processed < total) {
                                setTimeout(function() {
                                    processBatch(processed, total);
                                }, 500);
                            } else {
                                // Fertig!
                                batchRunning = false;
                                document.getElementById('batch-status').textContent = 'Abgeschlossen!';
                                document.getElementById('batch-status').style.color = '#46b450';
                                
                                // Statistiken aktualisieren
                                setTimeout(cwoRefreshImageStats, 1000);
                            }
                        } else {
                            batchRunning = false;
                            document.getElementById('batch-status').textContent = 'Fehler aufgetreten!';
                            document.getElementById('batch-status').style.color = '#dc3232';
                        }
                    } catch(e) {
                        batchRunning = false;
                        console.error('Fehler:', e);
                    }
                }
            };
            
            xhr.send('action=cwo_batch_optimize&nonce=' + imageNonce + '&limit=10&offset=' + offset);
        }
        </script>
        <?php
    }
    
    /**
     * WebP Settings rendern
     */
    private function render_webp_settings($module) {
        $webp_enabled = $module->get_option('webp_enabled', '0');
        $webp_quality = $module->get_option('webp_quality', '85');
        $webp_delete_original = $module->get_option('webp_delete_original', '0');
        $webp_htaccess = $module->get_option('webp_htaccess', '0');
        ?>
        
        <div class="olpo-performance-settings">
            <h3 style="margin-top: 0;">WebP Konvertierung</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">WebP erstellen</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_webp_enabled" value="1" <?php checked($webp_enabled, '1'); ?>>
                            WebP-Versionen automatisch erstellen
                        </label>
                        <p class="description">
                            Erstellt zusätzlich zu JPG/PNG auch WebP-Versionen. WebP ist moderner und bis zu 30% kleiner.<br>
                            <?php if (function_exists('imagewebp')): ?>
                                <span style="color: #46b450;">✓ WebP-Unterstützung verfügbar</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ WebP-Unterstützung nicht verfügbar (GD Library fehlt)</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">WebP Qualität</th>
                    <td>
                        <input type="range" 
                               name="cwo_img_webp_quality" 
                               value="<?php echo esc_attr($webp_quality); ?>" 
                               min="1" 
                               max="100" 
                               step="1"
                               oninput="document.getElementById('webp-quality-value').textContent = this.value"
                               style="width: 300px; vertical-align: middle;">
                        <strong id="webp-quality-value" style="margin-left: 15px; font-size: 16px;"><?php echo esc_html($webp_quality); ?></strong>%
                        <p class="description">Empfohlen: 80-85 für beste Kompression bei guter Qualität.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Original löschen</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_webp_delete_original" value="1" <?php checked($webp_delete_original, '1'); ?>>
                            Original JPG/PNG nach WebP-Konvertierung löschen
                        </label>
                        <p class="description" style="color: #dc3232;">
                            <strong>Achtung:</strong> Nur aktivieren wenn du sicher bist, dass alle Browser WebP unterstützen!
                        </p>
                    </td>
                </tr>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h3>WebP Auslieferung</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Automatische WebP Auslieferung</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cwo_img_webp_htaccess" value="1" <?php checked($webp_htaccess, '1'); ?>>
                            WebP via .htaccess automatisch ausliefern
                        </label>
                        <p class="description">
                            Fügt .htaccess-Regeln hinzu die automatisch WebP-Versionen ausliefern wenn der Browser es unterstützt.<br>
                            <?php if (is_writable(ABSPATH . '.htaccess')): ?>
                                <span style="color: #46b450;">✓ .htaccess ist beschreibbar</span>
                            <?php else: ?>
                                <span style="color: #dc3232;">✗ .htaccess ist nicht beschreibbar</span>
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($webp_htaccess === '1'): ?>
                            <div style="margin-top: 15px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                                <strong style="color: #155724;">✓ WebP Auslieferung ist aktiv</strong>
                                <p style="margin: 10px 0 0 0; color: #155724;">
                                    Browser die WebP unterstützen erhalten automatisch die kleineren WebP-Versionen.
                                </p>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php if ($webp_htaccess === '1'): ?>
            <div style="margin-top: 20px;">
                <details style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
                    <summary style="cursor: pointer; font-weight: 600; user-select: none;">
                        <span class="dashicons dashicons-editor-code" style="margin-right: 5px;"></span>
                        .htaccess WebP Regeln anzeigen
                    </summary>
                    <pre style="margin-top: 15px; padding: 15px; background: #1e1e1e; color: #d4d4d4; border-radius: 4px; overflow-x: auto; font-size: 12px; line-height: 1.5;"><?php echo esc_html($module->get_htaccess_webp_rules()); ?></pre>
                </details>
            </div>
            <?php endif; ?>
            
            <hr style="margin: 30px 0;">
            
            <h3>Browser-Kompatibilität</h3>
            <div style="background: #f6f7f7; padding: 20px; border-radius: 4px;">
                <p style="margin: 0 0 15px 0;"><strong>WebP wird unterstützt von:</strong></p>
                <ul style="margin: 0; padding-left: 25px;">
                    <li>Chrome 23+</li>
                    <li>Firefox 65+</li>
                    <li>Edge 18+</li>
                    <li>Safari 14+ (macOS 11+, iOS 14+)</li>
                    <li>Opera 12.1+</li>
                </ul>
                <p style="margin: 15px 0 0 0; color: #646970;">
                    Mit der .htaccess-Auslieferung erhalten ältere Browser automatisch die JPG/PNG-Versionen.
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Prüfen ob Kommando verfügbar ist
     */
    private function command_exists($command) {
        $return = shell_exec(sprintf("which %s", escapeshellarg($command)));
        return !empty($return);
    }
}
