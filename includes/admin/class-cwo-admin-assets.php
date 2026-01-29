<?php
/**
 * Admin Assets Manager
 * Verwaltet CSS und JavaScript für das Admin-Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class CWO_Admin_Assets {
    
    /**
     * Assets laden
     */
    public function enqueue_assets($hook) {
        // Nur auf unserer Settings-Seite laden
        if ('settings_page_olpomizer' !== $hook) {
            return;
        }
        
        $this->enqueue_styles();
        $this->enqueue_scripts();
    }
    
    /**
     * CSS Styles laden
     */
    private function enqueue_styles() {
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
    }
    
    /**
     * JavaScript laden
     */
    private function enqueue_scripts() {
        wp_add_inline_script('jquery', $this->get_admin_scripts());
    }
    
    /**
     * Admin CSS zurückgeben
     */
    private function get_admin_styles() {
        return '
            /* Layout Container */
            .olpo-container {
                margin: 20px 0 0 0;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            
            /* Haupttabs oben */
            .olpo-main-tabs {
                display: flex;
                border-bottom: 1px solid #ccd0d4;
                background: #f6f7f7;
                margin: 0;
                padding: 0;
            }
            
            .olpo-main-tab {
                padding: 15px 25px;
                cursor: pointer;
                border: none;
                background: transparent;
                border-bottom: 3px solid transparent;
                font-size: 14px;
                font-weight: 500;
                color: #50575e;
                transition: all 0.2s;
                position: relative;
            }
            
            .olpo-main-tab:hover {
                background: #fff;
                color: #2271b1;
            }
            
            .olpo-main-tab.active {
                background: #fff;
                color: #2271b1;
                border-bottom-color: #2271b1;
            }
            
            .olpo-main-tab .dashicons {
                margin-right: 5px;
                font-size: 18px;
                height: 18px;
                width: 18px;
            }
            
            /* Content Bereich mit Sidebar */
            .olpo-content-wrapper {
                display: flex;
                min-height: 500px;
            }
            
            .olpo-tab-content {
                display: none;
                width: 100%;
            }
            
            .olpo-tab-content.active {
                display: flex;
            }
            
            /* Sidebar Links */
            .olpo-sidebar {
                width: 220px;
                border-right: 1px solid #ccd0d4;
                background: #fafafa;
                padding: 20px 0;
            }
            
            .olpo-sidebar-item {
                padding: 12px 20px;
                cursor: pointer;
                color: #50575e;
                border-left: 3px solid transparent;
                transition: all 0.2s;
                display: block;
                text-decoration: none;
                font-size: 14px;
            }
            
            .olpo-sidebar-item:hover {
                background: #fff;
                color: #2271b1;
                border-left-color: #2271b1;
            }
            
            .olpo-sidebar-item.active {
                background: #fff;
                color: #2271b1;
                border-left-color: #2271b1;
                font-weight: 600;
            }
            
            .olpo-sidebar-item .dashicons {
                margin-right: 8px;
                font-size: 16px;
                height: 16px;
                width: 16px;
                color: #a7aaad;
            }
            
            .olpo-sidebar-item.active .dashicons {
                color: #2271b1;
            }
            
            /* Content Bereich Rechts */
            .olpo-main-content {
                flex: 1;
                padding: 30px;
            }
            
            .olpo-section {
                display: none;
            }
            
            .olpo-section.active {
                display: block;
            }
            
            /* Module Cards */
            .olpo-module-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .olpo-module-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            
            .olpo-module-title {
                font-size: 16px;
                font-weight: 600;
                margin: 0;
            }
            
            .olpo-module-description {
                color: #646970;
                margin-bottom: 15px;
            }
            
            .olpo-module-settings {
                padding: 20px;
                background: #f6f7f7;
                border-radius: 4px;
                margin-top: 15px;
                display: none;
            }
            
            .olpo-module-settings.show {
                display: block;
            }
            
            /* Toggle Switch */
            .olpo-toggle {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            
            .olpo-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .olpo-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }
            
            .olpo-toggle-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            .olpo-toggle input:checked + .olpo-toggle-slider {
                background-color: #2271b1;
            }
            
            .olpo-toggle input:checked + .olpo-toggle-slider:before {
                transform: translateX(26px);
            }
            
            /* Section Header */
            .olpo-section-header {
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 1px solid #ccd0d4;
            }
            
            .olpo-section-title {
                font-size: 20px;
                font-weight: 600;
                margin: 0 0 5px 0;
            }
            
            .olpo-section-description {
                color: #646970;
                margin: 0;
            }
            
            /* Performance Settings ohne Card */
            .olpo-performance-settings {
                background: #fff;
            }
        ';
    }
    
    /**
     * Admin JavaScript zurückgeben
     */
    private function get_admin_scripts() {
        return "
        (function() {
            'use strict';
            
            document.addEventListener('DOMContentLoaded', function() {
                // Haupttabs
                var mainTabs = document.querySelectorAll('.olpo-main-tab');
                var tabContents = document.querySelectorAll('.olpo-tab-content');
                
                mainTabs.forEach(function(tab) {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetTab = this.getAttribute('data-tab');
                        
                        mainTabs.forEach(function(t) { t.classList.remove('active'); });
                        tabContents.forEach(function(c) { c.classList.remove('active'); });
                        
                        this.classList.add('active');
                        document.querySelector('.olpo-tab-content[data-tab=\"' + targetTab + '\"]').classList.add('active');
                    });
                });
                
                // Sidebar Navigation
                var sidebarItems = document.querySelectorAll('.olpo-sidebar-item');
                
                sidebarItems.forEach(function(item) {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        var targetSection = this.getAttribute('data-section');
                        var parentTab = this.closest('.olpo-tab-content');
                        
                        parentTab.querySelectorAll('.olpo-sidebar-item').forEach(function(i) {
                            i.classList.remove('active');
                        });
                        
                        parentTab.querySelectorAll('.olpo-section').forEach(function(s) {
                            s.classList.remove('active');
                        });
                        
                        this.classList.add('active');
                        var targetSectionElement = parentTab.querySelector('.olpo-section[data-section=\"' + targetSection + '\"]');
                        if (targetSectionElement) {
                            targetSectionElement.classList.add('active');
                        }
                    });
                });
                
                // Module Toggle
                var form = document.getElementById('olpo-settings-form');
                var toggles = document.querySelectorAll('.olpo-module-toggle');
                
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!e.submitter || e.submitter.name !== 'cwo_save_settings') {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    });
                }
                
                toggles.forEach(function(toggle) {
                    toggle.addEventListener('change', function(e) {
                        var card = this.closest('.olpo-module-card');
                        var settings = card ? card.querySelector('.olpo-module-settings') : null;
                        
                        if (!settings) return;
                        
                        if (this.checked) {
                            settings.classList.add('show');
                        } else {
                            settings.classList.remove('show');
                        }
                    });
                });
            });
        })();
        ";
    }
}
