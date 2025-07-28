<?php
/**
 * Plugin Name: Yenolx Restaurant Reservation
 * Description: Advanced restaurant reservation management with discount coupons, table booking, and dynamic pricing
 * Version: 1.5.1
 * Author: Yenolx
 * Text Domain: yenolx-restaurant
 */

if (defined('YRR_PLUGIN_LOADED')) return;
define('YRR_PLUGIN_LOADED', true);

if (!defined('ABSPATH')) exit;

define('YRR_VERSION', '1.5.1');
define('YRR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YRR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YRR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Enhanced database structure for v1.5.1 with coupons
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
    
    // Reservations table (enhanced with coupon support)
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
        UNIQUE KEY reservation_code (reservation_code),
        INDEX idx_date (reservation_date),
        INDEX idx_status (status),
        INDEX idx_table (table_id),
        INDEX idx_coupon (coupon_code)
    ) $charset_collate");
    
    // Tables management
    $tables_table = $wpdb->prefix . 'yrr_tables';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $tables_table (
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
    
    // Pricing rules
    $pricing_table = $wpdb->prefix . 'yrr_pricing_rules';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $pricing_table (
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
    ) $charset_collate");
    
    // NEW: Discount Coupons table
    $coupons_table = $wpdb->prefix . 'yrr_coupons';
    $wpdb->query("CREATE TABLE IF NOT EXISTS $coupons_table (
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
        INDEX idx_active (is_active),
        INDEX idx_dates (valid_from, valid_until)
    ) $charset_collate");
    
    // Insert default data
    yrr_insert_default_data();
    
    set_transient('yrr_db_check_done_v151', true, DAY_IN_SECONDS);
}

function yrr_insert_default_data() {
    global $wpdb;
    
    // Default settings
    $settings = array(
        'restaurant_open' => '1',
        'restaurant_name' => get_bloginfo('name'),
        'restaurant_email' => get_option('admin_email'),
        'restaurant_phone' => '',
        'restaurant_address' => '',
        'max_party_size' => '12',
        'base_price_per_person' => '10.00',
        'booking_time_slots' => '30',
        'max_booking_advance_days' => '60',
        'currency_symbol' => '$',
        'enable_coupons' => '1'
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
                'modifier_type' => 'add'
            ),
            array(
                'rule_name' => 'Dinner Premium',
                'start_time' => '18:00:00',
                'end_time' => '21:00:00',
                'days_applicable' => 'all',
                'price_modifier' => 5.00,
                'modifier_type' => 'add'
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
                'coupon_name' => 'Welcome Discount',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'min_order_amount' => 25.00,
                'usage_limit' => 100,
                'valid_until' => date('Y-m-d H:i:s', strtotime('+6 months'))
            ),
            array(
                'coupon_code' => 'SAVE10',
                'coupon_name' => '$10 Off Discount',
                'discount_type' => 'fixed',
                'discount_value' => 10.00,
                'min_order_amount' => 50.00,
                'usage_limit' => 50,
                'valid_until' => date('Y-m-d H:i:s', strtotime('+3 months'))
            )
        );
        
        foreach ($sample_coupons as $coupon) {
            $wpdb->insert($wpdb->prefix . 'yrr_coupons', $coupon);
        }
    }
}

add_action('admin_init', 'yrr_ensure_database_structure', 1);

// Safe autoloader with error handling
spl_autoload_register('yrr_autoloader');

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

// Enhanced plugin initialization with error handling
class YRR_Plugin {
    private $loader;
    private $controllers = array();
    
    public function __construct() {
        try {
            $this->load_dependencies();
            $this->init_controllers();
            $this->define_hooks();
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>Yenolx Restaurant Reservation Error: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
    
    private function load_dependencies() {
        $required_files = array(
            'includes/class-database.php',
            'includes/class-plugin-loader.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = YRR_PLUGIN_PATH . $file;
            if (!file_exists($file_path)) {
                throw new Exception("Required file missing: $file - Please ensure all plugin files are uploaded correctly.");
            }
            require_once $file_path;
        }
        
        $this->loader = new YRR_Plugin_Loader();
    }
    
    private function init_controllers() {
        $controller_classes = array(
            'admin' => 'YRR_Admin_Controller',
            'reservation' => 'YRR_Reservation_Controller',
            'settings' => 'YRR_Settings_Controller',
            'tables' => 'YRR_Tables_Controller',
            'hours' => 'YRR_Hours_Controller',
            'pricing' => 'YRR_Pricing_Controller',
            'coupons' => 'YRR_Coupons_Controller'
        );
        
        foreach ($controller_classes as $key => $class_name) {
            if (class_exists($class_name)) {
                $this->controllers[$key] = new $class_name();
            } else {
                error_log("YRR: Controller class $class_name not found");
            }
        }
    }
    
    private function define_hooks() {
        register_activation_hook(__FILE__, array('YRR_Database', 'create_tables'));
        
        if (isset($this->controllers['admin'])) {
            $this->loader->add_action('admin_menu', $this->controllers['admin'], 'add_admin_menu');
            $this->loader->add_action('admin_enqueue_scripts', $this->controllers['admin'], 'enqueue_admin_assets');
        }
        
        if (isset($this->controllers['reservation'])) {
            $this->loader->add_shortcode('yenolx_booking_form', $this->controllers['reservation'], 'display_booking_form');
        }
        
        // AJAX hooks
        if (isset($this->controllers['tables'])) {
            $this->loader->add_action('wp_ajax_yrr_get_available_tables', $this->controllers['tables'], 'ajax_get_available_tables');
            $this->loader->add_action('wp_ajax_nopriv_yrr_get_available_tables', $this->controllers['tables'], 'ajax_get_available_tables');
        }
        
        if (isset($this->controllers['pricing'])) {
            $this->loader->add_action('wp_ajax_yrr_calculate_price', $this->controllers['pricing'], 'ajax_calculate_price');
            $this->loader->add_action('wp_ajax_nopriv_yrr_calculate_price', $this->controllers['pricing'], 'ajax_calculate_price');
        }
        
        if (isset($this->controllers['coupons'])) {
            $this->loader->add_action('wp_ajax_yrr_validate_coupon', $this->controllers['coupons'], 'ajax_validate_coupon');
            $this->loader->add_action('wp_ajax_nopriv_yrr_validate_coupon', $this->controllers['coupons'], 'ajax_validate_coupon');
        }
    }
    
    public function run() {
        $this->loader->run();
    }
}






function yrr_init_plugin() {
    $yrr_plugin = new YRR_Plugin();
    $yrr_plugin->run();
}

add_action('plugins_loaded', 'yrr_init_plugin');



/**
 * Enhanced Email Function with Discount Support
 * Add this before the closing ?> tag in wp-restaurant-reservation.php
 */
function yrr_send_reservation_email_with_discount($reservation_data, $coupon_data = null) {
    // Get restaurant settings
    $settings_model = new YRR_Settings_Model();
    $restaurant_name = $settings_model->get('restaurant_name', get_bloginfo('name'));
    $restaurant_email = $settings_model->get('restaurant_email', get_option('admin_email'));
    $restaurant_phone = $settings_model->get('restaurant_phone', '');
    $restaurant_address = $settings_model->get('restaurant_address', '');
    $currency_symbol = $settings_model->get('currency_symbol', '$');
    
    // Prepare email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $restaurant_name . ' <' . $restaurant_email . '>'
    );
    
    // **CUSTOMER EMAIL** - Reservation confirmation with discount details
    $customer_subject = '🎉 Reservation Confirmed - ' . $restaurant_name;
    
    // Start building HTML email for customer
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
            .highlight { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
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
                    <p><strong>Discount:</strong> ';
        
        if ($coupon_data['discount_type'] === 'percentage') {
            $customer_message .= number_format($coupon_data['discount_value'], 0) . '%';
        } else {
            $customer_message .= $currency_symbol . number_format($coupon_data['discount_value'], 2);
        }
        
        $customer_message .= '</p>
                    <div class="highlight">
                        <p><strong>💰 Pricing Breakdown:</strong></p>
                        <p>Original Amount: ' . $currency_symbol . number_format($reservation_data['original_price'], 2) . '</p>
                        <p>Discount Amount: <span style="color: #28a745;">-' . $currency_symbol . number_format($reservation_data['discount_amount'], 2) . '</span></p>
                        <p><strong>Final Amount: ' . $currency_symbol . number_format($reservation_data['final_price'], 2) . '</strong></p>
                        <p style="color: #28a745; font-weight: bold;">🎉 You saved ' . $currency_symbol . number_format($reservation_data['discount_amount'], 2) . '!</p>
                    </div>
                </div>';
    }
    
    // Add restaurant contact information
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
                
                <p>We look forward to serving you! If you need to make any changes to your reservation, please contact us as soon as possible.</p>
                
                <div class="footer">
                    <p>Best regards,<br><strong>' . esc_html($restaurant_name) . '</strong></p>
                    <p><small>This is an automated confirmation email. Please save it for your records.</small></p>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    // **ADMIN EMAIL** - New reservation notification
    $admin_subject = '🆕 New Reservation' . ($coupon_data ? ' with Discount' : '') . ' - ' . $reservation_data['reservation_code'];
    
    $admin_message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 5px; }
            .info { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 5px; }
            .discount { background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 5px solid #ffc107; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>New Reservation Received</h2>
            </div>
            
            <div class="info">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> ' . esc_html($reservation_data['customer_name']) . '</p>
                <p><strong>Email:</strong> ' . esc_html($reservation_data['customer_email']) . '</p>
                <p><strong>Phone:</strong> ' . esc_html($reservation_data['customer_phone']) . '</p>
            </div>
            
            <div class="info">
                <h3>Reservation Details</h3>
                <p><strong>Code:</strong> ' . esc_html($reservation_data['reservation_code']) . '</p>
                <p><strong>Date:</strong> ' . date('F j, Y', strtotime($reservation_data['reservation_date'])) . '</p>
                <p><strong>Time:</strong> ' . date('g:i A', strtotime($reservation_data['reservation_time'])) . '</p>
                <p><strong>Party Size:</strong> ' . intval($reservation_data['party_size']) . ' guests</p>';
    
    if (!empty($reservation_data['special_requests'])) {
        $admin_message .= '<p><strong>Special Requests:</strong> ' . esc_html($reservation_data['special_requests']) . '</p>';
    }
    
    $admin_message .= '</div>';
    
    // Add discount information for admin
    if ($coupon_data && isset($reservation_data['discount_amount']) && $reservation_data['discount_amount'] > 0) {
        $admin_message .= '
            <div class="discount">
                <h3>💰 Discount Coupon Used</h3>
                <p><strong>Coupon Code:</strong> ' . esc_html($coupon_data['coupon_code']) . '</p>
                <p><strong>Coupon Name:</strong> ' . esc_html($coupon_data['coupon_name']) . '</p>
                <p><strong>Discount Applied:</strong> ' . $currency_symbol . number_format($reservation_data['discount_amount'], 2) . '</p>
                <p><strong>Final Amount:</strong> ' . $currency_symbol . number_format($reservation_data['final_price'], 2) . '</p>
            </div>';
    }
    
    $admin_message .= '
            <p><strong>Action Required:</strong> Please review and confirm this reservation in your admin dashboard.</p>
        </div>
    </body>
    </html>';
    
    // Send emails
    $customer_sent = wp_mail($reservation_data['customer_email'], $customer_subject, $customer_message, $headers);
    $admin_sent = wp_mail($restaurant_email, $admin_subject, $admin_message, $headers);
    
    // Log email results for debugging
    if (!$customer_sent) {
        error_log('YRR: Failed to send customer confirmation email to ' . $reservation_data['customer_email']);
    }
    
    if (!$admin_sent) {
        error_log('YRR: Failed to send admin notification email to ' . $restaurant_email);
    }
    
    return array(
        'customer_sent' => $customer_sent,
        'admin_sent' => $admin_sent
    );
}

/**
 * Helper function to send simple coupon notification emails
 */
function yrr_send_coupon_notification($coupon_data) {
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

// Add this to wp-restaurant-reservation.php for debugging
function yrr_debug_manual_reservation() {
    if (!current_user_can('manage_options') || !isset($_GET['test_manual'])) {
        return;
    }
    
    global $wpdb;
    
    // Test database connection
    echo '<div style="background: white; padding: 20px; margin: 20px; border: 2px solid #007cba;">';
    echo '<h3>🔍 Manual Reservation Debug Test</h3>';
    
    // Check if reservation model exists
    if (class_exists('YRR_Reservation_Model')) {
        echo '<p>✅ YRR_Reservation_Model class exists</p>';
        
        $reservation_model = new YRR_Reservation_Model();
        
        // Test data
        $test_data = array(
            'reservation_code' => 'TEST-' . date('Ymd') . '-001',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+1234567890',
            'party_size' => 2,
            'reservation_date' => date('Y-m-d'),
            'reservation_time' => '19:00:00',
            'status' => 'confirmed',
            'notes' => 'Debug test reservation'
        );
        
        echo '<p><strong>Test Data:</strong></p>';
        echo '<pre>' . print_r($test_data, true) . '</pre>';
        
        // Try to create reservation
        $result = $reservation_model->create($test_data);
        
        if ($result) {
            echo '<p>✅ TEST RESERVATION CREATED with ID: ' . $result . '</p>';
        } else {
            echo '<p>❌ FAILED TO CREATE TEST RESERVATION</p>';
            echo '<p>Last database error: ' . $wpdb->last_error . '</p>';
        }
        
    } else {
        echo '<p>❌ YRR_Reservation_Model class NOT found</p>';
    }
    
///////////////////////////////
// Add to wp-restaurant-reservation.php for debugging
function yrr_debug_manual_reservation_detailed() {
    if (!current_user_can('manage_options') || !isset($_GET['debug_manual'])) {
        return;
    }
    
    global $wpdb;
    echo '<div style="background: white; padding: 20px; margin: 20px; border: 2px solid #007cba; font-family: monospace;">';
    echo '<h3>🔍 Manual Reservation Debug Test</h3>';
    
    // Check if classes exist
    $classes = array('YRR_Reservation_Model', 'YRR_Settings_Model', 'YRR_Admin_Controller');
    foreach ($classes as $class) {
        echo '<p>' . ($class_exists($class) ? '✅' : '❌') . ' ' . $class . '</p>';
    }
    
    // Check database table
    $table_name = $wpdb->prefix . 'yrr_reservations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    echo '<p>' . ($table_exists ? '✅' : '❌') . ' Database table: ' . $table_name . '</p>';
    
    if ($table_exists) {
        // Check table structure
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        echo '<p><strong>Table columns:</strong></p><ul>';
        foreach ($columns as $column) {
            echo '<li>' . $column->Field . ' (' . $column->Type . ')</li>';
        }
        echo '</ul>';
        
        // Test insert
        if (class_exists('YRR_Reservation_Model')) {
            $model = new YRR_Reservation_Model();
            $test_data = array(
                'customer_name' => 'Debug Test User',
                'customer_email' => 'debug@test.com',
                'customer_phone' => '1234567890',
                'party_size' => 2,
                'reservation_date' => date('Y-m-d'),
                'reservation_time' => '19:00:00',
                'status' => 'confirmed'
            );
            
            $result = $model->create($test_data);
            echo '<p>' . ($result ? '✅ TEST INSERT SUCCESS (ID: ' . $result . ')' : '❌ TEST INSERT FAILED') . '</p>';
            
            if (!$result) {
                echo '<p><strong>Last Error:</strong> ' . $wpdb->last_error . '</p>';
                echo '<p><strong>Last Query:</strong> ' . $wpdb->last_query . '</p>';
            }
        }
    }
    
    echo '</div>';
}
add_action('admin_notices', 'yrr_debug_manual_reservation_detailed');

    // Add this function to your main plugin file
function yrr_create_reservations_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'yrr_reservations';
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook it to plugin activation
register_activation_hook(__FILE__, 'yrr_create_reservations_table');

// Also run it on admin_init to ensure it exists
add_action('admin_init', 'yrr_create_reservations_table');

    // Check database table
    $table_name = $wpdb->prefix . 'yrr_reservations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    echo '<p>Database table ' . $table_name . ': ' . ($table_exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';
    
    if ($table_exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<p>Total reservations in database: ' . $count . '</p>';
    }
    
    echo '</div>';
}
add_action('admin_notices', 'yrr_debug_manual_reservation');

?>
