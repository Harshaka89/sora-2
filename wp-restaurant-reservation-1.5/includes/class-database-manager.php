<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('YRR_Database')) {

class YRR_Database {
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Reservations table
        $sql_reservations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_reservations (
            id int(11) NOT NULL AUTO_INCREMENT,
            reservation_code varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            party_size int(11) NOT NULL,
            reservation_date date NOT NULL,
            reservation_time time NOT NULL,
            table_id int(11) DEFAULT NULL,
            status enum('pending','confirmed','cancelled') DEFAULT 'pending',
            special_requests text,
            notes text,
            original_price decimal(10,2) DEFAULT 0.00,
            discount_amount decimal(10,2) DEFAULT 0.00,
            final_price decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_code (reservation_code),
            KEY idx_date_time (reservation_date, reservation_time),
            KEY idx_status (status)
        ) $charset_collate;";
        
        // Tables management
        $sql_tables = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_tables (
            id int(11) NOT NULL AUTO_INCREMENT,
            table_number varchar(50) NOT NULL,
            capacity int(11) NOT NULL,
            location varchar(255) DEFAULT NULL,
            table_type varchar(50) DEFAULT 'standard',
            status enum('available','occupied','maintenance') DEFAULT 'available',
            position_x int(11) DEFAULT 0,
            position_y int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY table_number (table_number)
        ) $charset_collate;";
        
        // Operating hours
        $sql_hours = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_operating_hours (
            id int(11) NOT NULL AUTO_INCREMENT,
            day_of_week varchar(20) NOT NULL,
            open_time time NOT NULL,
            close_time time NOT NULL,
            is_closed tinyint(1) DEFAULT 0,
            break_start time DEFAULT NULL,
            break_end time DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY day_of_week (day_of_week)
        ) $charset_collate;";
        
        // Settings
        $sql_settings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        // Pricing rules
        $sql_pricing = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_pricing (
            id int(11) NOT NULL AUTO_INCREMENT,
            rule_name varchar(255) NOT NULL,
            rule_type enum('base','time','date','party_size') DEFAULT 'base',
            conditions longtext,
            price decimal(10,2) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Discount coupons
        $sql_coupons = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_coupons (
            id int(11) NOT NULL AUTO_INCREMENT,
            coupon_code varchar(50) NOT NULL,
            discount_type enum('percentage','fixed') DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL,
            min_amount decimal(10,2) DEFAULT 0.00,
            max_uses int(11) DEFAULT 0,
            current_uses int(11) DEFAULT 0,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY coupon_code (coupon_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_reservations);
        dbDelta($sql_tables);
        dbDelta($sql_hours);
        dbDelta($sql_settings);
        dbDelta($sql_pricing);
        dbDelta($sql_coupons);
        
        // Insert default tables
        $this->create_default_tables();
    }
    
    private function create_default_tables() {
        global $wpdb;
        
        $existing_tables = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_tables");
        
        if ($existing_tables == 0) {
            $default_tables = array(
                array('T1', 2, 'Main Dining'),
                array('T2', 4, 'Main Dining'),
                array('T3', 4, 'Main Dining'),
                array('T4', 6, 'Main Dining'),
                array('T5', 6, 'Main Dining'),
                array('T6', 8, 'Private Section'),
                array('T7', 2, 'Bar Area'),
                array('T8', 2, 'Bar Area')
            );
            
            foreach ($default_tables as $table) {
                $wpdb->insert(
                    $wpdb->prefix . 'yrr_tables',
                    array(
                        'table_number' => $table[0],
                        'capacity' => $table[1],
                        'location' => $table[2],
                        'status' => 'available'
                    )
                );
            }
        }
    }
}

}
?>
