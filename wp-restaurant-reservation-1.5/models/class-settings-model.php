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

    /**
 * Generate time slot preview for settings page - FIXED VERSION
 */
public function get_time_slot_preview($duration = 60) {
    if (!class_exists('YRR_Hours_Model')) {
        return array();
    }
    
    $hours_model = new YRR_Hours_Model();
    
    // Try multiple methods to get today's hours
    $today_hours = null;
    
    // Method 1: Try get_today_hours (if available)
    if (method_exists($hours_model, 'get_today_hours')) {
        $today_hours = $hours_model->get_today_hours();
    }
    
    // Method 2: Try get_day_hours with today's day
    if (!$today_hours && method_exists($hours_model, 'get_day_hours')) {
        $today = strtolower(date('l')); // monday
        $today_hours = $hours_model->get_day_hours($today);
    }
    
    // Method 3: Try get_hours_for_day (your original method)
    if (!$today_hours && method_exists($hours_model, 'get_hours_for_day')) {
        $today = strtolower(date('l')); // monday
        $hours_array = $hours_model->get_hours_for_day($today);
        if (!empty($hours_array)) {
            $today_hours = $hours_array[0]; // Get first shift
        }
    }
    
    // Debug logging
    error_log("YRR Debug: Today is " . date('l') . " - Hours data: " . print_r($today_hours, true));
    
    if (!$today_hours) {
        error_log("YRR Debug: No hours data found for today");
        return array();
    }
    
    // Check if closed (handle both boolean and integer)
    $is_closed = false;
    if (isset($today_hours->is_closed)) {
        $is_closed = ($today_hours->is_closed == 1 || $today_hours->is_closed === true);
    }
    
    if ($is_closed) {
        error_log("YRR Debug: Restaurant is closed today (is_closed = " . $today_hours->is_closed . ")");
        return array();
    }
    
    // Get open and close times
    $open_time = '';
    $close_time = '';
    
    if (isset($today_hours->open_time) && isset($today_hours->close_time)) {
        $open_time = $today_hours->open_time;
        $close_time = $today_hours->close_time;
    }
    
    if (empty($open_time) || empty($close_time)) {
        error_log("YRR Debug: Missing open/close times - Open: '{$open_time}', Close: '{$close_time}'");
        return array();
    }
    
    // Convert to H:i format
    $open_time = date('H:i', strtotime($open_time));
    $close_time = date('H:i', strtotime($close_time));
    
    error_log("YRR Debug: Generating slots - Open: {$open_time}, Close: {$close_time}, Duration: {$duration}");
    
    $slots = $this->generate_slots_from_times($open_time, $close_time, $duration);
    
    error_log("YRR Debug: Generated " . count($slots) . " slots");
    
    return $slots;
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
