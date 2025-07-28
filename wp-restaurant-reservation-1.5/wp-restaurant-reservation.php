<?php
/**
 * Plugin Name: Yenolx Restaurant Reservation System
 * Plugin URI: https://yenolx.com
 * Description: Complete restaurant reservation system with time slots and table management
 * Version: 1.5.1
 * Author: Yenolx Team
 * Text Domain: yenolx-reservations
 * Requires at least: 5.0
 * Tested up to: 6.3
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
define('YRR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * ✅ DATABASE SETUP - Complete Enhanced Tables Structure
 */
function yrr_create_database_tables() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();
    
    // Settings table
    $settings_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_settings (
        id int(11) NOT NULL AUTO_INCREMENT,
        setting_name varchar(100) NOT NULL,
        setting_value longtext DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY setting_name (setting_name)
    ) $charset_collate;";
    
    // Tables management
    $tables_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_tables (
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
    
    // Time slots management
    $time_slots_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_time_slots (
        id int(11) NOT NULL AUTO_INCREMENT,
        slot_time time NOT NULL,
        slot_name varchar(50) NOT NULL,
        max_reservations int(11) DEFAULT 10,
        is_active boolean DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slot_time (slot_time)
    ) $charset_collate;";
    
    // Enhanced Reservations table - connects Customer + Time Slot + Table
    $reservations_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_reservations (
        id int(11) NOT NULL AUTO_INCREMENT,
        reservation_code varchar(20) NOT NULL DEFAULT '',
        customer_name varchar(100) NOT NULL DEFAULT '',
        customer_email varchar(100) NOT NULL DEFAULT '',
        customer_phone varchar(20) NOT NULL DEFAULT '',
        party_size int(11) NOT NULL DEFAULT 1,
        reservation_date date NOT NULL,
        reservation_time time NOT NULL,
        time_slot_id int(11) DEFAULT NULL,
        table_id int(11) DEFAULT NULL,
        special_requests text DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        coupon_code varchar(50) DEFAULT NULL,
        original_price decimal(10,2) DEFAULT 0.00,
        discount_amount decimal(10,2) DEFAULT 0.00,
        final_price decimal(10,2) DEFAULT 0.00,
        price_breakdown text DEFAULT NULL,
        notes text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY reservation_code (reservation_code),
        KEY time_slot_id (time_slot_id),
        KEY table_id (table_id),
        KEY reservation_date (reservation_date),
        KEY idx_date (reservation_date),
        KEY idx_status (status),
        KEY idx_coupon (coupon_code)
    ) $charset_collate;";
    
    // Operating hours
    $hours_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_operating_hours (
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
    $pricing_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_pricing_rules (
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
    
    // Discount Coupons table
    $coupons_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}yrr_coupons (
        id int(11) NOT NULL AUTO_INCREMENT,
        coupon_code varchar(50) NOT NULL,
        coupon_name varchar(100) NOT NULL,
        discount_type varchar(20) DEFAULT 'percentage',
        discount_value decimal(10,2) NOT NULL,
        min_order_amount decimal(10,2) DEFAULT 0.00,
        max_discount_amount decimal(10,2) DEFAULT NULL,
        usage_limit int(11) DEFAULT NULL,
        usage_count int(11) DEFAULT 0,
        valid_from datetime DEFAULT CURRENT_TIMESTAMP,
        valid_until datetime DEFAULT NULL,
        is_active boolean DEFAULT 1,
        created_by int(11) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY coupon_code (coupon_code),
        KEY idx_active (is_active),
        KEY idx_dates (valid_from, valid_until)
    ) $charset_collate;";
    
    dbDelta($settings_table);
    dbDelta($tables_table);
    dbDelta($time_slots_table);
    dbDelta($reservations_table);
    dbDelta($hours_table);
    dbDelta($pricing_table);
    dbDelta($coupons_table);
    
    // Insert default data
    yrr_insert_default_data();
}

/**
 * ✅ INSERT DEFAULT DATA (SINGLE DEFINITION)
 */
function yrr_insert_default_data() {
    global $wpdb;
    
    // Default settings with all enhanced features
    $default_settings = array(
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
    
    foreach ($default_settings as $name => $value) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$wpdb->prefix}yrr_settings WHERE setting_name = %s", $name
        ));
        if ($existing === null) {
            $wpdb->insert($wpdb->prefix . 'yrr_settings', array(
                'setting_name' => $name, 'setting_value' => $value
            ));
        }
    }
    
    // Default time slots (Admin can manage these)
    $default_slots = array(
        '10:00:00' => '10:00 AM - Breakfast',
        '11:00:00' => '11:00 AM - Brunch',
        '12:00:00' => '12:00 PM - Lunch',
        '13:00:00' => '1:00 PM - Lunch',
        '14:00:00' => '2:00 PM - Afternoon',
        '15:00:00' => '3:00 PM - Tea Time',
        '16:00:00' => '4:00 PM - Snack Time',
        '17:00:00' => '5:00 PM - Early Dinner',
        '18:00:00' => '6:00 PM - Dinner',
        '19:00:00' => '7:00 PM - Prime Dinner',
        '20:00:00' => '8:00 PM - Late Dinner',
        '21:00:00' => '9:00 PM - Night'
    );
    
    foreach ($default_slots as $time => $name) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}yrr_time_slots WHERE slot_time = %s", $time
        ));
        if (!$existing) {
            $wpdb->insert($wpdb->prefix . 'yrr_time_slots', array(
                'slot_time' => $time,
                'slot_name' => $name,
                'max_reservations' => 8,
                'is_active' => 1
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
    
    // Default tables (Admin can assign these to reservations)
    $existing_tables = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_tables");
    if ($existing_tables == 0) {
        $default_tables = array(
            array('table_number' => 'T1', 'capacity' => 2, 'location' => 'Window Side', 'table_type' => 'intimate'),
            array('table_number' => 'T2', 'capacity' => 4, 'location' => 'Main Hall', 'table_type' => 'standard'),
            array('table_number' => 'T3', 'capacity' => 6, 'location' => 'Private Room', 'table_type' => 'family'),
            array('table_number' => 'T4', 'capacity' => 8, 'location' => 'VIP Section', 'table_type' => 'vip'),
            array('table_number' => 'T5', 'capacity' => 2, 'location' => 'Balcony', 'table_type' => 'romantic'),
            array('table_number' => 'T6', 'capacity' => 4, 'location' => 'Garden View', 'table_type' => 'outdoor')
        );
        
        foreach ($default_tables as $table) {
            $wpdb->insert($wpdb->prefix . 'yrr_tables', $table);
        }
    }
    
    // Default pricing rules
    $existing_rules = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_pricing_rules");
    if ($existing_rules == 0) {
        $pricing_rules = array(
            array(
                'rule_name' => 'Lunch Discount',
                'start_time' => '11:00:00',
                'end_time' => '15:00:00',
                'days_applicable' => 'weekdays',
                'price_modifier' => -2.00,
                'modifier_type' => 'add',
                'is_active' => 1
            ),
            array(
                'rule_name' => 'Dinner Premium',
                'start_time' => '18:00:00',
                'end_time' => '21:00:00',
                'days_applicable' => 'all',
                'price_modifier' => 5.00,
                'modifier_type' => 'add',
                'is_active' => 1
            )
        );
        
        foreach ($pricing_rules as $rule) {
            $wpdb->insert($wpdb->prefix . 'yrr_pricing_rules', $rule);
        }
    }
    
    // Default sample coupons
    $existing_coupons = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}yrr_coupons");
    if ($existing_coupons == 0) {
        $sample_coupons = array(
            array(
                'coupon_code' => 'WELCOME20',
                'coupon_name' => 'Welcome 20% Discount',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'min_order_amount' => 30.00,
                'usage_limit' => 100,
                'valid_until' => date('Y-m-d H:i:s', strtotime('+6 months')),
                'is_active' => 1
            ),
            array(
                'coupon_code' => 'SAVE10',
                'coupon_name' => '$10 Off Your Order',
                'discount_type' => 'fixed',
                'discount_value' => 10.00,
                'min_order_amount' => 50.00,
                'usage_limit' => 50,
                'valid_until' => date('Y-m-d H:i:s', strtotime('+3 months')),
                'is_active' => 1
            ),
            array(
                'coupon_code' => 'FIRST15',
                'coupon_name' => 'First Time Customer 15% Off',
                'discount_type' => 'percentage',
                'discount_value' => 15.00,
                'min_order_amount' => 25.00,
                'usage_limit' => 200,
                'valid_until' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'is_active' => 1
            )
        );
        
        foreach ($sample_coupons as $coupon) {
            $wpdb->insert($wpdb->prefix . 'yrr_coupons', $coupon);
        }
    }
}

/**
 * ✅ MAIN PLUGIN CLASS (SINGLE DEFINITION)
 */
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
        
        // Load text domain for translations
        load_plugin_textdomain('yenolx-reservations', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Initialize shortcodes
        $this->init_shortcodes();
    }
    
    private function load_dependencies() {
        $files = array(
            'models/class-reservation-model.php',
            'models/class-settings-model.php',
            'models/class-tables-model.php',
            'models/class-time-slots-model.php',
            'controllers/class-admin-controller.php'
        );
        
        foreach ($files as $file) {
            $file_path = YRR_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log('YRR: Missing file - ' . $file);
            }
        }
    }
    
    private function init_admin() {
        if (class_exists('YRR_Admin_Controller')) {
            $admin_controller = new YRR_Admin_Controller();
            add_action('admin_menu', array($admin_controller, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($admin_controller, 'enqueue_admin_assets'));
            
            // Force admin menu icon
            add_action('admin_head', array($this, 'admin_menu_icon_fix'));
        }
    }
    
    /**
     * ✅ FIX ADMIN MENU ICON
     */
    public function admin_menu_icon_fix() {
        echo '<style>
        #adminmenu .wp-menu-image.dashicons-calendar-alt:before {
            content: "\f145";
            font-family: dashicons;
        }
        #adminmenu li.menu-top:hover .wp-menu-image.dashicons-calendar-alt:before,
        #adminmenu li.opensub .wp-menu-image.dashicons-calendar-alt:before,
        #adminmenu li.current .wp-menu-image.dashicons-calendar-alt:before {
            color: #00a0d2;
        }
        </style>';
    }
    
    private function init_shortcodes() {
        add_shortcode('yenolx_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('yrr_reservations', array($this, 'reservations_shortcode'));
    }
    
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_title' => 'true',
            'title' => 'Make a Reservation'
        ), $atts);
        
        ob_start();
        echo '<div class="yrr-booking-form-container">';
        if ($atts['show_title'] === 'true') {
            echo '<h3>' . esc_html($atts['title']) . '</h3>';
        }
        echo '<p>Booking form will be displayed here once frontend is implemented.</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    public function reservations_shortcode($atts) {
        return '<div class="yrr-reservations-list">Reservations list shortcode placeholder</div>';
    }
    
    public function activate() {
        // Force database creation
        yrr_create_database_tables();
        flush_rewrite_rules();
        
        // Clear any cached data
        wp_cache_flush();
        delete_transient('yrr_db_check_done');
        
        error_log('YRR: Plugin activated successfully - Version ' . YRR_VERSION);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
        wp_cache_flush();
        error_log('YRR: Plugin deactivated');
    }
}

/**
 * ✅ ENHANCED EMAIL FUNCTION WITH DISCOUNT SUPPORT
 */
function yrr_send_reservation_email_with_discount($reservation_data, $coupon_data = null) {
    if (!class_exists('YRR_Settings_Model')) return false;
    
    $settings_model = new YRR_Settings_Model();
    $restaurant_name = $settings_model->get('restaurant_name', get_bloginfo('name'));
    $restaurant_email = $settings_model->get('restaurant_email', get_option('admin_email'));
    $restaurant_phone = $settings_model->get('restaurant_phone', '');
    $restaurant_address = $settings_model->get('restaurant_address', '');
    $currency_symbol = $settings_model->get('currency_symbol', '$');
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $restaurant_name . ' <' . $restaurant_email . '>'
    );
    
    // Customer email
    $customer_subject = '🎉 Reservation Confirmed - ' . $restaurant_name;
    $customer_message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 5px solid #667eea; }
            .discount-box { background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 5px solid #28a745; }
            .footer { text-align: center; margin-top: 30px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🍽️ ' . esc_html($restaurant_name) . '</h1>
                <p>Your reservation has been confirmed!</p>
            </div>
            <div class="content">
                <h2>Dear ' . esc_html($reservation_data['customer_name']) . ',</h2>
                <p>Thank you for choosing us! Your reservation has been successfully confirmed.</p>
                <div class="info-box">
                    <h3>📅 Reservation Details</h3>
                    <p><strong>Reservation Code:</strong> ' . esc_html($reservation_data['reservation_code']) . '</p>
                    <p><strong>Date:</strong> ' . date('F j, Y', strtotime($reservation_data['reservation_date'])) . '</p>
                    <p><strong>Time:</strong> ' . date('g:i A', strtotime($reservation_data['reservation_time'])) . '</p>
                    <p><strong>Party Size:</strong> ' . intval($reservation_data['party_size']) . ' guests</p>';
    
    if (!empty($reservation_data['special_requests'])) {
        $customer_message .= '<p><strong>Special Requests:</strong> ' . esc_html($reservation_data['special_requests']) . '</p>';
    }
    
    $customer_message .= '</div>';
    
    // Add discount information if coupon was used
    if ($coupon_data && isset($reservation_data['discount_amount']) && $reservation_data['discount_amount'] > 0) {
        $customer_message .= '
                <div class="discount-box">
                    <h3>🎫 Discount Applied - You Saved!</h3>
                    <p><strong>Coupon Code:</strong> ' . esc_html($coupon_data['coupon_code']) . '</p>
                    <p><strong>Original Amount:</strong> ' . $currency_symbol . number_format($reservation_data['original_price'], 2) . '</p>
                    <p><strong>Discount:</strong> -' . $currency_symbol . number_format($reservation_data['discount_amount'], 2) . '</p>
                    <p><strong>Final Amount:</strong> ' . $currency_symbol . number_format($reservation_data['final_price'], 2) . '</p>
                    <p style="color: #28a745; font-weight: bold;">🎉 You saved ' . $currency_symbol . number_format($reservation_data['discount_amount'], 2) . '!</p>
                </div>';
    }
    
    $customer_message .= '
                <div class="info-box">
                    <h3>📞 Contact Information</h3>';
    
    if ($restaurant_phone) {
        $customer_message .= '<p><strong>Phone:</strong> ' . esc_html($restaurant_phone) . '</p>';
    }
    if ($restaurant_address) {
        $customer_message .= '<p><strong>Address:</strong> ' . esc_html($restaurant_address) . '</p>';
    }
    
    $customer_message .= '<p><strong>Email:</strong> ' . esc_html($restaurant_email) . '</p>
                </div>
                <p>We look forward to serving you!</p>
                <div class="footer">
                    <p>Best regards,<br><strong>' . esc_html($restaurant_name) . '</strong></p>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    // Admin email
    $admin_subject = '🆕 New Reservation' . ($coupon_data ? ' with Discount' : '') . ' - ' . $reservation_data['reservation_code'];
    $admin_message = '
    <h3>New Reservation Received</h3>
    <p><strong>Customer:</strong> ' . esc_html($reservation_data['customer_name']) . '</p>
    <p><strong>Email:</strong> ' . esc_html($reservation_data['customer_email']) . '</p>
    <p><strong>Phone:</strong> ' . esc_html($reservation_data['customer_phone']) . '</p>
    <p><strong>Date:</strong> ' . date('F j, Y', strtotime($reservation_data['reservation_date'])) . '</p>
    <p><strong>Time:</strong> ' . date('g:i A', strtotime($reservation_data['reservation_time'])) . '</p>
    <p><strong>Party Size:</strong> ' . intval($reservation_data['party_size']) . ' guests</p>';
    
    if ($coupon_data && isset($reservation_data['discount_amount']) && $reservation_data['discount_amount'] > 0) {
        $admin_message .= '<p><strong>Coupon Used:</strong> ' . esc_html($coupon_data['coupon_code']) . ' (Saved: ' . $currency_symbol . number_format($reservation_data['discount_amount'], 2) . ')</p>';
    }
    
    // Send emails
    $customer_sent = wp_mail($reservation_data['customer_email'], $customer_subject, $customer_message, $headers);
    $admin_sent = wp_mail($restaurant_email, $admin_subject, $admin_message, $headers);
    
    return array('customer_sent' => $customer_sent, 'admin_sent' => $admin_sent);
}

/**
 * ✅ COUPON NOTIFICATION EMAIL
 */
function yrr_send_coupon_notification($coupon_data) {
    if (!class_exists('YRR_Settings_Model')) return false;
    
    $settings_model = new YRR_Settings_Model();
    $restaurant_email = $settings_model->get('restaurant_email', get_option('admin_email'));
    $restaurant_name = $settings_model->get('restaurant_name', get_bloginfo('name'));
    
    $subject = 'New Discount Coupon Created - ' . $coupon_data['coupon_code'];
    $message = "A new discount coupon has been created in " . $restaurant_name . ":\n\n";
    $message .= "Coupon Code: " . $coupon_data['coupon_code'] . "\n";
    $message .= "Coupon Name: " . $coupon_data['coupon_name'] . "\n";
    $message .= "Discount: ";
    
    if ($coupon_data['discount_type'] === 'percentage') {
        $message .= $coupon_data['discount_value'] . "%\n";
    } else {
        $message .= "$" . number_format($coupon_data['discount_value'], 2) . "\n";
    }
    
    $message .= "Valid Until: " . ($coupon_data['valid_until'] ?: 'No expiry') . "\n";
    $message .= "Created: " . date('Y-m-d H:i:s') . "\n";
    
    return wp_mail($restaurant_email, $subject, $message);
}

/**
 * ✅ DEBUG SYSTEM STATUS
 */
function yrr_debug_system() {
    if (!current_user_can('manage_options') || !isset($_GET['yrr_debug'])) return;
    
    global $wpdb;
    echo '<div style="background: white; padding: 20px; margin: 20px; border: 2px solid #007cba; font-family: monospace;">';
    echo '<h3>🔍 Yenolx Restaurant System Debug - Version ' . YRR_VERSION . '</h3>';
    
    // Check classes
    $classes = array('YRR_Reservation_Model', 'YRR_Settings_Model', 'YRR_Tables_Model', 'YRR_Time_Slots_Model', 'YRR_Admin_Controller');
    echo '<h4>📦 Classes Status:</h4>';
    foreach ($classes as $class) {
        echo '<p>' . ($class_exists($class) ? '✅' : '❌') . ' ' . $class . '</p>';
    }
    
    // Check database tables
    echo '<h4>🗄️ Database Tables:</h4>';
    $tables = array('yrr_settings', 'yrr_reservations', 'yrr_tables', 'yrr_time_slots', 'yrr_operating_hours', 'yrr_pricing_rules', 'yrr_coupons');
    foreach ($tables as $table) {
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
        $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $full_table") : 0;
        echo '<p>' . ($exists ? '✅' : '❌') . ' ' . $table . ' (' . $count . ' records)</p>';
    }
    
    echo '<h4>🔧 System Info:</h4>';
    echo '<p>Plugin Version: ' . YRR_VERSION . '</p>';
    echo '<p>WordPress Version: ' . get_bloginfo('version') . '</p>';
    echo '<p>PHP Version: ' . phpversion() . '</p>';
    echo '<p>Current Time: ' . current_time('Y-m-d H:i:s') . '</p>';
    echo '<p>Plugin Path: ' . YRR_PLUGIN_PATH . '</p>';
    echo '<p>Plugin URL: ' . YRR_PLUGIN_URL . '</p>';
    
    // Test manual reservation creation
    if (class_exists('YRR_Reservation_Model')) {
        echo '<h4>🧪 Test Reservation Creation:</h4>';
        $model = new YRR_Reservation_Model();
        $test_data = array(
            'customer_name' => 'Debug Test User',
            'customer_email' => 'test@debug.com',
            'customer_phone' => '1234567890',
            'party_size' => 2,
            'reservation_date' => date('Y-m-d'),
            'reservation_time' => '19:00:00',
            'status' => 'confirmed'
        );
        
        echo '<p>💡 Add <code>&test_insert=1</code> to URL to test reservation creation</p>';
        
        if (isset($_GET['test_insert'])) {
            $result = $model->create($test_data);
            echo '<p>' . ($result ? '✅ TEST RESERVATION CREATED (ID: ' . $result . ')' : '❌ TEST FAILED') . '</p>';
            if (!$result) {
                echo '<p><strong>Error:</strong> ' . $wpdb->last_error . '</p>';
            }
        }
    }
    
    echo '</div>';
}
add_action('admin_notices', 'yrr_debug_system');

/**
 * ✅ AUTO-LOAD PLUGIN DEPENDENCIES
 */
function yrr_autoload_dependencies() {
    $files = array(
        'models/class-reservation-model.php',
        'models/class-settings-model.php', 
        'models/class-tables-model.php',
        'models/class-time-slots-model.php'
    );
    
    foreach ($files as $file) {
        $path = YRR_PLUGIN_PATH . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }
}

/**
 * ✅ PLUGIN INITIALIZATION (SINGLE POINT)
 */
function yrr_init_plugin() {
    // Load dependencies first
    yrr_autoload_dependencies();
    
    // Initialize main plugin class
    return YenolxRestaurantReservation::get_instance();
}

// Initialize plugin
add_action('plugins_loaded', 'yrr_init_plugin');

// Force database check on admin pages
add_action('admin_init', function() {
    if (is_admin() && !get_transient('yrr_db_check_done_v151')) {
        yrr_create_database_tables();
        set_transient('yrr_db_check_done_v151', true, DAY_IN_SECONDS);
    }
}, 1);

// Activation hook
register_activation_hook(__FILE__, function() {
    yrr_create_database_tables();
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
