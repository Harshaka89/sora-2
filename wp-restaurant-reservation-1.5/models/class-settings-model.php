<?php
/**
 * Settings Model - Yenolx Restaurant Reservation v1.5.1
 */

<?php
if (!defined('ABSPATH')) exit;

class YRR_Settings_Model {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_settings';
    }
    
    public function get($setting_name, $default = '') {
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM {$this->table_name} WHERE setting_name = %s",
            $setting_name
        ));
        return $value !== null ? $value : $default;
    }
    
    public function set($setting_name, $setting_value) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE setting_name = %s",
            $setting_name
        ));
        
        if ($existing) {
            return $this->wpdb->update(
                $this->table_name,
                array('setting_value' => $setting_value),
                array('setting_name' => $setting_name)
            );
        } else {
            return $this->wpdb->insert(
                $this->table_name,
                array('setting_name' => $setting_name, 'setting_value' => $setting_value)
            );
        }
    }
    
    public function get_all() {
        $results = $this->wpdb->get_results("SELECT setting_name, setting_value FROM {$this->table_name}");
        $settings = array();
        foreach ($results as $row) {
            $settings[$row->setting_name] = $row->setting_value;
        }
        return $settings;
    }
    
    /**
     * ✅ SIMPLE: Get predefined time slots for admin selection
     */
    public function get_simple_time_slots() {
        return array(
            '10:00:00' => '10:00 AM',
            '10:30:00' => '10:30 AM', 
            '11:00:00' => '11:00 AM',
            '11:30:00' => '11:30 AM',
            '12:00:00' => '12:00 PM',
            '12:30:00' => '12:30 PM',
            '13:00:00' => '1:00 PM',
            '13:30:00' => '1:30 PM',
            '14:00:00' => '2:00 PM',
            '14:30:00' => '2:30 PM',
            '15:00:00' => '3:00 PM',
            '15:30:00' => '3:30 PM',
            '16:00:00' => '4:00 PM',
            '16:30:00' => '4:30 PM',
            '17:00:00' => '5:00 PM',
            '17:30:00' => '5:30 PM',
            '18:00:00' => '6:00 PM',
            '18:30:00' => '6:30 PM',
            '19:00:00' => '7:00 PM',
            '19:30:00' => '7:30 PM',
            '20:00:00' => '8:00 PM',
            '20:30:00' => '8:30 PM',
            '21:00:00' => '9:00 PM',
            '21:30:00' => '9:30 PM',
            '22:00:00' => '10:00 PM'
        );
    }
    
    /**
     * ✅ SIMPLE: Check if time slot is available
     */
    public function is_time_available($date, $time) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}yrr_reservations 
             WHERE reservation_date = %s AND reservation_time = %s 
             AND status IN ('confirmed', 'pending')",
            $date, $time
        ));
        
        $max_bookings = intval($this->get('max_bookings_per_slot', '5'));
        return $count < $max_bookings;
    }
}



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
