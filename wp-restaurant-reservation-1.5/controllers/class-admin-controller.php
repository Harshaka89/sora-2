<?php
/**
 * Admin Controller - Yenolx Restaurant Reservation v1.5.1
 * FIXED: Removed duplicate create_manual_reservation method
 */

if (!defined('ABSPATH')) exit;

class YRR_Admin_Controller {
    private $reservation_model;
    private $settings_model;
    private $tables_model;
    private $hours_model;
    private $pricing_model;
    private $coupons_model;
    
    public function __construct() {
        $this->reservation_model = new YRR_Reservation_Model();
        $this->settings_model = new YRR_Settings_Model();
        $this->tables_model = new YRR_Tables_Model();
        $this->hours_model = new YRR_Hours_Model();
        $this->pricing_model = new YRR_Pricing_Model();
        $this->coupons_model = new YRR_Coupons_Model();
        
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
            $admin_role->add_cap('yrr_manage_pricing');
            $admin_role->add_cap('yrr_manage_coupons');
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
        add_submenu_page('yenolx-reservations', 'Weekly View', 'Weekly View', 'yrr_manage_reservations', 'yrr-weekly-reservations', array($this, 'weekly_reservations_page'));
        
        if ($this->is_super_admin()) {
            add_submenu_page('yenolx-reservations', 'Tables Management', 'Tables', 'yrr_manage_tables', 'yrr-tables', array($this, 'tables_page'));
            add_submenu_page('yenolx-reservations', 'Operating Hours', 'Hours', 'yrr_manage_hours', 'yrr-hours', array($this, 'hours_page'));
            add_submenu_page('yenolx-reservations', 'Pricing Rules', 'Pricing', 'yrr_manage_pricing', 'yrr-pricing', array($this, 'pricing_page'));
            add_submenu_page('yenolx-reservations', 'Discount Coupons', 'Coupons', 'yrr_manage_coupons', 'yrr-coupons', array($this, 'coupons_page'));
            add_submenu_page('yenolx-reservations', 'Settings', 'Settings', 'yrr_manage_settings', 'yrr-settings', array($this, 'settings_page'));
        }
    }
    
    public function dashboard_page() {
        $this->check_permissions('yrr_view_dashboard');
        
        // Handle manual reservation creation
        if (isset($_POST['create_manual_reservation']) && wp_verify_nonce($_POST['manual_reservation_nonce'], 'create_manual_reservation')) {
            $this->create_manual_reservation();
        }
        
        // Handle reservation actions
        if (isset($_GET['action']) && isset($_GET['id']) && wp_verify_nonce($_GET['_wpnonce'], 'reservation_action')) {
            $id = intval($_GET['id']);
            $redirect_url = admin_url('admin.php?page=yenolx-reservations');
            
            switch ($_GET['action']) {
                case 'confirm':
                    $result = $this->reservation_model->update($id, array('status' => 'confirmed'));
                    $redirect_url = add_query_arg('message', $result ? 'confirmed' : 'error', $redirect_url);
                    break;
                case 'cancel':
                    $result = $this->reservation_model->update($id, array('status' => 'cancelled'));
                    $redirect_url = add_query_arg('message', $result ? 'cancelled' : 'error', $redirect_url);
                    break;
                case 'delete':
                    $result = $this->reservation_model->delete($id);
                    $redirect_url = add_query_arg('message', $result ? 'deleted' : 'error', $redirect_url);
                    break;
            }
            
            wp_redirect($redirect_url);
            exit;
        }
        
        // Handle edit form submission
        if (isset($_POST['edit_reservation']) && wp_verify_nonce($_POST['edit_nonce'], 'edit_reservation')) {
            $id = intval($_POST['reservation_id']);
            $update_data = array(
                'customer_name' => sanitize_text_field($_POST['customer_name']),
                'customer_email' => sanitize_email($_POST['customer_email']),
                'customer_phone' => sanitize_text_field($_POST['customer_phone']),
                'party_size' => intval($_POST['party_size']),
                'reservation_date' => sanitize_text_field($_POST['reservation_date']),
                'reservation_time' => sanitize_text_field($_POST['reservation_time']),
                'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? ''),
                'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
            );
            
            $result = $this->reservation_model->update($id, $update_data);
            wp_redirect(add_query_arg('message', $result ? 'updated' : 'error', admin_url('admin.php?page=yenolx-reservations')));
            exit;
        }
        
        $statistics = $this->reservation_model->get_statistics();
        $today_reservations = $this->reservation_model->get_by_date(date('Y-m-d'));
        $restaurant_status = $this->settings_model->get('restaurant_open', '1');
        $restaurant_name = $this->settings_model->get('restaurant_name', get_bloginfo('name'));
        
        $this->load_view('admin/dashboard', array(
            'statistics' => $statistics,
            'today_reservations' => $today_reservations,
            'restaurant_status' => $restaurant_status,
            'restaurant_name' => $restaurant_name
        ));
    }
    
    // ✅ ONLY ONE create_manual_reservation method (no duplicates)
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
                'party_size' => intval($_POST['party_size']),
                'reservation_date' => sanitize_text_field($_POST['reservation_date']),
                'reservation_time' => sanitize_text_field($_POST['reservation_time']),
                'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? ''),
                'status' => sanitize_text_field($_POST['initial_status'] ?? 'confirmed'),
                'notes' => sanitize_textarea_field($_POST['admin_notes'] ?? 'Manual reservation created by admin'),
                'original_price' => 0.00,
                'discount_amount' => 0.00,
                'final_price' => 0.00
            );
            
            $base_price = floatval($this->settings_model->get('base_price_per_person', 0));
            if ($base_price > 0) {
                $total_price = $base_price * $reservation_data['party_size'];
                $reservation_data['original_price'] = $total_price;
                $reservation_data['final_price'] = $total_price;
            }
            
            $result = $this->reservation_model->create($reservation_data);
            
            if ($result) {
                if (function_exists('yrr_send_reservation_email_with_discount')) {
                    yrr_send_reservation_email_with_discount($reservation_data);
                }
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
    
    public function all_reservations_page() {
        $this->check_permissions('yrr_manage_reservations');
        
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        $reservations = $this->reservation_model->get_filtered_reservations($search, $status_filter, $date_from, $date_to);
        
        $this->load_view('admin/all-reservations', array(
            'reservations' => $reservations,
            'search' => $search,
            'status_filter' => $status_filter,
            'date_from' => $date_from,
            'date_to' => $date_to
        ));
    }
    
    public function weekly_reservations_page() {
        $this->check_permissions('yrr_manage_reservations');
        $this->load_view('admin/weekly-reservations');
    }
    
    public function tables_page() {
        $this->check_permissions('yrr_manage_tables');
        
        if (isset($_POST['add_table']) && wp_verify_nonce($_POST['table_nonce'], 'yrr_table_action')) {
            $this->add_table();
        }
        
        if (isset($_POST['update_table']) && wp_verify_nonce($_POST['table_nonce'], 'yrr_table_action')) {
            $this->update_table();
        }
        
        if (isset($_GET['delete_table']) && wp_verify_nonce($_GET['_wpnonce'], 'yrr_table_action')) {
            $this->delete_table(intval($_GET['delete_table']));
        }
        
        $tables = $this->tables_model->get_all_tables();
        $this->load_view('admin/tables', array('tables' => $tables));
    }
    
    public function hours_page() {
        $this->check_permissions('yrr_manage_hours');
        
        if (isset($_POST['save_hours']) && wp_verify_nonce($_POST['hours_nonce'], 'yrr_hours_save')) {
            $this->save_operating_hours();
        }
        
        $hours = $this->hours_model->get_all_hours();
        $this->load_view('admin/hours', array('hours' => $hours));
    }
    
    public function pricing_page() {
        $this->check_permissions('yrr_manage_pricing');
        
        if (isset($_POST['add_rule']) && wp_verify_nonce($_POST['pricing_nonce'], 'yrr_pricing_action')) {
            $this->add_pricing_rule();
        }
        
        if (isset($_POST['update_rule']) && wp_verify_nonce($_POST['pricing_nonce'], 'yrr_pricing_action')) {
            $this->update_pricing_rule();
        }
        
        if (isset($_GET['delete_rule']) && wp_verify_nonce($_GET['_wpnonce'], 'yrr_pricing_action')) {
            $this->delete_pricing_rule(intval($_GET['delete_rule']));
        }
        
        $rules = $this->pricing_model->get_all_rules();
        $this->load_view('admin/pricing', array('rules' => $rules));
    }
    
    public function coupons_page() {
        $this->check_permissions('yrr_manage_coupons');
        $coupons_controller = new YRR_Coupons_Controller();
        $coupons_controller->coupons_page();
    }
    
    public function settings_page() {
        $this->check_permissions('yrr_manage_settings');
        
        if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['settings_nonce'], 'yrr_settings_save')) {
            $this->save_settings_enhanced();
        }
        
        $settings = $this->settings_model->get_all();
        $this->load_view('admin/settings', array('settings' => $settings));
    }
    
    // Helper methods
    private function add_table() {
        $data = array(
            'table_number' => sanitize_text_field($_POST['table_number']),
            'capacity' => intval($_POST['capacity']),
            'location' => sanitize_text_field($_POST['location']),
            'table_type' => sanitize_text_field($_POST['table_type'] ?? 'standard'),
            'status' => 'available'
        );
        
        $result = $this->tables_model->create_table($data);
        $redirect_url = add_query_arg('message', $result ? 'table_added' : 'error', admin_url('admin.php?page=yrr-tables'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function update_table() {
        $id = intval($_POST['table_id']);
        $data = array(
            'table_number' => sanitize_text_field($_POST['table_number']),
            'capacity' => intval($_POST['capacity']),
            'location' => sanitize_text_field($_POST['location']),
            'table_type' => sanitize_text_field($_POST['table_type'])
        );
        
        $result = $this->tables_model->update_table($id, $data);
        $redirect_url = add_query_arg('message', $result ? 'table_updated' : 'error', admin_url('admin.php?page=yrr-tables'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function delete_table($id) {
        $result = $this->tables_model->delete_table($id);
        $redirect_url = add_query_arg('message', $result ? 'table_deleted' : 'error', admin_url('admin.php?page=yrr-tables'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function save_operating_hours() {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $saved_count = 0;
        
        foreach ($days as $day) {
            $is_closed = isset($_POST[$day . '_closed']) ? 1 : 0;
            $open_time = sanitize_text_field($_POST[$day . '_open'] ?? '10:00');
            $close_time = sanitize_text_field($_POST[$day . '_close'] ?? '22:00');
            
            $result = $this->hours_model->set_hours($day, 'all_day', $open_time . ':00', $close_time . ':00', $is_closed);
            if ($result !== false) $saved_count++;
        }
        
        $redirect_url = add_query_arg(array('message' => 'hours_saved', 'count' => $saved_count), admin_url('admin.php?page=yrr-hours'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function add_pricing_rule() {
        $data = array(
            'rule_name' => sanitize_text_field($_POST['rule_name']),
            'start_time' => sanitize_text_field($_POST['start_time']) . ':00',
            'end_time' => sanitize_text_field($_POST['end_time']) . ':00',
            'days_applicable' => sanitize_text_field($_POST['days_applicable']),
            'price_modifier' => floatval($_POST['price_modifier']),
            'modifier_type' => sanitize_text_field($_POST['modifier_type']),
            'is_active' => 1
        );
        
        $result = $this->pricing_model->create_rule($data);
        $redirect_url = add_query_arg('message', $result ? 'rule_added' : 'error', admin_url('admin.php?page=yrr-pricing'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function update_pricing_rule() {
        $id = intval($_POST['rule_id']);
        $data = array(
            'rule_name' => sanitize_text_field($_POST['rule_name']),
            'start_time' => sanitize_text_field($_POST['start_time']) . ':00',
            'end_time' => sanitize_text_field($_POST['end_time']) . ':00',
            'days_applicable' => sanitize_text_field($_POST['days_applicable']),
            'price_modifier' => floatval($_POST['price_modifier']),
            'modifier_type' => sanitize_text_field($_POST['modifier_type']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        
        $result = $this->pricing_model->update_rule($id, $data);
        $redirect_url = add_query_arg('message', $result ? 'rule_updated' : 'error', admin_url('admin.php?page=yrr-pricing'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function delete_pricing_rule($id) {
        $result = $this->pricing_model->delete_rule($id);
        $redirect_url = add_query_arg('message', $result ? 'rule_deleted' : 'error', admin_url('admin.php?page=yrr-pricing'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function save_settings_enhanced() {
        $settings_to_save = array(
            'restaurant_open', 'restaurant_name', 'restaurant_email', 'restaurant_phone',
            'restaurant_address', 'max_party_size', 'base_price_per_person', 'booking_time_slots',
            'max_booking_advance_days', 'currency_symbol', 'booking_buffer_minutes',
            'max_dining_duration', 'enable_coupons'
        );
        
        $saved_count = 0;
        
        foreach ($settings_to_save as $setting) {
            if (isset($_POST[$setting])) {
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
            
            wp_localize_script('yrr-admin-js', 'yrr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yrr_ajax_nonce')
            ));
        }
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
?>
