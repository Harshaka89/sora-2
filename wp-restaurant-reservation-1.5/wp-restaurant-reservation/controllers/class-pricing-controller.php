<?php
/**
 * Pricing Controller - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Pricing_Controller {
    private $pricing_model;
    
    public function __construct() {
        $this->pricing_model = new YRR_Pricing_Model();
    }
    
    public function ajax_calculate_price() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $party_size = intval($_POST['party_size']);
        
        $pricing = $this->pricing_model->calculate_price($date, $time, $party_size);
        
        wp_send_json_success($pricing);
    }
}
?>
