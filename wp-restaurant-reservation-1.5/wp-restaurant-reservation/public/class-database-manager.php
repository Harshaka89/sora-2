<?php
class RRS_Database_Manager {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Reservations table
        $reservations_sql = "CREATE TABLE {$wpdb->prefix}rrs_reservations (
            id int(11) NOT NULL AUTO_INCREMENT,
            reservation_code varchar(20) UNIQUE NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            party_size int(11) NOT NULL,
            reservation_date date NOT NULL,
            reservation_time time NOT NULL,
            special_requests text,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tables table
        $tables_sql = "CREATE TABLE {$wpdb->prefix}rrs_tables (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            capacity_min int(11) NOT NULL DEFAULT 1,
            capacity_max int(11) NOT NULL DEFAULT 8,
            x_position int(11) DEFAULT 0,
            y_position int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($reservations_sql);
        dbDelta($tables_sql);
        
        // Insert sample data
        self::insert_sample_data();
    }
    
    private static function insert_sample_data() {
        global $wpdb;
        
        // Sample reservations
        $wpdb->insert($wpdb->prefix . 'rrs_reservations', array(
            'reservation_code' => 'RES-001',
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
            'customer_phone' => '123-456-7890',
            'party_size' => 4,
            'reservation_date' => date('Y-m-d'),
            'reservation_time' => '19:00:00',
            'special_requests' => 'Window table please',
            'status' => 'confirmed'
        ));
        
        // Sample tables
        $sample_tables = array(
            array('name' => 'Table 1', 'capacity_min' => 2, 'capacity_max' => 4),
            array('name' => 'Table 2', 'capacity_min' => 2, 'capacity_max' => 4),
            array('name' => 'Table 3', 'capacity_min' => 4, 'capacity_max' => 6),
            array('name' => 'Table 4', 'capacity_min' => 6, 'capacity_max' => 8)
        );
        
        foreach ($sample_tables as $table) {
            $wpdb->insert($wpdb->prefix . 'rrs_tables', $table);
        }
    }
}
