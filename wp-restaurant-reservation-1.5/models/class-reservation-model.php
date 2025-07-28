<?php
/**
 * Reservation Model - Fixed for Manual Reservations v1.5.1
 */

if (!defined('ABSPATH')) exit;

class YRR_Reservation_Model {
    private $table_name;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_reservations';
    }
    
    public function create($data) {
        // Ensure required fields have defaults
        $defaults = array(
            'reservation_code' => $this->generate_reservation_code(),
            'customer_name' => '',
            'customer_email' => '',
            'customer_phone' => '',
            'party_size' => 1,
            'reservation_date' => date('Y-m-d'),
            'reservation_time' => '19:00:00',
            'special_requests' => '',
            'status' => 'pending',
            'table_id' => null,
            'coupon_code' => null,
            'original_price' => 0.00,
            'discount_amount' => 0.00,
            'final_price' => 0.00,
            'price_breakdown' => null,
            'notes' => '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = array_merge($defaults, $data);
        
        // Validate required fields
        if (empty($data['customer_name']) || empty($data['customer_email'])) {
            error_log('YRR: Missing required fields - name or email');
            return false;
        }
        
        // Ensure proper time format
        if (!empty($data['reservation_time']) && strlen($data['reservation_time']) === 5) {
            $data['reservation_time'] = $data['reservation_time'] . ':00';
        }
        
        $result = $this->wpdb->insert($this->table_name, $data);
        
        if ($result === false) {
            error_log('YRR: Failed to create reservation - ' . $this->wpdb->last_error);
            error_log('YRR: Data attempted: ' . print_r($data, true));
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    public function get_all() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY reservation_date DESC, reservation_time DESC");
    }
    
    public function get_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    public function get_by_date($date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE reservation_date = %s ORDER BY reservation_time",
            $date
        ));
    }
    
    public function get_by_date_range($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE reservation_date BETWEEN %s AND %s 
             ORDER BY reservation_date, reservation_time",
            $start_date, $end_date
        ));
    }
    
    public function get_weekly_reservations($start_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('monday this week'));
        }
        $end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));
        
        return $this->get_by_date_range($start_date, $end_date);
    }
    
    public function update($id, $data) {
        $data['updated_at'] = current_time('mysql');
        
        // Ensure proper time format
        if (!empty($data['reservation_time']) && strlen($data['reservation_time']) === 5) {
            $data['reservation_time'] = $data['reservation_time'] . ':00';
        }
        
        return $this->wpdb->update($this->table_name, $data, array('id' => $id));
    }
    
    public function delete($id) {
        return $this->wpdb->delete($this->table_name, array('id' => $id));
    }
    
    public function get_statistics() {
        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $confirmed = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'confirmed'");
        $pending = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'");
        $today = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE reservation_date = %s",
            date('Y-m-d')
        ));
        
        return array(
            'total' => intval($total),
            'confirmed' => intval($confirmed),
            'pending' => intval($pending),
            'today' => intval($today)
        );
    }
    
    public function get_paginated_reservations($offset = 0, $limit = 15, $filters = array()) {
    $where_conditions = array('1=1');
    $where_values = array();
    
    // ✅ SEARCH FILTER
    if (!empty($filters['search'])) {
        $where_conditions[] = "(customer_name LIKE %s OR customer_email LIKE %s OR reservation_code LIKE %s OR customer_phone LIKE %s)";
        $search_term = '%' . $filters['search'] . '%';
        $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term));
    }
    
    // ✅ STATUS FILTER
    if (!empty($filters['status'])) {
        $where_conditions[] = "status = %s";
        $where_values[] = $filters['status'];
    }
    
    // ✅ DATE FILTER
    if (!empty($filters['date'])) {
        $where_conditions[] = "reservation_date = %s";
        $where_values[] = $filters['date'];
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // ✅ GET TOTAL COUNT
    $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
    if (!empty($where_values)) {
        $count_query = $this->wpdb->prepare($count_query, $where_values);
    }
    $total = intval($this->wpdb->get_var($count_query));
    
    // ✅ GET PAGINATED RESULTS
    $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC, reservation_date DESC LIMIT %d OFFSET %d";
    $query_values = array_merge($where_values, array($limit, $offset));
    
    if (!empty($query_values)) {
        $prepared_query = $this->wpdb->prepare($query, $query_values);
    } else {
        $prepared_query = $query;
    }
    
    $reservations = $this->wpdb->get_results($prepared_query);
    
    return array(
        'reservations' => $reservations ?: array(),
        'total' => $total
    );
}

public function update_status($id, $status) {
    $allowed_statuses = array('pending', 'confirmed', 'cancelled');
    
    if (!in_array($status, $allowed_statuses)) {
        return false;
    }
    
    return $this->wpdb->update(
        $this->table_name,
        array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $id),
        array('%s', '%s'),
        array('%d')
    ) !== false;
}

    public function get_filtered_reservations($search = '', $status = '', $date_from = '', $date_to = '') {
        $where_conditions = array();
        $params = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s OR reservation_code LIKE %s)";
            $search_term = '%' . $search . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($status)) {
            $where_conditions[] = "status = %s";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = "reservation_date >= %s";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "reservation_date <= %s";
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT * FROM {$this->table_name} $where_clause ORDER BY reservation_date DESC, reservation_time DESC";
        
        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params));
        } else {
            return $this->wpdb->get_results($sql);
        }
    }
    
    private function generate_reservation_code() {
        $prefix = 'YRR';
        $date = date('Ymd');
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . '-' . $date . '-' . $random;
    }
}
?>
