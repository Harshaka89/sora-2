<?php
/**
 * Admin Controller - Yenolx Restaurant Reservation v1.5.1
 * Clean version with simple time slots support
 */

if (!defined('ABSPATH')) exit;

class YRR_Admin_Controller {
    private $reservation_model;
    private $settings_model;
    private $tables_model;
    private $hours_model;
    
    public function __construct() {
        // Safe model initialization with error handling
        try {
            $this->reservation_model = class_exists('YRR_Reservation_Model') ? new YRR_Reservation_Model() : null;
            $this->settings_model = class_exists('YRR_Settings_Model') ? new YRR_Settings_Model() : null;
            $this->tables_model = class_exists('YRR_Tables_Model') ? new YRR_Tables_Model() : null;
            $this->hours_model = class_exists('YRR_Hours_Model') ? new YRR_Hours_Model() : null;
        } catch (Exception $e) {
            error_log('YRR Controller Init Error: ' . $e->getMessage());
        }
        
        add_action('init', array($this, 'add_custom_roles'));
    }
    
    public function add_custom_roles() {
        if (!get_role('yrr_admin')) {
            add_role('yrr_admin', 'Restaurant Admin', array(
                'read' => true,
                'yrr_manage_reservations' => true,
                'yrr_view_dashboard' => true
            ));
        }
        
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('yrr_manage_reservations');
            $admin_role->add_cap('yrr_view_dashboard');
            $admin_role->add_cap('yrr_manage_tables');
            $admin_role->add_cap('yrr_manage_hours');
            $admin_role->add_cap('yrr_manage_settings');
        }
    }
    
    private function check_permissions($capability = 'yrr_view_dashboard') {
        if (!current_user_can($capability)) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
    }
    
    private function is_super_admin() {
        return current_user_can('administrator');
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
        
        // ✅ Simple Time Slots Management
        add_submenu_page('yenolx-reservations', 'Time Slots', 'Time Slots', 'yrr_manage_settings', 'yrr-simple-slots', array($this, 'simple_time_slots_page'));
        
        // Super Admin only pages
        if ($this->is_super_admin()) {
            add_submenu_page('yenolx-reservations', 'Tables', 'Tables', 'yrr_manage_tables', 'yrr-tables', array($this, 'tables_page'));
            add_submenu_page('yenolx-reservations', 'Hours', 'Hours', 'yrr_manage_hours', 'yrr-hours', array($this, 'hours_page'));
            add_submenu_page('yenolx-reservations', 'Settings', 'Settings', 'yrr_manage_settings', 'yrr-settings', array($this, 'settings_page'));
        }
    }
    
    public function dashboard_page() {
        $this->check_permissions('yrr_view_dashboard');
        
        // Handle manual reservation creation
        if (isset($_POST['create_manual_reservation']) && wp_verify_nonce($_POST['manual_reservation_nonce'], 'create_manual_reservation')) {
            $this->create_manual_reservation();
        }
        
        // Handle edit form submission
        if (isset($_POST['edit_reservation']) && wp_verify_nonce($_POST['edit_nonce'], 'edit_reservation')) {
            $this->handle_edit_reservation();
        }
        
        // Handle reservation actions (confirm, cancel, delete)
        if (isset($_GET['action']) && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'reservation_action')) {
            $this->handle_reservation_actions();
        }
        
        // Get dashboard data with error handling
        try {
            $statistics = $this->reservation_model ? $this->reservation_model->get_statistics() : array(
                'total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'today' => 0
            );
            
            $today_reservations = $this->reservation_model ? $this->reservation_model->get_by_date(date('Y-m-d')) : array();
            $restaurant_status = $this->settings_model ? $this->settings_model->get('restaurant_open', '1') : '1';
            $restaurant_name = $this->settings_model ? $this->settings_model->get('restaurant_name', get_bloginfo('name')) : get_bloginfo('name');
            
        } catch (Exception $e) {
            error_log('YRR Dashboard Error: ' . $e->getMessage());
            
            // Fallback data
            $statistics = array('total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'today' => 0);
            $today_reservations = array();
            $restaurant_status = '1';
            $restaurant_name = get_bloginfo('name');
        }
        
        // Load dashboard view
        $this->load_view('admin/dashboard', array(
            'statistics' => $statistics,
            'today_reservations' => $today_reservations,
            'restaurant_status' => $restaurant_status,
            'restaurant_name' => $restaurant_name
        ));
    }
    
    /**
     * ✅ Simple Time Slots Management Page
     */
    public function simple_time_slots_page() {
        $this->check_permissions('yrr_manage_settings');
        
        // Handle settings save
        if (isset($_POST['save_simple_slots']) && wp_verify_nonce($_POST['slots_nonce'], 'yrr_simple_slots')) {
            $this->save_simple_time_slots();
        }
        
        $settings = $this->settings_model ? $this->settings_model->get_all() : array();
        $available_slots = $this->get_default_time_slots();
        
        $this->load_view('admin/simple-time-slots', array(
            'settings' => $settings,
            'available_slots' => $available_slots
        ));
    }
    
    /**
     * ✅ Get default time slots for admin selection
     */
    private function get_default_time_slots() {
        return array(
            '10:00:00' => '10:00 AM',
            '10:30:00' => '10:30 AM', 
            '11:00:00' => '11:00 AM',
            '11:30:00' => '11:30 AM',
            '12:00:00' => '12:00 PM (Noon)',
            '12:30:00' => '12:30 PM',
            '13:00:00' => '1:00 PM',
            '13:30:00' => '1:30 PM',
            '14:00:00' => '2:00 PM',
            '14:30:00' => '2:30 PM',
            '15:00:00' => '3:00 PM',
            '15:30:00' => '3:30 PM',
            '16:00:00' => '4:00 PM',
            '16:30:00' => '4:30 PM',
            '17:00:00' => '5:00 PM',
            '17:30:00' => '5:30 PM',
            '18:00:00' => '6:00 PM',
            '18:30:00' => '6:30 PM',
            '19:00:00' => '7:00 PM',
            '19:30:00' => '7:30 PM',
            '20:00:00' => '8:00 PM',
            '20:30:00' => '8:30 PM',
            '21:00:00' => '9:00 PM',
            '21:30:00' => '9:30 PM',
            '22:00:00' => '10:00 PM'
        );
    }
    
    /**
     * ✅ Save simple time slots settings
     */
    private function save_simple_time_slots() {
        if (!$this->settings_model) return;
        
        $settings = array(
            'enabled_time_slots' => isset($_POST['enabled_slots']) ? implode(',', $_POST['enabled_slots']) : '',
            'max_bookings_per_slot' => intval($_POST['max_bookings_per_slot'] ?? 5),
            'booking_duration' => intval($_POST['booking_duration'] ?? 60),
            'enable_time_slots' => isset($_POST['enable_time_slots']) ? '1' : '0'
        );
        
        $saved = 0;
        foreach ($settings as $name => $value) {
            if (method_exists($this->settings_model, 'set') && $this->settings_model->set($name, $value)) {
                $saved++;
            }
        }
        
        wp_redirect(add_query_arg('message', 'slots_saved', admin_url('admin.php?page=yrr-simple-slots')));
        exit;
    }
    
    public function all_reservations_page() {
        $this->check_permissions('yrr_manage_reservations');
        
        // Handle reservation actions
        if (isset($_GET['action'], $_GET['id'], $_GET['_wpnonce'])) {
            $this->handle_reservation_actions();
        }
        
        // Pagination parameters
        $current_page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 15;
        $offset = ($current_page - 1) * $per_page;
        
        // Filter parameters
        $search = sanitize_text_field($_GET['search'] ?? '');
        $status_filter = sanitize_text_field($_GET['status'] ?? '');
        $date_filter = sanitize_text_field($_GET['date'] ?? '');
        
        // Get paginated data
        $reservations_data = array('reservations' => array(), 'total' => 0);
        if ($this->reservation_model && method_exists($this->reservation_model, 'get_paginated_reservations')) {
            $reservations_data = $this->reservation_model->get_paginated_reservations($offset, $per_page, array(
                'search' => $search,
                'status' => $status_filter,
                'date' => $date_filter
            ));
        }
        
        // Calculate pagination values
        $total_reservations = intval($reservations_data['total'] ?? 0);
        $total_pages = $total_reservations > 0 ? ceil($total_reservations / $per_page) : 1;
        $showing_from = $total_reservations > 0 ? $offset + 1 : 0;
        $showing_to = min($offset + $per_page, $total_reservations);
        
        // Pass data to view
        $this->load_view('admin/all-reservations', array(
            'reservations' => $reservations_data['reservations'] ?? array(),
            'total_reservations' => $total_reservations,
            'current_page' => $current_page,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
            'search' => $search,
            'status_filter' => $status_filter,
            'date_filter' => $date_filter,
            'showing_from' => $showing_from,
            'showing_to' => $showing_to
        ));
    }
    
    private function handle_edit_reservation() {
        $id = intval($_POST['reservation_id'] ?? 0);
        
        if (!$id) {
            wp_redirect(add_query_arg('message', 'invalid_id', admin_url('admin.php?page=yenolx-reservations')));
            exit;
        }
        
        $update_data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_email' => sanitize_email($_POST['customer_email'] ?? ''),
            'customer_phone' => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'party_size' => intval($_POST['party_size'] ?? 1),
            'reservation_date' => sanitize_text_field($_POST['reservation_date'] ?? ''),
            'reservation_time' => sanitize_text_field($_POST['reservation_time'] ?? ''),
            'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? '')
        );
        
        $result = false;
        if ($this->reservation_model && method_exists($this->reservation_model, 'update')) {
            $result = $this->reservation_model->update($id, $update_data);
        }
        
        if ($result !== false) {
            wp_redirect(add_query_arg('message', 'updated', admin_url('admin.php?page=yenolx-reservations')));
        } else {
            wp_redirect(add_query_arg('message', 'update_failed', admin_url('admin.php?page=yenolx-reservations')));
        }
        exit;
    }
    
    private function handle_reservation_actions() {
        $id = intval($_GET['id'] ?? 0);
        $action = sanitize_text_field($_GET['action'] ?? '');
        $redirect_url = admin_url('admin.php?page=yenolx-reservations');
        
        if (!$this->reservation_model) {
            wp_redirect(add_query_arg('message', 'error', $redirect_url));
            exit;
        }
        
        switch ($action) {
            case 'confirm':
                $result = method_exists($this->reservation_model, 'update') 
                    ? $this->reservation_model->update($id, array('status' => 'confirmed'))
                    : false;
                $redirect_url = add_query_arg('message', $result ? 'confirmed' : 'error', $redirect_url);
                break;
            case 'cancel':
                $result = method_exists($this->reservation_model, 'update')
                    ? $this->reservation_model->update($id, array('status' => 'cancelled'))
                    : false;
                $redirect_url = add_query_arg('message', $result ? 'cancelled' : 'error', $redirect_url);
                break;
            case 'delete':
                $result = method_exists($this->reservation_model, 'delete')
                    ? $this->reservation_model->delete($id)
                    : false;
                $redirect_url = add_query_arg('message', $result ? 'deleted' : 'error', $redirect_url);
                break;
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    private function create_manual_reservation() {
        try {
            $reservation_code = 'MAN-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            if (empty($_POST['customer_name']) || empty($_POST['customer_email']) || empty($_POST['customer_phone'])) {
                wp_redirect(add_query_arg('message', 'missing_fields', admin_url('admin.php?page=yenolx-reservations')));
                exit;
            }
            
            $reservation_data = array(
                'reservation_code' => $reservation_code,
                'customer_name' => sanitize_text_field($_POST['customer_name']),
                'customer_email' => sanitize_email($_POST['customer_email']),
                'customer_phone' => sanitize_text_field($_POST['customer_phone']),
                'party_size' => intval($_POST['party_size'] ?? 1),
                'reservation_date' => sanitize_text_field($_POST['reservation_date'] ?? ''),
                'reservation_time' => sanitize_text_field($_POST['reservation_time'] ?? ''),
                'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? ''),
                'status' => sanitize_text_field($_POST['initial_status'] ?? 'confirmed'),
                'notes' => sanitize_textarea_field($_POST['admin_notes'] ?? 'Manual reservation created by admin'),
                'original_price' => 0.00,
                'discount_amount' => 0.00,
                'final_price' => 0.00
            );
            
            $result = false;
            if ($this->reservation_model && method_exists($this->reservation_model, 'create')) {
                $result = $this->reservation_model->create($reservation_data);
            }
            
            if ($result) {
                $redirect_url = add_query_arg('message', 'reservation_created', admin_url('admin.php?page=yenolx-reservations'));
            } else {
                $redirect_url = add_query_arg('message', 'error', admin_url('admin.php?page=yenolx-reservations'));
            }
            
        } catch (Exception $e) {
            error_log('YRR: Exception in create_manual_reservation: ' . $e->getMessage());
            $redirect_url = add_query_arg('message', 'error', admin_url('admin.php?page=yenolx-reservations'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    public function tables_page() {
        $this->check_permissions('yrr_manage_tables');
        
        $tables = $this->tables_model && method_exists($this->tables_model, 'get_all_tables') 
            ? $this->tables_model->get_all_tables() 
            : array();
        $this->load_view('admin/tables', array('tables' => $tables));
    }
    
    public function hours_page() {
        $this->check_permissions('yrr_manage_hours');
        
        $hours = $this->hours_model && method_exists($this->hours_model, 'get_all_hours')
            ? $this->hours_model->get_all_hours()
            : array();
        $this->load_view('admin/hours', array('hours' => $hours));
    }
    
    public function settings_page() {
        $this->check_permissions('yrr_manage_settings');
        
        if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['settings_nonce'], 'yrr_settings_save')) {
            $this->save_settings();
        }
        
        $settings = $this->settings_model && method_exists($this->settings_model, 'get_all')
            ? $this->settings_model->get_all()
            : array();
        $this->load_view('admin/settings', array('settings' => $settings));
    }
    
    private function save_settings() {
        if (!$this->settings_model) return;
        
        $settings_to_save = array(
            'restaurant_open', 'restaurant_name', 'restaurant_email', 'restaurant_phone',
            'restaurant_address', 'max_party_size'
        );
        
        $saved_count = 0;
        foreach ($settings_to_save as $setting) {
            if (isset($_POST[$setting]) && method_exists($this->settings_model, 'set')) {
                $value = sanitize_text_field($_POST[$setting]);
                $result = $this->settings_model->set($setting, $value);
                if ($result !== false) $saved_count++;
            }
        }
        
        wp_cache_flush();
        $redirect_url = add_query_arg(array('message' => 'saved', 'count' => $saved_count), admin_url('admin.php?page=yrr-settings'));
        wp_redirect($redirect_url);
        exit;
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'yenolx') !== false || strpos($hook, 'yrr') !== false) {
            wp_enqueue_style('yrr-admin-styles', YRR_PLUGIN_URL . 'assets/admin.css', array(), YRR_VERSION);
            wp_enqueue_script('yrr-admin-js', YRR_PLUGIN_URL . 'assets/admin.js', array('jquery'), YRR_VERSION, true);
        }
    }
    
    private function load_view($view, $data = array()) {
        extract($data);
        $view_file = YRR_PLUGIN_PATH . 'views/' . $view . '.php';
        
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="notice notice-error"><p>View file not found: ' . esc_html($view) . '.php</p></div>';
            
            // Fallback display for missing views
            if ($view === 'admin/simple-time-slots') {
                $this->show_fallback_time_slots($data);
            }
        }
    }
    
    /**
     * ✅ Fallback display for missing time slots view
     */
    private function show_fallback_time_slots($data) {
        $settings = $data['settings'] ?? array();
        $available_slots = $data['available_slots'] ?? array();
        
        echo '<div class="wrap">';
        echo '<h1>🕐 Simple Time Slots Management</h1>';
        echo '<div style="background: white; padding: 30px; border-radius: 10px; margin: 20px 0;">';
        echo '<p>Time slots view file is missing. Please create: <code>views/admin/simple-time-slots.php</code></p>';
        echo '<h3>Available Time Slots:</h3>';
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">';
        
        foreach ($available_slots as $time => $display) {
            echo '<div style="padding: 10px; background: #f0f0f0; border-radius: 5px; text-align: center;">';
            echo '<strong>' . esc_html($display) . '</strong>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
?>
