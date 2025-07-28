<?php
/**
 * Plugin Name: Yenolx Restaurant Reservation System
 * Plugin URI: https://yenolx.com
 * Description: Complete restaurant reservation management system with MVC architecture
 * Version: 1.5.1
 * Author: Yenolx Team
 * Text Domain: yenolx-reservations
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

// Prevent duplicate loading
if (defined('YRR_VERSION')) {
    return;
}

// Define plugin constants
define('YRR_VERSION', '1.5.1');
define('YRR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YRR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YRR_PLUGIN_FILE', __FILE__);

/**
 * Enhanced database structure for v1.5.1
 */
function yrr_ensure_database_structure() {
    if (!is_admin() || wp_doing_ajax()) return;
    
    if (get_transient('yrr_db_check_done_v151')) return;
    
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();
    
    // Settings table
    $settings_table = $wpdb->prefix . 'yrr_settings';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $settings_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        setting_name varchar(100) NOT NULL,
        setting_value longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY setting_name (setting_name)
    ) $charset_collate");
    
    // Reservations table
    $reservations_table = $wpdb->prefix . 'yrr_reservations';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $reservations_table (
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
        coupon_code varchar(50) DEFAULT NULL,
        original_price decimal(10,2) DEFAULT 0.00,
        discount_amount decimal(10,2) DEFAULT 0.00,
        final_price decimal(10,2) DEFAULT 0.00,
        price_breakdown text DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY reservation_code (reservation_code)
    ) $charset_collate");
    
    // Tables management
    $tables_table = $wpdb->prefix . 'yrr_tables';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $tables_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        table_number varchar(20) NOT NULL,
        capacity int(11) NOT NULL,
        status varchar(20) DEFAULT 'available',
        location varchar(100) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY table_number (table_number)
    ) $charset_collate");
    
    // Operating hours
    $hours_table = $wpdb->prefix . 'yrr_operating_hours';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $hours_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        day_of_week varchar(10) NOT NULL,
        shift_name varchar(50) DEFAULT 'all_day',
        open_time time DEFAULT NULL,
        close_time time DEFAULT NULL,
        is_closed boolean DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY day_shift (day_of_week, shift_name)
    ) $charset_collate");
    
    // Insert default data
    yrr_insert_default_data();
    
    set_transient('yrr_db_check_done_v151', true, DAY_IN_SECONDS);
}

/**
 * Insert default data into database tables
 */
function yrr_insert_default_data() {
    global $wpdb;
    
    // Default settings
    $settings = array(
        'restaurant_open' => '1',
        'restaurant_name' => get_bloginfo('name'),
        'restaurant_email' => get_option('admin_email'),
        'max_party_size' => '12',
        'time_slot_duration' => '60',
        'max_advance_booking' => '30',
        'auto_confirm_reservations' => '0',
        'booking_buffer_minutes' => '60'
    );
    
    foreach ($settings as $name => $value) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$wpdb->prefix}yrr_settings WHERE setting_name = %s",
            $name
        ));
        
        if ($existing === null) {
            $wpdb->insert($wpdb->prefix . 'yrr_settings', array(
                'setting_name' => $name,
                'setting_value' => $value
            ));
        }
    }
    
    // Default operating hours
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    foreach ($days as $day) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}yrr_operating_hours WHERE day_of_week = %s AND shift_name = %s",
            $day, 'all_day'
        ));
        
        if (!$existing) {
            $wpdb->insert($wpdb->prefix . 'yrr_operating_hours', array(
                'day_of_week' => $day,
                'shift_name' => 'all_day',
                'open_time' => '10:00:00',
                'close_time' => '22:00:00',
                'is_closed' => 0
            ));
        }
    }
    
    // Default tables
    $existing_tables = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_tables");
    if ($existing_tables == 0) {
        $tables = array(
            array('table_number' => 'T1', 'capacity' => 2, 'location' => 'Window'),
            array('table_number' => 'T2', 'capacity' => 4, 'location' => 'Center'),
            array('table_number' => 'T3', 'capacity' => 6, 'location' => 'Private'),
            array('table_number' => 'T4', 'capacity' => 8, 'location' => 'VIP')
        );
        
        foreach ($tables as $table) {
            $wpdb->insert($wpdb->prefix . 'yrr_tables', $table);
        }
    }
}

/**
 * Safe autoloader with error handling
 */
function yrr_autoloader($class_name) {
    if (strpos($class_name, 'YRR_') !== 0) return;
    
    $class_file = str_replace('_', '-', strtolower(substr($class_name, 4)));
    $directories = array('models/', 'controllers/', 'includes/');
    
    foreach ($directories as $directory) {
        $file = YRR_PLUGIN_PATH . $directory . 'class-' . $class_file . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

/**
 * Main Plugin Class - Complete MVC Architecture
 */
if (!class_exists('YenolxRestaurantReservation')) {

class YenolxRestaurantReservation {
    
    private static $instance = null;
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        $this->load_dependencies();
        
        if (is_admin()) {
            $this->init_admin();
        }
        
        load_plugin_textdomain('yenolx-reservations', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    private function load_dependencies() {
        // Load core files
        $this->load_file('includes/class-database.php');
        
        // Load Models (Data Layer)
        $models = array(
            'models/class-settings-model.php',
            'models/class-hours-model.php',
            'models/class-reservation-model.php',
            'models/class-tables-model.php'
        );
        
        foreach ($models as $model) {
            $this->load_file($model);
        }
        
        // Load Controllers (Business Logic)
        $this->load_file('controllers/class-admin-controller.php');
    }
    
    private function load_file($file_path) {
        $full_path = YRR_PLUGIN_PATH . $file_path;
        
        if (file_exists($full_path)) {
            require_once $full_path;
        } else {
            error_log("YRR: Missing file - {$file_path}");
        }
    }
    
    private function init_admin() {
        if (!class_exists('YRR_Admin_Controller')) {
            return;
        }
        
        $admin_controller = new YRR_Admin_Controller();
        add_action('admin_menu', array($admin_controller, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin_controller, 'enqueue_admin_assets'));
        
        // Register AJAX handlers
        add_action('wp_ajax_get_time_slot_preview', array($this, 'ajax_get_time_slot_preview'));
    }
    
    public function activate() {
        yrr_ensure_database_structure();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    // AJAX Handler for time slot preview
    public function ajax_get_time_slot_preview() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $duration = intval($_POST['duration'] ?? 60);
        
        if (!class_exists('YRR_Settings_Model')) {
            wp_send_json_error('Settings model not available');
            return;
        }
        
        $settings_model = new YRR_Settings_Model();
        $slots = $settings_model->get_time_slot_preview($duration);
        
        wp_send_json_success($slots);
    }
    
} // ✅ Class closing brace

} // ✅ if (!class_exists) closing brace

/**
 * Debug functions for troubleshooting
 */
function yrr_debug_reservation_system() {
    if (!current_user_can('manage_options') || !isset($_GET['yrr_debug'])) {
        return;
    }
    
    global $wpdb;
    
    echo '<div style="background: white; padding: 20px; margin: 20px; border: 2px solid #007cba;">';
    echo '<h3>🔍 Yenolx Restaurant Reservation System Debug</h3>';
    
    // Check if classes exist
    $classes = array('YRR_Settings_Model', 'YRR_Hours_Model', 'YRR_Admin_Controller');
    foreach ($classes as $class) {
        echo '<p>' . ($class_exists($class) ? '✅' : '❌') . ' ' . $class . '</p>';
    }
    
    // Check database tables
    $tables = array('yrr_settings', 'yrr_reservations', 'yrr_tables', 'yrr_operating_hours');
    foreach ($tables as $table) {
        $full_table_name = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name") : 0;
        echo '<p>' . ($exists ? '✅' : '❌') . ' ' . $table . ' (' . $count . ' records)</p>';
    }
    
    echo '</div>';
}

// Hook registrations
add_action('admin_init', 'yrr_ensure_database_structure', 1);
add_action('admin_notices', 'yrr_debug_reservation_system');

// Register autoloader
spl_autoload_register('yrr_autoloader');

/**
 * Initialize plugin
 */
function yrr_init_plugin() {
    $yrr_plugin = YenolxRestaurantReservation::get_instance();
}

add_action('plugins_loaded', 'yrr_init_plugin');
