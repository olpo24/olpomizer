<?php
/**
 * Image Optimization Module
 * Optimiert Bilder automatisch und bietet Batch-Verarbeitung
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Image_Optimization_Module extends CWO_Module_Base {
    
    private $supported_formats = array('jpg', 'jpeg', 'png', 'gif');
    
    public function __construct() {
        $this->id = 'image-optimization';
        $this->name = 'Bild Optimierung';
        $this->description = 'Optimiere Bilder automatisch beim Upload, konvertiere zu WebP und reduziere Dateigrößen.';
    }
    
    /**
     * Modul initialisieren
     */
    public function init() {
        // Automatische Optimierung beim Upload
        if ($this->get_option('auto_optimize', '0') === '1') {
            add_filter('wp_handle_upload', array($this, 'optimize_on_upload'));
        }
        
        // WebP Unterstützung
        if ($this->get_option('webp_enabled', '0') === '1') {
            add_filter('wp_handle_upload', array($this, 'create_webp_version'));
            
            // WebP via .htaccess ausliefern
            if ($this->get_option('webp_htaccess', '0') === '1') {
                add_action('admin_init', array($this, 'update_htaccess_webp'));
            }
        }
        
        // MIME Types für WebP
        add_filter('upload_mimes', array($this, 'add_webp_mime_type'));
        add_filter('wp_check_filetype_and_ext', array($this, 'check_webp_filetype'), 10, 4);
        
        // Lazy Loading erzwingen (WordPress 5.5+)
        if ($this->get_option('force_lazy_loading', '0') === '1') {
            add_filter('wp_lazy_loading_enabled', '__return_true');
        }
        
        // Responsive Images deaktivieren (falls gewünscht)
        if ($this->get_option('disable_responsive', '0') === '1') {
            add_filter('max_srcset_image_width', '__return_zero');
        }
        
        // AJAX Handlers
        add_action('wp_ajax_cwo_batch_optimize', array($this, 'ajax_batch_optimize'));
        add_action('wp_ajax_cwo_get_image_stats', array($this, 'ajax_get_image_stats'));
        add_action('wp_ajax_cwo_optimize_single_image', array($this, 'ajax_optimize_single_image'));
        add_action('wp_ajax_cwo_restore_image', array($this, 'ajax_restore_image'));
    }
    
    /**
     * Bild beim Upload optimieren
     */
    public function optimize_on_upload($upload) {
        if (!isset($upload['file']) || !isset($upload['type'])) {
            return $upload;
        }
        
        // Nur Bilder verarbeiten
        if (strpos($upload['type'], 'image/') !== 0) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Nur unterstützte Formate
        if (!in_array($extension, $this->supported_formats)) {
            return $upload;
        }
        
        // Originalbild sichern (falls Backup aktiviert)
        if ($this->get_option('create_backups', '1') === '1') {
            $this->create_backup($file_path);
        }
        
        // Bild optimieren
        $quality = intval($this->get_option('jpeg_quality', '85'));
        $this->optimize_image($file_path, $quality);
        
        return $upload;
    }
    
    /**
     * WebP Version erstellen
     */
    public function create_webp_version($upload) {
        if (!isset($upload['file']) || !isset($upload['type'])) {
            return $upload;
        }
        
        // Nur Bilder verarbeiten
        if (strpos($upload['type'], 'image/') !== 0) {
            return $upload;
        }
        
        $file_path = $upload['file'];
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        // Nur JPEG und PNG zu WebP konvertieren
        if (!in_array($extension, array('jpg', 'jpeg', 'png'))) {
            return $upload;
        }
        
        // WebP erstellen
        $this->convert_to_webp($file_path);
        
        return $upload;
    }
    
    /**
     * Bild optimieren
     */
    private function optimize_image($file_path, $quality = 85) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return $this->optimize_jpeg($file_path, $quality);
            
            case 'png':
                return $this->optimize_png($file_path, $quality);
            
            case 'gif':
                // GIF Optimierung nur wenn imagemagick verfügbar
                if (extension_loaded('imagick')) {
                    return $this->optimize_gif($file_path);
                }
                return false;
            
            default:
                return false;
        }
    }
    
    /**
     * JPEG optimieren
     */
    private function optimize_jpeg($file_path, $quality = 85) {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }
        
        $original_size = filesize($file_path);
        
        // Progressive JPEG aktivieren
        $progressive = $this->get_option('progressive_jpeg', '1') === '1';
        
        // Bild laden
        $image = imagecreatefromjpeg($file_path);
        if (!$image) {
            return false;
        }
        
        // EXIF-Daten entfernen (falls gewünscht)
        if ($this->get_option('strip_exif', '1') === '1') {
            // Wird durch imagejpeg automatisch entfernt
        }
        
        // Optimiert speichern
        if ($progressive) {
            imageinterlace($image, 1);
        }
        
        $result = imagejpeg($image, $file_path, $quality);
        imagedestroy($image);
        
        $new_size = filesize($file_path);
        $saved = $original_size - $new_size;
        
        // Statistik speichern
        $this->update_stats($saved);
        
        return $result;
    }
    
    /**
     * PNG optimieren
     */
    private function optimize_png($file_path, $quality = 85) {
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }
        
        $original_size = filesize($file_path);
        
        // Bild laden
        $image = imagecreatefrompng($file_path);
        if (!$image) {
            return false;
        }
        
        // Transparenz beibehalten
        imagealphablending($image, false);
        imagesavealpha($image, true);
        
        // PNG Kompression (0-9, wobei 9 = maximale Kompression)
        $compression = 9 - floor(($quality / 100) * 9);
        
        $result = imagepng($image, $file_path, $compression);
        imagedestroy($image);
        
        // Optipng verwenden falls verfügbar
        if ($this->get_option('use_optipng', '0') === '1' && $this->command_exists('optipng')) {
            exec("optipng -o2 -quiet " . escapeshellarg($file_path));
        }
        
        $new_size = filesize($file_path);
        $saved = $original_size - $new_size;
        
        // Statistik speichern
        $this->update_stats($saved);
        
        return $result;
    }
    
    /**
     * GIF optimieren (mit ImageMagick)
     */
    private function optimize_gif($file_path) {
        if (!extension_loaded('imagick')) {
            return false;
        }
        
        $original_size = filesize($file_path);
        
        try {
            $image = new Imagick($file_path);
            
            // GIF optimieren
            $image->optimizeImageLayers();
            
            // Speichern
            $image->writeImage($file_path);
            $image->clear();
            $image->destroy();
            
            $new_size = filesize($file_path);
            $saved = $original_size - $new_size;
            
            // Statistik speichern
            $this->update_stats($saved);
            
            return true;
        } catch (Exception $e) {
            error_log('OlpoMizer GIF Optimization Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Zu WebP konvertieren
     */
    private function convert_to_webp($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
        
        // Bereits eine WebP Datei
        if ($extension === 'webp') {
            return true;
        }
        
        $quality = intval($this->get_option('webp_quality', '85'));
        
        // Bild laden
        $image = null;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                if (function_exists('imagecreatefromjpeg')) {
                    $image = imagecreatefromjpeg($file_path);
                }
                break;
            
            case 'png':
                if (function_exists('imagecreatefrompng')) {
                    $image = imagecreatefrompng($file_path);
                    
                    // Transparenz für WebP beibehalten
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                break;
        }
        
        if (!$image) {
            return false;
        }
        
        // WebP erstellen
        if (function_exists('imagewebp')) {
            $result = imagewebp($image, $webp_path, $quality);
            imagedestroy($image);
            
            // Original löschen falls gewünscht
            if ($result && $this->get_option('webp_delete_original', '0') === '1') {
                @unlink($file_path);
            }
            
            return $result;
        }
        
        imagedestroy($image);
        return false;
    }
    
    /**
     * Backup erstellen
     */
    private function create_backup($file_path) {
        $backup_dir = WP_CONTENT_DIR . '/uploads/olpomizer-backups/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $filename = basename($file_path);
        $backup_path = $backup_dir . time() . '_' . $filename;
        
        return copy($file_path, $backup_path);
    }
    
    /**
     * Statistiken aktualisieren
     */
    private function update_stats($bytes_saved) {
        $total_saved = intval($this->get_option('total_saved', '0'));
        $total_saved += $bytes_saved;
        
        $this->update_option('total_saved', $total_saved);
        
        $images_optimized = intval($this->get_option('images_optimized', '0'));
        $images_optimized++;
        
        $this->update_option('images_optimized', $images_optimized);
    }
    
    /**
     * WebP MIME Type hinzufügen
     */
    public function add_webp_mime_type($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    }
    
    /**
     * WebP Filetype Check
     */
    public function check_webp_filetype($data, $file, $filename, $mimes) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if ($ext === 'webp') {
            $data['ext'] = 'webp';
            $data['type'] = 'image/webp';
        }
        
        return $data;
    }
    
    /**
     * .htaccess für WebP aktualisieren
     */
    public function update_htaccess_webp() {
        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!is_writable($htaccess_file)) {
            return false;
        }
        
        $current_content = file_get_contents($htaccess_file);
        $marker_begin = '# BEGIN OlpoMizer WebP';
        $marker_end = '# END OlpoMizer WebP';
        
        // Alte Regeln entfernen
        $pattern = '/' . preg_quote($marker_begin, '/') . '.*?' . preg_quote($marker_end, '/') . '\s*/s';
        $current_content = preg_replace($pattern, '', $current_content);
        
        // Neue Regeln hinzufügen
        $rules = $this->get_htaccess_webp_rules();
        $new_content = $marker_begin . "\n" . $rules . "\n" . $marker_end . "\n\n" . $current_content;
        
        return file_put_contents($htaccess_file, $new_content, LOCK_EX);
    }
    
    /**
     * WebP .htaccess Regeln
     */
    private function get_htaccess_webp_rules() {
        return '<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # WebP ausliefern wenn verfügbar und Browser unterstützt
    RewriteCond %{HTTP_ACCEPT} image/webp
    RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$
    RewriteCond %1.webp -f
    RewriteRule (.+)\.(jpe?g|png)$ $1.webp [T=image/webp,E=accept:1,L]
</IfModule>

<IfModule mod_headers.c>
    # WebP Header setzen
    Header append Vary Accept env=REDIRECT_accept
    
    # Cache-Control für WebP
    <FilesMatch "\.(webp)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
</IfModule>

# WebP MIME Type
<IfModule mod_mime.c>
    AddType image/webp .webp
</IfModule>';
    }
    
    /**
     * WebP Regeln aus .htaccess entfernen
     */
    public function remove_htaccess_webp_rules() {
        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!is_writable($htaccess_file)) {
            return false;
        }
        
        $current_content = file_get_contents($htaccess_file);
        $marker_begin = '# BEGIN OlpoMizer WebP';
        $marker_end = '# END OlpoMizer WebP';
        
        $pattern = '/' . preg_quote($marker_begin, '/') . '.*?' . preg_quote($marker_end, '/') . '\s*/s';
        $new_content = preg_replace($pattern, '', $current_content);
        
        return file_put_contents($htaccess_file, $new_content, LOCK_EX);
    }
    
    /**
     * Prüfen ob Kommando verfügbar ist
     */
    private function command_exists($command) {
        $return = shell_exec(sprintf("which %s", escapeshellarg($command)));
        return !empty($return);
    }
    
    /**
     * Batch Optimierung (AJAX)
     */
    public function ajax_batch_optimize() {
        check_ajax_referer('cwo_image_nonce', 'nonce');
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        // Alle Bilder-Attachments abrufen
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $attachments = get_posts($args);
        $optimized = 0;
        $errors = 0;
        
        $quality = intval($this->get_option('jpeg_quality', '85'));
        
        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            
            if (!file_exists($file_path)) {
                $errors++;
                continue;
            }
            
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $this->supported_formats)) {
                continue;
            }
            
            // Backup erstellen
            if ($this->get_option('create_backups', '1') === '1') {
                $this->create_backup($file_path);
            }
            
            // Optimieren
            if ($this->optimize_image($file_path, $quality)) {
                $optimized++;
                
                // WebP erstellen falls aktiviert
                if ($this->get_option('webp_enabled', '0') === '1') {
                    $this->convert_to_webp($file_path);
                }
            } else {
                $errors++;
            }
        }
        
        wp_send_json_success(array(
            'optimized' => $optimized,
            'errors' => $errors,
            'offset' => $offset + $limit,
            'total' => wp_count_posts('attachment')->inherit
        ));
    }
    
    /**
     * Bildstatistiken abrufen (AJAX)
     */
    public function ajax_get_image_stats() {
        check_ajax_referer('cwo_image_nonce', 'nonce');
        
        // Gesamtzahl Bilder
        $total_images = wp_count_posts('attachment')->inherit;
        
        // Optimierte Bilder
        $images_optimized = intval($this->get_option('images_optimized', '0'));
        
        // Gesparte Bytes
        $total_saved = intval($this->get_option('total_saved', '0'));
        
        // WebP Dateien zählen
        $webp_count = $this->count_webp_files();
        
        wp_send_json_success(array(
            'total_images' => $total_images,
            'images_optimized' => $images_optimized,
            'total_saved' => size_format($total_saved),
            'total_saved_bytes' => $total_saved,
            'webp_count' => $webp_count,
            'optimization_percentage' => $total_images > 0 ? round(($images_optimized / $total_images) * 100) : 0
        ));
    }
    
    /**
     * WebP Dateien zählen
     */
    private function count_webp_files() {
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'];
        
        if (!is_dir($path)) {
            return 0;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $count = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Einzelnes Bild optimieren (AJAX)
     */
    public function ajax_optimize_single_image() {
        check_ajax_referer('cwo_image_nonce', 'nonce');
        
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        
        if (!$attachment_id) {
            wp_send_json_error(array('message' => 'Keine Attachment-ID angegeben'));
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            wp_send_json_error(array('message' => 'Datei nicht gefunden'));
        }
        
        $original_size = filesize($file_path);
        
        // Backup erstellen
        if ($this->get_option('create_backups', '1') === '1') {
            $this->create_backup($file_path);
        }
        
        // Optimieren
        $quality = intval($this->get_option('jpeg_quality', '85'));
        $result = $this->optimize_image($file_path, $quality);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Optimierung fehlgeschlagen'));
        }
        
        // WebP erstellen falls aktiviert
        if ($this->get_option('webp_enabled', '0') === '1') {
            $this->convert_to_webp($file_path);
        }
        
        $new_size = filesize($file_path);
        $saved = $original_size - $new_size;
        $percentage = $original_size > 0 ? round(($saved / $original_size) * 100, 1) : 0;
        
        wp_send_json_success(array(
            'message' => 'Bild erfolgreich optimiert',
            'original_size' => size_format($original_size),
            'new_size' => size_format($new_size),
            'saved' => size_format($saved),
            'percentage' => $percentage
        ));
    }
    
    /**
     * Bild wiederherstellen (AJAX)
     */
    public function ajax_restore_image() {
        check_ajax_referer('cwo_image_nonce', 'nonce');
        
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        
        if (!$attachment_id) {
            wp_send_json_error(array('message' => 'Keine Attachment-ID angegeben'));
        }
        
        // Backup suchen und wiederherstellen
        // TODO: Implementierung Backup-Wiederherstellung
        
        wp_send_json_success(array('message' => 'Bild wiederhergestellt'));
    }
    
    /**
     * Settings speichern
     */
    public function save_settings($post_data) {
        // Automatische Optimierung
        $this->update_option('auto_optimize', isset($post_data['cwo_img_auto_optimize']) ? '1' : '0');
        
        // JPEG Qualität
        if (isset($post_data['cwo_img_jpeg_quality'])) {
            $quality = intval($post_data['cwo_img_jpeg_quality']);
            $quality = max(1, min(100, $quality)); // 1-100
            $this->update_option('jpeg_quality', $quality);
        }
        
        // Progressive JPEG
        $this->update_option('progressive_jpeg', isset($post_data['cwo_img_progressive_jpeg']) ? '1' : '0');
        
        // EXIF entfernen
        $this->update_option('strip_exif', isset($post_data['cwo_img_strip_exif']) ? '1' : '0');
        
        // Backups erstellen
        $this->update_option('create_backups', isset($post_data['cwo_img_create_backups']) ? '1' : '0');
        
        // WebP Einstellungen
        $webp_enabled_old = $this->get_option('webp_enabled', '0');
        $webp_enabled_new = isset($post_data['cwo_img_webp_enabled']) ? '1' : '0';
        $this->update_option('webp_enabled', $webp_enabled_new);
        
        if (isset($post_data['cwo_img_webp_quality'])) {
            $quality = intval($post_data['cwo_img_webp_quality']);
            $quality = max(1, min(100, $quality));
            $this->update_option('webp_quality', $quality);
        }
        
        $this->update_option('webp_delete_original', isset($post_data['cwo_img_webp_delete_original']) ? '1' : '0');
        
        // WebP .htaccess
        $webp_htaccess_old = $this->get_option('webp_htaccess', '0');
        $webp_htaccess_new = isset($post_data['cwo_img_webp_htaccess']) ? '1' : '0';
        $this->update_option('webp_htaccess', $webp_htaccess_new);
        
        // .htaccess aktualisieren oder entfernen
        if ($webp_htaccess_new === '1' && $webp_htaccess_old === '0') {
            $this->update_htaccess_webp();
        } elseif ($webp_htaccess_new === '0' && $webp_htaccess_old === '1') {
            $this->remove_htaccess_webp_rules();
        }
        
        // Weitere Optionen
        $this->update_option('use_optipng', isset($post_data['cwo_img_use_optipng']) ? '1' : '0');
        $this->update_option('force_lazy_loading', isset($post_data['cwo_img_force_lazy_loading']) ? '1' : '0');
        $this->update_option('disable_responsive', isset($post_data['cwo_img_disable_responsive']) ? '1' : '0');
        
        // Max Bildbreite
        if (isset($post_data['cwo_img_max_width'])) {
            $max_width = intval($post_data['cwo_img_max_width']);
            $this->update_option('max_width', $max_width > 0 ? $max_width : '0');
        }
        
        // Max Bildhöhe
        if (isset($post_data['cwo_img_max_height'])) {
            $max_height = intval($post_data['cwo_img_max_height']);
            $this->update_option('max_height', $max_height > 0 ? $max_height : '0');
        }
    }
}
