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


if (!defined('ABSPATH')) exit;

// Define plugin constants
define('YRR_VERSION', '1.5.1');
define('YRR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YRR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YRR_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
if (!class_exists('YenolxRestaurantReservation')) {

class YenolxRestaurantReservation {
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Load models
        $this->load_models();
        
        // Load controllers
        $this->load_controllers();
        
        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Load textdomain
        load_plugin_textdomain('yenolx-reservations', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    private function load_models() {
        require_once YRR_PLUGIN_PATH . 'includes/class-database.php';
        require_once YRR_PLUGIN_PATH . 'models/class-settings-model.php';
        require_once YRR_PLUGIN_PATH . 'models/class-hours-model.php';
        require_once YRR_PLUGIN_PATH . 'models/class-reservation-model.php';
        require_once YRR_PLUGIN_PATH . 'models/class-tables-model.php';
        require_once YRR_PLUGIN_PATH . 'models/class-pricing-model.php';
        require_once YRR_PLUGIN_PATH . 'models/class-coupons-model.php';
    }
    
    private function load_controllers() {
        require_once YRR_PLUGIN_PATH . 'controllers/class-admin-controller.php';
        require_once YRR_PLUGIN_PATH . 'controllers/class-reservation-controller.php';
    }
    
    private function init_admin() {
        $admin_controller = new YRR_Admin_Controller();
        add_action('admin_menu', array($admin_controller, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin_controller, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_get_time_slot_preview', array($this, 'ajax_get_time_slot_preview'));
        add_action('wp_ajax_get_available_tables', array($this, 'ajax_get_available_tables'));
        add_action('wp_ajax_check_slot_availability', array($this, 'ajax_check_slot_availability'));
    }
    
    public function activate() {
        $database = new YRR_Database();
        $database->create_tables();
        $this->create_default_data();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    private function create_default_data() {
        $hours_model = new YRR_Hours_Model();
        $settings_model = new YRR_Settings_Model();
        
        // Create default hours
        $default_hours = array(
            'monday' => array('10:00:00', '22:00:00', 0),
            'tuesday' => array('10:00:00', '22:00:00', 0),
            'wednesday' => array('10:00:00', '22:00:00', 0),
            'thursday' => array('10:00:00', '22:00:00', 0),
            'friday' => array('10:00:00', '23:00:00', 0),
            'saturday' => array('09:00:00', '23:00:00', 0),
            'sunday' => array('11:00:00', '21:00:00', 0)
        );
        
        foreach ($default_hours as $day => $hours) {
            $hours_model->set_hours($day, $hours[0], $hours[1], $hours[2]);
        }
        
        // Create default settings
        $default_settings = array(
            'restaurant_name' => get_bloginfo('name'),
            'restaurant_open' => '1',
            'time_slot_duration' => '60',
            'max_party_size' => '12',
            'max_advance_booking' => '30',
            'auto_confirm_reservations' => '0'
        );
        
        foreach ($default_settings as $key => $value) {
            $settings_model->set($key, $value);
        }
    }
    
    // AJAX Handlers
    public function ajax_get_time_slot_preview() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $duration = intval($_POST['duration'] ?? 60);
        $settings_model = new YRR_Settings_Model();
        $slots = $settings_model->get_time_slot_preview($duration);
        
        wp_send_json_success($slots);
    }
    
    public function ajax_get_available_tables() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $party_size = intval($_POST['party_size']);
        
        $tables_model = new YRR_Tables_Model();
        $available_tables = $tables_model->get_available_tables($date, $time, $party_size);
        
        wp_send_json_success($available_tables);
    }
    
    public function ajax_check_slot_availability() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $settings_model = new YRR_Settings_Model();
        $slots = $settings_model->get_available_time_slots($date);
        
        wp_send_json_success($slots);
    }
}

}

// Initialize the plugin
new YenolxRestaurantReservation();
?>
