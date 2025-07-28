<?php
/**
 * Settings Model - Yenolx Restaurant Reservation v1.5.1
 */

if (!defined('ABSPATH')) exit;

class YRR_Settings_Model {
    private $table_name;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_settings';
    }
    
    public function get($setting_name, $default = '') {
        $result = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM {$this->table_name} WHERE setting_name = %s",
            $setting_name
        ));
        
        return $result !== null ? $result : $default;
    }
    
    public function set($setting_name, $setting_value) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE setting_name = %s",
            $setting_name
        ));
        
        if ($existing) {
            return $this->wpdb->update(
                $this->table_name,
                array(
                    'setting_value' => $setting_value,
                    'updated_at' => current_time('mysql')
                ),
                array('setting_name' => $setting_name)
            );
        } else {
            return $this->wpdb->insert($this->table_name, array(
                'setting_name' => $setting_name,
                'setting_value' => $setting_value,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ));
        }
    }
    
    public function get_all() {
        $results = $this->wpdb->get_results("SELECT setting_name, setting_value FROM {$this->table_name}");
        $settings = array();
        
        foreach ($results as $result) {
            $settings[$result->setting_name] = $result->setting_value;
        }
        
        return $settings;
    }
    
    public function validate_phone($phone) {
        if (empty($phone)) return '';
        
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d\+]/', '', $phone);
        
        return $phone;
    }
    
    public function validate_address($address) {
        return sanitize_text_field($address);
    }
}
?>
