<?php
/**
 * Database Management Class - Error-Free Version
 */

if (!defined('ABSPATH')) exit;

class RRS_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $reservations_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rrs_reservations (
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
            table_number varchar(20) DEFAULT '',
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_code (reservation_code)
        ) $charset_collate;";
        
        $settings_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rrs_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_name varchar(100) NOT NULL,
            setting_value longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($reservations_sql);
        dbDelta($settings_sql);
        
        self::insert_default_data();
    }
    
    private static function insert_default_data() {
        global $wpdb;
        
        $default_settings = array(
            'restaurant_open' => '1',
            'max_party_size' => '12',
            'restaurant_name' => get_bloginfo('name'),
            'restaurant_email' => get_option('admin_email'),
            'restaurant_phone' => '',
            'restaurant_address' => ''
        );
        
        foreach ($default_settings as $name => $value) {
            $wpdb->replace($wpdb->prefix . 'rrs_settings', array(
                'setting_name' => $name,
                'setting_value' => $value
            ));
        }
        
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations");
        if ($existing == 0) {
            $sample_data = array(
                array(
                    'reservation_code' => 'RES-' . date('Ymd') . '-001',
                    'customer_name' => 'John Smith',
                    'customer_email' => 'john@example.com',
                    'customer_phone' => '123-456-7890',
                    'party_size' => 4,
                    'reservation_date' => date('Y-m-d'),
                    'reservation_time' => '19:00:00',
                    'special_requests' => 'Window table please',
                    'status' => 'confirmed',
                    'table_number' => 'T1'
                )
            );
            
            foreach ($sample_data as $reservation) {
                $wpdb->insert($wpdb->prefix . 'rrs_reservations', $reservation);
            }
        }
    }
    
    public static function fix_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rrs_reservations';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            self::create_tables();
            return;
        }
        
        $columns_to_add = array(
            'table_number' => "ALTER TABLE $table_name ADD COLUMN table_number VARCHAR(20) DEFAULT ''",
            'notes' => "ALTER TABLE $table_name ADD COLUMN notes TEXT DEFAULT NULL",
            'updated_at' => "ALTER TABLE $table_name ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
        
        foreach ($columns_to_add as $column => $sql) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column'");
            if (empty($column_exists)) {
                $wpdb->query($sql);
            }
        }
    }
}
?>
