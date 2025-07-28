<?php
/**
 * Settings Model - Yenolx Restaurant Reservation System v1.5.1
 * Protected against class redeclaration
 */

if (!defined('ABSPATH')) exit;

// Prevent duplicate class declaration
if (class_exists('YRR_Settings_Model')) {
    return;
}

class YRR_Settings_Model {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_settings';
    }
    
    /**
     * ✅ GET SETTING VALUE
     */
    public function get($setting_name, $default = '') {
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM {$this->table_name} WHERE setting_name = %s",
            $setting_name
        ));
        
        return $value !== null ? $value : $default;
    }
    
    /**
     * ✅ SET SETTING VALUE
     */
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
                array('setting_name' => $setting_name),
                array('%s', '%s'),
                array('%s')
            );
        } else {
            return $this->wpdb->insert(
                $this->table_name,
                array(
                    'setting_name' => $setting_name,
                    'setting_value' => $setting_value,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * ✅ GET ALL SETTINGS
     */
    public function get_all() {
        $results = $this->wpdb->get_results("SELECT setting_name, setting_value FROM {$this->table_name}");
        $settings = array();
        
        if (is_array($results)) {
            foreach ($results as $row) {
                $settings[$row->setting_name] = $row->setting_value;
            }
        }
        
        return $settings;
    }
    
    /**
     * ✅ DELETE SETTING
     */
    public function delete($setting_name) {
        return $this->wpdb->delete(
            $this->table_name,
            array('setting_name' => $setting_name),
            array('%s')
        );
    }
    
    /**
     * ✅ BULK UPDATE SETTINGS
     */
    public function bulk_update($settings_array) {
        $updated = 0;
        $errors = array();
        
        foreach ($settings_array as $setting_name => $setting_value) {
            $result = $this->set($setting_name, $setting_value);
            if ($result !== false) {
                $updated++;
            } else {
                $errors[] = "Failed to update: " . $setting_name;
            }
        }
        
        return array(
            'success' => empty($errors),
            'updated' => $updated,
            'errors' => $errors
        );
    }
    
    /**
     * ✅ GET RESTAURANT BASIC INFO
     */
    public function get_restaurant_info() {
        return array(
            'name' => $this->get('restaurant_name', get_bloginfo('name')),
            'email' => $this->get('restaurant_email', get_option('admin_email')),
            'phone' => $this->get('restaurant_phone', ''),
            'address' => $this->get('restaurant_address', ''),
            'is_open' => $this->get('restaurant_open', '1') === '1'
        );
    }
    
    /**
     * ✅ UPDATE RESTAURANT INFO
     */
    public function update_restaurant_info($info) {
        $settings = array(
            'restaurant_name' => sanitize_text_field($info['name'] ?? ''),
            'restaurant_email' => sanitize_email($info['email'] ?? ''),
            'restaurant_phone' => sanitize_text_field($info['phone'] ?? ''),
            'restaurant_address' => sanitize_textarea_field($info['address'] ?? ''),
            'restaurant_open' => isset($info['is_open']) ? '1' : '0'
        );
        
        return $this->bulk_update($settings);
    }
    
    /**
     * ✅ GET BOOKING SETTINGS
     */
    public function get_booking_settings() {
        return array(
            'max_party_size' => intval($this->get('max_party_size', '12')),
            'base_price_per_person' => floatval($this->get('base_price_per_person', '15.00')),
            'currency_symbol' => $this->get('currency_symbol', '$'),
            'booking_buffer_minutes' => intval($this->get('booking_buffer_minutes', '30')),
            'max_advance_booking_days' => intval($this->get('max_booking_advance_days', '60')),
            'enable_coupons' => $this->get('enable_coupons', '1') === '1'
        );
    }
    
    /**
     * ✅ UPDATE BOOKING SETTINGS
     */
    public function update_booking_settings($settings) {
        $booking_settings = array(
            'max_party_size' => intval($settings['max_party_size'] ?? 12),
            'base_price_per_person' => floatval($settings['base_price_per_person'] ?? 15.00),
            'currency_symbol' => sanitize_text_field($settings['currency_symbol'] ?? '$'),
            'booking_buffer_minutes' => intval($settings['booking_buffer_minutes'] ?? 30),
            'max_booking_advance_days' => intval($settings['max_advance_booking_days'] ?? 60),
            'enable_coupons' => isset($settings['enable_coupons']) ? '1' : '0'
        );
        
        // Validate settings
        if ($booking_settings['max_party_size'] < 1 || $booking_settings['max_party_size'] > 50) {
            return array('success' => false, 'errors' => array('Max party size must be between 1 and 50'));
        }
        
        if ($booking_settings['base_price_per_person'] < 0) {
            return array('success' => false, 'errors' => array('Base price cannot be negative'));
        }
        
        return $this->bulk_update($booking_settings);
    }
    
    /**
     * ✅ INITIALIZE DEFAULT SETTINGS
     */
    public function initialize_defaults() {
        $defaults = array(
            'restaurant_open' => '1',
            'restaurant_name' => get_bloginfo('name'),
            'restaurant_email' => get_option('admin_email'),
            'restaurant_phone' => '',
            'restaurant_address' => '',
            'max_party_size' => '12',
            'base_price_per_person' => '15.00',
            'booking_time_slots' => '30',
            'max_booking_advance_days' => '60',
            'currency_symbol' => '$',
            'booking_buffer_minutes' => '30',
            'max_dining_duration' => '120',
            'enable_coupons' => '1'
        );
        
        $initialized = 0;
        foreach ($defaults as $setting_name => $default_value) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT setting_value FROM {$this->table_name} WHERE setting_name = %s",
                $setting_name
            ));
            
            if ($existing === null) {
                $result = $this->set($setting_name, $default_value);
                if ($result !== false) {
                    $initialized++;
                }
            }
        }
        
        return $initialized;
    }
    
    /**
     * ✅ CHECK IF SETTING EXISTS
     */
    public function exists($setting_name) {
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM {$this->table_name} WHERE setting_name = %s",
            $setting_name
        ));
        
        return $value !== null;
    }
    
    /**
     * ✅ GET SETTINGS BY PREFIX
     */
    public function get_by_prefix($prefix) {
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT setting_name, setting_value FROM {$this->table_name} WHERE setting_name LIKE %s",
            $prefix . '%'
        ));
        
        $settings = array();
        if (is_array($results)) {
            foreach ($results as $row) {
                $settings[$row->setting_name] = $row->setting_value;
            }
        }
        
        return $settings;
    }
    
    /**
     * ✅ CLEAR CACHE (if using object cache)
     */
    public function clear_cache() {
        wp_cache_delete('yrr_settings_all', 'yrr_settings');
        return true;
    }
}
