<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('YRR_Settings_Model')) {

class YRR_Settings_Model {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_settings';
    }
    
    public function get($setting_key, $default = '') {
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM {$this->table_name} WHERE setting_key = %s",
            $setting_key
        ));
        
        return $value !== null ? $value : $default;
    }
    
    public function set($setting_key, $setting_value) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE setting_key = %s",
            $setting_key
        ));
        
        $data = array(
            'setting_key' => $setting_key,
            'setting_value' => $setting_value,
            'updated_at' => current_time('mysql')
        );
        
        if ($existing) {
            return $this->wpdb->update(
                $this->table_name,
                $data,
                array('setting_key' => $setting_key)
            );
        } else {
            $data['created_at'] = current_time('mysql');
            return $this->wpdb->insert($this->table_name, $data);
        }
    }
    
    public function get_all() {
        $results = $this->wpdb->get_results(
            "SELECT setting_key, setting_value FROM {$this->table_name}"
        );
        
        $settings = array();
        foreach ($results as $result) {
            $settings[$result->setting_key] = $result->setting_value;
        }
        
        return $settings;
    }
    
    /**
     * ✅ Time Slot Generation - Preview for settings page
     */
    public function get_time_slot_preview($duration = 60) {
        $hours_model = new YRR_Hours_Model();
        $today_hours = $hours_model->get_today_hours();
        
        if (!$today_hours || $today_hours->is_closed) {
            return array();
        }
        
        $open_time = date('H:i', strtotime($today_hours->open_time));
        $close_time = date('H:i', strtotime($today_hours->close_time));
        
        return $this->generate_slots_from_times($open_time, $close_time, $duration);
    }
    
    /**
     * ✅ Available Slots - Real-time availability checking
     */
    public function get_available_time_slots($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $duration = intval($this->get('time_slot_duration', 60));
        $hours_model = new YRR_Hours_Model();
        
        // Get day of week
        $day_of_week = strtolower(date('l', strtotime($date)));
        $day_hours = $hours_model->get_day_hours($day_of_week);
        
        if (!$day_hours || $day_hours->is_closed) {
            return array();
        }
        
        $open_time = date('H:i', strtotime($day_hours->open_time));
        $close_time = date('H:i', strtotime($day_hours->close_time));
        
        $slots = array();
        $open_minutes = $this->time_to_minutes($open_time);
        $close_minutes = $this->time_to_minutes($close_time);
        
        if ($close_minutes <= $open_minutes) {
            $close_minutes += 24 * 60;
        }
        
        for ($current = $open_minutes; $current < $close_minutes; $current += $duration) {
            $hour = floor($current / 60) % 24;
            $minute = $current % 60;
            $time_string = sprintf('%02d:%02d', $hour, $minute);
            
            $slots[] = array(
                'value' => $time_string,
                'display' => $this->format_time_12hour($time_string),
                'available' => $this->is_slot_available($date, $time_string)
            );
        }
        
        return $slots;
    }
    
    private function generate_slots_from_times($open_time, $close_time, $duration) {
        $slots = array();
        $open_minutes = $this->time_to_minutes($open_time);
        $close_minutes = $this->time_to_minutes($close_time);
        
        if ($close_minutes <= $open_minutes) {
            $close_minutes += 24 * 60;
        }
        
        for ($current = $open_minutes; $current < $close_minutes; $current += $duration) {
            $hour = floor($current / 60) % 24;
            $minute = $current % 60;
            $time_string = sprintf('%02d:%02d', $hour, $minute);
            
            $slots[] = array(
                'value' => $time_string,
                'display' => $this->format_time_12hour($time_string)
            );
        }
        
        return $slots;
    }
    
    private function time_to_minutes($time) {
        list($hours, $minutes) = explode(':', $time);
        return intval($hours) * 60 + intval($minutes);
    }
    
    private function format_time_12hour($time) {
        return date('g:i A', strtotime($time));
    }
    
    /**
     * ✅ Real-time Availability Checking
     */
    private function is_slot_available($date, $time) {
        global $wpdb;
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}yrr_reservations 
             WHERE reservation_date = %s AND reservation_time = %s AND status IN ('confirmed', 'pending')",
            $date, $time . ':00'
        ));
        
        $total_tables = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_tables WHERE status = 'available'");
        
        return $existing < $total_tables;
    }
    
    public function validate_phone($phone) {
        if (empty($phone)) return '';
        return preg_replace('/[^\d\+]/', '', $phone);
    }
    
    public function validate_address($address) {
        return sanitize_text_field($address);
    }
}

}
?>
