<?php
/**
 * Tables Controller - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Tables_Controller {
    private $tables_model;
    
    public function __construct() {
        $this->tables_model = new YRR_Tables_Model();
    }
    
    public function ajax_get_available_tables() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $party_size = intval($_POST['party_size']);
        
        $tables = $this->tables_model->get_available_tables($date, $time, $party_size);
        
        wp_send_json_success($tables);
    }
}
?>
