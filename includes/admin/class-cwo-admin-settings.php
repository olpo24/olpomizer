<?php
/**
 * Admin Settings Handler
 * Verarbeitet das Speichern von Einstellungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Admin_Settings {
    
    /**
     * Settings speichern
     */
    public function save($post_data) {
        $optimizer = Custom_WP_Optimizer::get_instance();
        $all_modules = array();
        
        // Alle Module mit '0' initialisieren
        foreach ($optimizer->get_modules() as $module_id => $module) {
            $all_modules[$module_id] = '0';
        }
        
        // Aktivierte Module auf '1' setzen
        if (isset($post_data['cwo_modules']) && is_array($post_data['cwo_modules'])) {
            foreach ($post_data['cwo_modules'] as $module_id => $value) {
                if ($value === '1') {
                    $all_modules[$module_id] = '1';
                }
            }
        }
        
        // Performance Modul ist immer aktiv (keine Toggle-Aktivierung)
        if (isset($optimizer->get_modules()['performance'])) {
            $all_modules['performance'] = '1';
        }
        
        // Module-Status speichern
        update_option('cwo_modules', $all_modules);
        
        // Modul-spezifische Settings speichern
        foreach ($optimizer->get_modules() as $module_id => $module) {
            if ($all_modules[$module_id] === '1') {
                $module->save_settings($post_data);
            }
        }
        
        // Success-Nachricht anzeigen
        add_settings_error('cwo_messages', 'cwo_message', 'Einstellungen gespeichert', 'updated');
        settings_errors('cwo_messages');
    }
}
