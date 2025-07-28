<?php
/**
 * Settings Controller - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Settings_Controller {
    private $settings_model;
    
    public function __construct() {
        $this->settings_model = new YRR_Settings_Model();
    }
    
    public function get_setting($name, $default = '') {
        return $this->settings_model->get($name, $default);
    }
    
    public function update_setting($name, $value) {
        return $this->settings_model->set($name, $value);
    }
}
?>
