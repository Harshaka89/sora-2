<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('YRR_Admin_Controller')) {

class YRR_Admin_Controller {
    private $reservation_model;
    private $settings_model;
    private $tables_model;
    private $hours_model;
    
    public function __construct() {
        $this->reservation_model = new YRR_Reservation_Model();
        $this->settings_model = new YRR_Settings_Model();
        $this->tables_model = new YRR_Tables_Model();
        $this->hours_model = new YRR_Hours_Model();
    }
    
    private function check_permissions($capability = 'yrr_view_dashboard') {
        if (!current_user_can($capability)) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Yenolx Reservations',
            'Reservations',
            'yrr_view_dashboard',
            'yenolx-reservations',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            26
        );
        
        add_submenu_page('yenolx-reservations', 'Dashboard', 'Dashboard', 'yrr_view_dashboard', 'yenolx-reservations', array($this, 'dashboard_page'));
        add_submenu_page('yenolx-reservations', 'All Reservations', 'All Reservations', 'yrr_manage_reservations', 'yrr-all-reservations', array($this, 'all_reservations_page'));
        add_submenu_page('yenolx-reservations', 'Weekly View', 'Weekly View', 'yrr_manage_reservations', 'yrr-weekly-reservations', array($this, 'weekly_reservations_page'));
        add_submenu_page('yenolx-reservations', 'Tables Management', 'Tables', 'yrr_manage_tables', 'yrr-tables', array($this, 'tables_page'));
        add_submenu_page('yenolx-reservations', 'Operating Hours', 'Hours', 'yrr_manage_hours', 'yrr-hours', array($this, 'hours_page'));
        add_submenu_page('yenolx-reservations', 'Settings', 'Settings', 'yrr_manage_settings', 'yrr-settings', array($this, 'settings_page'));
    }
    
    public function dashboard_page() {
        $this->check_permissions('yrr_view_dashboard');
        
        if (isset($_POST['create_manual_reservation']) && wp_verify_nonce($_POST['manual_reservation_nonce'], 'create_manual_reservation')) {
            $this->create_manual_reservation();
        }
        
        $statistics = $this->reservation_model->get_statistics();
        $today_reservations = $this->reservation_model->get_by_date(date('Y-m-d'));
        $restaurant_status = $this->settings_model->get('restaurant_open', '1');
        $restaurant_name = $this->settings_model->get('restaurant_name', get_bloginfo('name'));
        
        $current_user = wp_get_current_user();
        $is_super_admin = in_array('administrator', $current_user->roles);
        $is_admin = $is_super_admin || in_array('yrr_admin', $current_user->roles);
        
        $this->load_view('admin/dashboard', array(
            'statistics' => $statistics,
            'today_reservations' => $today_reservations,
            'restaurant_status' => $restaurant_status,
            'restaurant_name' => $restaurant_name,
            'is_super_admin' => $is_super_admin,
            'is_admin' => $is_admin
        ));
    }
    
    // ✅ FIXED: Hours page with proper form handling
    public function hours_page() {
        $this->check_permissions('yrr_manage_hours');
        
        // Handle form submission for saving hours
        if (isset($_POST['save_hours']) && wp_verify_nonce($_POST['hours_nonce'], 'yrr_hours_save')) {
            $this->save_operating_hours_complete();
        }
        
        $hours = $this->hours_model->get_all_hours();
        
        $this->load_view('admin/hours', array(
            'hours' => $hours
        ));
    }
    
    // ✅ FIXED: Missing method added
    private function save_operating_hours_complete() {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $saved_count = 0;
        
        foreach ($days as $day) {
            $is_closed = isset($_POST[$day . '_closed']) ? 1 : 0;
            $open_time = sanitize_text_field($_POST[$day . '_open'] ?? '10:00');
            $close_time = sanitize_text_field($_POST[$day . '_close'] ?? '22:00');
            
            // Ensure proper time format with seconds
            if (!empty($open_time) && strpos($open_time, ':') !== false && strlen($open_time) == 5) {
                $open_time = $open_time . ':00';
            }
            if (!empty($close_time) && strpos($close_time, ':') !== false && strlen($close_time) == 5) {
                $close_time = $close_time . ':00';
            }
            
            $result = $this->hours_model->set_hours(
                $day, 
                $open_time, 
                $close_time, 
                $is_closed
            );
            
            if ($result) {
                $saved_count++;
            }
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg(array(
            'message' => 'hours_saved', 
            'count' => $saved_count
        ), admin_url('admin.php?page=yrr-hours')));
        exit;
    }
    
    public function settings_page() {
        $this->check_permissions('yrr_manage_settings');
        
        if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['settings_nonce'], 'yrr_settings_save')) {
            $this->save_settings_enhanced();
        }
        
        $settings = $this->settings_model->get_all();
        $this->load_view('admin/settings', array('settings' => $settings));
    }
    
    private function save_settings_enhanced() {
        $settings_to_save = array(
            'restaurant_open', 'restaurant_name', 'restaurant_email', 'restaurant_phone',
            'max_party_size', 'time_slot_duration', 'booking_buffer_minutes',
            'max_advance_booking', 'auto_confirm_reservations'
        );
        
        $saved_count = 0;
        
        foreach ($settings_to_save as $setting) {
            if (isset($_POST[$setting])) {
                $value = sanitize_text_field($_POST[$setting]);
                $result = $this->settings_model->set($setting, $value);
                if ($result !== false) $saved_count++;
            }
        }
        
        wp_redirect(add_query_arg(array('message' => 'saved', 'count' => $saved_count), admin_url('admin.php?page=yrr-settings')));
        exit;
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'yenolx') !== false || strpos($hook, 'yrr') !== false) {
            wp_enqueue_style('yrr-admin-styles', YRR_PLUGIN_URL . 'assets/admin.css', array(), YRR_VERSION);
            wp_enqueue_script('yrr-admin-js', YRR_PLUGIN_URL . 'assets/admin.js', array('jquery'), YRR_VERSION, true);
            
            wp_localize_script('yrr-admin-js', 'yrr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yrr_ajax_nonce')
            ));
        }
    }
    
    public function all_reservations_page() {
        $this->check_permissions('yrr_manage_reservations');
        $this->load_view('admin/all-reservations');
    }
    
    public function weekly_reservations_page() {
        $this->check_permissions('yrr_manage_reservations');
        
        $current_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d', strtotime('monday this week'));
        $weekly_reservations = $this->reservation_model->get_weekly_reservations($current_week);
        
        $this->load_view('admin/weekly-view', array(
            'weekly_reservations' => $weekly_reservations,
            'current_week' => $current_week
        ));
    }
    
    public function tables_page() {
        $this->check_permissions('yrr_manage_tables');
        $tables = $this->tables_model->get_all_tables();
        $this->load_view('admin/tables', array('tables' => $tables));
    }
    
    private function create_manual_reservation() {
        // Manual reservation creation logic
        $reservation_code = 'MAN-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $reservation_data = array(
            'reservation_code' => $reservation_code,
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? ''),
            'status' => 'confirmed',
            'table_id' => !empty($_POST['table_id']) ? intval($_POST['table_id']) : null,
            'original_price' => 0.00,
            'final_price' => 0.00
        );
        
        $result = $this->reservation_model->create($reservation_data);
        
        if ($result) {
            wp_redirect(add_query_arg('message', 'reservation_created', admin_url('admin.php?page=yenolx-reservations')));
        } else {
            wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=yenolx-reservations')));
        }
        exit;
    }
    
    private function load_view($view, $data = array()) {
        extract($data);
        $view_file = YRR_PLUGIN_PATH . 'views/' . $view . '.php';
        
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: ' . esc_html($view) . '.php</p></div>';
        }
    }
}

}
