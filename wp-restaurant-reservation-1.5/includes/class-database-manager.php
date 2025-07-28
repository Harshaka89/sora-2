<?php
/**
 * Database Management - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Settings table
        $settings_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_name varchar(100) NOT NULL,
            setting_value longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";
        
        // Reservations table
        $reservations_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_reservations (
            id int(11) NOT NULL AUTO_INCREMENT,
            reservation_code varchar(20) NOT NULL DEFAULT '',
            customer_name varchar(100) NOT NULL DEFAULT '',
            customer_email varchar(100) NOT NULL DEFAULT '',
            customer_phone varchar(20) NOT NULL DEFAULT '',
            party_size int(11) NOT NULL DEFAULT 1,
            reservation_date date NOT NULL,
            reservation_time time NOT NULL,
            special_requests text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            table_id int(11) DEFAULT NULL,
            total_price decimal(10,2) DEFAULT 0.00,
            price_breakdown text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_code (reservation_code),
            INDEX idx_date (reservation_date),
            INDEX idx_status (status),
            INDEX idx_table (table_id)
        ) $charset_collate;";
        
        // Tables management
        $tables_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_tables (
            id int(11) NOT NULL AUTO_INCREMENT,
            table_number varchar(20) NOT NULL,
            capacity int(11) NOT NULL,
            status varchar(20) DEFAULT 'available',
            location varchar(100) DEFAULT '',
            table_type varchar(50) DEFAULT 'standard',
            position_x int(11) DEFAULT 0,
            position_y int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY table_number (table_number)
        ) $charset_collate;";
        
        // Operating hours
        $hours_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_operating_hours (
            id int(11) NOT NULL AUTO_INCREMENT,
            day_of_week varchar(10) NOT NULL,
            shift_name varchar(50) DEFAULT 'all_day',
            open_time time DEFAULT NULL,
            close_time time DEFAULT NULL,
            is_closed boolean DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY day_shift (day_of_week, shift_name)
        ) $charset_collate;";
        
        // Pricing rules
        $pricing_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_pricing_rules (
            id int(11) NOT NULL AUTO_INCREMENT,
            rule_name varchar(100) NOT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            days_applicable varchar(20) DEFAULT 'all',
            price_modifier decimal(10,2) DEFAULT 0.00,
            modifier_type varchar(10) DEFAULT 'add',
            is_active boolean DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($settings_sql);
        dbDelta($reservations_sql);
        dbDelta($tables_sql);
        dbDelta($hours_sql);
        dbDelta($pricing_sql);
        
        self::insert_default_data();
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
        // Insert default settings
        $default_settings = array(
            'restaurant_open' => '1',
            'restaurant_name' => get_bloginfo('name'),
            'restaurant_email' => get_option('admin_email'),
            'restaurant_phone' => '',
            'restaurant_address' => '',
            'max_party_size' => '12',
            'base_price_per_person' => '0.00',
            'booking_time_slots' => '30',
            'max_booking_advance_days' => '60'
        );
        
        foreach ($default_settings as $name => $value) {
            $wpdb->replace($wpdb->prefix . 'yrr_settings', array(
                'setting_name' => $name,
                'setting_value' => $value
            ));
        }
        
        // Insert default tables if none exist
        $existing_tables = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_tables");
        if ($existing_tables == 0) {
            $default_tables = array(
                array('table_number' => 'T1', 'capacity' => 2, 'location' => 'Window'),
                array('table_number' => 'T2', 'capacity' => 4, 'location' => 'Center'),
                array('table_number' => 'T3', 'capacity' => 6, 'location' => 'Private'),
                array('table_number' => 'T4', 'capacity' => 8, 'location' => 'VIP')
            );
            
            foreach ($default_tables as $table) {
                $wpdb->insert($wpdb->prefix . 'yrr_tables', $table);
            }
        }
        
        // Insert default operating hours
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        foreach ($days as $day) {
            $wpdb->replace($wpdb->prefix . 'yrr_operating_hours', array(
                'day_of_week' => $day,
                'shift_name' => 'all_day',
                'open_time' => '10:00:00',
                'close_time' => '22:00:00',
                'is_closed' => 0
            ));
        }
    }
}
?>
