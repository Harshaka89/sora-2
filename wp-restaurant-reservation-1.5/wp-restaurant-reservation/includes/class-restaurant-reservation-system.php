<?php
class Restaurant_Reservation_System {
    
    public function __construct() {
        $this->load_dependencies();
    }
    
    public function run() {
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once RRS_PLUGIN_DIR . 'includes/class-reservation.php';
        require_once RRS_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
        require_once RRS_PLUGIN_DIR . 'public/class-booking-form.php';
    }
    
    private function init_hooks() {
        // Initialize admin
        if (is_admin()) {
            new RRS_Admin_Dashboard();
        }
        
        // Initialize public
        new RRS_Booking_Form();
    }
}
