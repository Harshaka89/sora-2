<?php
/**
 * Pricing Model - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Pricing_Model {
    private $table_name;
    private $wpdb;
    private $settings_model;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_pricing_rules';
        $this->settings_model = new YRR_Settings_Model();
    }
    
    public function get_all_rules() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY start_time");
    }
    
    public function get_active_rules() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name} WHERE is_active = 1 ORDER BY start_time");
    }
    
    public function create_rule($data) {
        $data['created_at'] = current_time('mysql');
        return $this->wpdb->insert($this->table_name, $data);
    }
    
    public function update_rule($id, $data) {
        return $this->wpdb->update($this->table_name, $data, array('id' => $id));
    }
    
    public function delete_rule($id) {
        return $this->wpdb->delete($this->table_name, array('id' => $id));
    }
    
    public function calculate_price($date, $time, $party_size) {
        $base_price = $this->settings_model->get_base_price();
        $total_base = $base_price * $party_size;
        
        $day_of_week = strtolower(date('l', strtotime($date)));
        $is_weekend = in_array($day_of_week, array('saturday', 'sunday'));
        
        $applicable_rules = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE is_active = 1 
             AND (start_time <= %s AND end_time >= %s)
             AND (days_applicable = 'all' 
                  OR (days_applicable = 'weekends' AND %d = 1)
                  OR (days_applicable = 'weekdays' AND %d = 0))",
            $time, $time, $is_weekend ? 1 : 0, $is_weekend ? 1 : 0
        ));
        
        $price_breakdown = array(
            'base_price' => $base_price,
            'party_size' => $party_size,
            'base_total' => $total_base,
            'modifiers' => array(),
            'final_total' => $total_base
        );
        
        $current_total = $total_base;
        
        foreach ($applicable_rules as $rule) {
            $modifier_amount = 0;
            
            if ($rule->modifier_type === 'percent') {
                $modifier_amount = ($current_total * $rule->price_modifier) / 100;
            } else {
                $modifier_amount = $rule->price_modifier * $party_size;
            }
            
            $current_total += $modifier_amount;
            
            $price_breakdown['modifiers'][] = array(
                'rule_name' => $rule->rule_name,
                'type' => $rule->modifier_type,
                'value' => $rule->price_modifier,
                'amount' => $modifier_amount
            );
        }
        
        $price_breakdown['final_total'] = max(0, $current_total);
        
        return $price_breakdown;
    }
    
    public function get_rules_for_time($time, $date = null) {
        $day_of_week = $date ? strtolower(date('l', strtotime($date))) : null;
        $is_weekend = $day_of_week ? in_array($day_of_week, array('saturday', 'sunday')) : false;
        
        $where_clause = "is_active = 1 AND start_time <= %s AND end_time >= %s";
        $params = array($time, $time);
        
        if ($date) {
            $where_clause .= " AND (days_applicable = 'all' OR (days_applicable = 'weekends' AND %d = 1) OR (days_applicable = 'weekdays' AND %d = 0))";
            $params[] = $is_weekend ? 1 : 0;
            $params[] = $is_weekend ? 1 : 0;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE $where_clause ORDER BY start_time",
            ...$params
        ));
    }
}
?>
