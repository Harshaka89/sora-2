<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('YRR_Reservation_Model')) {

class YRR_Reservation_Model {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_reservations';
    }
    
    public function get_statistics() {
        $today = date('Y-m-d');
        
        return array(
            'total' => intval($this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}")),
            'today' => intval($this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE reservation_date = %s", $today
            ))),
            'pending' => intval($this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'")),
            'confirmed' => intval($this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'confirmed'"))
        );
    }
    
    public function get_by_date($date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE reservation_date = %s ORDER BY reservation_time ASC",
            $date
        ));
    }
    
    public function create($data) {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $this->wpdb->insert($this->table_name, $data);
    }
    
    public function update($id, $data) {
        $data['updated_at'] = current_time('mysql');
        
        return $this->wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id)
        );
    }
    
    public function delete($id) {
        return $this->wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }
    
    public function get_all() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
        );
    }
    
    public function get_weekly_reservations($start_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('monday this week'));
        }
        
        $end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE reservation_date BETWEEN %s AND %s 
             ORDER BY reservation_date ASC, reservation_time ASC",
            $start_date, $end_date
        ));
    }
    
    public function get_total_count($filters = array()) {
        $where_clause = $this->build_where_clause($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        return intval($this->wpdb->get_var($sql));
    }
    
    public function get_paginated($per_page = 20, $offset = 0, $filters = array()) {
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = sanitize_text_field($_GET['search']);
        }
        
        $where_clause = $this->build_where_clause($filters);
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} {$where_clause} 
             ORDER BY reservation_date DESC, reservation_time DESC 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
    }
    
    private function build_where_clause($filters = array()) {
        $conditions = array();
        
        if (!empty($filters['status'])) {
            $conditions[] = $this->wpdb->prepare("status = %s", $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = $this->wpdb->prepare("reservation_date >= %s", $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = $this->wpdb->prepare("reservation_date <= %s", $filters['date_to']);
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $conditions[] = $this->wpdb->prepare("(customer_name LIKE %s OR customer_email LIKE %s)", $search, $search);
        }
        
        return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }
    
    public function get_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    public function get_by_code($code) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE reservation_code = %s",
            $code
        ));
    }
}

}
