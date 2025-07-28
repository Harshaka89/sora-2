<?php
/**
 * Hours Controller - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Hours_Controller {
    private $hours_model;
    
    public function __construct() {
        $this->hours_model = new YRR_Hours_Model();
    }
    
    public function is_open($date, $time) {
        return $this->hours_model->is_open_at($date, $time);
    }
    
    public function get_time_slots($date) {
        return $this->hours_model->get_available_time_slots($date);
    }
}
?>
