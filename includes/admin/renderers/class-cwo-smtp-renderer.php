<?php
/**
 * SMTP Module Renderer
 * Rendert SMTP-spezifische Sections
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_SMTP_Renderer {
    
    /**
     * SMTP Settings Section rendern
     */
    public function render_smtp_section($section, $category, $enabled_modules) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Konfiguriere deine SMTP-Einstellungen für den E-Mail-Versand</p>
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
                    <?php $module->render_settings(); ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
    }
    
    /**
     * Email Log Viewer Section rendern
     */
    public function render_log_section($section, $category) {
        ?>
        <div class="olpo-section-header">
            <h2 class="olpo-section-title"><?php echo esc_html($section['title']); ?></h2>
            <p class="olpo-section-description">Protokolliere und überwache alle von WordPress versendeten E-Mails</p>
        </div>
        
        <?php 
        foreach ($category['modules'] as $module_id => $module):
            if ($module_id === 'smtp') {
                $this->render_email_log_viewer($module);
            }
        endforeach;
        ?>
        <?php
    }
    
    /**
     * Email Log Viewer rendern
     */
    private function render_email_log_viewer($module) {
        ?>
        <p class="description" style="margin-bottom: 15px;">
            Hier werden alle von WordPress versendeten E-Mails protokolliert. Das Log zeigt Empfänger, Betreff, Status und Zeitstempel.
        </p>
        
        <div style="margin: 15px 0;">
            <button type="button" class="button button-primary" onclick="cwoLoadEmailLog()">
                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Aktualisieren
            </button>
            <button type="button" class="button" onclick="cwoClearEmailLog()" style="color: #b32d2e;">
                <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Log leeren
            </button>
        </div>
        
        <div id="email-log-container" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; overflow: hidden;">
            <table class="wp-list-table widefat fixed striped" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Zeit</th>
                        <th style="width: 20%;">Empfänger</th>
                        <th style="width: 35%;">Betreff</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 15%;">Aktion</th>
                    </tr>
                </thead>
                <tbody id="email-log-tbody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #646970;">
                            Klicke auf "Aktualisieren" um das E-Mail Log zu laden...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Email Detail Modal -->
        <div id="email-detail-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; align-items: center; justify-content: center;">
            <div style="background: #fff; max-width: 800px; max-height: 90vh; overflow-y: auto; border-radius: 4px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <div style="padding: 20px; border-bottom: 1px solid #ccd0d4; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;">E-Mail Details</h2>
                    <button type="button" class="button" onclick="cwoCloseEmailDetail()" style="padding: 5px 10px;">×</button>
                </div>
                <div id="email-detail-content" style="padding: 20px;">
                    <!-- Wird dynamisch gefüllt -->
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        var emailLogNonce = '<?php echo wp_create_nonce('cwo_email_log_nonce'); ?>';
        
        function cwoLoadEmailLog() {
            var tbody = document.getElementById('email-log-tbody');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">Lade E-Mail Log...</td></tr>';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data.emails.length > 0) {
                            var html = '';
                            response.data.emails.forEach(function(email) {
                                var statusColor = email.status === 'success' ? '#46b450' : '#dc3232';
                                var statusText = email.status === 'success' ? 'Erfolg' : 'Fehler';
                                
                                html += '<tr>';
                                html += '<td>' + email.time + '</td>';
                                html += '<td>' + email.to + '</td>';
                                html += '<td>' + email.subject + '</td>';
                                html += '<td><span style="color: ' + statusColor + '; font-weight: 600;">● ' + statusText + '</span></td>';
                                html += '<td><button type="button" class="button button-small" onclick="cwoShowEmailDetail(' + email.id + ')">Details</button></td>';
                                html += '</tr>';
                            });
                            tbody.innerHTML = html;
                        } else {
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #646970;">Keine E-Mails im Log vorhanden.</td></tr>';
                        }
                    } catch(e) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #dc3232;">Fehler beim Laden des Logs.</td></tr>';
                    }
                }
            };
            xhr.send('action=cwo_get_email_log&nonce=' + emailLogNonce);
        }
        
        function cwoShowEmailDetail(emailId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var email = response.data;
                            var html = '<table class="form-table">';
                            html += '<tr><th>Zeit:</th><td>' + email.time + '</td></tr>';
                            html += '<tr><th>Empfänger:</th><td>' + email.to + '</td></tr>';
                            html += '<tr><th>Betreff:</th><td>' + email.subject + '</td></tr>';
                            html += '<tr><th>Status:</th><td>' + (email.status === 'success' ? '<span style="color: #46b450;">✓ Erfolg</span>' : '<span style="color: #dc3232;">✗ Fehler</span>') + '</td></tr>';
                            if (email.error) {
                                html += '<tr><th>Fehler:</th><td style="color: #dc3232;">' + email.error + '</td></tr>';
                            }
                            html += '<tr><th>Nachricht:</th><td><pre style="background: #f6f7f7; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">' + email.message + '</pre></td></tr>';
                            html += '<tr><th>Header:</th><td><pre style="background: #f6f7f7; padding: 15px; border-radius: 4px; max-height: 200px; overflow-y: auto; font-size: 11px;">' + email.headers + '</pre></td></tr>';
                            html += '</table>';
                            
                            document.getElementById('email-detail-content').innerHTML = html;
                            document.getElementById('email-detail-modal').style.display = 'flex';
                        }
                    } catch(e) {
                        alert('Fehler beim Laden der E-Mail Details.');
                    }
                }
            };
            xhr.send('action=cwo_get_email_detail&nonce=' + emailLogNonce + '&email_id=' + emailId);
        }
        
        function cwoCloseEmailDetail() {
            document.getElementById('email-detail-modal').style.display = 'none';
        }
        
        function cwoClearEmailLog() {
            if (!confirm('Möchtest du das E-Mail Log wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) return;
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            cwoLoadEmailLog();
                        }
                    } catch(e) {
                        alert('Fehler beim Löschen des Logs.');
                    }
                }
            };
            xhr.send('action=cwo_clear_email_log&nonce=' + emailLogNonce);
        }
        
        // Modal schließen bei Klick außerhalb
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('email-detail-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        cwoCloseEmailDetail();
                    }
                });
            }
        });
        </script>
        <?php
    }
}
