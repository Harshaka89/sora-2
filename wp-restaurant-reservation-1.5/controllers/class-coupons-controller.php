<?php
/**
 * Coupons Controller - Yenolx Restaurant Reservation v1.5.1
 */

if (!defined('ABSPATH')) exit;

class YRR_Coupons_Controller {
    private $coupons_model;
    
    public function __construct() {
        $this->coupons_model = new YRR_Coupons_Model();
    }
    
    public function coupons_page() {
        // Handle form submissions
        if (isset($_POST['create_coupon']) && wp_verify_nonce($_POST['coupon_nonce'], 'yrr_coupon_action')) {
            $this->create_coupon();
        }
        
        if (isset($_POST['update_coupon']) && wp_verify_nonce($_POST['coupon_nonce'], 'yrr_coupon_action')) {
            $this->update_coupon();
        }
        
        if (isset($_GET['delete_coupon']) && wp_verify_nonce($_GET['_wpnonce'], 'yrr_coupon_action')) {
            $this->delete_coupon(intval($_GET['delete_coupon']));
        }
        
        $coupons = $this->coupons_model->get_all_coupons();
        $statistics = $this->coupons_model->get_coupon_statistics();
        
        $this->load_view('admin/coupons', array(
            'coupons' => $coupons,
            'statistics' => $statistics
        ));
    }
    
    private function create_coupon() {
        $data = array(
            'coupon_code' => sanitize_text_field($_POST['coupon_code']),
            'coupon_name' => sanitize_text_field($_POST['coupon_name']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'min_order_amount' => floatval($_POST['min_order_amount']),
            'max_discount_amount' => !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null,
            'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
            'valid_until' => !empty($_POST['valid_until']) ? sanitize_text_field($_POST['valid_until']) : null,
            'is_active' => 1
        );
        
        $result = $this->coupons_model->create_coupon($data);
        
        if ($result) {
            $this->send_coupon_notification($data);
        }
        
        $redirect_url = add_query_arg('message', $result ? 'coupon_created' : 'error', admin_url('admin.php?page=yrr-coupons'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function update_coupon() {
        $id = intval($_POST['coupon_id']);
        $data = array(
            'coupon_name' => sanitize_text_field($_POST['coupon_name']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'min_order_amount' => floatval($_POST['min_order_amount']),
            'max_discount_amount' => !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null,
            'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
            'valid_until' => !empty($_POST['valid_until']) ? sanitize_text_field($_POST['valid_until']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        
        $result = $this->coupons_model->update_coupon($id, $data);
        
        $redirect_url = add_query_arg('message', $result ? 'coupon_updated' : 'error', admin_url('admin.php?page=yrr-coupons'));
        wp_redirect($redirect_url);
        exit;
    }
    
    private function delete_coupon($id) {
        $result = $this->coupons_model->delete_coupon($id);
        
        $redirect_url = add_query_arg('message', $result ? 'coupon_deleted' : 'error', admin_url('admin.php?page=yrr-coupons'));
        wp_redirect($redirect_url);
        exit;
    }
    
    public function ajax_validate_coupon() {
        check_ajax_referer('yrr_ajax_nonce', 'nonce');
        
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        $order_amount = floatval($_POST['order_amount']);
        
        $validation = $this->coupons_model->validate_coupon($coupon_code, $order_amount);
        
        if ($validation['valid']) {
            $discount = $this->coupons_model->calculate_discount($validation['coupon'], $order_amount);
            $final_amount = $order_amount - $discount;
            
            wp_send_json_success(array(
                'message' => 'Coupon applied successfully!',
                'coupon' => $validation['coupon'],
                'discount' => $discount,
                'final_amount' => $final_amount
            ));
        } else {
            wp_send_json_error(array('message' => $validation['message']));
        }
    }
    
    private function send_coupon_notification($coupon_data) {
        $settings_model = new YRR_Settings_Model();
        $restaurant_email = $settings_model->get('restaurant_email', get_option('admin_email'));
        
        $subject = 'New Discount Coupon Created - ' . $coupon_data['coupon_code'];
        $message = "A new discount coupon has been created:\n\n";
        $message .= "Coupon Code: " . $coupon_data['coupon_code'] . "\n";
        $message .= "Coupon Name: " . $coupon_data['coupon_name'] . "\n";
        $message .= "Discount: ";
        
        if ($coupon_data['discount_type'] === 'percentage') {
            $message .= $coupon_data['discount_value'] . "%\n";
        } else {
            $message .= "$" . number_format($coupon_data['discount_value'], 2) . "\n";
        }
        
        $message .= "Valid Until: " . ($coupon_data['valid_until'] ?: 'No expiry') . "\n";
        $message .= "Created: " . date('Y-m-d H:i:s') . "\n";
        
        wp_mail($restaurant_email, $subject, $message);
    }
    
    private function load_view($view, $data = array()) {
        extract($data);
        include YRR_PLUGIN_PATH . 'views/' . $view . '.php';
    }
}
?>
