<?php
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
 * ✅ FORCE DATABASE SETUP ON EVERY ADMIN LOAD
 */
function yrr_force_database_setup() {
    if (!is_admin()) return;
    
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();
    
    // Force create all tables
    $tables = array(
        'yrr_settings' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_name varchar(100) NOT NULL,
            setting_value longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate",
        
        'yrr_reservations' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_reservations (
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
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_code (reservation_code)
        ) $charset_collate"
    );
    
    foreach ($tables as $table => $sql) {
        $wpdb->query($sql);
    }
    
    // Insert default settings
    $default_settings = array(
        'restaurant_open' => '1',
        'restaurant_name' => get_bloginfo('name'),
        'restaurant_email' => get_option('admin_email'),
        'max_party_size' => '12'
    );
    
    foreach ($default_settings as $name => $value) {
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
}

/**
 * ✅ SAFE FILE LOADER WITH ERROR LOGGING
 */
function yrr_load_file($file_path) {
    $full_path = YRR_PLUGIN_PATH . $file_path;
    
    if (file_exists($full_path)) {
        require_once $full_path;
        error_log("YRR: Successfully loaded - {$file_path}");
        return true;
    } else {
        error_log("YRR: MISSING FILE - {$file_path} at {$full_path}");
        return false;
    }
}

/**
 * ✅ MAIN PLUGIN CLASS - BULLETPROOF VERSION
 */
if (!class_exists('YenolxRestaurantReservation')) {

class YenolxRestaurantReservation {
    
    private static $instance = null;
    private $admin_controller = null;
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hook into WordPress initialization
        add_action('init', array($this, 'init'), 1);
        add_action('admin_init', array($this, 'admin_init'), 1);
        add_action('admin_menu', array($this, 'force_admin_menu'), 5);
        add_action('wp_loaded', array($this, 'ensure_everything_loaded'));
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        // Force database setup
        yrr_force_database_setup();
        
        // Load all dependencies
        $this->load_all_dependencies();
        
        error_log('YRR: Plugin initialized successfully');
    }
    
    public function admin_init() {
        // Force load admin components
        $this->force_load_admin();
        
        error_log('YRR: Admin initialized');
    }
    
    /**
     * ✅ FORCE LOAD ALL DEPENDENCIES
     */
    private function load_all_dependencies() {
        // Load models first
        $models = array(
            'models/class-settings-model.php',
            'models/class-reservation-model.php',
            'models/class-tables-model.php',
            'models/class-hours-model.php'
        );
        
        foreach ($models as $model) {
            if (!yrr_load_file($model)) {
                error_log("YRR: CRITICAL - Failed to load model: {$model}");
            }
        }
        
        // Load controllers
        if (!yrr_load_file('controllers/class-admin-controller.php')) {
            error_log("YRR: CRITICAL - Failed to load admin controller");
        }
    }
    
    /**
     * ✅ FORCE ADMIN LOADING
     */
    private function force_load_admin() {
        if (!is_admin()) return;
        
        try {
            if (class_exists('YRR_Admin_Controller')) {
                $this->admin_controller = new YRR_Admin_Controller();
                error_log('YRR: Admin controller instantiated successfully');
            } else {
                error_log('YRR: CRITICAL - YRR_Admin_Controller class not found');
            }
        } catch (Exception $e) {
            error_log('YRR: CRITICAL - Error instantiating admin controller: ' . $e->getMessage());
        }
    }
    
    /**
     * ✅ FORCE ADMIN MENU CREATION
     */
    public function force_admin_menu() {
        if (!current_user_can('manage_options')) return;
        
        // Force create menu even if controller fails
        add_menu_page(
            'Yenolx Reservations',
            'Reservations',
            'manage_options',
            'yenolx-reservations',
            array($this, 'fallback_dashboard'),
            'dashicons-calendar-alt',
            26
        );
        
        add_submenu_page(
            'yenolx-reservations',
            'All Reservations',
            'All Reservations',
            'manage_options',
            'yrr-all-reservations',
            array($this, 'fallback_all_reservations')
        );
        
        error_log('YRR: Admin menu created (fallback mode)');
    }
    
    /**
     * ✅ FALLBACK DASHBOARD (ALWAYS WORKS)
     */
    public function fallback_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>🍽️ Yenolx Restaurant Dashboard</h1>';
        
        // Try to load proper dashboard
        if ($this->admin_controller && method_exists($this->admin_controller, 'dashboard_page')) {
            try {
                $this->admin_controller->dashboard_page();
                return;
            } catch (Exception $e) {
                error_log('YRR: Dashboard error: ' . $e->getMessage());
            }
        }
        
        // Fallback dashboard content
        $this->show_emergency_dashboard();
        echo '</div>';
    }
    
    /**
     * ✅ EMERGENCY DASHBOARD DISPLAY
     */
    private function show_emergency_dashboard() {
        global $wpdb;
        
        echo '<div style="background: white; padding: 30px; border-radius: 10px; margin: 20px 0;">';
        echo '<h2 style="color: #dc3545;">⚠️ Emergency Dashboard Mode</h2>';
        echo '<p>The main dashboard controller failed to load. Using emergency mode.</p>';
        
        // Show basic stats
        $reservations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_reservations WHERE 1=1");
        $today_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}yrr_reservations WHERE reservation_date = %s",
            date('Y-m-d')
        ));
        
        echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">';
        echo '<div style="background: #007cba; color: white; padding: 20px; border-radius: 10px; text-align: center;">';
        echo '<h3>Total Reservations</h3>';
        echo '<div style="font-size: 2rem; font-weight: bold;">' . intval($reservations_count) . '</div>';
        echo '</div>';
        echo '<div style="background: #28a745; color: white; padding: 20px; border-radius: 10px; text-align: center;">';
        echo '<h3>Today\'s Reservations</h3>';
        echo '<div style="font-size: 2rem; font-weight: bold;">' . intval($today_count) . '</div>';
        echo '</div>';
        echo '</div>';
        
        // Debug information
        echo '<div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;">';
        echo '<h3>🔧 System Debug Information</h3>';
        echo '<ul>';
        echo '<li><strong>Plugin Path:</strong> ' . YRR_PLUGIN_PATH . '</li>';
        echo '<li><strong>Admin Controller Loaded:</strong> ' . (class_exists('YRR_Admin_Controller') ? 'YES' : 'NO') . '</li>';
        echo '<li><strong>Models Available:</strong> ' . (class_exists('YRR_Reservation_Model') ? 'YES' : 'NO') . '</li>';
        echo '<li><strong>Database Tables:</strong> ' . ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}yrr_reservations'") ? 'EXISTS' : 'MISSING') . '</li>';
        echo '<li><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</li>';
        echo '<li><strong>PHP Version:</strong> ' . phpversion() . '</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * ✅ FALLBACK ALL RESERVATIONS
     */
    public function fallback_all_reservations() {
        echo '<div class="wrap">';
        echo '<h1>📋 All Reservations</h1>';
        
        // Try to load proper page
        if ($this->admin_controller && method_exists($this->admin_controller, 'all_reservations_page')) {
            try {
                $this->admin_controller->all_reservations_page();
                return;
            } catch (Exception $e) {
                error_log('YRR: All reservations error: ' . $e->getMessage());
            }
        }
        
        // Fallback content
        global $wpdb;
        $reservations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yrr_reservations ORDER BY created_at DESC LIMIT 20");
        
        if (!empty($reservations)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Customer</th><th>Date</th><th>Time</th><th>Party</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            foreach ($reservations as $reservation) {
                echo '<tr>';
                echo '<td>' . esc_html($reservation->customer_name) . '</td>';
                echo '<td>' . esc_html($reservation->reservation_date) . '</td>';
                echo '<td>' . esc_html($reservation->reservation_time) . '</td>';
                echo '<td>' . esc_html($reservation->party_size) . '</td>';
                echo '<td>' . esc_html($reservation->status) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No reservations found.</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * ✅ ENSURE EVERYTHING IS LOADED
     */
    public function ensure_everything_loaded() {
        if (is_admin()) {
            // Double-check everything is working
            if (!class_exists('YRR_Admin_Controller')) {
                error_log('YRR: CRITICAL - Admin controller still not loaded after wp_loaded');
                $this->emergency_load_controller();
            }
        }
    }
    
    /**
     * ✅ EMERGENCY CONTROLLER LOADER
     */
    private function emergency_load_controller() {
        $controller_path = YRR_PLUGIN_PATH . 'controllers/class-admin-controller.php';
        
        if (file_exists($controller_path)) {
            include_once $controller_path;
            error_log('YRR: Emergency loaded admin controller');
        } else {
            error_log('YRR: CRITICAL - Admin controller file missing at: ' . $controller_path);
        }
    }
    
    public function activate() {
        yrr_force_database_setup();
        flush_rewrite_rules();
        error_log('YRR: Plugin activated');
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        error_log('YRR: Plugin deactivated');
    }
}

} // End class check

/**
 * ✅ INITIALIZE PLUGIN - MULTIPLE ATTEMPTS
 */
function yrr_init_plugin() {
    try {
        $yrr_plugin = YenolxRestaurantReservation::get_instance();
        error_log('YRR: Plugin instance created successfully');
    } catch (Exception $e) {
        error_log('YRR: CRITICAL - Failed to initialize plugin: ' . $e->getMessage());
    }
}

// Hook plugin initialization to multiple WordPress events
add_action('plugins_loaded', 'yrr_init_plugin', 1);
add_action('init', 'yrr_init_plugin', 1);
add_action('admin_init', 'yrr_init_plugin', 1);

/**
 * ✅ FORCE ACTIVATION SEQUENCE
 */
register_activation_hook(__FILE__, function() {
    yrr_force_database_setup();
    yrr_init_plugin();
    error_log('YRR: Activation hook completed');
});

// ✅ EMERGENCY DEBUG FUNCTION
function yrr_emergency_debug() {
    if (!current_user_can('manage_options') || !isset($_GET['yrr_debug'])) return;
    
    echo '<div style="background: red; color: white; padding: 20px; margin: 20px; position: fixed; top: 32px; right: 20px; z-index: 9999; border-radius: 10px; max-width: 400px;">';
    echo '<h3>🚨 YRR Emergency Debug</h3>';
    echo '<p><strong>Plugin Path:</strong> ' . (defined('YRR_PLUGIN_PATH') ? YRR_PLUGIN_PATH : 'UNDEFINED') . '</p>';
    echo '<p><strong>Admin Controller:</strong> ' . (class_exists('YRR_Admin_Controller') ? 'LOADED' : 'MISSING') . '</p>';
    echo '<p><strong>Current User Can:</strong> ' . (current_user_can('manage_options') ? 'YES' : 'NO') . '</p>';
    echo '<p><strong>Is Admin:</strong> ' . (is_admin() ? 'YES' : 'NO') . '</p>';
    echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
    echo '</div>';
}
add_action('admin_notices', 'yrr_emergency_debug');


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
