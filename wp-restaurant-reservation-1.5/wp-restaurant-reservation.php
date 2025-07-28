<?php
/**
 * Plugin Name: Yenolx Restaurant Reservation System
 * Plugin URI: https://yenolx.com
 * Description: Complete restaurant reservation management system with dynamic time slots, table assignment, and real-time availability checking
 * Version: 1.5.1
 * Author: Yenolx Team
 * Author URI: https://yenolx.com/
 * Text Domain: yenolx-reservations
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
define('YRR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class - Yenolx Restaurant Reservation System
 */
if (!class_exists('YenolxRestaurantReservation')) {

class YenolxRestaurantReservation {
    
    private static $instance = null;
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize admin interface
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Load textdomain for translations
        load_plugin_textdomain('yenolx-reservations', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Load all plugin dependencies
     */
    private function load_dependencies() {
        // Load database setup
        $this->load_file('includes/class-database.php');
        
        // Load models
        $models = array(
            'models/class-settings-model.php',
            'models/class-hours-model.php',
            'models/class-reservation-model.php',
            'models/class-tables-model.php',
            'models/class-pricing-model.php',
            'models/class-coupons-model.php'
        );
        
        foreach ($models as $model) {
            $this->load_file($model);
        }
        
        // Load controllers
        $controllers = array(
            'controllers/class-admin-controller.php',
            'controllers/class-reservation-controller.php'
        );
        
        foreach ($controllers as $controller) {
            $this->load_file($controller);
        }
    }
    
    /**
     * Load individual file with error checking
     */
    private function load_file($file_path) {
        $full_path = YRR_PLUGIN_PATH . $file_path;
        
        if (file_exists($full_path)) {
            require_once $full_path;
        } else {
            error_log("Yenolx Restaurant Reservation: Missing file - {$file_path}");
        }
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        if (!class_exists('YRR_Admin_Controller')) {
            return;
        }
        
        $admin_controller = new YRR_Admin_Controller();
        
        // Add admin hooks
        add_action('admin_menu', array($admin_controller, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin_controller, 'enqueue_admin_assets'));
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Time slot preview for settings page
        add_action('wp_ajax_get_time_slot_preview', array($this, 'ajax_get_time_slot_preview'));
        
        // Get available tables for reservation
        add_action('wp_ajax_get_available_tables', array($this, 'ajax_get_available_tables'));
        
        // Check slot availability in real-time
        add_action('wp_ajax_check_slot_availability', array($this, 'ajax_check_slot_availability'));
        
        // Additional AJAX handlers
        add_action('wp_ajax_create_reservation', array($this, 'ajax_create_reservation'));
        add_action('wp_ajax_update_reservation', array($this, 'ajax_update_reservation'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (class_exists('YRR_Database')) {
            $database = new YRR_Database();
            $database->create_tables();
        }
        
        // Create default data
        $this->create_default_data();
        
        // Set activation flag
        update_option('yrr_activation_time', current_time('mysql'));
        update_option('yrr_version', YRR_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events if any
        wp_clear_scheduled_hook('yrr_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create default data on activation
     */
    private function create_default_data() {
        if (!class_exists('YRR_Hours_Model') || !class_exists('YRR_Settings_Model')) {
            return;
        }
        
        $hours_model = new YRR_Hours_Model();
        $settings_model = new YRR_Settings_Model();
        
        // Create default operating hours
        $default_hours = array(
            'monday'    => array('10:00:00', '22:00:00', 0),
            'tuesday'   => array('10:00:00', '22:00:00', 0),
            'wednesday' => array('10:00:00', '22:00:00', 0),
            'thursday'  => array('10:00:00', '22:00:00', 0),
            'friday'    => array('10:00:00', '23:00:00', 0),
            'saturday'  => array('09:00:00', '23:00:00', 0),
            'sunday'    => array('11:00:00', '21:00:00', 0)
        );
        
        foreach ($default_hours as $day => $hours) {
            $hours_model->set_hours($day, $hours[0], $hours[1], $hours[2]);
        }
        
        // Create default settings
        $default_settings = array(
            'restaurant_name' => get_bloginfo('name') ?: 'Yenolx Restaurant',
            'restaurant_open' => '1',
            'time_slot_duration' => '60',
            'max_party_size' => '12',
            'max_advance_booking' => '30',
            'auto_confirm_reservations' => '0',
            'restaurant_email' => get_option('admin_email'),
            'booking_buffer_minutes' => '60'
        );
        
        foreach ($default_settings as $key => $value) {
            // Only set if not already exists
            if (empty($settings_model->get($key))) {
                $settings_model->set($key, $value);
            }
        }
    }
    
    // ===== AJAX HANDLERS =====
    
    /**
     * AJAX: Get time slot preview for settings page
     */
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
    
    /**
     * AJAX: Get available tables for specific date/time
     */
    public function ajax_get_available_tables() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date'] ?? '');
        $time = sanitize_text_field($_POST['time'] ?? '');
        $party_size = intval($_POST['party_size'] ?? 1);
        
        if (!class_exists('YRR_Tables_Model')) {
            wp_send_json_error('Tables model not available');
            return;
        }
        
        $tables_model = new YRR_Tables_Model();
        $available_tables = $tables_model->get_available_tables($date, $time, $party_size);
        
        wp_send_json_success($available_tables);
    }
    
    /**
     * AJAX: Check slot availability in real-time
     */
    public function ajax_check_slot_availability() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date'] ?? '');
        
        if (!class_exists('YRR_Settings_Model')) {
            wp_send_json_error('Settings model not available');
            return;
        }
        
        $settings_model = new YRR_Settings_Model();
        $slots = $settings_model->get_available_time_slots($date);
        
        wp_send_json_success($slots);
    }
    
    /**
     * AJAX: Create new reservation
     */
    public function ajax_create_reservation() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        // Implementation for creating reservations via AJAX
        wp_send_json_success('Reservation creation handler ready');
    }
    
    /**
     * AJAX: Update existing reservation
     */
    public function ajax_update_reservation() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        // Implementation for updating reservations via AJAX
        wp_send_json_success('Reservation update handler ready');
    }
}

}

// Initialize the plugin using singleton pattern
YenolxRestaurantReservation::get_instance();
