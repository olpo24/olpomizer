<?php
/**
 * Basis-Klasse für alle Module
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class CWO_Module_Base {
    
    protected $id;
    protected $name;
    protected $description;
    
    /**
     * Modul initialisieren (wird aufgerufen wenn Modul aktiviert ist)
     */
    abstract public function init();
    
    /**
     * Modul-ID abrufen
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Modul-Name abrufen
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Modul-Beschreibung abrufen
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Settings-Felder rendern (optional)
     */
    public function render_settings() {
        echo '<p>Keine zusätzlichen Einstellungen verfügbar.</p>';
    }
    
    /**
     * Settings speichern (optional)
     */
    public function save_settings($post_data) {
        // Überschreiben wenn Modul eigene Settings hat
    }
    
    /**
     * Option-Key für dieses Modul generieren
     */
    public function get_option_name($key) {
        return 'cwo_' . $this->id . '_' . $key;
    }
    
    /**
     * Option abrufen
     */
    public function get_option($key, $default = '') {
        return get_option($this->get_option_name($key), $default);
    }
    
    /**
     * Option speichern
     */
    public function update_option($key, $value) {
        return update_option($this->get_option_name($key), $value);
    }
}
