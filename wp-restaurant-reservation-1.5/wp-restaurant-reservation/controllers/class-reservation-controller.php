<?php
/**
 * Reservation Controller - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Reservation_Controller {
    private $reservation_model;
    private $tables_model;
    private $pricing_model;
    
    public function __construct() {
        $this->reservation_model = new YRR_Reservation_Model();
        $this->tables_model = new YRR_Tables_Model();
        $this->pricing_model = new YRR_Pricing_Model();
    }
    
    public function display_booking_form($atts) {
        $atts = shortcode_atts(array(
            'show_tables' => 'true',
            'show_pricing' => 'true'
        ), $atts);
        
        ob_start();
        include YRR_PLUGIN_PATH . 'views/public/booking-form.php';
        return ob_get_clean();
    }
    
    public function ajax_update_reservation() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $id = intval($_POST['id']);
        $data = array(
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->reservation_model->update($id, $data);
        
        wp_send_json_success(array(
            'message' => 'Reservation updated successfully',
            'result' => $result
        ));
    }
    
    public function ajax_delete_reservation() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $id = intval($_POST['id']);
        $result = $this->reservation_model->delete($id);
        
        wp_send_json_success(array(
            'message' => 'Reservation deleted successfully',
            'result' => $result
        ));
    }
}
?>
