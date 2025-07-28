<?php
/**
 * Coupons Model - Yenolx Restaurant Reservation v1.5.1
 */

if (!defined('ABSPATH')) exit;

class YRR_Coupons_Model {
    private $table_name;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_coupons';
    }
    
    public function get_all_coupons() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
    }
    
    public function get_active_coupons() {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE is_active = 1 
             AND (valid_until IS NULL OR valid_until > %s)
             ORDER BY created_at DESC",
            current_time('mysql')
        ));
    }
    
    public function get_coupon_by_code($coupon_code) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE coupon_code = %s",
            strtoupper($coupon_code)
        ));
    }
    
    public function validate_coupon($coupon_code, $order_amount = 0) {
        $coupon = $this->get_coupon_by_code($coupon_code);
        
        if (!$coupon) {
            return array('valid' => false, 'message' => 'Coupon code not found');
        }
        
        if (!$coupon->is_active) {
            return array('valid' => false, 'message' => 'Coupon is not active');
        }
        
        // Check expiry date
        if ($coupon->valid_until && strtotime($coupon->valid_until) < time()) {
            return array('valid' => false, 'message' => 'Coupon has expired');
        }
        
        // Check usage limit
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return array('valid' => false, 'message' => 'Coupon usage limit reached');
        }
        
        // Check minimum order amount
        if ($coupon->min_order_amount && $order_amount < $coupon->min_order_amount) {
            return array(
                'valid' => false, 
                'message' => 'Minimum order amount of $' . number_format($coupon->min_order_amount, 2) . ' required'
            );
        }
        
        return array('valid' => true, 'coupon' => $coupon);
    }
    
    public function calculate_discount($coupon, $order_amount) {
        if ($coupon->discount_type === 'percentage') {
            $discount = ($order_amount * $coupon->discount_value) / 100;
            
            // Apply maximum discount limit if set
            if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
                $discount = $coupon->max_discount_amount;
            }
        } else {
            $discount = $coupon->discount_value;
        }
        
        // Ensure discount doesn't exceed order amount
        return min($discount, $order_amount);
    }
    
    public function create_coupon($data) {
        $data['coupon_code'] = strtoupper($data['coupon_code']);
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        $data['created_by'] = get_current_user_id();
        
        return $this->wpdb->insert($this->table_name, $data);
    }
    
    public function update_coupon($id, $data) {
        if (isset($data['coupon_code'])) {
            $data['coupon_code'] = strtoupper($data['coupon_code']);
        }
        $data['updated_at'] = current_time('mysql');
        
        return $this->wpdb->update($this->table_name, $data, array('id' => $id));
    }
    
    public function delete_coupon($id) {
        return $this->wpdb->delete($this->table_name, array('id' => $id));
    }
    
    public function use_coupon($coupon_code) {
        return $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table_name} SET usage_count = usage_count + 1 WHERE coupon_code = %s",
            strtoupper($coupon_code)
        ));
    }
    
    public function generate_coupon_code($prefix = 'YRR') {
        return strtoupper($prefix . substr(uniqid(), -6));
    }
    
    public function get_coupon_statistics() {
        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $active = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_active = 1");
        $expired = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE valid_until < %s",
            current_time('mysql')
        ));
        $used = $this->wpdb->get_var("SELECT SUM(usage_count) FROM {$this->table_name}");
        
        return array(
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'used' => $used ?: 0
        );
    }
}
?>
