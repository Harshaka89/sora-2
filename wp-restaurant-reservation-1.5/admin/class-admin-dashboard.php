<?php
class RRS_Admin_Dashboard {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_post_create_reservation', array($this, 'handle_create_reservation'));
        add_action('admin_post_update_reservation', array($this, 'handle_update_reservation'));
        add_action('admin_post_delete_reservation', array($this, 'handle_delete_reservation'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_update_reservation_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_get_reservation_details', array($this, 'ajax_get_reservation_details'));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'restaurant-reservations') !== false || strpos($hook, 'rrs-') !== false) {
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
            
            wp_enqueue_script('rrs-admin-js', RRS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-dialog'), RRS_VERSION, true);
            wp_enqueue_style('rrs-admin-css', RRS_PLUGIN_URL . 'assets/css/admin.css', array(), RRS_VERSION);
            
            wp_localize_script('rrs-admin-js', 'rrs_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rrs_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this reservation?', 'restaurant-reservation'),
                    'loading' => __('Loading...', 'restaurant-reservation'),
                    'error' => __('An error occurred. Please try again.', 'restaurant-reservation'),
                    'success' => __('Action completed successfully!', 'restaurant-reservation')
                )
            ));
        }
    }
    
    public function handle_admin_actions() {
        if (isset($_GET['rrs_action'])) {
            switch ($_GET['rrs_action']) {
                case 'confirm':
                    if (isset($_GET['reservation_id'])) {
                        $this->confirm_reservation(intval($_GET['reservation_id']));
                    }
                    break;
                    
                case 'cancel':
                    if (isset($_GET['reservation_id'])) {
                        $this->cancel_reservation(intval($_GET['reservation_id']));
                    }
                    break;
                    
                case 'delete':
                    if (isset($_GET['reservation_id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_reservation')) {
                        $this->delete_reservation(intval($_GET['reservation_id']));
                    }
                    break;
                    
                case 'seat':
                    if (isset($_GET['reservation_id'])) {
                        $this->seat_reservation(intval($_GET['reservation_id']));
                    }
                    break;
                    
                case 'complete':
                    if (isset($_GET['reservation_id'])) {
                        $this->complete_reservation(intval($_GET['reservation_id']));
                    }
                    break;
            }
        }
    }
    
    private function confirm_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'rrs_reservations',
            array('status' => 'confirmed', 'updated_at' => current_time('mysql')),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=confirmed'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=error'));
        }
        exit;
    }
    
    private function cancel_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'rrs_reservations',
            array('status' => 'cancelled', 'updated_at' => current_time('mysql')),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=cancelled'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=error'));
        }
        exit;
    }
    
    private function seat_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'rrs_reservations',
            array('status' => 'seated', 'updated_at' => current_time('mysql')),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=seated'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=error'));
        }
        exit;
    }
    
    private function complete_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'rrs_reservations',
            array('status' => 'completed', 'updated_at' => current_time('mysql')),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=completed'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=error'));
        }
        exit;
    }
    
    private function delete_reservation($reservation_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'rrs_reservations',
            array('id' => $reservation_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=error'));
        }
        exit;
    }
    
    public function add_admin_menus() {
        // Main dashboard
        add_menu_page(
            'Restaurant Reservations',
            'Reservations',
            'manage_options',
            'restaurant-reservations',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            26
        );
        
        // Submenu pages
        add_submenu_page(
            'restaurant-reservations',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'restaurant-reservations',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'restaurant-reservations',
            'All Reservations',
            'All Reservations',
            'manage_options',
            'rrs-all-reservations',
            array($this, 'all_reservations_page')
        );
        
        add_submenu_page(
            'restaurant-reservations',
            'Today\'s Schedule',
            'Today\'s Schedule',
            'manage_options',
            'rrs-todays-schedule',
            array($this, 'todays_schedule_page')
        );
        
        add_submenu_page(
            'restaurant-reservations',
            'Table Management',
            'Tables',
            'manage_options',
            'rrs-tables',
            array($this, 'tables_page')
        );
        
        add_submenu_page(
            'restaurant-reservations',
            'Reports',
            'Reports',
            'manage_options',
            'rrs-reports',
            array($this, 'reports_page')
        );
        
        add_submenu_page(
            'restaurant-reservations',
            'Settings',
            'Settings',
            'manage_options',
            'rrs-settings',
            array($this, 'settings_page')
        );
    }
    
    public function dashboard_page() {
        global $wpdb;
        
        // Display messages
        $this->display_admin_messages();
        
        // Get enhanced statistics
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this_week = date('Y-m-d', strtotime('monday this week'));
        $last_week = date('Y-m-d', strtotime('monday last week'));
        
        $stats = array(
            'today_reservations' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE reservation_date = %s",
                $today
            )),
            'yesterday_reservations' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE reservation_date = %s",
                $yesterday
            )),
            'pending_reservations' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE status = 'pending'"
            ),
            'confirmed_reservations' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE status = 'confirmed'"
            ),
            'seated_reservations' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE status = 'seated' AND reservation_date = %s",
                $today
            )),
            'completed_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE status = 'completed' AND reservation_date = %s",
                $today
            )),
            'total_reservations' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations"
            ),
            'total_guests_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(party_size) FROM {$wpdb->prefix}rrs_reservations WHERE reservation_date = %s AND status IN ('confirmed', 'seated', 'completed')",
                $today
            )) ?: 0,
            'avg_party_size' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(party_size) FROM {$wpdb->prefix}rrs_reservations WHERE reservation_date = %s",
                $today
            )) ?: 0
        );
        
        // Get upcoming reservations (next 2 hours)
        $upcoming_time = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $upcoming_reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations 
             WHERE CONCAT(reservation_date, ' ', reservation_time) BETWEEN NOW() AND %s 
             AND status IN ('confirmed', 'pending')
             ORDER BY reservation_date, reservation_time LIMIT 5",
            $upcoming_time
        ));
        
        // Get today's reservations
        $todays_reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE reservation_date = %s ORDER BY reservation_time",
            $today
        ));
        
        // Get recent activity
        $recent_reservations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations ORDER BY created_at DESC LIMIT 5"
        );
        
        include RRS_PLUGIN_DIR . 'admin/partials/enhanced-dashboard.php';
    }
    
    public function all_reservations_page() {
        global $wpdb;
        
        // Handle search and filters
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
        
        // Build query with filters
        $where_conditions = array('1=1');
        $query_params = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(customer_name LIKE %s OR customer_email LIKE %s OR reservation_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $query_params[] = $search_term;
            $query_params[] = $search_term;
            $query_params[] = $search_term;
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "status = %s";
            $query_params[] = $status_filter;
        }
        
        if (!empty($date_filter)) {
            $where_conditions[] = "reservation_date = %s";
            $query_params[] = $date_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get reservations with pagination
        $per_page = 25;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}rrs_reservations WHERE {$where_clause}";
        $reservations_query = "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE {$where_clause} ORDER BY reservation_date DESC, reservation_time DESC LIMIT %d OFFSET %d";
        
        $query_params[] = $per_page;
        $query_params[] = $offset;
        
        $total_items = $wpdb->get_var($wpdb->prepare($total_query, array_slice($query_params, 0, -2)));
        $reservations = $wpdb->get_results($wpdb->prepare($reservations_query, $query_params));
        
        $total_pages = ceil($total_items / $per_page);
        
        include RRS_PLUGIN_DIR . 'admin/partials/all-reservations.php';
    }
    
    public function todays_schedule_page() {
        global $wpdb;
        
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        
        $reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE reservation_date = %s ORDER BY reservation_time",
            $date
        ));
        
        include RRS_PLUGIN_DIR . 'admin/partials/todays-schedule.php';
    }
    
    public function tables_page() {
        echo '<div class="wrap"><h1>Table Management</h1><p>Table management features will be available in version 1.2</p></div>';
    }
    
    public function reports_page() {
        echo '<div class="wrap"><h1>Reports & Analytics</h1><p>Detailed reports will be available in version 1.2</p></div>';
    }
    
    public function settings_page() {
        echo '<div class="wrap"><h1>Restaurant Settings</h1><p>Settings panel will be available in version 1.2</p></div>';
    }
    
    // AJAX handlers
    public function ajax_update_status() {
        check_ajax_referer('rrs_admin_nonce', 'nonce');
        
        $reservation_id = intval($_POST['reservation_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'rrs_reservations',
            array('status' => $new_status, 'updated_at' => current_time('mysql')),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }
    
    public function ajax_get_reservation_details() {
        check_ajax_referer('rrs_admin_nonce', 'nonce');
        
        $reservation_id = intval($_POST['reservation_id']);
        
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE id = %d",
            $reservation_id
        ), ARRAY_A);
        
        if ($reservation) {
            wp_send_json_success($reservation);
        } else {
            wp_send_json_error(array('message' => 'Reservation not found'));
        }
    }
    
    public function handle_create_reservation() {
        if (!wp_verify_nonce($_POST['reservation_nonce'], 'create_reservation')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $reservation_data = array(
            'reservation_code' => 'ADM-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false)),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests']),
            'status' => sanitize_text_field($_POST['status']),
            'gdpr_consent' => 1
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'rrs_reservations', $reservation_data);
        
        if ($result) {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=created'));
        } else {
            wp_redirect(admin_url('admin.php?page=restaurant-reservations&message=error'));
        }
        exit;
    }
    
    public function handle_update_reservation() {
        if (!wp_verify_nonce($_POST['update_nonce'], 'update_reservation')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $reservation_id = intval($_POST['reservation_id']);
        
        $update_data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests']),
            'status' => sanitize_text_field($_POST['status']),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->update(
            $wpdb->prefix . 'rrs_reservations',
            $update_data,
            array('id' => $reservation_id),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=rrs-all-reservations&message=updated'));
        } else {
            wp_redirect(admin_url('admin.php?page=rrs-all-reservations&message=error'));
        }
        exit;
    }
    
    public function handle_delete_reservation() {
        if (!wp_verify_nonce($_POST['delete_nonce'], 'delete_reservation')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $reservation_id = intval($_POST['reservation_id']);
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'rrs_reservations',
            array('id' => $reservation_id),
            array('%d')
        );
        
        if ($result !== false) {
            wp_redirect(admin_url('admin.php?page=rrs-all-reservations&message=deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=rrs-all-reservations&message=error'));
        }
        exit;
    }
    
    private function display_admin_messages() {
        if (isset($_GET['message'])) {
            $message = '';
            $type = 'success';
            
            switch ($_GET['message']) {
                case 'created':
                    $message = 'Reservation created successfully!';
                    break;
                case 'updated':
                    $message = 'Reservation updated successfully!';
                    break;
                case 'confirmed':
                    $message = 'Reservation confirmed successfully!';
                    break;
                case 'cancelled':
                    $message = 'Reservation cancelled successfully!';
                    break;
                case 'seated':
                    $message = 'Guest has been seated!';
                    break;
                case 'completed':
                    $message = 'Reservation completed!';
                    break;
                case 'deleted':
                    $message = 'Reservation deleted successfully!';
                    break;
                case 'error':
                    $message = 'An error occurred. Please try again.';
                    $type = 'error';
                    break;
            }
            
            if ($message) {
                echo "<div class='notice notice-{$type} is-dismissible'><p>{$message}</p></div>";
            }
        }
    }
}
