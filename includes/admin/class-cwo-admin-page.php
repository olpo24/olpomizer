<?php
/**
 * Admin Page Renderer
 * Erstellt das HTML der Admin-Seite
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Admin_Page {
    
    private $debug_renderer;
    private $smtp_renderer;
    private $performance_renderer;
    
    public function __construct() {
        $this->debug_renderer = new CWO_Debug_Renderer();
        $this->smtp_renderer = new CWO_SMTP_Renderer();
        $this->performance_renderer = new CWO_Performance_Renderer();
    }
    
    /**
     * Admin-Seite rendern
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $optimizer = Custom_WP_Optimizer::get_instance();
        $modules = $optimizer->get_modules();
        $enabled_modules = get_option('cwo_modules', array());
        
        // Module nach Kategorie organisieren
        $categories = $this->get_categories($modules);
        
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-generic" style="font-size: 28px; margin-right: 10px;"></span>
                OlpoMizer
            </h1>
            <p style="color: #646970; margin-top: 5px;">Modulares WordPress Optimierungs-Plugin</p>
            
            <form method="post" action="" id="olpo-settings-form">
                <?php wp_nonce_field('cwo_settings_nonce'); ?>
                
                <div class="olpo-container">
                    <?php $this->render_main_tabs($categories); ?>
                    <?php $this->render_tab_contents($categories, $enabled_modules); ?>
                </div>
                
                <p class="submit" style="margin-top: 20px;">
                    <?php submit_button('Einstellungen speichern', 'primary large', 'cwo_save_settings', false); ?>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Kategorien mit Modulen erstellen
     */
    private function get_categories($modules) {
        $categories = array(
            'email' => array(
                'title' => 'E-Mail',
                'icon' => 'email',
                'modules' => array(),
                'sections' => array(
                    'smtp' => array(
                        'title' => 'SMTP Einstellungen',
                        'icon' => 'admin-settings'
                    ),
                    'log' => array(
                        'title' => 'E-Mail Log',
                        'icon' => 'list-view'
                    )
                )
            ),
            'debug' => array(
                'title' => 'Debug',
                'icon' => 'admin-tools',
                'modules' => array(),
                'sections' => array(
                    'settings' => array(
                        'title' => 'Einstellungen',
                        'icon' => 'admin-settings'
                    ),
                    'viewer' => array(
                        'title' => 'Log Viewer',
                        'icon' => 'media-text'
                    )
                )
            ),
            'performance' => array(
                'title' => 'Performance',
                'icon' => 'performance',
                'modules' => array(),
                'sections' => array(
                    'general' => array(
                        'title' => 'Allgemein',
                        'icon' => 'admin-generic'
                    ),
                    'cache' => array(
                        'title' => 'Cache',
                        'icon' => 'database'
                    )
                )
            )
        );
        
        // Module zuordnen
        foreach ($modules as $module_id => $module) {
            if ($module_id === 'smtp') {
                $categories['email']['modules'][$module_id] = $module;
            } elseif ($module_id === 'debug') {
                $categories['debug']['modules'][$module_id] = $module;
            } elseif ($module_id === 'performance') {
                $categories['performance']['modules'][$module_id] = $module;
            } else {
                $categories['performance']['modules'][$module_id] = $module;
            }
        }
        
        return $categories;
    }
    
    /**
     * Haupttabs rendern
     */
    private function render_main_tabs($categories) {
        ?>
        <div class="olpo-main-tabs">
            <?php 
            $first = true;
            foreach ($categories as $cat_id => $category): 
                if (empty($category['modules'])) continue;
            ?>
                <button type="button" 
                        class="olpo-main-tab <?php echo $first ? 'active' : ''; ?>" 
                        data-tab="<?php echo esc_attr($cat_id); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($category['icon']); ?>"></span>
                    <?php echo esc_html($category['title']); ?>
                </button>
            <?php 
                $first = false;
            endforeach; 
            ?>
        </div>
        <?php
    }
    
    /**
     * Tab Contents rendern
     */
    private function render_tab_contents($categories, $enabled_modules) {
        $first = true;
        foreach ($categories as $cat_id => $category): 
            if (empty($category['modules'])) continue;
            $has_sections = !empty($category['sections']);
        ?>
            <div class="olpo-tab-content <?php echo $first ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($cat_id); ?>">
                
                <?php if ($has_sections): ?>
                    <div class="olpo-sidebar">
                        <?php $this->render_sidebar($category, $cat_id); ?>
                    </div>
                    
                    <div class="olpo-main-content">
                        <?php $this->render_sections($category, $cat_id, $enabled_modules); ?>
                    </div>
                <?php endif; ?>
                
            </div>
        <?php 
            $first = false;
        endforeach;
    }
    
    /**
     * Sidebar rendern
     */
    private function render_sidebar($category, $cat_id) {
        $first_section = true;
        foreach ($category['sections'] as $section_id => $section): 
        ?>
            <a href="#" 
               class="olpo-sidebar-item <?php echo $first_section ? 'active' : ''; ?>" 
               data-section="<?php echo esc_attr($cat_id . '-' . $section_id); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr($section['icon']); ?>"></span>
                <?php echo esc_html($section['title']); ?>
            </a>
        <?php 
            $first_section = false;
        endforeach;
    }
    
    /**
     * Sections rendern
     */
    private function render_sections($category, $cat_id, $enabled_modules) {
        $first_section = true;
        foreach ($category['sections'] as $section_id => $section): 
        ?>
            <div class="olpo-section <?php echo $first_section ? 'active' : ''; ?>" 
                 data-section="<?php echo esc_attr($cat_id . '-' . $section_id); ?>">
                
                <?php $this->render_section_content($cat_id, $section_id, $section, $category, $enabled_modules); ?>
                
            </div>
        <?php 
            $first_section = false;
        endforeach;
    }
    
    /**
     * Section Content rendern (delegiert an spezialisierte Renderer)
     */
    private function render_section_content($cat_id, $section_id, $section, $category, $enabled_modules) {
        // Debug Sections
        if ($cat_id === 'debug' && $section_id === 'settings') {
            $this->debug_renderer->render_settings_section($section, $category, $enabled_modules);
        } elseif ($cat_id === 'debug' && $section_id === 'viewer') {
            $this->debug_renderer->render_viewer_section($section, $category);
        }
        
        // Email Sections
        elseif ($cat_id === 'email' && $section_id === 'smtp') {
            $this->smtp_renderer->render_smtp_section($section, $category, $enabled_modules);
        } elseif ($cat_id === 'email' && $section_id === 'log') {
            $this->smtp_renderer->render_log_section($section, $category);
        }
        
        // Performance Sections
        elseif ($cat_id === 'performance' && $section_id === 'general') {
            $this->performance_renderer->render_general_section($section, $category);
        } elseif ($cat_id === 'performance' && $section_id === 'cache') {
            $this->performance_renderer->render_cache_section($section, $category);
        }
    }
}
