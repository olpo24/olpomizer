<?php
/**
 * SMTP Modul
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_SMTP_Module extends CWO_Module_Base {
    
    public function __construct() {
        $this->id = 'smtp';
        $this->name = 'SMTP E-Mail Konfiguration';
        $this->description = 'Konfiguriere SMTP-Einstellungen für den E-Mail-Versand über externe SMTP-Server.';
    }
    
    /**
     * Modul initialisieren
     */
    public function init() {
        add_action('phpmailer_init', array($this, 'configure_smtp'));
    }
    
    /**
     * SMTP konfigurieren
     */
    public function configure_smtp($phpmailer) {
        $smtp_host = $this->get_option('host');
        $smtp_port = $this->get_option('port', '587');
        $smtp_encryption = $this->get_option('encryption', 'tls');
        $smtp_auth = $this->get_option('auth', '1');
        $smtp_username = $this->get_option('username');
        $smtp_password = $this->get_option('password');
        $from_email = $this->get_option('from_email');
        $from_name = $this->get_option('from_name');
        
        if (empty($smtp_host)) {
            return;
        }
        
        $phpmailer->isSMTP();
        $phpmailer->Host = $smtp_host;
        $phpmailer->Port = $smtp_port;
        $phpmailer->SMTPSecure = $smtp_encryption;
        
        if ($smtp_auth === '1') {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $smtp_username;
            $phpmailer->Password = $smtp_password;
        }
        
        if (!empty($from_email)) {
            $phpmailer->From = $from_email;
        }
        
        if (!empty($from_name)) {
            $phpmailer->FromName = $from_name;
        }
    }
    
    /**
     * Settings-Felder rendern
     */
    public function render_settings() {
        $host = $this->get_option('host');
        $port = $this->get_option('port', '587');
        $encryption = $this->get_option('encryption', 'tls');
        $auth = $this->get_option('auth', '1');
        $username = $this->get_option('username');
        $password = $this->get_option('password');
        $from_email = $this->get_option('from_email');
        $from_name = $this->get_option('from_name');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="smtp_host">SMTP Host</label></th>
                <td>
                    <input type="text" id="smtp_host" name="cwo_smtp_host" value="<?php echo esc_attr($host); ?>" class="regular-text">
                    <p class="description">z.B. smtp.gmail.com oder mail.example.com</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_port">SMTP Port</label></th>
                <td>
                    <input type="number" id="smtp_port" name="cwo_smtp_port" value="<?php echo esc_attr($port); ?>" class="small-text">
                    <p class="description">Standard: 587 (TLS) oder 465 (SSL)</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_encryption">Verschlüsselung</label></th>
                <td>
                    <select id="smtp_encryption" name="cwo_smtp_encryption">
                        <option value="tls" <?php selected($encryption, 'tls'); ?>>TLS</option>
                        <option value="ssl" <?php selected($encryption, 'ssl'); ?>>SSL</option>
                        <option value="" <?php selected($encryption, ''); ?>>Keine</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_auth">SMTP Authentifizierung</label></th>
                <td>
                    <label>
                        <input type="checkbox" id="smtp_auth" name="cwo_smtp_auth" value="1" <?php checked($auth, '1'); ?>>
                        Authentifizierung verwenden
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_username">Benutzername</label></th>
                <td>
                    <input type="text" id="smtp_username" name="cwo_smtp_username" value="<?php echo esc_attr($username); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_password">Passwort</label></th>
                <td>
                    <input type="password" id="smtp_password" name="cwo_smtp_password" value="<?php echo esc_attr($password); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_from_email">Absender E-Mail</label></th>
                <td>
                    <input type="email" id="smtp_from_email" name="cwo_smtp_from_email" value="<?php echo esc_attr($from_email); ?>" class="regular-text">
                    <p class="description">Optional: Standard-Absender-Adresse überschreiben</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_from_name">Absender Name</label></th>
                <td>
                    <input type="text" id="smtp_from_name" name="cwo_smtp_from_name" value="<?php echo esc_attr($from_name); ?>" class="regular-text">
                    <p class="description">Optional: Standard-Absender-Name überschreiben</p>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 15px;">
            <button type="button" class="button" onclick="cwoTestSMTP()">Test-E-Mail senden</button>
            <span id="smtp-test-result"></span>
        </div>
        
        <script type="text/javascript">
        function cwoTestSMTP() {
            var result = document.getElementById('smtp-test-result');
            result.textContent = ' Sende...';
            result.style.color = '#000';
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        result.textContent = ' ' + data.message;
                        result.style.color = data.success ? 'green' : 'red';
                    } catch(e) {
                        result.textContent = ' Fehler beim Parsen der Antwort';
                        result.style.color = 'red';
                    }
                } else {
                    result.textContent = ' Fehler bei der Anfrage';
                    result.style.color = 'red';
                }
            };
            
            xhr.send('action=cwo_test_smtp&nonce=<?php echo wp_create_nonce('cwo_test_smtp'); ?>');
        }
        </script>
        <?php
    }
    
    /**
     * Settings speichern
     */
    public function save_settings($post_data) {
        if (isset($post_data['cwo_smtp_host'])) {
            $this->update_option('host', sanitize_text_field($post_data['cwo_smtp_host']));
        }
        if (isset($post_data['cwo_smtp_port'])) {
            $this->update_option('port', sanitize_text_field($post_data['cwo_smtp_port']));
        }
        if (isset($post_data['cwo_smtp_encryption'])) {
            $this->update_option('encryption', sanitize_text_field($post_data['cwo_smtp_encryption']));
        }
        $this->update_option('auth', isset($post_data['cwo_smtp_auth']) ? '1' : '0');
        
        if (isset($post_data['cwo_smtp_username'])) {
            $this->update_option('username', sanitize_text_field($post_data['cwo_smtp_username']));
        }
        if (isset($post_data['cwo_smtp_password'])) {
            $this->update_option('password', $post_data['cwo_smtp_password']);
        }
        if (isset($post_data['cwo_smtp_from_email'])) {
            $this->update_option('from_email', sanitize_email($post_data['cwo_smtp_from_email']));
        }
        if (isset($post_data['cwo_smtp_from_name'])) {
            $this->update_option('from_name', sanitize_text_field($post_data['cwo_smtp_from_name']));
        }
    }
}

// AJAX Handler für Test-E-Mail
add_action('wp_ajax_cwo_test_smtp', function() {
    check_ajax_referer('cwo_test_smtp', 'nonce');
    
    $to = get_option('admin_email');
    $subject = 'SMTP Test von Custom WP Optimizer';
    $message = 'Dies ist eine Test-E-Mail. Wenn du diese E-Mail erhalten hast, funktioniert deine SMTP-Konfiguration korrekt!';
    
    $result = wp_mail($to, $subject, $message);
    
    wp_send_json(array(
        'success' => $result,
        'message' => $result ? 'Test-E-Mail erfolgreich versendet!' : 'Fehler beim Versenden der Test-E-Mail.'
    ));
});
